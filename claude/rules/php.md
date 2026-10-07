# PHP / Symfony

- Classes de service `final readonly` avec constructor property promotion. Visibilité `private`
  par défaut (propriétés, méthodes, constantes). DTO et value objects `readonly`.
- `\DateTimeImmutable` uniquement, jamais `\DateTime`.
- Pas d'`empty()`. `in_array()` toujours strict (`, true`).
- PHPDoc seulement pour ce que le type natif ne dit pas (shapes d'array, generics).
- Exceptions : jamais lever `\Exception`. `\LogicException` = bug de notre code (invariant violé),
  `\RuntimeException` (ou plus précis) sinon. Messages = phrase complète terminée par un point.
- Injection de dépendances par attributs (`#[Autowire]`, `#[Target]`, `#[AutoconfigureTag]`…)
  plutôt qu'en YAML.
- Contrôle d'accès via les voters / `is_granted`, jamais de check ad hoc. Validation par
  contraintes, pas de `if` + exception construite à la main.
- Doctrine :
  - `flush()` uniquement dans les controllers, commands et handlers Messenger, jamais dans un
    service métier.
  - Injecter le repository plutôt que l'EntityManager ; les requêtes vivent dans le repository.
  - Attributs ORM minimaux : ne rien spécifier de déductible (name, type, nullable par défaut).
  - Migrations : supprimer les commentaires auto-générés, remplir `getDescription()`.
- API Platform : étendre par processor / provider, jamais par controller custom ni EventSubscriber.
