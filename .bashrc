if [ -f ~/.bash_aliases ]; then
    . ~/.bash_aliases
fi

if [ -f ~/.bash_private ]; then
    . ~/.bash_private
fi

if [ -f /etc/bash_completion ] && ! shopt -oq posix; then
    . /etc/bash_completion
fi

PATH="./node_modules/.bin:$PATH"

if [[ -d /usr/local/go/bin ]]; then
    PATH=$PATH:/usr/local/go/bin
fi

if [[ -d $HOME/dev/go ]]; then
    export GOPATH=$HOME/dev/go
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

if [ -f $HOME/.ripgreprc ] ; then
    export RIPGREP_CONFIG_PATH=$HOME/.ripgreprc
fi

export GIT_PS1_SHOWDIRTYSTATE=true
export GIT_PS1_SHOWUNTRACKEDFILES=true

export HISTSIZE=50000
export HISTFILESIZE=${HISTSIZE}
export HISTIGNORE="ls:cd:[bf]g:exit"
export HISTCONTROL="ignoreboth" # ignore duplicate line + line which start by a space

export ANSIBLE_STDOUT_CALLBACK=debug
# export BUILDKIT_PROGRESS=plain

export COMPOSE_DOCKER_CLI_BUILD=1
export DOCKER_BUILDKIT=1

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

# BEGIN SNIPPET: Platform.sh CLI configuration
HOME=${HOME:-'/home/gregoire'}
export PATH="$HOME/"'.platformsh/bin':"$PATH"
if [ -f "$HOME/"'.platformsh/shell-config.rc' ]; then . "$HOME/"'.platformsh/shell-config.rc'; fi # END SNIPPET
