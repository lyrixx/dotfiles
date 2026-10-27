#!/bin/bash

# Claude Code status line: session name, git worktree, tokens in the context
# window and used by the session, and what is left of the 5-hour and weekly
# usage limits. Reads the JSON Claude Code writes on stdin
# (https://code.claude.com/docs/en/statusline). The limits are only there for a
# subscription, after the first API response.

set -euo pipefail

input=$(cat)

# Orca reads the usage limits through its own status line, which this one
# replaces: keep feeding it. In the background, so its HTTP call (up to 1.5 s)
# never delays the display.
orca_statusline=~/.orca/agent-hooks/claude-statusline.sh
if [[ -x $orca_statusline ]]; then
    /bin/sh "$orca_statusline" <<<"$input" >/dev/null 2>&1 &
fi

# Claude Code gives no token totals for the session: sum them from the
# transcripts, the session's and its subagents'. An API response spans one line
# per content block, each carrying its usage: count each message once. grep
# first, jq alone takes a second on a 100 MB transcript. fromjson? skips the
# last line while Claude Code is still writing it.
transcript=$(jq -r '.transcript_path' <<<"$input")
shopt -s nullglob
transcripts=("$transcript" "${transcript%.jsonl}"/subagents/*.jsonl)
session_usage=$(LC_ALL=C grep -hs '"type":"assistant"' "${transcripts[@]}" | jq -nR '
    reduce (inputs | fromjson? | select(.type == "assistant") | .message) as $message ({}; .[$message.id] = $message.usage)
    | select(length > 0)
    | [.[]]
    | {
        input: (map(.input_tokens + .cache_creation_input_tokens) | add),
        cache: (map(.cache_read_input_tokens) | add),
        output: (map(.output_tokens) | add)
    }
' || true)

jq -r --argjson session_usage "${session_usage:-null}" '
    def human: if . >= 1000000000 then "\(. / 100000000 | round / 10)B" elif . >= 1000000 then "\(. / 100000 | round / 10)M" elif . >= 1000 then "\(. / 1000 | round)k" else tostring end;
    def left($reset_format):
        (100 - .used_percentage | round) as $left
        | (if $left < 20 then 31 elif $left < 50 then 33 else 32 end) as $color
        | "\u001b[\($color)m\($left)% left\u001b[0m, reset \(.resets_at | strflocaltime($reset_format))";
    [
        .session_name,
        (.workspace.git_worktree // empty | "worktree: \(.)"),
        (.context_window | select(.used_percentage != null) | "\(.total_input_tokens | human) / \(.context_window_size | human) tokens (\(.used_percentage | round)%)"),
        ($session_usage // empty | "in \(.input | human), cache \(.cache | human), out \(.output | human)"),
        (.rate_limits.five_hour // empty | "5h: \(left("%H:%M"))"),
        (.rate_limits.seven_day // empty | "week: \(left("%a %H:%M"))")
    ]
    | map(values)
    | join(" │ ")
' <<<"$input"
