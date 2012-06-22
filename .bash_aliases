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

alias grep='grep -n --exclude-dir ".svn" --exclude-dir ".git" --exclude tagsi --color=tty'
alias sed="sed --follow-symlinks"

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
mktar() { tar cvf  "${1%%/}.tar"     "${1%%/}/"; }
mktgz() { tar cvzf "${1%%/}.tar.gz"  "${1%%/}/"; }
mktbz() { tar cvjf "${1%%/}.tar.bz2" "${1%%/}/"; }

## Services
alias apache_restart="/etc/init.d/apache2 restart"
alias mysql_restart="/etc/init.d/mysql restart"
alias nginx_restart="/etc/init.d/nginx restart"
alias varnish_restart="/etc/init.d/varnish restart"
alias phpfpm_restart="/etc/init.d/php5-fpm restart"

## Jump to a dir
alias go_vhost="cd /etc/apache2/vhosts.d"
alias go_ngninx="cd /etc/nginx/"
alias go_php="cd /etc/php5"
alias go_phpConf="cd /etc/php5/conf.d"
alias go_log="cd /var/log"
alias go_logApache="cd /var/log/apache2"
alias go_logNginx="cd /var/log/nginx"
alias go_logMunin="cd /var/log/munin"
alias go_logVarnish="cd /var/log/varnish"

## Ssh shortchut
alias sshs1="ssh lyrixx@lyrixx.info"

## SVN
alias svnst="svn status --ignore-externals"
alias svnup="svn up --ignore-externals"

## Ctags
alias ctags='ctags -f tags -h ".php" -R --exclude=".svn" --exclude="*.yml.php" --totals=yes --tag-relative=yes --fields=+afkst --PHP-kinds=+cf'

## Cool shortcut
#alias toLC="find . -depth -exec rename 's/(.*)\/([^\/]*)/$1\/\L$2/' {} \;" # does not work very well
alias subl="/usr/bin/sublime-text-2 . > /dev/null 2>&1 &"
alias top_process="ps -eo pcpu,pid,user,args | sort -k 1 -r | head -10"
alias mysql="mysql --sigint-ignore"
alias whatsmyip="curl -s checkip.dyndns.org|sed -e 's/.*Current IP Address: //' -e 's/<.*$//'"
alias serve_this="python -m SimpleHTTPServer 8111" # Serveur python sur le port 8111
alias reload_bash="source ~/.bashrc" # recharger le ~/.bashrc

