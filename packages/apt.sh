#!/bin/bash

# apt packages, from Ubuntu or from the vendor's repository when Ubuntu doesn't
# ship them or ships them too old. Each repository is described in apt/, under
# the file name of the vendor's instructions or package: it replaces the file
# already there instead of adding a second entry, which apt refuses when the
# keys differ. The signing keys are downloaded again on every run, so a renewed
# key is picked up.

set -euo pipefail

DIR=$(dirname "$(realpath "$0")")

packages=(
    # Needed by the scripts of these dotfiles
    build-essential # C compiler: mise builds the cargo: tools with it
    curl            # Downloads the keys below and the installers of install.sh
    git             # Clones the Symfony components of install.sh
    jq              # Parses the input of the Claude Code hooks, in claude/hooks
    mise            # Installs the tools of mise.toml

    # PHP. The php- packages follow the PHP version of Ubuntu, unlike php8.5-.
    php-amqp        # RabbitMQ client: the amqp transport of Symfony Messenger
    php-cli         # php on the command line, install.sh needs it
    php-curl        # HTTP clients: symfony/http-client, Guzzle
    php-dev         # Headers and phpize, to build PECL extensions or php-src
    php-excimer     # Low-overhead sampling profiler
    php-gd          # Image resizing and drawing
    php-intl        # ICU: translations, localized dates and numbers
    php-mbstring    # Multibyte strings, needed by most projects
    php-mysql       # MySQL and MariaDB drivers: pdo_mysql, mysqli
    php-pcov        # Code coverage for PHPUnit, faster than Xdebug
    php-pgsql       # PostgreSQL driver
    php-redis       # Redis client (phpredis): cache, sessions, Messenger
    php-sqlite3     # SQLite driver
    php-uuid        # Fast UUID generation
    php-xml         # DOM, SimpleXML…: PHPUnit and php-cs-fixer need it
    php-zip         # Zip archives: composer unpacks packages with it
    symfony-cli     # symfony: local web server, Symfony Cloud

    # nativella, see its docs/development.md
    libclang-dev          # bindgen generates the PHP bindings with it (ext-php-rs)
    libphp-embed          # PHP as a shared library, libphp.so
    librsvg2-dev          # SVG rendering, needed by Tauri
    libwebkit2gtk-4.1-dev # WebKitGTK, the webview of Tauri
    libxdo-dev            # Keyboard and mouse simulation, needed by Tauri

    # Building C code
    bison           # Parser generator, to build php-src
    cmake           # C and C++ build system: -sys crates, llama.cpp
    flex            # Lexer generator, to build php-src
    re2c            # Lexer generator, to build php-src

    # Python
    python3-poetry  # Dependency manager of some Python projects
    python3-venv    # python3 -m venv: PlatformIO creates its environment with it

    # Containers and VMs
    containerd.io         # Container runtime of Docker
    docker-buildx-plugin  # docker buildx: BuildKit builds
    docker-ce             # Docker engine
    docker-ce-cli         # docker command
    docker-compose-plugin # docker compose
    vagrant               # VMs described in a Vagrantfile
    virtualbox-7.2        # VirtualBox, the hypervisor of Vagrant

    # Hosted services
    cloudflared     # Cloudflare Tunnel: exposes a local service through Cloudflare
    gh              # GitHub CLI
    ngrok           # Exposes a local port on a public URL
    terraform       # Infrastructure as code

    # Local web development
    apache2-utils   # ab (HTTP benchmark) and htpasswd, without the Apache server
    dnsmasq         # *.test resolves to 127.0.0.1, see setup_dnsmasq in install.sh
    libnss3-tools   # certutil: mkcert installs its CA in Chrome and Firefox with it
    redis-tools     # redis-cli and redis-benchmark, without the Redis server

    # Terminal
    tmux            # Terminal multiplexer
    tree            # Prints a directory tree
    ttyd            # Shares a terminal in the browser: vhs records with it
    vim             # Editor: Ubuntu only ships vim-tiny
    xsel            # Reads and writes the X clipboard from the terminal

    # Search in the terminal
    bat             # cat with syntax highlighting: batcat, linked as bat by install.sh
    fd-find         # Simpler, faster find: fdfind, aliased to fd
    fzf             # Fuzzy finder (Ctrl+R, Ctrl+T): install.sh writes ~/.fzf.bash
    ripgrep         # rg, fast recursive grep

    # System and hardware
    adb             # Android Debug Bridge: drives an Android phone over USB
    htop            # Interactive process viewer
    inxi            # Summary of the hardware: CPU, GPU, disks, network
    iotop           # top for disk reads and writes
    lm-sensors      # sensors: temperatures and fans
    ncdu            # Disk usage explorer
    rocm-smi        # Load, memory and temperature of the AMD GPU, like nvidia-smi

    # Network
    cifs-utils      # Mounts Windows and Samba shares: mount -t cifs
    nmap            # Network and port scanner
    whois           # Owner of a domain or an IP, also ships mkpasswd

    # GNOME
    gnome-shell-extension-prefs # The Extensions app, for GNOME Shell extensions
    gnome-tweaks                # Fonts, themes, window behavior, keys
    nautilus-image-converter    # Files: resize or rotate images in bulk

    # Desktop applications
    code                 # Visual Studio Code
    dropbox              # Dropbox: its daemon updates itself in ~/.dropbox-dist
    google-chrome-stable # Google Chrome
    signal-desktop       # Signal
    slack-desktop        # Slack
    spotify-client       # Spotify
    transmission         # BitTorrent client

    # Media and graphics
    ffmpeg          # Audio and video conversion, vhs needs it too
    gimp            # Image editing
    graphviz        # dot: draws graphs, Symfony workflows, dependencies
    vlc             # Video player

    # 3D and electronics
    blender         # 3D modeling and rendering
    kicad           # Electronic circuit design: schematics, PCB
)

