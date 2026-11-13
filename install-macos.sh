#!/bin/bash

# Sets this Mac up, lighter than install.sh: bash, git and Ghostty, the rest
# case by case. Run it as your user, and again after any change: each step
# leaves alone what is already done. Nothing updates on its own: run
# `brew upgrade` by hand.
#
# It starts under the bash 3.2 of macOS: no bash 4 syntax here.

set -euo pipefail

DOTFILES=$(dirname "$(realpath "$0")")
BASH_PATH=/opt/homebrew/bin/bash

# \033, not \e: the echo of bash 3.2 doesn't know \e
NOCOLOR='\033[0m'
REDCOLOR='\033[37;41m'
TITLECOLOR='\033[1;34m'

formulae=(
    bash    # bash 5, the login shell: macOS only ships bash 3.2
)

casks=(
    ghostty # GPU-accelerated terminal emulator
)

function main {
    if [[ $EUID -eq 0 ]]; then
        echo -e "$REDCOLOR Run this script as your user: it calls sudo when needed$NOCOLOR"
        exit 1
    fi
    if [[ ! -x /opt/homebrew/bin/brew ]]; then
        echo -e "$REDCOLOR Install Homebrew first: https://brew.sh$NOCOLOR"
        exit 1
    fi
    # The zsh of a fresh Mac doesn't put it in the PATH
    eval "$(/opt/homebrew/bin/brew shellenv)"

    link_dotfiles
    link_bin
    install_packages
    setup_ghostty
    setup_shell
}

function link_dotfiles {
    title 'Link the dotfiles into $HOME'
    # Not versioned: .gitconfig includes it for the identity
    touch $DOTFILES/.private-gitconfig

    ln -sf $DOTFILES/.bash_aliases        ~/.bash_aliases
    ln -sf $DOTFILES/.bashrc              ~/.bashrc
    ln -sf $DOTFILES/.gitconfig           ~/.gitconfig
    ln -sf $DOTFILES/.gitglobalexclude    ~/.gitglobalexclude
    ln -sf $DOTFILES/.private-gitconfig   ~/.private-gitconfig
    # The login shells of macOS read it, and it sources .bashrc
    ln -sf $DOTFILES/.profile             ~/.profile
    ln -sfn $DOTFILES/git-template        ~/.git-template
}

function link_bin {
    title 'Link the commands of bin/ the Mac uses'
    mkdir -p ~/.local/bin

    # git new, of .gitconfig
    ln -sf $DOTFILES/bin/git-new ~/.local/bin/
}

function install_packages {
    title 'Install Homebrew packages'
    # Installs only: brew install upgrades a package already there
    local package
    for package in "${formulae[@]}"; do
        brew list --formula "$package" &>/dev/null || brew install --formula "$package"
    done
    for package in "${casks[@]}"; do
        brew list --cask "$package" &>/dev/null || brew install --cask "$package"
    done
}

function setup_ghostty {
    title 'Setup Ghostty'
    mkdir -p ~/.config/ghostty
    ln -sf $DOTFILES/ghostty/config.ghostty ~/.config/ghostty/config.ghostty
}

function setup_shell {
    title 'Make bash 5 the login shell'
    # chsh refuses a shell missing from /etc/shells
    if ! grep -qx "$BASH_PATH" /etc/shells; then
        echo "$BASH_PATH" | sudo tee -a /etc/shells > /dev/null
    fi
    if [[ $(dscl . -read ~ UserShell) != "UserShell: $BASH_PATH" ]]; then
        chsh -s "$BASH_PATH"
    fi
}

function title {
    echo -e "\n$TITLECOLOR==> $1$NOCOLOR"
}

main
