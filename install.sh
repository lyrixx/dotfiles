#!/bin/bash

CURRENT=`pwd`

ln -sf $CURRENT/.bash_aliases        ~/.bash_aliases
ln -sf $CURRENT/.bash_logout         ~/.bash_logout
ln -sf $CURRENT/.bashrc              ~/.bashrc
ln -sf $CURRENT/.gitconfig           ~/.gitconfig
ln -sf $CURRENT/.gitglobalexclude    ~/.gitglobalexclude
ln -sf $CURRENT/.profile             ~/.profile
ln -sf $CURRENT/.screenrc            ~/.screenrc
ln -sf $CURRENT/.vimrc               ~/.vimrc

. ~/.bashrc
