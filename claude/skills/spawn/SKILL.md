---
name: spawn
description: Ouvre une session Claude sur un autre projet, dans son workspace herdr, avec un brief prêt à l'emploi.
argument-hint: "<projet> <ce que la session doit faire>"
disable-model-invocation: true
---

# Ouvrir une session Claude sur un projet

Demande : $ARGUMENTS

Le premier mot est le projet, le reste dit ce que la session doit faire. S'il manque l'un des deux,
demande-le-moi.

Lancer ce skill vaut demande explicite de piloter herdr, pour cette fois. Ne change jamais mon focus.

1. **Vérifier herdr.** `test "${HERDR_ENV:-}" = 1`. Sinon, dis-le et arrête-toi.

2. **Trouver le projet.** Un nom (`gitlib`) ou `owner/repo` (`gitonomy/gitlib`) :

   ```bash
   find ~/dev -maxdepth 5 -type d -name .git -path "*/<projet>/.git" -not -path '*/vendor/*' -not -path '*/node_modules/*'
   ```

   Sans résultat, cherche un nom partiel (`*<projet>*/.git`). Plusieurs dossiers : demande lequel. Aucun :
   dis-le et arrête-toi.

3. **Vérifier que le dossier est libre.** `herdr agent list` : si un agent est `working` dans ce dossier,
   une deuxième session y écrirait en même temps. Demande-moi : même dossier, ou worktree
   (`herdr worktree create --cwd <dossier> --branch <nom> --label <nom> --no-focus`, qui donne aussi le
   pane de l'étape 5, dans `.result.root_pane.pane_id`).

4. **Écrire le brief** dans `~/.claude/tmp/spawn-<nom>.md`. La nouvelle session ne sait rien de cette
   conversation.

   - Pars de ma demande. Si elle se suffit, envoie-la telle quelle.
   - Ajoute ce que cette conversation lui évite de redécouvrir : fichiers, décisions, résultats de tests,
     et ce qui reste non vérifié. Marque clairement la différence.
   - N'invente rien. Pas de contexte utile ici : n'en fabrique pas.
   - Reprends les limites que j'ai données. Les règles de mes `CLAUDE.md` s'appliquent déjà là-bas.

5. **Lancer Claude.** `<nom>` respecte `[a-z][a-z0-9_-]{0,31}` et ne figure pas dans `herdr agent list`,
   par exemple `gitlib-171`. `herdr-claude` ouvre un onglet dans le workspace du dépôt et le crée s'il
   n'existe pas :

   ```bash
   herdr-claude --no-focus <nom> <dossier>
   ```

   Avec le worktree de l'étape 3, le pane existe déjà : `herdr agent start <nom> --kind claude --pane <pane> --timeout 45000`.

6. **Envoyer le brief.**

   ```bash
   herdr agent prompt <nom> "$(cat ~/.claude/tmp/spawn-<nom>.md)" --wait --until working --until blocked --timeout 30000
   ```

   `agent_not_ready` ou `blocked` : lis `herdr agent read <nom> --source recent-unwrapped --lines 30`.
   Ne réponds jamais à un dialogue à ma place.

   - Dialogue de confiance d'un dossier jamais ouvert (`Is this a project you created or one you trust?`) :
     dis-moi d'y répondre dans le workspace, attends `herdr agent wait <nom> --until idle --timeout 300000`,
     puis envoie le prompt.
   - Autre blocage : rapporte-le et arrête-toi.

   Un timeout ne prouve pas que le prompt n'est pas parti : ne le renvoie pas.

7. **Rendre compte.** Supprime le brief, puis donne en deux ou trois lignes le workspace, le nom de
   l'agent et ce que tu lui as demandé.
