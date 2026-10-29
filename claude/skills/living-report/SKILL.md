---
name: living-report
description: Rapport vivant d'un chantier ou d'une semaine (décisions, actions, PR, points de vigilance) écrit en YAML, rendu en HTML par une tâche Castor, puis publié en artefact. À utiliser pour créer, mettre à jour ou relire un rapport de suivi, dès qu'un fichier `.claude/tmp/*.yaml` de rapport existe ou qu'on en démarre un.
---

# Rapport vivant

Le rapport est un fichier YAML, la seule source : on ne modifie jamais le HTML à la main. Une
tâche Castor le rend en HTML à côté du YAML, et c'est ce HTML qu'on publie avec l'outil Artifact,
toujours depuis le même chemin pour garder la même URL.

Le générateur calcule ce qu'on tenait avant à la main : les compteurs (résumé sous le chapeau,
« 3 ouverts sur 27 », « 15/15 décidées », PR par état), le grisé et la case cochée des éléments
faits, l'ordre (les ouverts d'abord, puis les faits, chaque groupe dans l'ordre du fichier), la date
de mise à jour, les espaces insécables, et les liens vers GitHub et entre éléments.

## Construire et publier

```bash
castor --castor-file="${CLAUDE_SKILL_DIR}/castor.php" report:build /chemin/absolu/rapport.yaml
```

Le HTML sort à côté (`rapport.html`) ; `--output` pour un autre chemin. Chemins absolus : Castor
ne tourne pas forcément depuis le dossier courant. Il installe les dépendances
(`castor.composer.json`) au premier lancement. Une clé inconnue ou un mauvais type arrête le build
avec son chemin (`section "vigilance".rows[3]`) : corriger le YAML, jamais contourner.

Castor 1.9.1 minimum, sinon les tâches refusent de tourner. `Class "LivingReport\…" not found`
après une mise à jour de Castor : l'autoload date de l'ancienne version, le régénérer avec
`castor --castor-file="${CLAUDE_SKILL_DIR}/castor.php" composer dump-autoload`.

Puis publier le HTML (outil Artifact, même `file_path` que la fois précédente).

## Mettre à jour

Les changements d'état passent par les commandes, la prose s'édite à la main dans le YAML.

```bash
R="castor --castor-file=${CLAUDE_SKILL_DIR}/castor.php"
$R report:sync-prs  <yaml>                                  # état de chaque PR lu sur GitHub
$R report:done      <yaml> <sélecteur> [--note "Fait: …"]   # grise l'élément, ajoute la note
$R report:note      <yaml> <sélecteur> "Mesuré: …"          # ajoute une note
$R report:history   <yaml> "Tu merges #1234." [--date …]   # complète l'entrée du jour
$R report:find      <yaml> <sélecteur>                      # montre l'élément avant une édition
$R report:open      <yaml>                                  # prochaine étape, attentes, tout ce qui est ouvert
```

- L'état d'un élément et les compteurs se lisent avec `report:open` ou `report:find`, jamais dans
  un extrait du YAML (`grep`, `sed -n`, lecture partielle) : un `done: true` vu hors contexte se
  rattache vite à l'élément voisin, et un élément ouvert n'a souvent pas de ligne `done` du tout.
- « Quoi faire maintenant », « où on en est » : `report:sync-prs` si des PR ont pu bouger, puis
  `report:open`, et répondre à partir de sa sortie.

- Sélecteur : `D14` ou `1a` (un numéro), `#1234` (la carte de la PR), un `id`, sinon un morceau
  du titre ou du texte. Plusieurs éléments correspondent : la commande les liste, sans rien changer.
- Note : `"Étiquette: texte"`, deux-points collés à l'étiquette ; sans étiquette (le « : » français
  a une espace avant), « Fait » pour `done`, « Le <jour> » pour `note`.
- `report:sync-prs` met à jour le `status` des cartes et les puces « Draft #N », « Ouverte #N »,
  « Mergée #N », « Fermée #N » où qu'elles soient, puis liste ce qui a changé, avec la date du
  merge : c'est la liste de la prose à relire (meta de la carte, vigilances, à faire, historique).
  Une carte de PR ne se grise pas avec `done` : elle suit GitHub.
- Chaque commande réécrit le YAML dans sa forme canonique, puis reconstruit le HTML : il ne reste
  qu'à publier. Une édition à la main se termine par `report:build`.
- Forme canonique : `done` et `dropped` juste sous les clés qui nomment l'élément (`id`, `num`,
  `pr`, `title`, `type`, `who`), avant la prose. Écrire dans cet ordre à la main aussi.

À chaque mise à jour :

1. `report:sync-prs` d'abord, puis vérifier le reste du monde extérieur (déploiements, issues).
2. Relire le YAML entier : il est 3 à 4 fois plus court que le HTML, et chaque phrase doit rester
   vraie à la date du jour. Réécrire ce qui a changé, au passé si c'est fini ; un élément qui a
   beaucoup bougé se réécrit en son état actuel, l'historique condensé.
3. Un élément fini ne disparaît jamais : `done` et une note qui dit ce qui s'est passé. Il passe en
   gris, après les ouverts.
4. Publier le HTML.

## Le YAML

