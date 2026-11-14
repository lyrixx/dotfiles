#!/bin/bash

# Installs my Claude Code config in a cloud session (claude.ai/code), whose VM
# starts with an empty ~/.claude. The setup script of the cloud environment
# runs it, as root, before Claude Code starts: see README.md.

set -euo pipefail

# github.com serves the VM only the repositories of the session: the files come
# one by one from raw.githubusercontent.com
BASE_URL=https://raw.githubusercontent.com/lyrixx/dotfiles/master/claude

function main {
    fetch CLAUDE.md
    fetch hooks/block-claude-attribution.sh
    # Keep in sync with the files of rules/
    fetch rules/code.md
    fetch rules/docs.md
    fetch rules/front.md
    fetch rules/php.md
    chmod +x ~/.claude/hooks/block-claude-attribution.sh

    # Only what makes sense away from this laptop: no herdr hook, no status
    # line, no local path
    curl -fsSL --max-time 30 "$BASE_URL/settings.json" | jq '{
        attribution,
        effortLevel,
        hooks: {
            PreToolUse: .hooks.PreToolUse | map(select(any(.hooks[]; .command | endswith("/block-claude-attribution.sh"))))
        },
        modelSettings
    }' > ~/.claude/settings.json
}

function fetch {
    mkdir -p "$(dirname ~/.claude/"$1")"
    curl -fsSL --max-time 30 "$BASE_URL/$1" -o ~/.claude/"$1"
}

main
