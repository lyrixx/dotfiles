---
name: install-software
description: Installs a piece of software on this machine and declares it in the dotfiles (packages/), so that a new machine gets it from install.sh. Picks the channel (apt, vendor repository, mise, Flatpak, deb-get) and checks keys, file names and versions before writing anything.
argument-hint: "<software>"
---

# Install a piece of software and track it in the dotfiles

Requested software: `$ARGUMENTS`

Everything installed on this machine is declared in `packages/` of this repository, and
`install.sh` runs its scripts. Work in the main checkout
(`dirname "$(git rev-parse --path-format=absolute --git-common-dir)"`), not in a worktree:
`~/.bashrc` and `~/.config/mise/config.toml` link to it, so what is installed matches what is
versioned.

## 1. Pick the channel

The first one that fits, in this order:

1. **Ubuntu package**, if its version is recent enough (`apt-cache policy <package>`, against
   the latest release): the list of `packages/apt.sh`.
2. **Vendor apt repository**: a file in `packages/apt/`, an `add_repository` call, and the list
   of `packages/apt.sh`.
3. **Command-line tool or language**: mise, `packages/mise.toml`. The registry first
   (`mise registry <tool>`), then a backend: `cargo:`, `go:`, `npm:`, `pypi:`,
   `github:<owner>/<repo>`. `"latest"`, or the major version where it matters (`node = "24"`).
4. **Desktop application on Flathub**: `packages/flatpak.sh`. Preferably a verified app;
   otherwise, say so.
5. **`.deb` published on GitHub releases, without a repository**: deb-get,
   `packages/deb-get.sh`. Its catalog if it has the package and picks the right version,
   otherwise a local definition in `packages/deb-get/<package>`.

None fits (`curl | sh`, AppImage, binary copied by hand): ask before installing, and explain
why.

## 2. Check before writing

Download files to `~/.claude/tmp/`, and delete them at the end.

- **apt repository:**
  - The key URL comes from the vendor's official documentation. Download the key and the
    `InRelease` of the suite: `gpgv --keyring <key> InRelease` must answer "Good signature"
    (dearmor an armored key with `gpg --dearmor` first). The package must be in the
    `Packages` index.
  - Suite: `resolute` if the vendor publishes it, otherwise its own (`stable`, `any`,
    `xenial`…).
  - File name: the one of the vendor's instructions or package, so it replaces a file already
    there instead of adding a second one. apt refuses two entries for the same repository with
    different `Signed-By`, and `apt-get update` fails.
  - Check whether the package rewrites its source itself:
    `grep -lE 'sources.list|trusted.gpg' /var/lib/dpkg/info/<package>.postinst /etc/cron.daily/*`.
    If so, keep its name and format (`.list` for dropbox, slack and spotify), otherwise deb822
    (`.sources`).
  - The key goes to `/etc/apt/keyrings/`, as `.asc` if armored, `.gpg` if binary: apt picks the
    format from the extension. Exception: a package that puts its new keys in
    `/etc/apt/trusted.gpg.d` itself (Slack, Spotify). Its source then has no `signed-by`, and
    the initial key goes to `trusted.gpg.d`.
- **deb-get:**
  - The exact asset name: `gh api repos/<owner>/<repo>/releases/latest --jq '.assets[].name'`.
    If the API redirects, the repository moved: use the new name.
  - The catalog: `https://raw.githubusercontent.com/wimpysworld/deb-get/main/01-main/packages/<package>`.
    A local definition follows those of `packages/deb-get/` (`DEFVER`, `URL`,
    `VERSION_PUBLISHED`, `PRETTY_NAME`, `WEBSITE`, `SUMMARY`).
  - Simulate the extraction of `URL` and `VERSION_PUBLISHED` on the JSON of the release.
  - Read `Package` and `Version` of the `.deb` without downloading all of it:
    `curl -fsSL -r 0-200000`, then `ar p <deb> control.tar.zst | zstd -dc | tar -xO ./control`
    (or `.xz`, `.gz`). `Package` must be the name of the definition, and `VERSION_PUBLISHED`
    must not be greater than `Version` (as `dpkg --compare-versions` sees it): otherwise
    deb-get reinstalls it on every `upgrade`.
- **Flathub:** the app ID, and its status with
  `curl -fsS https://flathub.org/api/v2/verification/<id>/status`.
- **mise:** the name in the registry, and for `github:` the asset (`asset_pattern`) when the
  release ships several.

## 3. Write

- One piece of software per line, never several on the same line.
- `apt.sh`: the `packages` array, grouped by theme, sorted alphabetically within each group.
  Each package has its description at the end of the line: what it is, and what needs it when
  that isn't obvious ("vhs records with it"). Align the description with the others of its
  group. A package that fits no group: ask instead of creating a group for it.

  ```bash
  # Search in the terminal
  bat             # cat with syntax highlighting: batcat, linked as bat by install.sh
  fd-find         # Simpler, faster find: fdfind, aliased to fd
  ```

- The other lists (`deb-get.sh`, `flatpak.sh`), sorted, with `\` at the end of each line:

  ```bash
  deb-get install \
      appimagelauncher \
      balena-etcher
  ```

- The scripts stay idempotent: running `install.sh` again breaks nothing and installs nothing
  twice.
- Nothing updates on its own: no timer, no cron, no automatic update. Updates go through
  `bin/maj`, run by hand: a new channel must be added to it.
- A new script of `packages/` is called by the `install_packages` function of `install.sh`.
- `packages/` only holds the installers. The config of a piece of software goes to a folder
  named after it at the root of the repository (`dnsmasq/`, `php/`), set up by a
  `setup_<software>` function of `install.sh`, called by `main` (a group too: `setup_docker`).
  A file read by a daemon goes through `install_system_config`: copied, not linked, because
  `$HOME` is 750 and a daemon that doesn't run as root can't read the repository; the service
  only restarts when the file changed.
- Software that replaces a manual install: give the user the commands that remove the old
  one, to review and run themselves.
- Removing a piece of software: take it out of its list. Only uninstall it with the user's
  agreement.

## 4. Install

Run only the script of the channel, from the repository: `packages/apt.sh`,
`packages/deb-get.sh`, `packages/flatpak.sh` or `packages/mise.sh`. The first three call
`sudo`: if `sudo -n true` fails, give the command to the user, with the `!` prefix. Then check
that the software answers (`--version`, `dpkg -s`, `flatpak info`, `mise which`).

## 5. Report

The channel picked and why, what was checked, the diff. Don't commit without an explicit
request: suggest a message in the format of the repository, `[packages] add <software>`.
