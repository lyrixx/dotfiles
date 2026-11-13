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

My Mac, a lighter setup: bash, git and Ghostty, the rest case by case. With
[Homebrew](https://brew.sh) installed:

    git clone https://github.com/lyrixx/dotfiles ~/dev/github.com/lyrixx/dotfiles
    ~/dev/github.com/lyrixx/dotfiles/install-macos.sh

It makes the bash 5 of Homebrew the login shell. `brew upgrade` updates it all.

credit
------

* https://github.com/marcw/vim-config
* https://github.com/willdurand/dotfiles
* https://github.com/smaftoul/dotfiles
* https://github.com/brantb/dotfiles
* https://github.com/everzet/dotfiles
* http://www.bordel-de-nerd.net/2010/08/configurer-son-terminal-bash/ [Fr]
* https://github.com/holman/dotfiles
