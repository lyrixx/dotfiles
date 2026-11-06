---
name: ship
description: Commite le travail en cours, le pousse, ouvre une PR en draft et surveille la CI jusqu'au résultat.
argument-hint: "[précision pour le commit ou la PR]"
disable-model-invocation: true
---

# Livrer le travail en cours

Lancer ce skill vaut demande explicite de commiter, de pousser et d'ouvrir une PR en draft, pour
cette fois. Il ne couvre rien d'autre : ni le passage en ready, ni l'édition d'une PR déjà ouverte,
ni un commentaire. Ces actions restent à demander.

Précision de l'utilisateur : $ARGUMENTS

1. **Regarder ce qui part.** `git status` et `git diff`, puis `git log` pour le style de commit du
   projet. Un fichier sans rapport avec le travail ou un secret : arrête-toi et demande. Rien à
   commiter ni à pousser : dis-le et arrête-toi.

2. **Se mettre sur une branche.** Sur la branche par défaut, crée une branche au nom court et
   parlant.

3. **Commiter.** Suis le style du projet, sans mention d'attribution. Jamais `--no-verify` : si un
   hook de commit échoue, corrige la cause ou arrête-toi.

4. **Pousser.** `git push`, jamais de force push : si le push est rejeté, arrête-toi et demande.
   Sans droit d'écriture sur `origin` (`gh repo view --json viewerPermission`), pousse sur le remote
   de mon fork. S'il n'y en a pas, demande.

5. **Ouvrir la PR.** Si `gh pr view` en trouve déjà une pour cette branche, n'en ouvre pas d'autre
   et ne l'édite pas. Signale seulement si le commit rend sa description fausse.

   Sinon, `gh pr create --draft`, avec le modèle du projet s'il en a un. La description suit la
   section « Publication » de mon `CLAUDE.md` : courte, le pourquoi plutôt que le quoi.

6. **Surveiller la CI.** `gh pr checks --watch` en arrière-plan, pour rendre la main pendant
   l'attente. `no checks reported` juste après la création ne prouve rien : réessaie une minute plus
   tard avant de conclure que le projet n'a pas de CI.

   - Verte : donne le lien de la PR, en une ligne.
   - Rouge : lis `gh run view --log-failed` et donne la cause en deux ou trois phrases. Ne corrige
     rien et ne repousse rien sans demande.

Pas de relecture poussée de la PR une fois ouverte : seulement si je la demande.
