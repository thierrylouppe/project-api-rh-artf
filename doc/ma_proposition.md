après evaluation je propose cette structure :

Etape 1 : Selection du type_d'integration.
Etape 2 : Creation de la fiche agent avec les informations de base qui ne peuvent pas etre fourni par le systeme de façon automatique comme les nom, prenom, date de naissance ...
Creation du dossier en arriere plan avec des info bref
Etape 3 : creation du contrat (si `necessite_contrat`)
Etape 4 : Depot des pieces justificatives
Etape 5 : Validation de chaque document
Etape 6 : Marquer le dossier complet
Etape 7 : Validation RH
Etape 7-bis : Circuit de validation hierarchiques (DG retiré si `necessite_validation_dg = false`)
Etape 8 : Intégration (`POST …/integrer` → `INTEGRE`)
  - compte auto si `necessite_compte_utilisateur`
  - ConventionStage si type stage

Et pour les prochaines étapes (post-intégration, ordre libre) :
Générer l'acte administratif (depuis `INTEGRE` ou `VALIDE_DG`)
Affecter / nommer l'agent (nomination absente pour les stages)
Créer le compte utilisateur (si non auto et si requis)
Remettre le materiel
Confirmer la prise de service

Doc de référence : [`workflow-integration-par-type.md`](./workflow-integration-par-type.md)
