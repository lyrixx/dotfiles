---
name: clean
description: Repère les branches et les worktrees du dépôt courant dont la PR est mergée, détruit leur stack Castor, puis supprime worktrees et branches locales.
disable-model-invocation: true
---

# Nettoyer ce qui a été mergé

Lancer ce skill vaut demande explicite de supprimer, dans le dépôt courant, les worktrees et les
branches locales dont la PR est mergée, et de détruire la stack Castor de ces worktrees, pour cette
fois. Il ne touche ni aux branches distantes, ni aux autres dépôts.

`git worktree-drop` (`~/.local/bin`, depuis `bin/` des dotfiles) fait la suppression d'un
worktree : il vérifie qu'il est libre et propre, détruit sa stack Castor si elle est à lui, puis le
supprime. Passe-lui toujours `--ignore-own-session` : le verrou et l'agent herdr de cette session
ne comptent alors pas. Ne refais pas ses vérifications à la main.

1. **Situer la session.** Si `git rev-parse --git-dir` diffère de `--git-common-dir`, la session
   tourne dans un worktree lié : note son chemin.

2. **Faire l'inventaire.** Lecture seule, rien n'est supprimé avant le tableau de l'étape 3.

   - `git fetch --prune`, puis `git worktree list --porcelain` et `git branch --format='%(refname:short) %(objectname)'`.
   - `gh pr list --state all --limit 300 --json number,state,headRefName,headRefOid,url` : un seul
     appel, à croiser avec les branches locales. Avant de conclure qu'une branche n'a pas de PR,
     confirme avec `gh pr list --state all --head <branche>`.

   Le verdict de chaque branche, branche par défaut et HEAD détachées exclues :

   - Pas de PR mergée : `PR ouverte`, `PR fermée` ou `pas de PR`. Une branche sans PR déjà contenue
     dans la branche par défaut ressemble à un worktree tout juste créé : on ne la supprime pas.
   - PR mergée mais `git rev-parse <branche>` différent de `headRefOid` : `commits hors PR`.
   - PR mergée, même SHA, sans worktree : `à nettoyer`.
   - PR mergée, même SHA, dans un worktree lié : `git worktree-drop --dry-run --ignore-own-session
     <chemin>` décide. `0` : `à nettoyer`, avec ce qu'il dit de la stack. `12` : `session active`,
     ou `verrou orphelin` si le processus du verrou n'existe plus. `13` : `modifs locales`. `14` :
     `stack partagée`.

3. **Montrer le tableau.** Branche, worktree, PR, verdict. Aucune ligne `à nettoyer` : dis-le et
   arrête-toi. Les autres lignes sont là pour info, sans question.

4. **Supprimer les worktrees `à nettoyer`.**

   - Worktree de la session (étape 1) : `ExitWorktree` avec `action: "keep"` d'abord (`"remove"`
     laisserait la stack derrière). Si la session n'en sort pas (lancée directement dedans), ne le
     supprime pas sous ses pieds : passe-le et donne-moi la commande à la fin.
   - Puis, depuis le worktree principal, `git worktree-drop --ignore-own-session <chemin>`. Un code
     non nul : garde ce worktree et sa branche, relaie le message du script et continue avec les
     autres. Jamais `git worktree remove --force` ni `git worktree unlock` à la main.

5. **Supprimer les branches locales `à nettoyer`** dont le worktree a disparu. `git branch -D
   <branche>` : le `-d` refuse une branche squashée, la comparaison des SHA de l'étape 2 vaut
   vérification. Branche extraite dans le worktree principal : `git switch <branche par défaut>`
   d'abord, seulement si `git status --porcelain` y est vide. Sinon, garde la branche et dis-le.

6. **Mettre à jour.** Worktree principal sur la branche par défaut : `git merge --ff-only`. Pas
   `git sync` : il supprime aussi les branches dont la branche distante a disparu, y compris
   celles que l'étape 2 a gardées pour des commits hors PR.

7. **Conclure.** Une ligne par élément nettoyé (branche, worktree, stack détruite ou non), puis les
   lignes laissées de côté avec leur verdict, et les commandes à lancer pour celles qui me reviennent.
