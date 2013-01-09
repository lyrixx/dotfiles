#!/bin/bash

DOTFILES=`pwd`

ln -sf $DOTFILES/.ackrc               ~/.ackrc
ln -sf $DOTFILES/.bash_aliases        ~/.bash_aliases
ln -sf $DOTFILES/.bash_logout         ~/.bash_logout
ln -sf $DOTFILES/.bashrc              ~/.bashrc
ln -sf $DOTFILES/.gitconfig           ~/.gitconfig
ln -sf $DOTFILES/.gitglobalexclude    ~/.gitglobalexclude
ln -sf $DOTFILES/.profile             ~/.profile
ln -sf $DOTFILES/.screenrc            ~/.screenrc
ln -sf $DOTFILES/.vimrc               ~/.vimrc

if type -t git &> /dev/null; then
  git submodule update --init
  ln -sf $DOTFILES/dircolors-solarized/dircolors.ansi-dark  ~/.dir_colors
  ln -sf $DOTFILES/symfony2-autocomplete/symfony2-autocomplete.bash  ~/.symfony2-autocomplete.bash
fi

. ~/.bashrc

