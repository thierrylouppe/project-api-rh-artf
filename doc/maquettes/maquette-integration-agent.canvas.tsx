import {
  Button,
  Callout,
  Card,
  CardBody,
  CardHeader,
  Checkbox,
  Divider,
  Grid,
  H1,
  H2,
  H3,
  Pill,
  Row,
  Select,
  Spacer,
  Stack,
  Stat,
  Table,
  Text,
  TextInput,
  useCanvasState,
} from "cursor/canvas";

type TypeId =
  | "recrutement"
  | "mutation"
  | "detachement"
  | "mad"
  | "reintegration"
  | "contractuel"
  | "stage_pro"
  | "stage_acad"
  | "stage_qual";

type Ecran =
  | "type"
  | "fiche"
  | "contrat"
  | "pieces"
  | "circuit"
  | "integrer"
  | "taches";

type Famille = "permanent" | "contractuel" | "stage";

interface TypeDef {
  id: TypeId;
  nom: string;
  description: string;
  contrat: boolean;
  dg: boolean;
  compte: boolean;
  acte: string;
  prefixe: string;
  famille: Famille;
  documents: string[];
}

const TYPES: TypeDef[] = [
  {
    id: "recrutement",
    nom: "Recrutement externe",
    description: "Concours, appel à candidature ou recrutement direct",
    contrat: false,
    dg: true,
    compte: true,
    acte: "decision_recrutement",
    prefixe: "ARTF",
    famille: "permanent",
    documents: [
      "Curriculum vitae",
      "Demande",
      "Diplôme",
      "Engagement",
      "Certificat de nationalité",
      "Casier judiciaire",
      "Certificat médical",
      "Acte de naissance",
    ],
  },
  {
    id: "mutation",
    nom: "Mutation",
    description: "Agent provenant d’une autre administration",
    contrat: false,
    dg: true,
    compte: true,
    acte: "decision_mutation",
    prefixe: "ARTF",
    famille: "permanent",
    documents: [
      "Curriculum vitae",
      "Demande",
      "Diplôme",
      "Engagement",
      "Certificat de nationalité",
    ],
  },
  {
    id: "detachement",
    nom: "Détachement",
    description: "Affectation temporaire depuis une autre administration",
    contrat: false,
    dg: true,
    compte: true,
    acte: "arrete_detachement",
    prefixe: "ARTF",
    famille: "permanent",
    documents: ["Curriculum vitae", "Demande", "Diplôme", "Engagement"],
  },
  {
    id: "mad",
    nom: "Mise à disposition",
    description: "Agent prêté par une autre institution",
    contrat: false,
    dg: true,
    compte: true,
    acte: "note_de_service",
    prefixe: "ARTF",
    famille: "permanent",
    documents: ["Curriculum vitae", "Demande", "Diplôme", "Engagement"],
  },
  {
    id: "reintegration",
    nom: "Réintégration",
    description: "Retour après disponibilité, détachement ou congé",
    contrat: false,
    dg: true,
    compte: true,
    acte: "decision_recrutement",
    prefixe: "ARTF",
    famille: "permanent",
    documents: ["Curriculum vitae", "Demande", "Engagement"],
  },
  {
    id: "contractuel",
    nom: "Contractuel",
    description: "CDI ou CDD — contrat + salaire",
    contrat: true,
    dg: true,
    compte: true,
    acte: "contrat",
    prefixe: "ARTF",
    famille: "contractuel",
    documents: [
      "Curriculum vitae",
      "Demande",
      "Diplôme",
      "Engagement",
      "Certificat de nationalité",
      "Casier judiciaire",
      "Certificat médical",
      "Acte de naissance",
    ],
  },
  {
    id: "stage_pro",
    nom: "Stage professionnel",
    description: "Stagiaire non statutaire — pas de DG ni de compte",
    contrat: true,
    dg: false,
    compte: false,
    acte: "contrat",
    prefixe: "STG",
    famille: "stage",
    documents: [
      "Demande de stage adressée au Directeur Général",
      "Lettre de recommandation de l’établissement",
      "Convention de stage",
    ],
  },
  {
    id: "stage_acad",
    nom: "Stage académique",
    description: "Étudiant — certificat de scolarité",
    contrat: true,
    dg: false,
    compte: false,
    acte: "contrat",
    prefixe: "STG",
    famille: "stage",
    documents: [
      "Demande de stage adressée au Directeur Général",
      "Lettre de recommandation de l’établissement",
      "Convention de stage",
      "Certificat de scolarité",
    ],
  },
  {
    id: "stage_qual",
    nom: "Stage de qualification",
    description: "Mise en stage après concours — DG requis",
    contrat: true,
    dg: true,
    compte: false,
    acte: "contrat",
    prefixe: "STG",
    famille: "stage",
    documents: [
      "Demande de stage adressée au Directeur Général",
      "Lettre de recommandation de l’établissement",
      "Convention de stage",
      "Décision de mise en stage",
    ],
  },
];

