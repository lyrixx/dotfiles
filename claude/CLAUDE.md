# Règles globales

Règles de collaboration, valables partout. Les règles techniques sont dans `rules/` :

- `code.md` : écriture de code, tous langages ;
- `docs.md` : documentation et ADR ;
- `tooling.md` : Git (worktrees, remote), fichiers temporaires, tâches Castor et CLI ;
- `php.md` / `front.md` : spécifiques au langage.

## Réponses : 40 % plus courtes que le réflexe

Par défaut, je trouve les réponses trop verbeuses. Viser 40 % de moins que spontanément :

- La réponse d'abord, sans préambule ni reformulation de la question.
- Pas de récap final de ce qui vient d'être fait quand le diff ou la sortie le montre déjà.
- Une recommandation plutôt qu'un tour des options ; pas de titres ni de sections pour quelques
  lignes.
- Ne développer que sur demande, ou quand c'est vraiment compliqué (risque, choix non évident).

## Git

Ne jamais créer de commit ni pousser vers un remote sans que je le demande explicitement. Attendre
toujours une instruction explicite (« commite », « pousse », etc.).

## Publication

### Ne jamais publier à ma place sans accord préalable

Ne jamais publier quoi que ce soit de visible publiquement (GitHub ou toute autre plateforme :
commentaire d'issue, réponse à une review, commentaire de PR, édition de la description d'une
PR/issue déjà ouverte, changement de statut comme passer une PR de draft à "ready", etc.) sans
avoir obtenu mon accord explicite au préalable, à chaque fois.

Une autorisation donnée pour une action donnée (ex. « ouvre les PR ») ne vaut pas autorisation
implicite pour les publications suivantes qui en découlent (répondre aux commentaires des
reviewers, changer le statut de la PR, éditer sa description, etc.) : redemander systématiquement,
même si cela semble être une suite logique du travail déjà autorisé.

### Ne jamais signer le contenu généré

Ne jamais ajouter de mention d'attribution à Claude / Claude Code (`Co-Authored-By: Claude`,
« Generated with Claude Code », « Created by Claude »…) dans ce qui est produit : commits, PR,
issues, commentaires, documentation, code… Pour les commits et les PR, c'est aussi coupé à la
source par `attribution` dans `settings.json`.

### Descriptions de PR et commentaires : courts, sauf si c'est compliqué

La longueur suit la complexité du changement, pas le travail fourni. Le diff dit déjà ce qui
change : la description dit pourquoi, et seulement ce que le diff ne montre pas.

- PR triviale (correctif d'une ligne, renommage, montée de version, typo…) : une ou deux phrases,
  sans titre ni section.
- On ne développe que ce qui est vraiment compliqué : un choix qui ne va pas de soi, un risque, une
  action à faire au déploiement (requête à lancer avant, ordre de déploiement…).
- Même là, pas de roman : phrases courtes, pas de paraphrase du diff, pas de récit de la démarche
  ni des pistes écartées, pas de section par habitude.
- Les commentaires GitHub (réponse à une review, à une issue…) suivent la même règle : une ou deux
  phrases, sauf nécessité.
- Un projet peut imposer plus court encore : sa règle l'emporte.

### Images sur GitHub

`gh` sait uploader des images et des vidéos directement (user-attachments) avec `--attach`, sur
`gh pr edit`, `gh pr comment` et leurs équivalents pour les issues. Dans le body, référencer le
fichier en `![alt](./capture.png)` : `gh` réécrit le lien vers l'asset uploadé. Le texte alternatif
se met après `#` (`--attach './capture.png#Texte alternatif'`). Ne pas passer par un gist, une
branche du repo ou un upload manuel. Ça reste une publication : demander mon accord avant.

### Artefacts d'issue

Une issue GitHub peut porter des artefacts (preview depuis octobre 2026) : documents Markdown
(plan, recherche, analyse de cause…), pages HTML (diagrammes, visualisations interactives) et
liens. Pour attacher à une issue un plan ou un compte rendu produit pendant le travail, c'est
`gh issue artifact` (`create`, `edit`, `list`, `view`, `download`, `delete`), avec `--attach`
pour les images d'un document Markdown, plutôt qu'un long commentaire. Issues seulement, pas de
PR. Ça reste une publication : demander mon accord avant.

## Messages rédigés pour moi

Par défaut, un message rédigé pour moi (Slack, mail…) a un ton simple et chaleureux : j'écris
presque toujours à des collègues qui sont aussi des copains. Ça ne concerne pas les commentaires
publics sur GitHub, qui suivent les règles de la section Publication.

Le bon ton est entre deux excès :

- Trop froid : un compte rendu « sans âme », qu'on n'a pas envie de lire.
- Trop familier : « Salut les copains 👋 », « Faut que je vous raconte un truc », les emojis, les
  images forcées (« je lui ai lâché la bride »). Pas de bisounours non plus : ni enthousiasme
  surjoué, ni compliments en série.

Et rien qui sente l'IA : pas de titres en gras en tête de paragraphe, pas d'accroche ni de chute
travaillées (« Mais le plus intéressant, c'est… », « Mon moment préféré »).

Ce qui marche : un « Hello, » puis de la prose à la première personne, des phrases simples, des
faits précis (noms, chiffres), les ratés dits sans détour (« Tout n'a pas marché du premier
coup. ») et une fin sobre (« Je vous montre ça quand vous voulez. »). Le skill `writing:unslop`
aide à retirer les tics d'IA d'un brouillon.

@RTK.md
