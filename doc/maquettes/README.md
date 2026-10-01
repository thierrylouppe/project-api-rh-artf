# Maquettes FE

Fichiers de maquettes interactives (Cursor Canvas). Ils sont versionnés ici pour les rouvrir à tout moment, indépendamment d’une conversation.

## Comment ouvrir

1. Dans Cursor, ouvrez le fichier `.canvas.tsx` depuis ce dossier **ou** depuis le panneau Canvas à côté du chat.
2. Le rendu interactif Cursor lit aussi une copie dans le dossier Canvas de l’IDE :
   `~/.cursor/projects/Users-thierrylouppe-WebProjects-project-api-rh-artf/canvases/`

Quand une maquette est créée ou mise à jour, **garder les deux copies alignées** (`doc/maquettes/` = dépôt Git, `canvases/` = aperçu live).

Ne pas committer les `.canvas.data.json` (état de clic local).

## Catalogue

| Fichier | Sujet |
|---------|--------|
| [maquette-integration-agent.canvas.tsx](./maquette-integration-agent.canvas.tsx) | Wizard d’intégration (types, pièces, circuit, `/integrer`, tâches) |
| [maquette-affectation-fe.canvas.tsx](./maquette-affectation-fe.canvas.tsx) | Affectations & note de service (checklist → unitaire / groupée → PDF) |
| [maquette-nomination-fe.canvas.tsx](./maquette-nomination-fe.canvas.tsx) | Nominations carrière (création, circuit, postes vacants, acte, synthèse) |