const CIRCUIT_COMPLET = [
  { id: "chef_bureau", label: "Chef de bureau" },
  { id: "chef_service", label: "Chef de service" },
  { id: "directeur", label: "Directeur" },
  { id: "directeur_general", label: "Directeur général" },
] as const;

function typeById(id: TypeId): TypeDef {
  return TYPES.find((t) => t.id === id) ?? TYPES[0];
}

function circuitPour(t: TypeDef) {
  return CIRCUIT_COMPLET.filter((n) => t.dg || n.id !== "directeur_general");
}

function FieldLabel({ children }: { children: string }) {
  return (
    <Text size="small" tone="secondary" weight="medium">
      {children}
    </Text>
  );
}

function StatutPill({ statut }: { statut: string }) {
  return <Pill active={statut === "INTEGRE" || statut === "VALIDE_DG"}>{statut}</Pill>;
}

export default function MaquetteIntegrationAgent() {
  const [typeId, setTypeId] = useCanvasState<TypeId>("int-type", "recrutement");
  const [ecran, setEcran] = useCanvasState<Ecran>("int-ecran", "type");
  const [ficheCreee, setFicheCreee] = useCanvasState("int-fiche", false);
  const [nom, setNom] = useCanvasState("int-nom", "NGOMA");
  const [prenom, setPrenom] = useCanvasState("int-prenom", "Amina");
  const [docs, setDocs] = useCanvasState<string[]>("int-docs", []);
  const [rhOk, setRhOk] = useCanvasState("int-rh", false);
  const [approuves, setApprouves] = useCanvasState<string[]>("int-circ", []);
  const [integre, setIntegre] = useCanvasState("int-ok", false);
  const [taches, setTaches] = useCanvasState<string[]>("int-taches", []);
  const [natureContrat, setNatureContrat] = useCanvasState("int-cdi", "CDD");

  const type = typeById(typeId);
  const circuit = circuitPour(type);
  const docsOk = type.documents.every((d) => docs.includes(d));
  const circuitOk =
    circuit.length === 0 || circuit.every((n) => approuves.includes(n.id));

  let statut = "BROUILLON";
  if (integre) statut = "INTEGRE";
  else if (circuitOk && rhOk && docsOk && ficheCreee) statut = "VALIDE_DG";
  else if (rhOk && docsOk && ficheCreee) statut = "VALIDE_RH";
  else if (docsOk && ficheCreee) statut = "DOSSIER_COMPLET";

  const ecrans: { id: Ecran; label: string; visible: boolean }[] = [
    { id: "type", label: "1. Type", visible: true },
    { id: "fiche", label: "2. Fiche agent", visible: true },
    { id: "contrat", label: "3. Contrat", visible: type.contrat },
    { id: "pieces", label: type.contrat ? "4. Pièces" : "3. Pièces", visible: true },
    {
      id: "circuit",
      label: type.contrat ? "5. Circuit" : "4. Circuit",
      visible: true,
    },
    {
      id: "integrer",
      label: type.contrat ? "6. Intégrer" : "5. Intégrer",
      visible: true,
    },
    {
      id: "taches",
      label: type.contrat ? "7. Tâches" : "6. Tâches",
      visible: true,
    },
  ];

  function resetFlux(next: TypeId) {
    setTypeId(next);
    setFicheCreee(false);
    setDocs([]);
    setRhOk(false);
    setApprouves([]);
    setIntegre(false);
    setTaches([]);
    setEcran("fiche");
  }

  function recommencer() {
    setFicheCreee(false);
    setDocs([]);
    setRhOk(false);
    setApprouves([]);
    setIntegre(false);
    setTaches([]);
    setEcran("type");
  }

  return (
    <Stack gap={20}>
      <Stack gap={6}>
        <H1>Maquette FE — Intégration d’un agent</H1>
        <Text tone="secondary">
          Wizard du chemin actuel : jusqu’à VALIDE_DG, puis POST /integrer, puis
          tâches en ordre libre. Changez le type pour voir contrat, DG et compte
          s’adapter.
        </Text>
      </Stack>

      <Grid columns={4} gap={12}>
        <Stat value={statut} label="Statut dossier" tone={integre ? "success" : "info"} />
        <Stat value={type.prefixe} label="Préfixe matricule" />
        <Stat
          value={type.dg ? "Oui" : "Non"}
          label="Validation DG"
          tone={type.dg ? "warning" : "success"}
        />
        <Stat
          value={type.compte ? "Oui" : "Non"}
          label="Compte utilisateur"
        />
      </Grid>

      <Row gap={8} wrap>
        {ecrans
          .filter((e) => e.visible)
          .map((e) => (
            <span key={e.id}>
              <Pill active={ecran === e.id} onClick={() => setEcran(e.id)}>
                {e.label}
              </Pill>
            </span>
          ))}
        <Spacer />
        <Button variant="ghost" onClick={recommencer}>
          Recommencer
        </Button>
      </Row>

      {ecran === "type" && (
        <EcranType typeId={typeId} onChoisir={resetFlux} />
      )}
      {ecran === "fiche" && (
        <EcranFiche
          type={type}
          nom={nom}
          prenom={prenom}
          setNom={setNom}
          setPrenom={setPrenom}
          ficheCreee={ficheCreee}
          onCreer={() => {
            setFicheCreee(true);
            setEcran(type.contrat ? "contrat" : "pieces");
          }}
        />
      )}
      {ecran === "contrat" && type.contrat && (
        <EcranContrat
          type={type}
          nature={natureContrat}
          setNature={setNatureContrat}
          ficheCreee={ficheCreee}
          onSuite={() => setEcran("pieces")}
        />
      )}
      {ecran === "pieces" && (
        <EcranPieces
          type={type}
          docs={docs}
          setDocs={setDocs}
          ficheCreee={ficheCreee}
          docsOk={docsOk}
          onSuite={() => setEcran("circuit")}
        />
      )}
      {ecran === "circuit" && (
        <EcranCircuit
          type={type}
          circuit={circuit}
          docsOk={docsOk}
          ficheCreee={ficheCreee}
          rhOk={rhOk}
          approuves={approuves}
          onValiderRh={() => setRhOk(true)}
          onApprouver={(id) =>
            setApprouves((prev) => (prev.includes(id) ? prev : [...prev, id]))
          }
          onSuite={() => setEcran("integrer")}
        />
      )}
      {ecran === "integrer" && (
        <EcranIntegrer
          type={type}
          nom={nom}
          prenom={prenom}
          statut={statut}
          integre={integre}
          onIntegrer={() => {
            setIntegre(true);
            const auto: string[] = [];
            if (type.compte) auto.push("compte");
            setTaches(auto);
            setEcran("taches");
          }}
        />
      )}
      {ecran === "taches" && (
        <EcranTaches
          type={type}
          nom={nom}
          prenom={prenom}
          integre={integre}
          taches={taches}
          setTaches={setTaches}
        />
      )}
    </Stack>
  );
}

