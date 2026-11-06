---
name: land
description: Merge une PR quand elle est verte, puis nettoie la branche et le worktree et met à jour la branche par défaut.
argument-hint: "[numéro ou URL de PR]"
disable-model-invocation: true
---

# Merger une PR et nettoyer

Lancer ce skill vaut demande explicite de merger cette PR et de nettoyer ce qu'elle laisse, pour
cette fois. Il ne couvre ni le passage en ready, ni un rebase, ni une autre PR.

PR visée : $ARGUMENTS (à défaut, celle de la branche courante)

1. **Regarder la PR.** `gh pr view <pr> --json number,url,state,isDraft,mergeable,headRefName,headRefOid,baseRefName,isCrossRepository`.

   - Déjà mergée : passe à l'étape 4.
   - Fermée : dis-le et arrête-toi.
   - En draft : arrête-toi, le passage en ready se demande à part.
   - En conflit : arrête-toi. Le rebase réécrit l'historique de la branche, c'est à moi de décider.

2. **Choisir comment merger.** `gh api repos/{owner}/{repo} --jq '{allow_auto_merge, allow_merge_commit, allow_rebase_merge, allow_squash_merge, delete_branch_on_merge}'`.

   Pour la méthode, suis l'historique récent de la branche de base (`git log --first-parent`).
   Sans indication claire, `--squash` s'il est permis.

3. **Merger quand c'est vert.** `gh pr checks <pr> --required` liste les checks requis.

   - `allow_auto_merge` activé et au moins un check requis : `gh pr merge <pr> --auto <méthode>`, puis
     attends en arrière-plan que la PR passe à `MERGED`.
   - Sinon : `gh pr checks <pr> --watch` en arrière-plan, puis `gh pr merge <pr> <méthode>` une fois
     tout vert. Sans check requis, `--auto` merge tout de suite, sans attendre la CI.

   CI rouge : lis `gh run view --log-failed`, donne la cause en deux ou trois phrases et arrête-toi.
   Merge refusé (review manquante…) : rapporte le message de `gh` et arrête-toi. Jamais `--admin`, et
   pas de `--delete-branch` : l'étape suivante s'occupe de la branche.

4. **Nettoyer.** Uniquement quand `state` vaut `MERGED`, et en regardant avant de supprimer.

   - Compare `git rev-parse <branche>` à `headRefOid`. S'ils diffèrent, des commits locaux ne sont
     pas dans la PR : arrête-toi et demande.
   - Worktree de la branche (`git worktree list`) : s'il a des modifications, arrête-toi et demande.
     Sinon, si la session tourne dedans et qu'elle l'a créé, appelle `ExitWorktree` avec
     `action: "remove"`. Sinon, sors-en et lance `git worktree remove <chemin>`, sans `--force`.
   - Branche locale : `git branch -D <branche>`, le `-d` refuse une branche squashée. La comparaison
     des SHA ci-dessus vaut vérification.
   - Branche distante : seulement si `delete_branch_on_merge` est faux et que `isCrossRepository`
     est faux, `git push origin --delete <branche>`.
   - Worktree principal, s'il est sur la branche par défaut : `git sync`. Sinon, `git fetch`.

5. **Conclure.** `gh pr list --base <branche>` : s'il reste des PR empilées sur cette branche,
   signale-les sans les rebaser. Termine par une ligne : PR mergée et ce qui a été nettoyé.
