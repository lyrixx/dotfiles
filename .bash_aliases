## Common bash function

alias sudo="sudo " # Hack, for sudo an aliases

alias ls="ls --color"
alias l="ls -lh --color"
alias ll="ls -lh --color"
alias lla="ll -a --color"

alias df="df -h"
alias du="du -h"
alias free="free -m"

alias cp="cp -i"
alias mv="mv -i"

alias grep='grep --exclude-dir ".svn" --exclude-dir ".git" --exclude tagsi --color=tty'
alias ack="ack-grep"

## notification

alias n="notify-send"
function m() { echo "Hi" | mail -s "$*" lyrixx@lyrixx.info ; }

## Apt
alias agi="apt-get install"
alias agua="apt-get update"
alias agup="apt-get upgrade"
alias aguap="apt-get update && apt-get upgrade"
alias adel=" apt-get remove"
alias asearch="apt-cache search"

## Archive
alias untar="tar xvf"
alias ungz="gunzip"
alias unzip2="bzip2 -d"
alias untargz="tar zxvf"
alias untarbz1="tar jxvf"
alias ungz2="bunzip2 -k"

## Services
### Flush
alias memcached_flush='echo "flush_all" | nc localhost 11211'
alias varnish_flush='varnishadm "ban.url .*"'

## Cool shortcut
alias top_process="ps -eo pcpu,pid,user,args | sort -k 1 -r | head -10"
alias mysql="mysql --sigint-ignore"
alias whatsmyip="curl -s checkip.dyndns.org|sed -e 's/.*Current IP Address: //' -e 's/<.*$//'"