function EcranType({
  typeId,
  onChoisir,
}: {
  typeId: TypeId;
  onChoisir: (id: TypeId) => void;
}) {
  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <H2>Choisir le type d’intégration</H2>
        <Text tone="secondary">
          GET /types-integrations — les flags filtrent le wizard et les tâches.
        </Text>
      </Stack>

      <Callout tone="info" title="Un type = un parcours">
        Recrutement, mutation, détachement : pas de contrat, DG et compte.
        Contractuel : contrat obligatoire. Stages : préfixe STG, souvent sans
        compte ; seuls les stages pro / académique sautent le DG.
      </Callout>

      <Table
        headers={["Type", "Contrat", "DG", "Compte", "Acte", "Préfixe", ""]}
        striped
        rows={TYPES.map((t) => [
          <span>
            <Stack gap={2}>
              <Text weight="medium">{t.nom}</Text>
              <Text size="small" tone="tertiary">
                {t.description}
              </Text>
            </Stack>
          </span>,
          t.contrat ? "Oui" : "Non",
          t.dg ? "Oui" : "Non",
          t.compte ? "Oui" : "Non",
          t.acte,
          t.prefixe,
          <span>
            <Button
              variant={typeId === t.id ? "primary" : "secondary"}
              onClick={() => onChoisir(t.id)}
            >
              {typeId === t.id ? "Sélectionné" : "Choisir"}
            </Button>
          </span>,
        ])}
        rowTone={TYPES.map((t) => (t.id === typeId ? "info" : "neutral"))}
      />
    </Stack>
  );
}

