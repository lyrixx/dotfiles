# Écriture de code

Règles valables pour tous les langages. Les spécificités sont dans `php.md` et `front.md`.

## Avant d'écrire

- Chercher l'existant avant d'écrire un mécanisme : le framework d'abord, le repo ensuite. Pas de
  nouvelle dépendance pour quelques lignes.
- Une seule source de vérité. Si une duplication est inévitable (back/front), le dire en
  commentaire avec le chemin exact de la contrepartie (« Keep in sync with … »).

## Un concept = un seul nom

Un même concept porte le même nom dans toutes les couches qu'il traverse : propriété, claim JWT,
clé de payload, colonne, assertion de test… Pas de variante raccourcie ou reformulée d'une couche
à l'autre (ex. propriété `impersonatorIdentifier` mais claim `impersonator` : à éviter). Avant de
considérer une tâche terminée, chercher le nom du nouveau concept et vérifier que chaque occurrence
utilise la même orthographe. Exception : quand le nom suit une convention déjà en place dans le code.

## Conception

- La logique métier vit dans le modèle (méthodes d'intention, named constructors), pas dans une
  suite de setters appelés depuis un service. Petits services à responsabilité unique.
- Pas d'array associatif fourre-tout qui traverse les couches : un objet typé et immuable.
- Pas de paramètre booléen qui fait bifurquer une méthode : deux méthodes explicites.

## Robuste, mais pas de code pour l'impossible

La frontière est mince. Le test : peut-on décrire le scénario concret qui mène à cette branche ?

- Oui, même tordu → on le gère : entité supprimée entre deux étapes, valeur inconnue envoyée par
  un client, `../` dans un nom de fichier, clé absente chez un vieux client, donnée corrompue en
  base, N workers en parallèle. Toujours se demander « que se passe-t-il si… ? ».
- Non → pas de branche (« ça arrive quand ? »), pas de paramètre ni de point d'extension sans cas
  d'usage réel.
- Pas de code qui n'existe que pour faire plaisir à un outil (PHPStan…) : un check, un
  `?? default` ou un nullable ajouté pour faire taire l'analyse alors que le cas n'arrive jamais →
  corriger le typage à la source. Si c'est impossible, un `@phpstan-ignore` ciblé avec un
  commentaire qui explique pourquoi le cas n'arrive pas.
- Préférer la robustesse par construction (types stricts, non-nullable, invariants portés par le
  modèle) à des checks éparpillés.

## Erreurs et logs

- Jamais de catch générique, un try minimal autour de ce qui peut vraiment échouer.
- Aucune branche d'échec silencieuse : log en warning minimum. Le niveau `error` déclenche une
  alerte (Sentry), le réserver aux vrais problèmes.
- Contexte de log : l'exception elle-même, sans dupliquer son message ni sa classe.
- Corriger la cause plutôt que masquer l'erreur.

## Penser prod

- Plusieurs workers en parallèle : race conditions, commit avant de dispatcher un message.
- Plusieurs serveurs : pas de fichier local partagé entre process.
- Volumétrie réelle : pas de tout-en-RAM, pas de requête dans une boucle.
- Toujours un timeout sur les appels HTTP sortants, et vérifier le status avant de traiter la
  réponse.

## Tests

- Jamais d'API de prod ajoutée pour les tests (setter, visibilité élargie).
- Un diff inattendu sur des fixtures ou snapshots est un bug à investiguer, jamais à régénérer pour
  faire passer le test.

## Micro-conventions

- Tri alphabétique : imports, dépendances, listes de colonnes, mappings, config. Nommer pour que le
  tri regroupe ce qui va ensemble (préfixe commun).
- Acronymes en majuscules dans les textes et la doc (DNS, URL, API) ; dans le code, suivre le
  casing du langage (`getDnsStatus()`, pas `getDNSStatus()`).
- Le principal d'abord dans un fichier (classe principale en tête, méthodes publiques avant les
  privées). Ordre des paramètres du constructeur aligné sur celui des propriétés.
