#!/bin/bash

# SessionEnd hook: once I have quit Claude, a Herdr tab on a default branch gets
# its position back, even with a custom name (herdr-claude names the tab after
# the agent). On another branch, the tab keeps its name.

set -euo pipefail

input=$(cat)

if [[ ${HERDR_ENV:-} != 1 ]]; then
    exit 0
fi

# Ctrl+D or /exit. Not a `claude -p` run in the pane, which ends with "other"
# while the session of the tab goes on, nor /clear or /resume
if [[ $(jq -r .reason <<< "$input") != prompt_input_exit ]]; then
    exit 0
fi

~/.local/bin/herdr-tab-follow-branch --drop-custom-name "$(jq -r .cwd <<< "$input")"