function EcranFiche({
  type,
  nom,
  prenom,
  setNom,
  setPrenom,
  ficheCreee,
  onCreer,
}: {
  type: TypeDef;
  nom: string;
  prenom: string;
  setNom: (v: string) => void;
  setPrenom: (v: string) => void;
  ficheCreee: boolean;
  onCreer: () => void;
}) {
  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <H2>Créer la fiche agent et le dossier</H2>
        <Text tone="secondary">
          POST /integration/agents — dossier créé en BROUILLON, lié au type «
          {type.nom} ».
        </Text>
      </Stack>

      <Grid columns={2} gap={20}>
        <Stack gap={10}>
          <FieldLabel>Nom</FieldLabel>
          <TextInput value={nom} onChange={setNom} />
          <FieldLabel>Prénom</FieldLabel>
          <TextInput value={prenom} onChange={setPrenom} />
          <FieldLabel>Sexe</FieldLabel>
          <Select
            value="F"
            options={[
              { value: "F", label: "Féminin" },
              { value: "M", label: "Masculin" },
            ]}
          />
          <FieldLabel>Date de naissance</FieldLabel>
          <TextInput value="1994-03-12" />
          <FieldLabel>Type d’intégration</FieldLabel>
          <TextInput value={type.nom} disabled />
        </Stack>

        <Stack gap={12}>
          <H3>Ce que l’API crée</H3>
          <Text size="small">
            Un agent (sans matricule) + un dossier d’intégration n° 18, statut
            BROUILLON.
          </Text>
          <Card>
            <CardHeader trailing={<StatutPill statut={ficheCreee ? "BROUILLON" : "—"} />}>
              Dossier #18
            </CardHeader>
            <CardBody>
              <Stack gap={6}>
                <Text size="small">
                  {prenom} {nom}
                </Text>
                <Text size="small" tone="secondary">
                  Type {type.nom} · acte {type.acte}
                </Text>
                <Text size="small" tone="tertiary">
                  Matricule : attribué plus tard ({type.prefixe}-…)
                </Text>
              </Stack>
            </CardBody>
          </Card>
          {ficheCreee ? (
            <Callout tone="success" title="Fiche créée">
              Passez au {type.contrat ? "contrat" : "dépôt des pièces"}.
            </Callout>
          ) : (
            <Button variant="primary" onClick={onCreer}>
              Créer le dossier
            </Button>
          )}
        </Stack>
      </Grid>
    </Stack>
  );
}