```yaml
title: Chantier export des commandes
eyebrow: Boutique · chantier prioritaire          # suivi de « · mis à jour le <date du build> »
github: acme/shop                                 # cible des « #1234 »
key: export-commandes                             # préfixe des cases cochées dans le navigateur ; défaut : nom du fichier
lede: La V1 de l'export…
status:                                           # les trois encadrés sous le chapeau
  done: L'export CSV est en prod.
  next: Paginer la requête (#1230).
  waiting:
    - "**Stockage :** l'accès au bucket, demandé à l'infra."
  more: '#progress'                               # lien « Le détail »
history:                                          # dépliable, « du 20 au 28 octobre » calculé
  - date: 2026-10-28
    text: Déploiement en prod.
aside: Les autres sujets restent suivis dans [Semaine du 20 octobre](https://…).
sections: [...]
```

Chaque section a `id`, `title`, `type`, et en option `intro`, `count`, `tally`.

- `count` : texte à droite du titre, où `{open}`, `{done}` et `{total}` comptent les éléments ;
  `auto` pour une section de cartes de PR (« 1 ouverte · 16 mergées, 1 fermée »).
- `tally: [décision ouverte, décisions ouvertes]` : l'entrée du résumé sous le chapeau, avec le
  nombre d'éléments ouverts (singulier pour 0 et 1).

### `type: checklist` (décisions, actions)

```yaml
- id: decisions
  title: À décider
  type: checklist
  group: decision            # « decision » : cases « Décidé » ; autre chose : « Fait »
  count: '{done}/{total} décidées'
  tally: [décision ouverte, décisions ouvertes]
  items:
    - id: decision-async-export
      num: D14               # « D14 » écrit n'importe où dans le rapport devient un lien vers lui
      title: 'Les gros exports : les passer en tâche de fond ?'
      done: true
      text: |-
        Paragraphes en Markdown.
      options:
        - "**(a) Augmenter le timeout** : écarté le 28."
      reco: (b), (c) par la pagination, (d).
      notes:
        - Le 28: |-
            Tranché par toi : #1230.
```

### `type: cards` (PR, enquêtes)

```yaml
cards:
  - id: branch-async-export
    title: L'export en tâche de fond
    pr: 1234
    done: true               # seulement pour une carte sans status
    status: draft            # local, draft, open, merged, closed, stopped : puce, lien et grisé en découlent
    chips:                   # puces en plus ; sans status, ce sont les seules
      - {warn: 'Accès au bucket'}
    meta:                    # la ligne grise sous le titre, un morceau par élément
      - '`feat/async-export`'
      - worktree `async-export`, depuis `main` à `a1b2c3d4e5`
    text: |-
      - **Messenger :** …
    notes: [...]
    sublots: [...]           # voir les lots
```

### `type: blocks` (le plan)

Une suite de blocs titrés ; chacun peut avoir, dans cet ordre : `text`, `progress` (la ligne
« Fait » verte), `todo_title` et `todo` (`{who: Toi, done: false, text: …}` : puce orange au nom
de la personne, verte « Fait » une fois fait), `cards`, `table` (`columns`, puis `rows` en listes
de cellules). Un `id` sur un bloc en fait une ancre (`more: '#progress'`).

Les cartes de lots ont des `sublots` : `num` (1a, 2b ; sans numéro, la liste n'est pas numérotée),
`status` (puces), `status_text`, `text`, `done`, `dropped`, `notes`.

### `type: vigilance`

```yaml
rows:
  - type: {warn: Données}
    done: false
    text: Les montants exportés… n'ont pas été comparés à la comptabilité.
    notes:
      - Mesuré: Le 28, sur 90 jours…
    status:
      - {info: 'Draft #1234'}
    detail: 'à merger après la pagination de #1230'
```

### Puces et notes

- Puce : `{<ton>: '<texte>'}`, le ton parmi `ok`, `warn`, `crit`, `info`, `muted`. Le premier
  `#123` du texte la rend cliquable vers GitHub ; sinon `href: '#ancre'` ou une URL.
- Note : `{<étiquette>: <texte>}`, par exemple `- Fait: Corrigé par #1218.` ou `- Le 28: …`.

## Écrire le texte

- Markdown CommonMark. `**Label :**` en tête d'un point de liste ; `~~barré~~` ; une ligne
  `{.watch}` ou `{.meta}` juste avant un paragraphe lui donne la classe (texte gris discret).
- `#1234` devient un lien GitHub, `D14` un lien vers la décision ; dans un lien explicite
  (`[commentée le 26](https://…#issuecomment-…)`), rien n'est re-lié.
- Espaces normales partout : les insécables sont ajoutées au rendu (avant `: ; ? ! »`, après `«`,
  `1 459`, `8 h`, `30 %`, `lot 1a`). Pas de `&nbsp;`.
- **Piège YAML : ` #` commence un commentaire** dans une valeur non citée. `text: Mergée #1211`
  devient `Mergée` sans prévenir. Tout texte qui contient ` #` ou `: ` va dans un bloc `|-` (le plus
  sûr pour tout texte de plus d'une ligne), ou entre apostrophes simples (`'Mergée #1211'`, les
  apostrophes du texte doublées).
- Du HTML brut passe tel quel (dernier recours, sans insécables automatiques).

## Mise en page

Le gabarit (`templates/`) reprend le style des rapports précédents : une colonne, chapeau et
résumé, puis les sections dans l'ordre du fichier. L'ordre habituel : le plan ou le tableau
thématique, les enquêtes, « À décider », « À faire de ton côté », branches et PR, points de
vigilance. Pas de section « Sous-agents » ni « État de ton environnement ».
