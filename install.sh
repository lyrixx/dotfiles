#!/bin/bash

NOCOLOR='\e[0m'
REDCOLOR='\e[37;41m'

if [[ $EUID -ne 0 ]]; then
   echo -e "$REDCOLOR Think to run this bash script as root$NOCOLOR"
fi

DOTFILES=`pwd`

touch $DOTFILES/.private-gitconfig

echo 'Create $HOME symlink'

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
ln -sf $DOTFILES/git-template ~/.git-template

echo 'Setup Claude Code'
mkdir -p ~/.claude/skills

ln -sf $DOTFILES/claude/CLAUDE.md             ~/.claude/CLAUDE.md
ln -sfn $DOTFILES/claude/rules                 ~/.claude/rules
for skill in $DOTFILES/claude/skills/*/; do
    ln -sfn "${skill%/}" ~/.claude/skills/"$(basename "$skill")"
done
# https://letape-dapres.fr/ressources/skills/writing-unslop#installation
mkdir -p ~/.claude/skills/writing:unslop && \
    curl -sSL https://letape-dapres.fr/api/skills/writing-unslop/raw \
        -o ~/.claude/skills/writing:unslop/SKILL.md

if [[ ! -f ~/.claude/settings.json ]]; then
    cp $DOTFILES/claude/settings.json ~/.claude/settings.json
fi

if [[ `which rtk` ]] ; then
    echo 'Install rtk for Claude Code'
    rtk init -g --auto-patch
fi

echo 'Create custom bin symlink'
mkdir -p $HOME/.local/bin

ln -sf $DOTFILES/bin/\$                         /$HOME/.local/bin/\$
ln -sf $DOTFILES/bin/demo-bash                  /$HOME/.local/bin/demo-bash
ln -sf $DOTFILES/bin/git-new                    /$HOME/.local/bin/git-new
ln -sf $DOTFILES/bin/json_pp                    /$HOME/.local/bin/json_pp
ln -sf $DOTFILES/bin/remove-csi                 /$HOME/.local/bin/remove-csi
ln -sf $DOTFILES/bin/tmux-start                 /$HOME/.local/bin/tmux-start
ln -sf $DOTFILES/bin/slug                       /$HOME/.local/bin/slug

if [[ `which php` ]] ; then
    if [[ ! -f $HOME/.local/bin/composer ]]; then
        echo 'Install composer'
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar $HOME/.local/bin/composer
    fi
fi

if [[ `which fzf` ]] ; then
    echo 'Install fzf'
    fzf --bash > ~/.fzf.bash
fi