function EcranContrat({
  type,
  nature,
  setNature,
  ficheCreee,
  onSuite,
}: {
  type: TypeDef;
  nature: string;
  setNature: (v: string) => void;
  ficheCreee: boolean;
  onSuite: () => void;
}) {
  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <H2>Contrat {type.famille === "stage" ? "/ convention" : ""}</H2>
        <Text tone="secondary">
          Visible uniquement si necessite_contrat. La signature réelle est une
          tâche post-intégration (étape 12).
        </Text>
      </Stack>

      {!ficheCreee && (
        <Callout tone="warning" title="Dossier manquant">
          Créez d’abord la fiche agent.
        </Callout>
      )}

      <Grid columns={2} gap={20}>
        <Stack gap={10}>
          <FieldLabel>Nature</FieldLabel>
          <Select
            value={nature}
            onChange={setNature}
            options={
              type.famille === "stage"
                ? [{ value: "convention", label: "Convention de stage" }]
                : [
                    { value: "CDD", label: "CDD" },
                    { value: "CDI", label: "CDI" },
                  ]
            }
          />
          <FieldLabel>Date début</FieldLabel>
          <TextInput value="2026-09-01" />
          <FieldLabel>Date fin</FieldLabel>
          <TextInput value={nature === "CDI" ? "" : "2027-08-31"} />
          <FieldLabel>Lieu</FieldLabel>
          <TextInput value="Brazzaville — siège ARTF" />
        </Stack>
        <Stack gap={12}>
          <Callout tone="info" title="Stage : convention auto à /integrer">
            Pour un stage, POST /integrer crée aussi la ConventionStage et le
            statut stagiaire. Ici on saisit seulement le brouillon.
          </Callout>
          <Button variant="primary" disabled={!ficheCreee} onClick={onSuite}>
            Enregistrer et déposer les pièces
          </Button>
        </Stack>
      </Grid>
    </Stack>
  );
}

function EcranPieces({
  type,
  docs,
  setDocs,
  ficheCreee,
  docsOk,
  onSuite,
}: {
  type: TypeDef;
  docs: string[];
  setDocs: (v: string[] | ((p: string[]) => string[])) => void;
  ficheCreee: boolean;
  docsOk: boolean;
  onSuite: () => void;
}) {
  function toggle(nomDoc: string, checked: boolean) {
    setDocs((prev) =>
      checked ? [...prev.filter((d) => d !== nomDoc), nomDoc] : prev.filter((d) => d !== nomDoc),
    );
  }

  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <H2>Pièces justificatives</H2>
        <Text tone="secondary">
          Liste issue du type (seeder). Toutes obligatoires → DOSSIER_COMPLET.
        </Text>
      </Stack>

      {!ficheCreee && (
        <Callout tone="warning" title="Dossier manquant">
          Créez d’abord la fiche agent.
        </Callout>
      )}

      <Table
        headers={["Pièce obligatoire", "Déposée"]}
        striped
        rows={type.documents.map((d) => [
          d,
          <span>
            <Checkbox
              checked={docs.includes(d)}
              disabled={!ficheCreee}
              onChange={(c) => toggle(d, c)}
              label={docs.includes(d) ? "Oui" : "Non"}
            />
          </span>,
        ])}
        rowTone={type.documents.map((d) =>
          docs.includes(d) ? "success" : "warning",
        )}
      />

      <Row gap={8} align="center">
        <Text size="small" tone="tertiary">
          {docs.filter((d) => type.documents.includes(d)).length} /{" "}
          {type.documents.length} pièces
        </Text>
        <Spacer />
        <Button variant="primary" disabled={!docsOk} onClick={onSuite}>
          Valider le dossier (DOSSIER_COMPLET)
        </Button>
      </Row>
    </Stack>
  );
}

