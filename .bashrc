if [ -f ~/.bash_aliases ]; then
    . ~/.bash_aliases
fi

if [ -f /etc/bash_completion ] && ! shopt -oq posix; then
    . /etc/bash_completion
fi

GIT_PS1_SHOWDIRTYSTATE=true
GIT_PS1_SHOWUNTRACKEDFILES=true

NoColor="\[\e[0m\]"
Red="\[\e[01;31m\]"
Green="\[\e[00;32m\]"
Yellow="\[\e[01;33m\]"
Blue="\[\e[01;34m\]"
Cyan="\[\e[00;36m\]"

if [[ $UID -eq 0 ]]; then
    PROMPT_PREFIX="$Red\u@\H$NoColor"
elif [[ $SSH_CONNECTION ]]; then
    PROMPT_PREFIX="$Yellow\u@\H$NoColor"
fi
if [[ `which git` ]]; then
    PS1GIT=$Blue'$(__git_ps1 "(%s)")'$NoColor
fi

PS1="$PROMPT_PREFIX$Green[\w]$NoColor$PS1GIT "
#PS1='[\W]\[\e[0m\]\[\e[01;34m\]$(__git_ps1 "(%s)")\[\e[0m\] ' # Formation
# PS1='\[\e[01;33m\]\u@\H\[\e[00;32m\][\w]\[\e[0m\]\[\e[01;34m\]$(__git_ps1 "(%s)")\[\e[0m\] ' #One line, OLD


# less with clor
export LESS=-R

export EDITOR=`which vim`
export PAGER=`which most`

# Bash symfony1 completion
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