function add_repository {
    local source_file=$1 key_url=$2 key_path=$3

    # Download next to the target first: a failed download must not break a working key
    sudo curl -fsSL --max-time 60 -o "$key_path.tmp" "$key_url"
    sudo mv "$key_path.tmp" "$key_path"
    sudo install -m 644 "$DIR/apt/$source_file" /etc/apt/sources.list.d/
}

# A fresh Ubuntu doesn't ship curl
if ! command -v curl >/dev/null; then
    sudo apt-get update
    sudo apt-get install -y curl
fi

sudo install -d -m 755 /etc/apt/keyrings

# apt reads a key as armored or binary from its extension: .asc or .gpg
add_repository cloudflared.sources https://pkg.cloudflare.com/cloudflare-public-v2.gpg /etc/apt/keyrings/cloudflared.gpg
add_repository docker.sources https://download.docker.com/linux/ubuntu/gpg /etc/apt/keyrings/docker.asc
# Same line and key as the dropbox package writes on every upgrade
add_repository dropbox.list https://linux.dropbox.com/fedora/rpm-public-key.asc /etc/apt/keyrings/dropbox.asc
add_repository github-cli.list https://cli.github.com/packages/githubcli-archive-keyring.gpg /etc/apt/keyrings/github-cli.gpg
# The google-chrome-stable package rewrites this file every day, with its own copy of the key
add_repository google-chrome.sources https://dl.google.com/linux/linux_signing_key.pub /etc/apt/keyrings/google-chrome.asc
add_repository hashicorp.list https://apt.releases.hashicorp.com/gpg /etc/apt/keyrings/hashicorp.asc
add_repository mise.sources https://mise.jdx.dev/gpg-key.pub /etc/apt/keyrings/mise.asc
add_repository ngrok.list https://ngrok-agent.s3.amazonaws.com/ngrok.asc /etc/apt/keyrings/ngrok.asc
add_repository signal-desktop.sources https://updates.signal.org/desktop/apt/keys.asc /etc/apt/keyrings/signal-desktop.asc
# Slack and Spotify put their new keys in /etc/apt/trusted.gpg.d themselves:
# their sources have no signed-by, so apt trusts those keys too. The keys below
# are only needed until the first install.
add_repository slack.list https://packagecloud.io/slacktechnologies/slack/gpgkey /etc/apt/trusted.gpg.d/slack.asc
# Spotify changes its key every year, under a new URL: https://www.spotify.com/download/linux/
add_repository spotify.list https://download.spotify.com/debian/pubkey_5384CE82BA52C83A.asc /etc/apt/trusted.gpg.d/spotify.asc
add_repository symfony-cli.sources https://dl.cloudsmith.io/public/symfony/stable/gpg.99CEF8A62216B73D.key /etc/apt/keyrings/symfony-cli.asc
add_repository virtualbox.sources https://www.virtualbox.org/download/oracle_vbox_2016.asc /etc/apt/keyrings/virtualbox.asc
# The code package rewrites this file on every upgrade, with its own copy of the key
add_repository vscode.sources https://packages.microsoft.com/keys/microsoft.asc /etc/apt/keyrings/vscode.asc

sudo apt-get update
# Installs only, bin/maj upgrades
sudo apt-get install -y --no-upgrade "${packages[@]}"
