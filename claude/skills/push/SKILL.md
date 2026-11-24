---
name: push
description: Commite tout le travail local, en un ou plusieurs commits par sujet, et le pousse sur la branche courante.
argument-hint: "[précision pour les commits]"
disable-model-invocation: true
---

# Commiter et pousser sur la branche courante

Lancer ce skill vaut demande explicite de commiter tout le travail local et de le pousser sur la
branche courante, pour cette fois. Il pousse sur la branche où l'on est, même la branche par
défaut : pas de nouvelle branche. Il ne couvre ni PR (c'est `/pr`), ni force push, ni amend ou
rebase de commits existants.

Précision de l'utilisateur : $ARGUMENTS

1. **Regarder ce qui part.** `git status`, `git diff`, `git diff --cached` et le contenu des
   fichiers non suivis, puis `git log` pour le style de commit du projet. Arrête-toi si :

   - HEAD est détachée, ou un merge, un rebase ou un cherry-pick est en cours ;
   - un secret, un artefact (build, dump, log…) ou un fichier sans rapport avec le travail traîne :
     demande quoi en faire ;
   - il n'y a rien à commiter ni à pousser : dis-le.

2. **Découper.** Un commit par sujet, pas forcément un seul. Ce qui est déjà indexé forme un sujet
   voulu, sauf si ça ne tient pas. `git add -p` est interactif : un fichier qui mélange deux sujets
   va entier dans le commit du sujet principal.

3. **Commiter.** Pour chaque sujet, `git add <chemins>` explicites (pas de `git add -A` à
   l'aveugle), puis un commit dans le style du projet, sans mention d'attribution. Jamais
   `--no-verify` : si un hook échoue, corrige la cause ou arrête-toi avant de pousser une série à
   moitié faite.

4. **Pousser.** `git push`, ou `git push -u origin <branche>` sans upstream. Les commits locaux
   déjà faits partent avec. Push rejeté : arrête-toi et demande, sans force push ni `pull --rebase`
   automatique.

5. **Conclure.** Une ligne par commit (hash court et sujet), puis la branche poussée.
