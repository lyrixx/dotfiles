#!/bin/bash

CURRENT=`pwd`

ln -sf $CURRENT/.bash_aliases        ~/.bash_aliases
ln -sf $CURRENT/.bash_common         ~/.bash_common
ln -sf $CURRENT/.bash_logout         ~/.bash_logout
ln -sf $CURRENT/.gitconfig           ~/.gitconfig
ln -sf $CURRENT/.gitglobalexclude    ~/.gitglobalexclude
ln -sf $CURRENT/.profile             ~/.profile
ln -sf $CURRENT/.screenrc            ~/.screenrc
ln -sf $CURRENT/.vimrc               ~/.vimrc

if [ $EUID -ne 0 ]; then
    ln -sf $CURRENT/.bashrc              ~/.bashrc
else
    ln -sf $CURRENT/.bashrc_root         ~/.bashrc
fi

. ~/.bashrc
