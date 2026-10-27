#!/bin/bash

# PreToolUse hook (Bash): refuse a commit, a PR or an issue that carries a
# Claude attribution: the session link (Claude-Session trailer,
# claude.ai/code/session URL), a Co-Authored-By: Claude trailer, or a
# "Generated with Claude Code" / "Created by Claude" footer. The message may be
# inline or in a file given with -F / --file / --body-file.

set -euo pipefail

command=$(jq -r '.tool_input.command // ""')

# git global options may sit before the subcommand (git -C dir commit,
# git -c key=value commit); -C and -c take their value as a separate word.
git_options='([[:space:]]+(-[Cc][[:space:]]+("[^"]*"|'\''[^'\'']*'\''|[^[:space:]]+)|-[^[:space:]]+))*'
if ! grep -qE "(^|[^[:alnum:]_-])(git${git_options}[[:space:]]+commit|gh[[:space:]]+(pr|issue)[[:space:]]+(create|edit|comment|review))" <<<"$command"; then
    exit 0
fi

content=$command
while read -r file; do
    file=${file/#\~/$HOME}
    if [ -f "$file" ]; then
        content+=$'\n'$(cat "$file")
    fi
done < <(grep -oE '(-F|--file|--body-file)[[:space:]=]+[^[:space:];|&]+' <<<"$command" | sed -E 's/^(-F|--file|--body-file)[[:space:]=]+//; s/^["'\'']//; s/["'\'']$//')

if grep -qiE 'claude\.ai/code/session|Claude-Session:|Co-Authored-By:.*Claude|Generated with \[?Claude Code|Created by \[?Claude' <<<"$content"; then
    jq -n '{
        hookSpecificOutput: {
            hookEventName: "PreToolUse",
            permissionDecision: "deny",
            permissionDecisionReason: "Claude attribution is forbidden (Claude-Session trailer, claude.ai/code/session link, Co-Authored-By: Claude, Generated with Claude Code, Created by Claude). Remove it from the commit message or the PR/issue body, then retry."
        }
    }'
fi
