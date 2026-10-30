#!/bin/bash

# Copy ~/.claude/settings.json into the dotfiles, without what must not be
# committed:
#  * autoMode: project specific, may describe private infrastructure
#  * rtk hook and permission: added by `rtk init -g`

cd "$(dirname "$0")"

jq '
    del(.autoMode)
    | if has("permissions") then .permissions.allow |= map(select(startswith("Bash(rtk ") | not)) else . end
    | if has("hooks") then
        .hooks |= (
            map_values(map(.hooks |= map(select(.command // "" | test("^rtk ") | not))) | map(select(.hooks | length > 0)))
            | with_entries(select(.value | length > 0))
        )
        | if (.hooks | length) == 0 then del(.hooks) else . end
      else . end
' ~/.claude/settings.json > settings.json
