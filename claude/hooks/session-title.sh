#!/bin/bash

# SessionStart hook: name the session after its repository and branch, so that
# /resume lists it as repo:branch. Nothing on a default branch: the name would
# say nothing about the work.

set -euo pipefail

cwd=$(jq -r '.cwd')

branch=$(git -C "$cwd" symbolic-ref --quiet --short HEAD 2>/dev/null) || exit 0
# Keep in sync with the branches brc, brcc, wtc and wtcc spare, in .gitconfig
if [[ $branch =~ ^(master|main|develop)$ ]]; then
    exit 0
fi

# Through the common dir, a worktree gets the name of its repository
common_dir=$(git -C "$cwd" rev-parse --path-format=absolute --git-common-dir)
repository=$(basename "$(dirname "$common_dir")")

jq -n --arg title "$repository:$branch" '{
    hookSpecificOutput: {
        hookEventName: "SessionStart",
        sessionTitle: $title
    }
}'
