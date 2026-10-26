#!/bin/bash

# Command-line tools and languages, listed in mise.toml. apt.sh installs mise.

set -euo pipefail

DIR=$(dirname "$(realpath "$0")")

mkdir -p ~/.config/mise
ln -sf "$DIR/mise.toml" ~/.config/mise/config.toml

if [[ ! -x ~/.cargo/bin/cargo ]]; then
    # mise builds the cargo: tools with it
    curl --proto '=https' --tlsv1.2 -fsS --max-time 60 https://sh.rustup.rs | sh -s -- -y --no-modify-path
fi
PATH="$HOME/.cargo/bin:$PATH" mise install --yes
