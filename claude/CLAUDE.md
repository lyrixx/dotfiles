# Règles globales

## Git

Ne jamais créer de commit ni pousser vers un remote sans que l'utilisateur le demande explicitement. Attendre toujours une instruction explicite ("commite", "pousse", etc.).

## Ne jamais commenter publiquement à ma place sans accord préalable

Ne jamais publier quoi que ce soit de visible publiquement (GitHub ou toute autre plateforme :
commentaire d'issue, réponse à une review, commentaire de PR, édition de la description d'une
PR/issue déjà ouverte, changement de statut comme passer une PR de draft à "ready", etc.) sans
avoir obtenu mon accord explicite au préalable, à chaque fois.

Une autorisation donnée pour une action donnée (ex. « ouvre les PR ») ne vaut pas autorisation
implicite pour les publications suivantes qui en découlent (répondre aux commentaires des
reviewers, changer le statut de la PR, éditer sa description, etc.) : redemander systématiquement,
même si cela semble être une suite logique du travail déjà autorisé.

## Ne jamais signer le contenu généré

Ne jamais ajouter de mention d'attribution à Claude / Claude Code (`Co-Authored-By: Claude`,
« Generated with Claude Code », « Created by Claude »…) dans ce qui est produit : commits, PR,
issues, commentaires, documentation, code… Pour les commits et les PR, c'est aussi coupé à la
source par `attribution` dans `settings.json`.

## Images sur GitHub

`gh` sait uploader des images et des vidéos directement (user-attachments) avec `--attach`, sur
`gh pr edit`, `gh pr comment` et leurs équivalents pour les issues. Dans le body, référencer le
fichier en `![alt](./capture.png)` : `gh` réécrit le lien vers l'asset uploadé. Le texte alternatif
se met après `#` (`--attach './capture.png#Texte alternatif'`). Ne pas passer par un gist, une
branche du repo ou un upload manuel. Ça reste une publication : demander mon accord avant.
