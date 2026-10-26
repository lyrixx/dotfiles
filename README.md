Dotfiles
========

My Ubuntu laptop, from a fresh install:

    sudo apt install git
    git clone https://github.com/lyrixx/dotfiles ~/dev/github.com/lyrixx/dotfiles
    ~/dev/github.com/lyrixx/dotfiles/install.sh

Run `install.sh` again after any change, and `maj` to update everything: nothing
updates on its own.

* `packages/`: what gets installed (apt, deb-get, Flatpak, mise)
* `dnsmasq/`, `php/`, `claude/`…: the config of each software
* `bin/`: commands linked into `~/.local/bin`

credit
------

* https://github.com/marcw/vim-config
* https://github.com/willdurand/dotfiles
* https://github.com/smaftoul/dotfiles
* https://github.com/brantb/dotfiles
* https://github.com/everzet/dotfiles
* http://www.bordel-de-nerd.net/2010/08/configurer-son-terminal-bash/ [Fr]
* https://github.com/holman/dotfiles
