---
name: worktree-to-main
description: Migre tout le travail du worktree git courant (commits, index, modifications non commitées, fichiers non suivis) vers le worktree principal, sur une nouvelle branche, pour pouvoir ensuite supprimer le worktree.
argument-hint: "[nom-de-branche]"
disable-model-invocation: true
---

# Migrer le worktree courant vers le worktree principal

La session travaille dans un worktree git lié. Il faut rapatrier tout son travail dans le
worktree principal, sur une nouvelle branche, puis proposer de supprimer le worktree.

Toute la mécanique git est dans `${CLAUDE_SKILL_DIR}/migrate.sh` : lance ce script, ne refais
pas ses étapes à la main. Il ne crée aucun commit (les modifications non commitées transitent
par un stash, supprimé une fois appliqué), conserve la distinction indexé / non indexé, et
laisse sur place les fichiers ignorés (`vendor/`, `.env.local`…).

Nom de branche demandé : `$ARGUMENTS`

- Vide : le script reprend la branche du worktree. Elle est libérée (worktree laissé en HEAD
  détachée) puis extraite dans le worktree principal.
- Renseigné : une nouvelle branche de ce nom est créée sur le HEAD du worktree.
- Vide, et la branche du worktree porte un nom généré automatiquement (`worktree-…`, nom
  aléatoire) : propose un nom parlant déduit du travail effectué et fais-le valider.

## Déroulé

1. **Repérer le worktree.** `git rev-parse --show-toplevel` : note ce chemin, il sert après la
   sortie du worktree.

2. **Simuler.** Depuis le worktree : `${CLAUDE_SKILL_DIR}/migrate.sh --dry-run [branche]`.
   Lecture seule. Traite le code de sortie :
   - `10` : la session n'est pas dans un worktree lié. Dis-le et arrête-toi.
   - `11` : le worktree principal a des modifications non commitées (listées). Demande à
     l'utilisateur : les mettre de côté avec `--stash-main` (stash nommé, à restaurer par lui
     plus tard), ou abandonner le temps qu'il s'en occupe. Ne décide pas à sa place.
   - `12` : nom de branche manquant, invalide ou déjà pris. Demande un autre nom.
   - `13` : des fichiers non suivis du worktree existent déjà dans le worktree principal.
     Montre la liste. Pour ceux marqués `identical`, propose de supprimer la copie du
     worktree ; pour les `different`, demande lequel garder. Ne supprime rien sans accord.
   - `14` : merge, rebase ou cherry-pick en cours. À terminer ou annuler d'abord.

3. **Sortir du worktree.** Appelle l'outil `ExitWorktree` avec `action: "keep"` (jamais
   `"remove"` : il supprimerait aussi la branche). S'il répond qu'aucune session worktree n'est
   active, la session a été lancée directement dans le worktree : continue quand même.

4. **Migrer.** `${CLAUDE_SKILL_DIR}/migrate.sh --worktree <chemin> [--stash-main] [branche]`.
   En cas d'échec, le script annule ce qu'il a commencé ou indique où se trouve le travail :
   relaie son message tel quel, ne tente pas de réparation sans accord.

5. **Rendre compte.** Branche obtenue, nombre de commits, `git status` du worktree principal,
   stash éventuel des anciennes modifications du worktree principal (avec la commande pour le
   restaurer), fichiers ignorés restés dans le worktree (signale ceux qui peuvent compter :
   `.env.local`, dumps, uploads…).

6. **Proposer la suppression du worktree.** Seulement après accord explicite :
   - regarde d'abord si le projet documente un nettoyage propre aux worktrees (CLAUDE.md,
     AGENTS.md : conteneurs, volumes, ports…) et propose-le ;
   - `git worktree remove <chemin>` depuis le worktree principal, sans `--force` : un refus
     signale un reste de travail, à montrer à l'utilisateur ;
   - si une nouvelle branche a été créée, l'ancienne branche du worktree pointe sur le même
     commit : propose `git branch --delete <ancienne>`.

   Si le répertoire courant de la session est encore dans le worktree (étape 3 sans effet), ne
   le supprime pas sous tes pieds : donne ces commandes à l'utilisateur.

## Garde d'isolation

Une session entrée dans un worktree refuse les commandes git qui visent le worktree principal.
Si la simulation de l'étape 2 est refusée pour cette raison, ne contourne pas la garde : passe
à l'étape 3, puis refais la simulation depuis le worktree principal avec `--worktree <chemin>`.
Si la garde bloque encore après l'étape 3, donne à l'utilisateur la commande à lancer lui-même
(préfixe `!`).
