#!/bin/bash

# SessionStart, CwdChanged and FileChanged hook: the Herdr tab follows the
# branch of the session, through herdr-tab-follow-branch. Claude Code watches
# the HEAD of the repository, so a branch switched anywhere renames the tab,
# from Claude or from another terminal.

set -euo pipefail

input=$(cat)
cwd=$(jq -r .cwd <<< "$input")
event=$(jq -r .hook_event_name <<< "$input")

if [[ ${HERDR_ENV:-} != 1 ]]; then
    exit 0
fi

# A failure here must not cost the watch of HEAD
~/.local/bin/herdr-tab-follow-branch "$cwd" || echo 'Could not rename the Herdr tab after the branch.' >&2

# watchPaths would replace the list, and it is already the right one
if [[ $event == FileChanged ]]; then
    exit 0
fi

# In a worktree, its own HEAD. Out of git, the empty list stops watching the
# previous repository
head=$(git -C "$cwd" rev-parse --path-format=absolute --git-path HEAD 2> /dev/null) || head=

jq -n --arg event "$event" --arg head "$head" '{
    hookSpecificOutput: {
        hookEventName: $event,
        watchPaths: [$head | select(. != "")]
    }
}'
