#!/bin/bash

# Installs my Claude Code config in a cloud session (claude.ai/code), whose VM
# starts with an empty ~/.claude and commits as Claude. The setup script of the
# cloud environment runs it, as root, before Claude Code starts: see README.md.

set -euo pipefail

# github.com serves the VM only the repositories of the session: the files come
# one by one from raw.githubusercontent.com
REPO_URL=https://raw.githubusercontent.com/lyrixx/dotfiles/master
BASE_URL=$REPO_URL/claude

function main {
    # The session writes the identity of Claude in the global git config, and
    # these variables win over any git config
    local gitconfig git_email git_name
    gitconfig=$(curl -fsSL --max-time 30 "$REPO_URL/.gitconfig")
    git_email=$(git config --file - user.email <<< "$gitconfig")
    git_name=$(git config --file - user.name <<< "$gitconfig")

    fetch CLAUDE.md
    # RTK.md comes from `rtk init -g`, and rtk isn't on the VM
    sed -i '/^@RTK\.md$/d' ~/.claude/CLAUDE.md
    fetch hooks/block-claude-attribution.sh
    # Keep in sync with the files of rules/
    fetch rules/code.md
    fetch rules/docs.md
    fetch rules/front.md
    fetch rules/php.md
    fetch rules/tooling.md
    chmod +x ~/.claude/hooks/block-claude-attribution.sh

    # Only what makes sense away from this laptop: no herdr hook, no status
    # line, no local path
    curl -fsSL --max-time 30 "$BASE_URL/settings.json" | jq --arg git_email "$git_email" --arg git_name "$git_name" '{
        attribution,
        effortLevel,
        env: {
            GIT_AUTHOR_EMAIL: $git_email,
            GIT_AUTHOR_NAME: $git_name,
            GIT_COMMITTER_EMAIL: $git_email,
            GIT_COMMITTER_NAME: $git_name
        },
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
