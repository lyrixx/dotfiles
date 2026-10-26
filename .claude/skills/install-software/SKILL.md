---
name: install-software
description: Installe un logiciel sur le poste et le déclare dans les dotfiles (packages/), pour qu'une nouvelle machine l'ait avec install.sh. Choisit le canal (apt, dépôt de l'éditeur, mise, Flatpak, deb-get) et vérifie clés, noms de fichiers et versions avant d'écrire.
argument-hint: "<logiciel>"
---

# Installer un logiciel et le suivre dans les dotfiles

Logiciel demandé : `$ARGUMENTS`

Tout ce qui est installé sur le poste est déclaré dans `packages/` de ce repo, et `install.sh`
en appelle les scripts. Travaille dans le checkout principal
(`dirname "$(git rev-parse --path-format=absolute --git-common-dir)"`), pas dans un worktree :
`~/.bashrc` et `~/.config/mise/config.toml` sont des liens vers lui, ce qui est installé
correspond donc à ce qui est versionné.

## 1. Choisir le canal

Le premier qui convient, dans cet ordre :

1. **Paquet Ubuntu**, si sa version convient (`apt-cache policy <paquet>`, comparée à la
   dernière version publiée) : liste de `packages/apt.sh`.
2. **Dépôt apt de l'éditeur** : fichier dans `packages/apt/`, appel à `add_repository` et liste
   de `packages/apt.sh`.
3. **Outil en ligne de commande ou langage** : mise, `packages/mise.toml`. Le registre d'abord
   (`mise registry <outil>`), sinon un backend : `cargo:`, `go:`, `npm:`, `pypi:`,
   `github:<owner>/<repo>`. `"latest"`, ou la version majeure là où ça compte (`node = "24"`).
4. **Appli graphique sur Flathub** : `packages/flatpak.sh`. De préférence une appli vérifiée ;
   sinon, le dire.
5. **`.deb` publié sur les releases GitHub, sans dépôt** : deb-get, `packages/deb-get.sh`. Son
   catalogue s'il a le paquet et prend la bonne version, sinon une définition locale dans
   `packages/deb-get/<paquet>`.

Aucun ne convient (`curl | sh`, AppImage, binaire copié à la main) : demande avant d'installer,
en expliquant pourquoi.

## 2. Vérifier avant d'écrire

Fichiers téléchargés dans `~/.claude/tmp/`, supprimés à la fin.

- **Dépôt apt :**
  - L'URL de la clé vient de la doc officielle de l'éditeur. Télécharge la clé et l'`InRelease`
    de la suite : `gpgv --keyring <clé> InRelease` doit répondre « Good signature » (une clé
    armurée passe d'abord par `gpg --dearmor`). Le paquet doit figurer dans l'index `Packages`.
  - Suite : `resolute` si l'éditeur la publie, sinon la sienne (`stable`, `any`, `xenial`…).
  - Nom du fichier : celui des instructions ou du paquet de l'éditeur, pour remplacer un fichier
    déjà là plutôt qu'en ajouter un second. apt refuse deux entrées pour le même dépôt avec des
    `Signed-By` différents, et `apt-get update` échoue.
  - Regarde si le paquet réécrit sa source lui-même :
    `grep -lE 'sources.list|trusted.gpg' /var/lib/dpkg/info/<paquet>.postinst /etc/cron.daily/*`.
    Si oui, garde son nom et son format (`.list` pour dropbox, slack et spotify), sinon deb822
    (`.sources`).
  - La clé va dans `/etc/apt/keyrings/`, en `.asc` si elle est armurée, en `.gpg` si elle est
    binaire : apt choisit le format d'après l'extension. Exception : un paquet qui met lui-même
    ses nouvelles clés dans `/etc/apt/trusted.gpg.d` (Slack, Spotify). Sa source n'a alors pas
    de `signed-by`, et la clé de départ va dans `trusted.gpg.d`.
- **deb-get :**
  - Le nom exact de l'asset : `gh api repos/<owner>/<repo>/releases/latest --jq '.assets[].name'`.
    Si l'API redirige, le dépôt a déménagé : prends le nouveau nom.
  - Le catalogue : `https://raw.githubusercontent.com/wimpysworld/deb-get/main/01-main/packages/<paquet>`.
    Une définition locale suit le modèle de celles de `packages/deb-get/` (`DEFVER`, `URL`,
    `VERSION_PUBLISHED`, `PRETTY_NAME`, `WEBSITE`, `SUMMARY`).
  - Simule l'extraction de `URL` et de `VERSION_PUBLISHED` sur le JSON de la release.
  - Lis `Package` et `Version` du `.deb` sans tout télécharger : `curl -fsSL -r 0-200000`, puis
    `ar p <deb> control.tar.zst | zstd -dc | tar -xO ./control` (ou `.xz`, `.gz`). `Package`
    doit être le nom de la définition, et `VERSION_PUBLISHED` ne doit pas dépasser `Version`
    (au sens de `dpkg --compare-versions`) : sinon deb-get le réinstalle à chaque `upgrade`.
- **Flathub :** l'identifiant, et son statut avec
  `curl -fsS https://flathub.org/api/v2/verification/<id>/status`.
- **mise :** le nom dans le registre, et pour `github:` l'asset (`asset_pattern`) quand la
  release en publie plusieurs.

## 3. Écrire

- Un logiciel par ligne, jamais plusieurs sur une même ligne.
- `apt.sh` : le tableau `packages`, groupé par thème, trié par ordre alphabétique dans chaque
  groupe. Chaque paquet a sa description en bout de ligne : ce que c'est, et qui en a besoin
  quand ce n'est pas évident (« vhs records with it »). Aligne la description sur celles de son
  groupe. Un paquet qui ne rentre dans aucun groupe : demande plutôt que d'en créer un pour lui.

  ```bash
  # Search in the terminal
  bat             # cat with syntax highlighting: batcat, linked as bat by install.sh
  fd-find         # Simpler, faster find: fdfind, aliased to fd
  ```

- Les autres listes (`deb-get.sh`, `flatpak.sh`), triées, avec `\` en fin de ligne :

  ```bash
  deb-get install \
      appimagelauncher \
      balena-etcher
  ```
- Les scripts restent idempotents : relancer `install.sh` ne casse rien et n'installe rien en
  double.
- Rien ne se met à jour tout seul : ni timer, ni cron, ni mise à jour automatique. Les mises à
  jour passent par `bin/maj`, lancé à la main : un nouveau canal doit y être ajouté.
- Un nouveau script de `packages/` est appelé par la fonction `install_packages` de
  `install.sh`.
- `packages/` ne contient que les installeurs. La config d'un logiciel va dans un dossier à son
  nom à la racine du repo (`dnsmasq/`, `php/`), posée par une fonction `setup_<logiciel>` de
  `install.sh`, appelée par `main` (un groupe aussi : `setup_docker`). Un fichier lu par un
  démon passe par `install_system_config` : copié, pas lié, car `$HOME` est en 750 et un démon
  qui ne tourne pas en root ne lit pas le repo ; le service ne redémarre que si le fichier a
  changé.
- Un logiciel qui remplace une installation manuelle : la suppression de l'ancienne va dans
  `packages/migrate.sh`, que l'utilisateur relit et lance lui-même.
- Retirer un logiciel : l'enlever de sa liste. Ne le désinstalle qu'avec son accord.

## 4. Installer

Lance seulement le script du canal, depuis le repo : `packages/apt.sh`, `packages/deb-get.sh`,
`packages/flatpak.sh` ou `packages/mise.sh`. Les trois premiers appellent `sudo` : si
`sudo -n true` échoue, donne la commande à l'utilisateur, avec le préfixe `!`. Vérifie ensuite que le logiciel répond (`--version`, `dpkg -s`, `flatpak info`,
`mise which`).

## 5. Rendre compte

Le canal choisi et pourquoi, ce qui a été vérifié, le diff. Ne commite pas sans demande
explicite : propose un message au format du repo, `[packages] add <logiciel>`.