function EcranCircuit({
  type,
  circuit,
  docsOk,
  ficheCreee,
  rhOk,
  approuves,
  onValiderRh,
  onApprouver,
  onSuite,
}: {
  type: TypeDef;
  circuit: { id: string; label: string }[];
  docsOk: boolean;
  ficheCreee: boolean;
  rhOk: boolean;
  approuves: string[];
  onValiderRh: () => void;
  onApprouver: (id: string) => void;
  onSuite: () => void;
}) {
  const circuitOk = circuit.every((n) => approuves.includes(n.id));
  const pret = ficheCreee && docsOk && rhOk && circuitOk;

  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <H2>Validation RH puis circuit hiérarchique</H2>
        <Text tone="secondary">
          POST /integration/dossiers/18/valider-rh puis
          POST /integration/validations/{"{id}"}/approuver
        </Text>
      </Stack>

      {!docsOk && (
        <Callout tone="warning" title="Pièces incomplètes">
          Le bouton RH reste inactif tant que le dossier n’est pas complet.
        </Callout>
      )}

      {!type.dg && (
        <Callout tone="info" title="necessite_validation_dg = false">
          Le niveau Directeur général est retiré. Fin de circuit → VALIDE_DG
          quand même (validerDG).
        </Callout>
      )}

      <Grid columns={2} gap={20}>
        <Stack gap={12}>
          <H3>RH (rôle rh)</H3>
          {rhOk ? (
            <Callout tone="success" title="VALIDE_RH">
              Le circuit est ouvert aux validateurs.
            </Callout>
          ) : (
            <Button variant="primary" disabled={!docsOk || !ficheCreee} onClick={onValiderRh}>
              Valider RH
            </Button>
          )}

          <Divider />

          <H3>Niveaux restants</H3>
          {circuit.map((n, i) => {
            const done = approuves.includes(n.id);
            const prevOk =
              i === 0 || approuves.includes(circuit[i - 1].id);
            const can = rhOk && prevOk && !done;
            return (
              <Row gap={8} align="center">
                <Text weight="medium">
                  {i + 1}. {n.label}
                </Text>
                <Spacer />
                {done ? (
                  <Pill active>Approuvé</Pill>
                ) : (
                  <Button variant="secondary" disabled={!can} onClick={() => onApprouver(n.id)}>
                    Approuver
                  </Button>
                )}
              </Row>
            );
          })}
        </Stack>

        <Stack gap={12}>
          <H3>Statut attendu</H3>
          <Text size="small">
            {pret
              ? "Circuit terminé → VALIDE_DG. Le FE peut appeler /integrer."
              : "En attente des validations dans l’ordre."}
          </Text>
          <Button variant="primary" disabled={!pret} onClick={onSuite}>
            Passer à l’intégration
          </Button>
          <Text size="small" tone="tertiary">
            Chemin A (acte → matricule → integrer) existe encore côté API,
            peu utilisé par le FE.
          </Text>
        </Stack>
      </Grid>
    </Stack>
  );
}

function EcranIntegrer({
  type,
  nom,
  prenom,
  statut,
  integre,
  onIntegrer,
}: {
  type: TypeDef;
  nom: string;
  prenom: string;
  statut: string;
  integre: boolean;
  onIntegrer: () => void;
}) {
  const ok = statut === "VALIDE_DG" || integre;

  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <Row gap={8} align="center">
          <H2>
            Intégrer {prenom} {nom}
          </H2>
          <StatutPill statut={integre ? "INTEGRE" : statut} />
        </Row>
        <Text tone="secondary">
          POST /integration/dossiers/18/integrer — chemin B (FE actuel).
        </Text>
      </Stack>

      {!ok && (
        <Callout tone="warning" title="Pas encore VALIDE_DG">
          Terminez le circuit. L’API refuserait la transition.
        </Callout>
      )}

      <H3>Effets automatiques à /integrer</H3>
      <Table
        headers={["Effet", "Ce type"]}
        rows={[
          [
            "Statut dossier → INTEGRE",
            "Toujours",
          ],
          [
            "Compte utilisateur",
            type.compte
              ? "Créé si absent (tâche 16 souvent déjà faite)"
              : "Aucun — stages",
          ],
          [
            "ConventionStage + statut stagiaire",
            type.famille === "stage" ? "Oui" : "Non",
          ],
          [
            "Matricule / acte / affectation",
            "Pas ici — tâches après, ordre libre",
          ],
        ]}
        rowTone={[
          "success",
          type.compte ? "info" : "neutral",
          type.famille === "stage" ? "info" : "neutral",
          "neutral",
        ]}
      />

      {integre ? (
        <Callout tone="success" title="Agent intégré">
          Le statut ne reviendra plus en arrière. Les tâches 11–18 se font
          ensuite, sans changer INTEGRE.
        </Callout>
      ) : (
        <Button variant="primary" disabled={!ok} onClick={onIntegrer}>
          Intégrer l’agent
        </Button>
      )}
    </Stack>
  );
}

