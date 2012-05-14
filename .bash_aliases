alias sudo="sudo "

alias ls="ls --color"
alias ll="ls -lh"
alias lla="ll -a"
alias df="df -h"
alias du="du -h"

alias grep='grep -n --exclude-dir ".svn" --exclude tagsi --color'

alias agi="apt-get install"
alias agua="apt-get update"
alias agup="apt-get upgrade"
alias aguap="apt-get update && apt-get upgrade"
alias adel=" apt-get remove"
alias asearch="apt-cache search"

alias untar="tar xvf"
alias ungz="gunzip"
alias unzip2="bzip2 -d"
alias untargz="tar zxvf"
alias untarbz1="tar jxvf"
alias ungz2="bunzip2 -k"

alias sshs1="ssh lyrixx@lyrixx.info"

alias apache_restart="/etc/init.d/apache2 restart"
alias mysql_restart="/etc/init.d/mysql restart"
alias nginx_restart="/etc/init.d/nginx restart"
alias varnish_restart="/etc/init.d/varnish restart"
alias phpfpm_restart="/etc/init.d/php5-fpm restart"

alias go_vhost="cd /etc/apache2/vhosts.d"
alias go_php="cd /etc/php5"
alias go_phpConf="cd /etc/php5/conf.d"
alias go_log="cd /var/log"
alias go_logApache="cd /var/log/apache2"
alias go_logNginx="cd /var/log/nginx"
alias go_logMunin="cd /var/log/munin"
alias go_logVarnish="cd /var/log/varnish"

alias svnst="svn status --ignore-externals"
alias svnup="svn up --ignore-externals"

alias ctags='ctags -f tags -h ".php" -R --exclude=".svn" --exclude="*.yml.php" --totals=yes --tag-relative=yes --fields=+afkst --PHP-kinds=+cf'

#alias toLC="find . -depth -exec rename 's/(.*)\/([^\/]*)/$1\/\L$2/' {} \;"

alias subl="/usr/bin/sublime-text-2 . > /dev/null 2>&1 &"

alias top_process="ps -eo pcpu,pid,user,args | sort -k 1 -r | head -10"

alias mysql="mysql --sigint-ignore"
