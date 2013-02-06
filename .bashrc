if [ -f ~/.bash_aliases ]; then
    . ~/.bash_aliases
fi

if [ -f /etc/bash_completion ] && ! shopt -oq posix; then
    . /etc/bash_completion
fi

if [ -d /var/lib/gems/1.8/bin/ ]; then
    PATH=/var/lib/gems/1.8/bin/:$PATH
fi;

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
if [[ `which git` ]]; then
    PS1GIT=$Purple'$(__git_ps1 "(%s)")'$NoColor
fi

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

PROMPT_COMMAND='_set_exit_color;_truncate_pwd;PS1="$EXITCOLOR$PROMPT_PREFIX$Cyan[$PWD2]$NoColor$PS1GIT "'
#PS1='[\W]\[\e[0m\]\[\e[01;34m\]$(__git_ps1 "(%s)")\[\e[0m\] ' # Oneline, trainging
#PS1='\[\e[01;33m\]\u@\H\[\e[00;32m\][\w]\[\e[0m\]\[\e[01;34m\]$(__git_ps1 "(%s)")\[\e[0m\] ' # Oneline, Old

# symfony1 autocompletion
_symfony()
{
    local cmds cur colonprefixes
    cmds="$( ${COMP_WORDS[0]} | perl -ne 'if( /^([a-zA-Z0-9\-]+)/ ) { $first = $1; } elsif ( /^\s*(:[a-zA-Z0-9\-]+)/ ) { print $first . $1 . "\n"; }' )"
    COMPREPLY=()
    cur=${COMP_WORDS[COMP_CWORD]}
    # Work-around bash_completion issue where bash interprets a colon as a separator.
    # Work-around borrowed from the darcs work-around for the same issue.
    colonprefixes=${cur%"${cur##*:}"}
    COMPREPLY=( $(compgen -W '$cmds' -- $cur))
    local i=${#COMPREPLY[*]}
    while [ $((--i)) -ge 0 ]; do
    COMPREPLY[$i]=${COMPREPLY[$i]#"$colonprefixes"}
    done

    return 0
} &&
complete -F _symfony symfony

# Symfony2 autocompletion
if [ -e ~/.symfony2-autocomplete.bash ]; then
    . ~/.symfony2-autocomplete.bash
fi

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

function reset_perm(){
    chmod -R u=rwX,go=rX "$@"
    chown -R ${USER}:users "$@"
}

function wget_mirror() { wget -r -l5 -k -E ${1} && cd $_;}

toLowercase() { for i in "$@"; do mv -f "$i" "`echo $i| tr [A-Z] [a-z]`" &>/dev/null; done }
toUpercase()  { for i in "$@"; do mv -f "$i" "`echo $i| tr [a-z] [A-Z]`" &>/dev/null; done }

#Temp hack
exportDbus() {
    # Get the pid of nautilus
    nautilus_pid=$(pgrep -u $LOGNAME -n nautilus)

    # Grab the DBUS_SESSION_BUS_ADDRESS variable from nautilus's environment
    if [ -f /proc/$nautilus_pid/environ ]; then
        eval $(tr '\0' '\n' < /proc/$nautilus_pid/environ | \grep '^DBUS_SESSION_BUS_ADDRESS=')
    fi

    # export it so that child processes will inherit it
    export DBUS_SESSION_BUS_ADDRESS
}
exportDbus

