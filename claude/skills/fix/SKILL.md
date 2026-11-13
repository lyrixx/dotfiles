---
name: fix
description: Démarre le correctif d'une issue GitHub dans un worktree dédié, de la lecture de l'issue jusqu'au correctif testé.
argument-hint: "<numéro ou URL d'issue>"
disable-model-invocation: true
---

# Corriger une issue dans un worktree

Lancer ce skill vaut demande explicite de travailler dans un worktree, pour cette fois, même dans un
dépôt où la règle est de ne pas en créer. Il ne couvre ni commit, ni push, ni PR, ni commentaire sur
l'issue : la livraison passe par `/ship`.

Issue visée : $ARGUMENTS

Sans argument, demande-moi l'issue.

1. **Lire l'issue.** `gh issue view <issue> --comments --json number,title,body,state,url,comments,closedByPullRequestsReferences`.

   - URL d'un autre dépôt que le dépôt courant (`gh repo view --json nameWithOwner`) : demande-moi si
     le correctif se fait bien ici.
   - Issue fermée : dis-le et arrête-toi.
   - Une PR ouverte y est déjà liée : donne son lien et arrête-toi.
   - Les commentaires comptent autant que la description : la cause ou la décision y est souvent.

2. **Créer le worktree.** Session déjà dans un worktree (`git rev-parse --git-dir` diffère de
   `--git-common-dir`) : dis-le et arrête-toi.

   Sinon, `EnterWorktree` avec `name: "fix-<numéro>-<slug>"`, le slug en deux ou trois mots tirés du
   titre, par exemple `fix-171-empty-diff`. Si la branche créée porte un autre nom
   (`git branch --show-current`), renomme-la : `git branch -m fix-<numéro>-<slug>`.

3. **Préparer le worktree.** Les fichiers ignorés n'ont pas suivi (`vendor/`, `node_modules/`,
   `.env.local`…). Installe ce qu'il faut pour lancer les tests, par la tâche du projet s'il en a une.
   Un fichier de configuration locale manquant : demande-moi avant de le copier depuis le worktree
   principal.

4. **Reproduire.** Par un test qui échoue quand c'est faisable, sinon par la commande ou la requête
   décrite dans l'issue. Impossible à reproduire : dis ce que tu as essayé et arrête-toi, sans
   corriger à l'aveugle.

5. **Annoncer, puis corriger.** Donne la cause et le correctif prévu en quelques lignes, puis
   corrige. Attends ma réponse seulement si plusieurs approches se valent ou si l'issue est ambiguë.
   Corrige la cause, pas le symptôme, et rien d'autre que ce que l'issue demande.

6. **Rendre compte.** Le chemin du worktree, la branche, la cause, ce qui a changé et le résultat
   des tests. Rappelle que `/ship` livre et que `/land` nettoiera le worktree.
