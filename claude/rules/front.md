# Front

- React : pas de `useMemo` / `useCallback` sans problème de perf prouvé.
- Le backend est la source de vérité : pas de règle métier ni de validation serveur recodée côté
  front, pas de patch des données API côté client. Si l'API renvoie une donnée fausse ou en trop,
  on corrige l'API.
