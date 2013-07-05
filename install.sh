#!/bin/bash

NOCOLOR='\e[0m'
REDCOLOR='\e[37;41m'

if [[ $EUID -ne 0 ]]; then
   echo -e "$REDCOLOR Think to run this bash script as root$NOCOLOR"
    IS_ROOT=0
else
    IS_ROOT=1
fi

DOTFILES=`pwd`

touch $DOTFILES/.private-gitconfig

echo 'Create $HOME symlink'
ln -sf $DOTFILES/.ackrc               ~/.ackrc
ln -sf $DOTFILES/.bash_aliases        ~/.bash_aliases
ln -sf $DOTFILES/.bash_logout         ~/.bash_logout
ln -sf $DOTFILES/.bashrc              ~/.bashrc
ln -sf $DOTFILES/.gitconfig           ~/.gitconfig
ln -sf $DOTFILES/.gitglobalexclude    ~/.gitglobalexclude
ln -sf $DOTFILES/.private-gitconfig   ~/.private-gitconfig
ln -sf $DOTFILES/.profile             ~/.profile
ln -sf $DOTFILES/.screenrc            ~/.screenrc
ln -sf $DOTFILES/.tmux.conf           ~/.tmux.conf
ln -sf $DOTFILES/.vimrc               ~/.vimrc
ln -sf $DOTFILES/git-template         ~/.git-template

echo 'Install git submodule'
if type -t git &> /dev/null; then
  git submodule update --init
  ln -sf $DOTFILES/dircolors-solarized/dircolors.ansi-dark  ~/.dir_colors
  ln -sf $DOTFILES/symfony2-autocomplete/symfony2-autocomplete.bash  ~/.symfony2-autocomplete.bash
fi

if [[ $IS_ROOT = 1 ]]; then
    if [[ ! -f /usr/local/bin/composer ]]; then
        echo 'Install composer'
        curl -sS https://getcomposer.org/installer | php
        mv composer.phar /usr/local/bin/composer
    fi
    composer install
    echo 'Create /usr/local/bin/* symlink'
    ln -sf $DOTFILES/vendor/bin/php-cs-fixer        /usr/local/bin/php-cs-fixer
    ln -sf $DOTFILES/vendor/bin/phpunit             /usr/local/bin/phpunit
    ln -sf $DOTFILES/vendor/bin/sismo               /usr/local/bin/sismo
    ln -sf $DOTFILES/vendor/bin/gh                  /usr/local/bin/gh
    ln -sf $DOTFILES/vendor/bin/insight             /usr/local/bin/insight

    echo 'Create custom bin symlink'
    ln -sf $DOTFILES/bin/sismo-add                  /usr/local/bin/sismo-add
    ln -sf $DOTFILES/bin/tmux-start                 /usr/local/bin/tmux-start
fi
