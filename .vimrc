" General
    filetype on
    filetype plugin indent on
    set backspace=indent,eol,start  " make backspace a bit more flexible, http://vim.wikia.com/wiki/Backspace_and_delete_problems
    set iskeyword+=_,@,%,#          " none of these are word dividers ???
    set nobackup
    set nowritebackup               " like set nobackup.
    set nocompatible                " Explicitly get out of vi-compatible mode
    set noerrorbells                " Do not make any noise!
    set vb                          " I said, NO noise
    set wildignore=.svn,CVS,.git,.hg,*.o,*.a,*.class,*.mo,*.la,*.so,*.obj,*.swp,*.jpg,*.png,*.xpm,*.gif,*.pyc,*.pyo,*/cache/**,*/logs/** " Ignore certain files
    syntax on

" UI
    set background=dark
    set cursorline                  " Highlight the current line
    set hlsearch                    " Highlight matches.
    set incsearch                   " Highlight matches as you type.
    set langmenu=en_US.UTF-8
    set list                        " Show special chars
    set number                      " Show line numbers in gutter
    set ruler                       " Always show current position along the bottom
    set scrolloff=8                 " Keep x line for scope while scrolling
    set showcmd                     " Show (partial) command in status line.
    set showmatch                   " Show matching bracket
    set sidescrolloff=8             " same same

" EDITING
    set encoding=UTF-8              " Display UTF-8
    set expandtab                   " We do not want tabs, do we?
    set ff=unix                     " Unix EOL
    set fileencoding=UTF-8          " Speak UTF-8
    set ignorecase                  " case sensitivity is dumb
    set listchars=trail:¤,tab:>-    " only show ther chars
    set nowrap                      " No, I don't want wordwrap
    set shiftround                  " when at 3 spaces, and I hit > ... go to 4, not 5
    set shiftwidth=4
    set smartcase                   " but not where there are different cases
    set tabstop=4

" Extra
    " return at last position
    au BufReadPost * if line("'\"") > 1 && line("'\"") <= line("$") | exe "normal! g'\"" | endif
