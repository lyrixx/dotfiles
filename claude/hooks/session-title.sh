#!/bin/bash

# SessionStart hook: name the session after its repository and branch, so that
# /resume lists it as repo:branch. A Herdr tab without a custom name takes the
# branch alone: its workspace already says the repository. Nothing on a default
# branch: the name would say nothing about the work.

set -euo pipefail

function rename_herdr_tab {
    local branch=$1 tab label

    # Through the pane: HERDR_TAB_ID is stale once the pane has been moved
    tab=$(herdr pane current --current | jq -r .result.pane.tab_id) || return
    label=$(herdr tab get "$tab" | jq -r .result.tab.label) || return

    # For Herdr, a branch written here is a custom name like any other: remember
    # it, or the tab would stop following the branch after the first rename
    local written_label=${XDG_STATE_HOME:-$HOME/.local/state}/claude/herdr-tab-labels/$tab

    # Without a custom name, the label of a tab is its position
    if [[ ! $label =~ ^[0-9]+$ && $label != "$(cat "$written_label" 2> /dev/null)" ]]; then
        return 0
    fi

    # Silent on stdout: Claude reads the JSON of this hook there
    herdr tab rename "$tab" "$branch" > /dev/null || return
    mkdir -p "$(dirname "$written_label")"
    printf '%s' "$branch" > "$written_label"
}

cwd=$(jq -r '.cwd')

branch=$(git -C "$cwd" symbolic-ref --quiet --short HEAD 2>/dev/null) || exit 0
# Keep in sync with the branches brc, brcc, wtc and wtcc spare, in .gitconfig
if [[ $branch =~ ^(master|main|develop)$ ]]; then
    exit 0
fi

# Through the common dir, a worktree gets the name of its repository
common_dir=$(git -C "$cwd" rev-parse --path-format=absolute --git-common-dir)
repository=$(basename "$(dirname "$common_dir")")

if [[ ${HERDR_ENV:-} == 1 ]]; then
    # A failure here must not cost the title of the session
    rename_herdr_tab "$branch" || echo "Could not rename the Herdr tab to \"$branch\"." >&2
fi

jq -n --arg title "$repository:$branch" '{
    hookSpecificOutput: {
        hookEventName: "SessionStart",
        sessionTitle: $title
    }
}'