function EcranTaches({
  type,
  nom,
  prenom,
  integre,
  taches,
  setTaches,
}: {
  type: TypeDef;
  nom: string;
  prenom: string;
  integre: boolean;
  taches: string[];
  setTaches: (v: string[] | ((p: string[]) => string[])) => void;
}) {
  const items: {
    id: string;
    etape: string;
    label: string;
    obligatoire: boolean;
    endpoint: string;
  }[] = [
    {
      id: "acte",
      etape: "11",
      label: "Générer l’acte",
      obligatoire: type.famille === "permanent",
      endpoint: "POST /integration/dossiers/18/generer-acte",
    },
  ];

  if (type.contrat) {
    items.push({
      id: "contrat_signe",
      etape: "12",
      label: "Marquer le contrat signé",
      obligatoire: false,
      endpoint: "POST …/marquer-contrat-signe",
    });
  }
  if (type.famille === "contractuel") {
    items.push({
      id: "salaire",
      etape: "12",
      label: "Salaire initial",
      obligatoire: false,
      endpoint: "POST /carriere/agents/{id}/salaires",
    });
  }
  items.push({
    id: "matricule",
    etape: "13",
    label: `Assigner le matricule (${type.prefixe}-2026-…)`,
    obligatoire: type.famille === "permanent",
    endpoint: "POST …/assigner-matricule",
  });
  items.push({
    id: "aff",
    etape: "14",
    label: "Affecter l’agent (module carrière)",
    obligatoire: false,
    endpoint: "POST /carriere/affectations",
  });
  if (type.famille !== "stage") {
    items.push({
      id: "nom",
      etape: "15",
      label: "Nommer l’agent (module carrière)",
      obligatoire: false,
      endpoint: "POST /carriere/nominations",
    });
  }
  if (type.compte) {
    items.push({
      id: "compte",
      etape: "16",
      label: "Compte utilisateur",
      obligatoire: true,
      endpoint: "déjà provisionné à /integrer",
    });
  }
  items.push({
    id: "matos",
    etape: "17",
    label: "Matériel",
    obligatoire: false,
    endpoint: "POST /integration/dossiers/18/materiel",
  });
  items.push({
    id: "pds",
    etape: "18",
    label: "Prise de service",
    obligatoire: false,
    endpoint: "POST /integration/dossiers/18/prise-de-service",
  });

  const obligatoires = items.filter((i) => i.obligatoire);
  const restantes = obligatoires.filter((i) => !taches.includes(i.id)).length;

  function toggle(id: string) {
    setTaches((prev) =>
      prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id],
    );
  }

  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <Row gap={8} align="center">
          <H2>
            Tâches post-intégration — {prenom} {nom}
          </H2>
          <StatutPill statut={integre ? "INTEGRE" : "—"} />
        </Row>
        <Text tone="secondary">
          GET /integration/dossiers/18/taches-post-integration — ordre libre.
          Compteur = obligatoire === true.
        </Text>
      </Stack>

      {!integre && (
        <Callout tone="warning" title="Pas encore INTEGRE">
          Ces actions n’apparaissent qu’après /integrer.
        </Callout>
      )}

      <Grid columns={3} gap={12}>
        <Stat
          value={String(restantes)}
          label="Obligatoires restantes"
          tone={restantes === 0 && integre ? "success" : "warning"}
        />
        <Stat value={type.acte} label="Type d’acte (étape 11)" />
        <Stat
          value={taches.includes("acte") ? "sans changement de statut" : "à faire"}
          label="generer-acte sur chemin B"
        />
      </Grid>

      <Callout tone="info" title="Étapes 14 et 15 = liens carrière">
        Ne pas bloquer le wizard. Préremplir agent_id. INTEGRE ne dépend plus
        d’une affectation ni d’une nomination.
      </Callout>

      <Table
        headers={["Étape", "Tâche", "Obligatoire", "État", "Action"]}
        striped
        rows={items.map((i) => {
          const done = taches.includes(i.id);
          return [
            i.etape,
            <span>
              <Stack gap={2}>
                <Text>{i.label}</Text>
                <Text size="small" tone="tertiary">
                  {i.endpoint}
                </Text>
              </Stack>
            </span>,
            i.obligatoire ? "Oui" : "Non",
            done ? "Fait" : "Non fait",
            <span>
              <Button
                variant={done ? "ghost" : "secondary"}
                disabled={!integre}
                onClick={() => toggle(i.id)}
              >
                {done ? "Annuler" : "Marquer fait"}
              </Button>
            </span>,
          ];
        })}
        rowTone={items.map((i) =>
          taches.includes(i.id)
            ? "success"
            : i.obligatoire
              ? "warning"
              : "neutral",
        )}
      />

      <Text size="small" tone="tertiary">
        Source : workflow-integration-par-type.md · chemin B
      </Text>
    </Stack>
  );
}
