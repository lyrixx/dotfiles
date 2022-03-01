if [ -f ~/.bash_aliases ]; then
    . ~/.bash_aliases
fi

if [ -f ~/.bash_private ]; then
    . ~/.bash_private
fi

if [ -f /etc/bash_completion ] && ! shopt -oq posix; then
    . /etc/bash_completion
fi

if [ -f /home/gregoire/.local/bin/aws_completer ] && ! shopt -oq posix; then
    complete -C /home/gregoire/.local/bin/aws_completer aws
fi

PATH="./node_modules/.bin:$PATH"

# if [ -d $HOME/.rbenv/bin ]; then
#     PATH="$HOME/.rbenv/bin:$PATH"
#     eval "$(rbenv init -)"
# fi

if [[ -d /usr/local/go/bin ]]; then
    export GOPATH=$HOME/dev/go
    export PATH=$PATH:/usr/local/go/bin
    export PATH=$PATH:$GOPATH/bin
fi

if [[ -d $HOME/.cargo ]]; then
    . "$HOME/.cargo/env"
fi

if [[ -d $HOME/.cargo/bin/ ]]; then
    PATH="$PATH:$HOME/.cargo/bin/"
fi

if [[ -d $HOME/.local/bin/ ]]; then
    PATH="$PATH:$HOME/.local/bin/"
fi

if [[ -d $HOME/.yarn/bin/ ]]; then
    PATH="$PATH:$HOME/.yarn/bin/"
fi

if [[ -d $HOME/dev/github.com ]]; then
    export CDPATH=".:$HOME:$HOME/dev/github.com"
fi

if [[ -f /usr/bin/terraform ]]; then
    complete -C /usr/bin/terraform terraform
fi

export GIT_PS1_SHOWDIRTYSTATE=true
export GIT_PS1_SHOWUNTRACKEDFILES=true

export HISTSIZE=50000
export HISTFILESIZE=${HISTSIZE}
export HISTIGNORE="ls:cd:[bf]g:exit"
export HISTCONTROL="ignoreboth" # ignore duplicate line + line which start by a space

export ANSIBLE_STDOUT_CALLBACK=debug

if [[ `which most` ]]; then export PAGER=`which most` ; fi
export LESS="FRSX"

export EDITOR=`which vim`

# 0 : normal, 1 : bold, 4 underline, nothing : background
NoColor="\[\e[0m\]"
Black="\[\e[00;30m\]"
Red="\[\e[00;31m\]"
Green="\[\e[00;32m\]"
Yellow="\[\e[00;33m\]"
Blue="\[\e[00;34m\]"
Purple="\[\e[00;35m\]"
Cyan="\[\e[00;36m\]"

if [[ $UID -eq 0 ]]; then
    PROMPT_PREFIX="$Red\u@\H$NoColor"
elif [[ $SSH_CONNECTION ]]; then
    PROMPT_PREFIX="$Yellow\u@\H$NoColor"
fi

type -t __git_ps1 &>/dev/null
GIT=$?
if [[ $GIT ]]; then
    PS1GIT=$Purple'$(__git_ps1 "(%s)")'$NoColor
fi
unset GIT

function _set_exit_color() {
    if [[ $? != "0" ]]; then EXITCOLOR=$Red\>$NoColor; else EXITCOLOR=$Green\>$Nocolor; fi
}

function _truncate_pwd() {
    PWD2="${PWD/#$HOME/~}"
    local pwdmaxlen=$((${COLUMNS:-20}/5))
    if [ ${#PWD2} -gt $pwdmaxlen ] ; then
        PWD2="…${PWD2: -$pwdmaxlen}"
    fi
}

PROMPT_COMMAND='_set_exit_color;history -a;_truncate_pwd;PS1="$EXITCOLOR$NoColor$PROMPT_PREFIX$Cyan$PWD2$NoColor$PS1GIT "'
#PS1='\[\e[01;33m\]\u \[\e[00;32m\]\w\[\e[0m\] ' # simple

if [[ `which dircolors` ]]; then
    if [ -f ~/.dir_colors ]; then
        eval `dircolors --bourne-shell ~/.dir_colors`
    fi
fi

# Autocomple with sudo
complete -cf sudo

shopt -s cdspell        # Pour que bash corrige automatiquement les fautes de frappes ex: cd ~/fiml sera remplacé par cd ~/film
shopt -s checkwinsize   # Pour que bash vérifie la taille de la fenêtre après chaque commande
shopt -s cmdhist        # Pour que bash sauve dans l'historique les commandes qui prennent plusieurs lignes sur une seule ligne.
shopt -s expand_aliases # Pour que bash montre la commande complete au lieu de l'alias
shopt -s extglob        # Pour que bash interprète les expressions génériques
shopt -s histappend     # Pour que bash ajoute au lieu d'écraser dans l'histo
shopt -s hostcomplete   # Pour que bash tente de résoudre le nom pour les ip suivis d'un @
shopt -s nocaseglob     # Pour que bash ne soit pas sensible a la casse

man() {
    env \
        LESS_TERMCAP_mb=$(printf "\e[1;31m") \
        LESS_TERMCAP_md=$(printf "\e[1;31m") \
        LESS_TERMCAP_me=$(printf "\e[0m") \
        LESS_TERMCAP_se=$(printf "\e[0m") \
        LESS_TERMCAP_so=$(printf "\e[1;41;37m") \
        LESS_TERMCAP_ue=$(printf "\e[0m") \
        LESS_TERMCAP_us=$(printf "\e[1;35m") \
            man "$@"
}

# tabtab source for serverless package
# uninstall by removing these lines or running `tabtab uninstall serverless`
[ -f /home/gregoire/.config/yarn/global/node_modules/tabtab/.completions/serverless.bash ] && . /home/gregoire/.config/yarn/global/node_modules/tabtab/.completions/serverless.bash
# tabtab source for sls package
# uninstall by removing these lines or running `tabtab uninstall sls`
[ -f /home/gregoire/.config/yarn/global/node_modules/tabtab/.completions/sls.bash ] && . /home/gregoire/.config/yarn/global/node_modules/tabtab/.completions/sls.bash
# tabtab source for slss package
# uninstall by removing these lines or running `tabtab uninstall slss`
[ -f /home/gregoire/.config/yarn/global/node_modules/tabtab/.completions/slss.bash ] && . /home/gregoire/.config/yarn/global/node_modules/tabtab/.completions/slss.bash

# heroku autocomplete setup
HEROKU_AC_BASH_SETUP_PATH=/home/gregoire/.cache/heroku/autocomplete/bash_setup && test -f $HEROKU_AC_BASH_SETUP_PATH && source $HEROKU_AC_BASH_SETUP_PATH;
