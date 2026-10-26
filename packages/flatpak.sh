#!/bin/bash

# Graphical applications from Flathub.

set -euo pipefail

sudo apt-get install -y flatpak
sudo flatpak remote-add --if-not-exists flathub https://dl.flathub.org/repo/flathub.flatpakrepo
# An application already installed is skipped, not updated: bin/maj does that
sudo flatpak install -y --noninteractive flathub \
    com.bambulab.BambuStudio \
    org.raspberrypi.rpi-imager
