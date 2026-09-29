#!/usr/bin/env bash
#
# Move all the work of the current linked worktree to the main worktree, on a
# new branch: commits, staged and unstaged changes, untracked files.
# Ignored files (vendor/, .env.local, ...) are left behind.
#
# Nothing is ever committed: uncommitted work travels through a stash entry
# (the stash is shared between worktrees), which is dropped once applied.
#
# Usage: migrate.sh [--dry-run] [--stash-main] [--worktree <path>] [<branch>]
#
#   <branch>      Branch to create in the main worktree. Defaults to the branch
#                 of the worktree, which is then released (the worktree is left
#                 on a detached HEAD) so the main worktree can check it out.
#   --worktree    Worktree to migrate. Defaults to the one containing the
#                 current directory.
#   --dry-run     Run every check and print the plan, change nothing.
#   --stash-main  Stash the uncommitted changes of the main worktree first,
#                 instead of refusing to run.
#
# Exit codes:
#   0   done
#   1   unexpected failure (the message says where the work is)
#   10  not inside a linked worktree
#   11  the main worktree has uncommitted changes (see --stash-main)
#   12  branch name missing, invalid or already taken
#   13  untracked files of the worktree already exist in the main worktree
#   14  a merge, rebase, cherry-pick... is in progress

set -euo pipefail

die() {
    local code=$1
    shift
    printf 'error: %s\n' "$*" >&2
    exit "$code"
}

run() {
    printf '+ %s\n' "$*"
    "$@"
}

usage() {
    sed -n '2,/^$/s/^# \{0,1\}//p' "$0"
}

# Is a merge, rebase, cherry-pick, revert or bisect in progress in worktree $1?
in_progress() {
    local marker
    for marker in MERGE_HEAD CHERRY_PICK_HEAD REVERT_HEAD BISECT_LOG rebase-merge rebase-apply; do
        if [ -e "$(git -C "$1" rev-parse --path-format=absolute --git-path "$marker")" ]; then
            return 0
        fi
    done

    return 1
}

dry_run=0
stash_main=0
wt=.
branch=

while [ $# -gt 0 ]; do
    case $1 in
        --dry-run) dry_run=1 ;;
        --stash-main) stash_main=1 ;;
        --worktree)
            [ $# -ge 2 ] || die 1 "--worktree requires a path"
            wt=$2
            shift
            ;;
        -h | --help)
            usage
            exit 0
            ;;
        -*) die 1 "unknown option: $1" ;;
        *)
            [ -z "$branch" ] || die 1 "too many arguments"
            branch=$1
            ;;
    esac
    shift
done

# --- Checks (read-only) -------------------------------------------------------

wt=$(git -C "$wt" rev-parse --show-toplevel 2>/dev/null) || die 10 "not inside a git work tree"
cd "$wt"
# The main worktree is always the first entry
main=$(git worktree list --porcelain | sed -n '1s/^worktree //p')

[ "$(git -C "$main" rev-parse --is-bare-repository)" = false ] || die 10 "the main repository is bare, there is no main worktree"
[ "$(realpath "$wt")" != "$(realpath "$main")" ] || die 10 "already in the main worktree ($main)"

! in_progress "$wt" || die 14 "an operation (merge, rebase, cherry-pick...) is in progress in $wt"
! in_progress "$main" || die 14 "an operation (merge, rebase, cherry-pick...) is in progress in $main"

wt_head=$(git -C "$wt" rev-parse --verify --quiet HEAD) || die 1 "the worktree has no commit yet"
wt_branch=$(git -C "$wt" symbolic-ref --quiet --short HEAD || true)
branch=${branch:-$wt_branch}

[ -n "$branch" ] || die 12 "the worktree is on a detached HEAD: a branch name is required"
git check-ref-format --branch "$branch" >/dev/null 2>&1 || die 12 "invalid branch name: $branch"

if [ "$branch" = "$wt_branch" ]; then
    mode=move
else
    mode=create
    ! git show-ref --verify --quiet "refs/heads/$branch" || die 12 "branch $branch already exists"
fi

main_branch=$(git -C "$main" symbolic-ref --quiet --short HEAD || git -C "$main" rev-parse --short HEAD)
main_dirty=$(git -C "$main" status --porcelain --untracked-files=no)

if [ -n "$main_dirty" ] && [ $stash_main = 0 ]; then
    printf '%s\n' "$main_dirty" >&2
    die 11 "the main worktree ($main, on $main_branch) has uncommitted changes: commit or stash them, or pass --stash-main"
fi

