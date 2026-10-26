#!/bin/bash

# .deb published on GitHub releases, without an apt repository: deb-get follows
# the releases. balena-etcher comes from its catalog; the others from a local
# definition in deb-get/, since the catalog has none or picks an outdated
# appimagelauncher.

set -euo pipefail

DIR=$(dirname "$(realpath "$0")")

if ! command -v deb-get >/dev/null; then
    # https://github.com/wimpysworld/deb-get#install
    sudo apt-get install -y \
        curl \
        jq \
        lsb-release \
        wget
    curl -fsSL --max-time 60 https://raw.githubusercontent.com/wimpysworld/deb-get/main/deb-get | sudo -E bash -s install deb-get
fi

# deb-get sources these files as root: copy them, a symlink would let any
# change in this repository run as root
sudo install -d -m 755 /etc/deb-get/99-local.d
sudo install -m 644 "$DIR"/deb-get/* /etc/deb-get/99-local.d/

deb-get install \
    appimagelauncher \
    balena-etcher \
    fastflowlm \
    solidcraft
