# bash completion for the Claude Code CLI (`claude`)
#
# Options, subcommands and option choices are read from `claude [subcommand...] --help`
# and cached per installed version, so the completion follows CLI upgrades on its own.
# Cache: ${XDG_CACHE_HOME:-~/.cache}/claude-bash-completion/

# Prints the parsed help of a command path, one line per entry:
#   cmd <name>                    a subcommand (aliases are separate entries)
#   opt <name> <arg> <choices...> an option; <arg> is "-" when it takes no value
_claude_help() {
    local bin version cache_dir cache_file
    bin=$(readlink -f "$(type -P claude)" 2>/dev/null) || return
    version=${bin##*/}
    cache_dir="${XDG_CACHE_HOME:-$HOME/.cache}/claude-bash-completion/$version"
    cache_file="$cache_dir/${*:-_root}"
    cache_file=${cache_file// /_}

    if [[ ! -s $cache_file ]]; then
        # a new version: the caches of the previous ones are of no more use
        [[ -d $cache_dir ]] || rm -rf "${cache_dir%/*}"
        mkdir -p "$cache_dir" || return
        claude "$@" --help 2>/dev/null | awk '
            function flush(    names, n, i, arg, choices, c, negatable) {
                if (entry == "") return
                # "--[no-]chrome" stands for --chrome and --no-chrome
                negatable = gsub(/\[no-\]/, "", head)
                arg = "-"
                if (match(head, /[<\[][^>\]]*[>\]]/)) {
                    arg = substr(head, RSTART + 1, RLENGTH - 2)
                    gsub(/[=<>]/, "", arg)  # "--debug[=<filter>]"
                }
                choices = ""
                if (match(entry, /\(choices: [^)]*\)/)) {
                    c = substr(entry, RSTART + 10, RLENGTH - 11)
                    sub(/, (default|preset):.*/, "", c)
                    gsub(/[",]/, "", c)
                    choices = c
                } else if (arg != "-" && match(entry, /\([a-zA-Z]+(, [a-zA-Z]+)+\)/)) {
                    # values listed without "choices:", e.g. --effort "(low, medium, high)"
                    c = substr(entry, RSTART + 1, RLENGTH - 2)
                    gsub(/,/, "", c)
                    choices = c
                }
                n = split(head, names, /[ ,]+/)
                for (i = 1; i <= n; i++) {
                    if (names[i] !~ /^-/) continue
                    sub(/[\[=<].*/, "", names[i])
                    print "opt", names[i], (arg == "" ? "-" : arg), choices
                    if (negatable && names[i] ~ /^--/) print "opt", "--no-" substr(names[i], 3), "-"
                }
                entry = ""
            }
            /^(Options:|OPTIONS)$/ { section = "opt"; next }
            /^Commands:/ { flush(); section = "cmd"; next }
            /^[A-Z]/     { flush(); section = ""; next }
            # the option names and their argument: the first line, before the description
            # (which starts on the next line when the names are long)
            section == "opt" && /^  -/ { flush(); entry = $0; sub(/^ +/, "", entry); head = entry; sub(/   +.*/, "", head); next }
            section == "opt" && /^   / { if (entry != "") { line = $0; sub(/^ +/, " ", line); entry = entry line }; next }
            section == "cmd" && /^  [a-z][a-z0-9-]*(\|[a-z0-9-]+)*( |$)/ {
                n = split($1, names, "|")
                for (i = 1; i <= n; i++) print "cmd", names[i]
            }
            END { flush() }
        ' >"$cache_file.$$" || return
        # commands hidden from `claude --help` but meant for users
        [[ $# -eq 0 ]] && printf 'cmd %s\n' remote-control rc >>"$cache_file.$$"
        mv "$cache_file.$$" "$cache_file"
    fi

    cat "$cache_file" 2>/dev/null
}

_claude() {
    local cur prev words cword
    _init_completion -n = || return

    # Walk the words typed so far to find the subcommand path (e.g. "mcp add")
    local path=() help i word
    help=$(_claude_help)
    for ((i = 1; i < cword; i++)); do
        word=${words[i]}
        [[ $word == -* ]] && continue
        if grep -qx "cmd $word" <<<"$help"; then
            path+=("$word")
            help=$(_claude_help "${path[@]}")
        fi
    done

    # --option=value
    if [[ $cur == --*=* ]]; then
        prev=${cur%%=*}
        cur=${cur#*=}
    fi

    # Value of the previous option
    if [[ $prev == -* ]]; then
        local spec arg choices
        spec=$(awk -v o="$prev" '$1 == "opt" && $2 == o { $1 = $2 = ""; print; exit }' <<<"$help")
        if [[ -n $spec ]]; then
            read -r arg choices <<<"$spec"
            if [[ -n $choices ]]; then
                COMPREPLY=($(compgen -W "$choices" -- "$cur"))
                return
            fi
            case $arg in
                -) ;;
                *dir*) _filedir -d; return ;;
                *path* | *file*) _filedir; return ;;
                *) return ;;
            esac
        fi
    fi

    if [[ $cur == -* ]]; then
        COMPREPLY=($(compgen -W "$(awk '$1 == "opt" { print $2 }' <<<"$help")" -- "$cur"))
        [[ ${#COMPREPLY[@]} -eq 1 && ${COMPREPLY[0]} == *= ]] && compopt -o nospace
        return
    fi

    local cmds
    cmds=$(awk '$1 == "cmd" { print $2 }' <<<"$help")
    if [[ -n $cmds ]]; then
        COMPREPLY=($(compgen -W "$cmds" -- "$cur"))
        return
    fi

    _filedir
}

complete -F _claude claude
