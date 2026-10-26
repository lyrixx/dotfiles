#!/bin/bash

# Removes the manual installations that packages/ replaces. Destructive: run it
# by hand, once install.sh has installed the replacements. A second run does
# nothing.
#
# Nothing to remove for cloudflared and virtualbox-7.2 (their apt repository
# takes over the package), nor for appimagelauncher, balena-etcher, fastflowlm
# and solidcraft (deb-get takes them over).

set -euo pipefail
shopt -s nullglob

function fail {
    echo "$1" >&2
    exit 1
}

function is_installed {
    [[ $(dpkg-query -W -f='${db:Status-Status}' $1 2>/dev/null) == installed ]]
}

# Each replacement must be there before what it replaces goes away
for command in deb-get flatpak mise; do
    command -v $command >/dev/null || fail "$command is missing: run install.sh first."
done
[[ -z $(mise ls --global --missing) ]] || fail 'Some mise tools are missing: run install.sh first.'
for app in com.bambulab.BambuStudio org.raspberrypi.rpi-imager; do
    flatpak info $app &>/dev/null || fail "Flatpak $app is missing: run install.sh first."
done
is_installed symfony-cli || fail 'symfony-cli is missing: run install.sh first.'

echo 'Remove the apt keys replaced by those of packages/apt.sh'
# google-chrome, slack and spotify keep theirs: their package writes them again
sudo rm -f \
    /etc/apt/keyrings/githubcli-archive-keyring.gpg \
    /etc/apt/trusted.gpg.d/ngrok.asc \
    /etc/apt/trusted.gpg.d/spotify.gpg \
    /usr/share/keyrings/hashicorp-archive-keyring.gpg \
    /usr/share/keyrings/microsoft.gpg \
    /usr/share/keyrings/signal-desktop-keyring.gpg

echo 'Remove the .deb replaced by mise or Flatpak'
for package in dive glab rpi-imager; do
    if is_installed $package; then
        sudo apt-get purge -y $package
    fi
done

echo 'Remove the binaries replaced by mise'
# mkcert keeps its CA in ~/.local/share/mkcert: the new binary uses the same one
sudo rm -f /usr/local/bin/{ctop,llmfit,mkcert,nativella}
sudo rm -rf /usr/local/go
rm -f ~/go/bin/{goimports,gopls,scc,vhs}
rm -f ~/.local/bin/{hf,rtk,uv,uvx}
# ~/.nvm also holds the global npm packages (diff2html-cli). The rest of
# ~/.opencode is opencode's plugin cache.
rm -rf ~/.hf-cli ~/.nvm ~/.opencode/bin
# Read the list once: grep -q would close the pipe early, and pipefail would
# turn cargo's SIGPIPE into a failure
installed_crates=$(cargo install --list)
for crate in asciinema cargo-hack cross tauri-cli zizmor; do
    if grep -q "^$crate v" <<< "$installed_crates"; then
        cargo uninstall $crate
    fi
done

echo 'Remove symfony, now installed in /usr/bin by the symfony-cli package'
# ~/.symfony5 stays: the CLI keeps its data there
sudo rm -f /usr/local/bin/symfony

echo 'Remove the bat link, now in ~/.local/bin by install.sh'
sudo rm -f /usr/local/bin/bat

echo 'Remove php-cs-fixer from composer global: /usr/local/bin/php-cs-fixer, the clone, is the one used'
if composer global show friendsofphp/php-cs-fixer &>/dev/null; then
    composer global remove friendsofphp/php-cs-fixer
fi

echo 'Remove the AppImages replaced by Flatpak'
for appimage in ~/.app/BambuStudio_*.AppImage ~/.app/rpi-imager_*; do
    ail-cli unintegrate "$appimage"
    rm -f "$appimage"
done
# Created by rpi-imager itself, it still points to the AppImage
rm -f ~/.local/share/applications/com.raspberrypi.rpi-imager-uri-handler.desktop
update-desktop-database ~/.local/share/applications
