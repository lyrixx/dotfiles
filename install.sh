#!/bin/bash

# Sets this machine up: links the dotfiles, installs the software of packages/
# and configures it. Run it as your user, and again after any change: each step
# leaves alone what is already done.

set -euo pipefail

DOTFILES=$(dirname "$(realpath "$0")")

NOCOLOR='\e[0m'
REDCOLOR='\e[37;41m'
TITLECOLOR='\e[1;34m'

function main {
    if [[ $EUID -eq 0 ]]; then
        echo -e "$REDCOLOR Run this script as your user: it calls sudo when needed$NOCOLOR"
        exit 1
    fi

    link_dotfiles
    link_bin
    install_packages
    setup_claude_code
    setup_dnsmasq
    setup_docker
    setup_fzf
    setup_php
}

function link_dotfiles {
    title 'Link the dotfiles into $HOME'
    # Not versioned: .gitconfig includes it for the identity
    touch $DOTFILES/.private-gitconfig

    ln -sf $DOTFILES/.bash_aliases        ~/.bash_aliases
    ln -sf $DOTFILES/.bash_logout         ~/.bash_logout
    ln -sf $DOTFILES/.bashrc              ~/.bashrc
    ln -sf $DOTFILES/.gitconfig           ~/.gitconfig
    ln -sf $DOTFILES/.gitglobalexclude    ~/.gitglobalexclude
    ln -sf $DOTFILES/.private-gitconfig   ~/.private-gitconfig
    ln -sf $DOTFILES/.profile             ~/.profile
    ln -sf $DOTFILES/.psqlrc              ~/.psqlrc
    ln -sf $DOTFILES/.ripgreprc           ~/.ripgreprc
    ln -sf $DOTFILES/.screenrc            ~/.screenrc
    ln -sf $DOTFILES/.tmux.conf           ~/.tmux.conf
    ln -sf $DOTFILES/.vimrc               ~/.vimrc
    ln -sfn $DOTFILES/git-template        ~/.git-template
}

function link_bin {
    title 'Link the commands of bin/'
    mkdir -p ~/.local/bin

    local executable
    for executable in $DOTFILES/bin/*; do
        ln -sf "$executable" ~/.local/bin/
    done
    # Debian names bat batcat
    ln -sf /usr/bin/batcat ~/.local/bin/bat
}

function install_packages {
    title 'Install apt packages'
    $DOTFILES/packages/apt.sh
    title 'Install deb-get packages'
    $DOTFILES/packages/deb-get.sh
    title 'Install Flatpak apps'
    $DOTFILES/packages/flatpak.sh
    title 'Install mise tools'
    $DOTFILES/packages/mise.sh
}

function setup_claude_code {
    title 'Setup Claude Code'
    mkdir -p ~/.claude/skills

    ln -sf $DOTFILES/claude/CLAUDE.md     ~/.claude/CLAUDE.md
    ln -sfn $DOTFILES/claude/hooks        ~/.claude/hooks
    ln -sfn $DOTFILES/claude/rules        ~/.claude/rules
    ln -sf $DOTFILES/claude/statusline.sh ~/.claude/statusline.sh
    local skill
    for skill in $DOTFILES/claude/skills/*/; do
        ln -sfn "${skill%/}" ~/.claude/skills/"$(basename "$skill")"
    done
    # https://letape-dapres.fr/ressources/skills/writing-unslop#installation
    mkdir -p ~/.claude/skills/writing:unslop
    curl -fsSL --max-time 60 https://letape-dapres.fr/api/skills/writing-unslop/raw \
        -o ~/.claude/skills/writing:unslop/SKILL.md
    # Generated from the installed hf, in ~/.agents/skills and linked into
    # ~/.claude/skills: --force regenerates it
    mise exec -- hf skills add --global --force
    # DISABLE_TELEMETRY: otherwise the CLI reports each install to Vercel
    DISABLE_TELEMETRY=1 mise exec -- skills add https://github.com/cloudflare/security-audit-skill \
        --skill security-audit --global --agent claude-code --yes

    if [[ ! -f ~/.claude/settings.json ]]; then
        cp $DOTFILES/claude/settings.json ~/.claude/settings.json
    fi

    mise exec -- rtk init -g --auto-patch
}

function setup_dnsmasq {
    title 'Setup dnsmasq: *.test resolves to 127.0.0.1'
    # dnsmasq answers on 127.0.0.1, systemd-resolved sends it the .test queries.
    # On a fresh install, dnsmasq only starts once its config is there: the
    # default one wants port 53 on every address, and systemd-resolved already
    # listens on 127.0.0.53.
    install_system_config dnsmasq/dev-domains.conf /etc/dnsmasq.d/dev-domains.conf dnsmasq
    install_system_config dnsmasq/resolved.conf /etc/systemd/resolved.conf.d/dnsmasq.conf systemd-resolved
}

function setup_docker {
    title 'Setup Docker'
    # docker without sudo, from the next login
    sudo usermod -aG docker "$USER"
}

function setup_fzf {
    title 'Setup fzf'
    fzf --bash > ~/.fzf.bash
}

function setup_php {
    title 'Setup PHP'
    if [[ ! -x ~/.local/bin/composer ]]; then
        curl -fsS --max-time 60 https://getcomposer.org/installer | php -- --install-dir="$HOME/.local/bin" --filename=composer
    fi
    # Before my.ini: it prepends the autoload of var-dumper to every script, and
    # PHP stops if the file is missing
    install_symfony_component var-dumper
    # For bin/slug
    install_symfony_component string

    # The PHP version of Ubuntu: php-cli comes from packages/apt.sh
    local version
    version=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
    sudo ln -sf $DOTFILES/php/my.ini /etc/php/$version/mods-available/my.ini
    sudo phpenmod my
}

# Copies a file of this repository into /etc, and restarts the service when the
# file changed. Copied, not linked: a daemon that doesn't run as root can't
# read $HOME.
function install_system_config {
    local source=$1 target=$2 service=$3

    if ! cmp -s "$DOTFILES/$source" "$target"; then
        sudo install -D -m 644 "$DOTFILES/$source" "$target"
        sudo systemctl restart "$service"
    fi
}

# Clones a Symfony component next to the other GitHub clones, with its
# dependencies. Over HTTPS: it works before any SSH key is set up, and these
# read-only splits of symfony/symfony take no push anyway.
function install_symfony_component {
    local dir=$HOME/dev/github.com/symfony/$1

    if [[ ! -d $dir ]]; then
        git clone https://github.com/symfony/$1 "$dir"
    fi
    if [[ ! -f $dir/vendor/autoload.php ]]; then
        ~/.local/bin/composer install --working-dir="$dir" --no-interaction
    fi
}

function title {
    echo -e "\n$TITLECOLOR==> $1$NOCOLOR"
}

main
