#!/bin/bash

# Event hook pane.agent_detected: once Claude has left a pane, after /exit as
# after a crash, its tab gets its position back. While Claude runs, its status
# line names the tab after the session, through herdr-tab-follow-session.
#
# Herdr sends the event when an agent shows up too, and sends nothing for a
# `claude -p` run in the pane of a session.

set -euo pipefail

event=$HERDR_PLUGIN_EVENT_JSON

if ! jq -e '.data.released == true and .data.agent == "claude"' <<< "$event" > /dev/null; then
    exit 0
fi

pane_id=$(jq -r .data.pane_id <<< "$event")
panes=$("$HERDR_BIN_PATH" pane list | jq -c .result.panes)
tab_id=$(jq -r --arg pane_id "$pane_id" '.[] | select(.pane_id == $pane_id) | .tab_id' <<< "$panes")

# The pane has been closed, and its tab too when it was the last one
if [[ -z $tab_id ]]; then
    exit 0
fi

# Another session in the tab: its status line keeps the tab named
if jq -e --arg pane_id "$pane_id" --arg tab_id "$tab_id" \
    'any(.[]; .tab_id == $tab_id and .pane_id != $pane_id and .agent == "claude")' <<< "$panes" > /dev/null; then
    exit 0
fi

~/.local/bin/herdr-tab-follow-session --tab "$tab_id"