# Untracked files are restored as is by `git stash apply`, which refuses to
# overwrite anything. Files tracked in the main worktree are not a problem: the
# branch switch removes them first.
collisions=()
while IFS= read -r -d '' path; do
    if [ -e "$main/$path" ] || [ -L "$main/$path" ]; then
        if ! git -C "$main" ls-files --error-unmatch -- "$path" >/dev/null 2>&1; then
            if cmp --silent -- "$wt/$path" "$main/$path"; then
                collisions+=("$path (identical)")
            else
                collisions+=("$path (different)")
            fi
        fi
    fi
done < <(git -C "$wt" ls-files --others --exclude-standard -z)

if [ ${#collisions[@]} -gt 0 ]; then
    printf '%s\n' "${collisions[@]}" >&2
    die 13 "these untracked files of the worktree already exist in the main worktree"
fi

wt_dirty=$(git -C "$wt" status --porcelain)

# --- Plan ---------------------------------------------------------------------

echo "worktree: $wt (${wt_branch:-detached HEAD} @ $(git rev-parse --short "$wt_head"))"
echo "main:     $main ($main_branch)"
if [ $mode = move ]; then
    echo "branch:   $branch, released by the worktree (left on a detached HEAD)"
else
    echo "branch:   $branch, created at $(git rev-parse --short "$wt_head")"
fi
echo "commits:  $(git rev-list --count "$(git -C "$main" rev-parse HEAD)..$wt_head") not on $main_branch"
if [ -n "$wt_dirty" ]; then
    echo "uncommitted: $(printf '%s\n' "$wt_dirty" | wc -l) path(s)"
    printf '%s\n' "$wt_dirty"
else
    echo "uncommitted: nothing"
fi
if [ -n "$main_dirty" ]; then
    echo "main worktree: $(printf '%s\n' "$main_dirty" | wc -l) uncommitted path(s) on $main_branch, stashed first"
fi

if [ $dry_run = 1 ]; then
    echo "dry run: nothing changed"
    exit 0
fi

# --- Migration ----------------------------------------------------------------

main_stash=
wt_detached=0

# Until the main worktree is on the new branch, a failure is fully undone
rollback() {
    [ $? -ne 0 ] || return 0
    set +e
    echo "rolling back" >&2
    if [ $wt_detached = 1 ]; then
        run git -C "$wt" switch "$wt_branch"
    fi
    if [ -n "$main_stash" ] && [ "$(git rev-parse --verify --quiet refs/stash)" = "$main_stash" ]; then
        run git -C "$main" stash pop --index
    fi
}
trap rollback EXIT

if [ -n "$main_dirty" ]; then
    run git -C "$main" stash push --quiet --message "worktree-to-main: $main_branch before migrating $branch"
    main_stash=$(git rev-parse --verify refs/stash)
fi

if [ $mode = move ]; then
    run git -C "$wt" switch --detach
    wt_detached=1
    run git -C "$main" switch "$branch"
else
    run git -C "$main" switch --create "$branch" "$wt_head"
fi

trap - EXIT

if [ -n "$wt_dirty" ]; then
    run git -C "$wt" stash push --quiet --include-untracked --message "worktree-to-main: $branch" \
        || die 1 "could not stash the changes of the worktree: they are untouched in $wt, and $main is now on $branch"
    wt_stash=$(git rev-parse --verify refs/stash)

    run git -C "$main" stash apply --quiet --index "$wt_stash" \
        || die 1 "could not apply the changes in $main: they are safe in stash $wt_stash (see 'git stash list'), fix the problem then run 'git -C $main stash apply --index $wt_stash'"

    if [ "$(git rev-parse --verify refs/stash)" = "$wt_stash" ]; then
        run git -C "$main" stash drop --quiet
    fi
fi

# --- Report -------------------------------------------------------------------

echo
echo "done: $main is on $branch"
git -C "$main" status --short --branch

if [ -n "$main_stash" ]; then
    echo
    echo "previous changes of the main worktree are stashed: $(git -C "$main" stash list --format='%gd: %gs' | grep --fixed-strings "worktree-to-main: $main_branch before migrating $branch" | head -n 1)"
fi

ignored=$(git -C "$wt" ls-files --others --ignored --exclude-standard --directory | head -n 20)
if [ -n "$ignored" ]; then
    echo
    echo "ignored files left in the worktree (first 20):"
    printf '%s\n' "$ignored"
fi

echo
echo "the worktree can now be removed: git -C $main worktree remove $wt"
if [ $mode = create ] && [ -n "$wt_branch" ]; then
    echo "and its former branch deleted: git -C $main branch --delete $wt_branch"
fi
