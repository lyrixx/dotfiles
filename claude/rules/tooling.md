# Outillage

## Git

Les worktrees se créent dans le projet, sous `.claude/worktrees/<nom>`, sauf si le projet indique
un autre emplacement. Un outil qui les place ailleurs par défaut (ex. `castor worktree:create`) se
configure ou se contourne, il ne décide pas de l'emplacement.

Pour un nouveau dépôt GitHub, configurer `origin` en SSH (`git@github.com:lyrixx/<repo>.git`), même
si un dépôt voisin est en HTTPS : en HTTPS, le push passe par le token `gh`, qui n'a pas le scope
`workflow`, et GitHub refuse tout fichier sous `.github/workflows/`.

## Fichiers : ne jamais utiliser `/tmp`

Ne jamais écrire dans `/tmp`, y compris dans le « scratchpad » de session proposé par
l'environnement (il est sous `/tmp`) : ça ne survit pas à un reboot. Valable pour tous les projets
et toutes les sessions.

- Tout fichier hors du code, intermédiaire ou produit pour moi (copies, requêtes, scripts jetables,
  exports, comptes rendus…), va dans le `.claude/tmp/` du projet courant, à la racine du dépôt.
  Jamais dans `~/.claude/tmp/` quand la session tourne dans un projet.
- `~/.claude/tmp/` seulement quand la session ne tourne dans aucun projet.
- Ces fichiers ne sont pas à commiter (sauf demande explicite) : `.claude/tmp` est dans l'exclude
  global de Git.

## Tâches et CLI

- Les tâches du projet (build, test, deploy, release, sync) vont dans castor.php sous forme de tâches Castor avec namespace, jamais dans des scripts bin/ ad hoc.
- Les tâches doivent marcher sur un clone vierge : vérifier que vendor/ et les sorties de build existent, et les construire s'ils manquent.
- Les CLI interactives (wrangler, gh, etc.) se lancent avec leurs options non interactives.
