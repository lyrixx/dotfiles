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

alias grep='grep --color=tty'

alias PS1="grep '#PS1' ~/.bashrc | sed 's/^#\(.*\)/\1/'"

## Notification

alias n="notify-send"

## Apt
alias agi="apt install"
alias agua="apt update"
alias agup="apt upgrade"
alias aguap="apt update && apt upgrade"
alias adel=" apt remove"
alias asearch="apt search"

## Archive
alias untar="tar xvf"
alias ungz="gunzip"
alias unzip2="bzip2 -d"
alias untargz="tar zxvf"
alias untarbz1="tar jxvf"
alias ungz2="bunzip2 -k"

## Services
alias docker-ip="docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}'"
alias docker-ips="docker ps -q | xargs -n 1 docker inspect --format '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}} {{ .Name }}' | sed 's/ \// /' | sort -n"
alias docker-clear-logs='sudo sh -c "truncate -s 0 /var/lib/docker/containers/*/*-json.log"'

## Cool shortcut
alias top_process="ps -eo pcpu,pid,user,args | sort -k 1 -r -n | head -10"
alias mysql="mysql --sigint-ignore --default-character-set=utf8 --auto-vertical-output"
alias http="http --pretty=all"
alias https="http --verify=no"
alias whatsmyip="curl https://ifconfig.co/"
alias whatsmypc="inxi -Fxz"

## Fun
alias meteo="curl wttr.in/Paris?format=v2"

## PHP
alias php_opcode='php -n -d "extension=vld.so" -d "vld.active=1" -d "vld.execute=0"'

## OS
alias huge_page_disable_transparent="hugeadm --thp-never"
alias battery='sudo bash -c "echo deep > /sys/power/mem_sleep"'

## Random

alias protips="code /home/gregoire/Dropbox/doc/CFP/idea/Protips.md"
