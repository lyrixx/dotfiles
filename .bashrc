if [ -f ~/.bash_aliases ]; then
    . ~/.bash_aliases
fi

if [ -f /etc/bash_completion ] && ! shopt -oq posix; then
    . /etc/bash_completion
fi

if [ -d $HOME/.rbenv/bin ]; then
    PATH="$HOME/.rbenv/bin:$PATH"
    eval "$(rbenv init -)"
fi

if [[ -d $HOME/dev/tools/go-installer/go/bin ]]; then
    export GOROOT=$HOME/dev/tools/go-installer/go/
    export GOPATH=$HOME/dev/go
    PATH="$PATH:$GOROOT/bin"
fi

if [ -d /usr/local/heroku/bin ]; then
    PATH=/usr/local/heroku/bin:$PATH
fi;

export GIT_PS1_SHOWDIRTYSTATE=true
export GIT_PS1_SHOWUNTRACKEDFILES=true

export HISTSIZE=10000
export HISTFILESIZE=${HISTSIZE}
export HISTIGNORE="ls:cd:[bf]g:exit"
export HISTCONTROL="ignoreboth" # ignore duplicate line + line which start by a space

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
    local pwdmaxlen=$((${COLUMNS:-20}/3))
    if [ ${#PWD2} -gt $pwdmaxlen ] ; then
        PWD2="{..}${PWD2: -$pwdmaxlen}"
    fi
}

function _todo_number() {
    if [ -d ~/Desktop ] ; then
        TODO_NB=`find ~/Desktop -maxdepth 1 -type f  | wc -l`
    fi
}

PROMPT_COMMAND='_set_exit_color;_truncate_pwd;_todo_number;PS1="$EXITCOLOR$NoColor($TODO_NB)$PROMPT_PREFIX$Cyan[$PWD2]$NoColor$PS1GIT "'
#PS1='[\W]\[\e[0m\]\[\e[01;34m\]$(__git_ps1 "(%s)")\[\e[0m\] ' # Oneline, training
#PS1='\[\e[01;33m\]\u@\H\[\e[00;32m\][\w]\[\e[0m\]\[\e[01;34m\]$(__git_ps1 "(%s)")\[\e[0m\] ' # Oneline, Old


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
shopt -s extglob        # Pour que bash, interprète les expressions génériques
shopt -s histappend     # Pour que bash ajoute au lieu d'écraser dans l'histo
shopt -s hostcomplete   # Pour que bash tente de résoudre le nom pour les ip suivis d'un @
shopt -s nocaseglob     # Pour que bash ne soit pas sensible a la casse

function reset_perm() {
    chmod -R u=rwX,go=rX "$@"
    chown -R ${USER}: "$@"
}

function wget_mirror() {
    wget -r -l5 -k -E ${1} && cd $_
}

function to_lower_case() {
    find . -depth -exec rename 's/(.*)\/([^\/]*)/$1\/\L$2/' {} \;
}
