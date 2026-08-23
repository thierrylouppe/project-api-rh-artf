import {
  Button,
  Callout,
  Card,
  CardBody,
  CardHeader,
  Code,
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

type Ecran =
  | "checklist"
  | "creer"
  | "fiche"
  | "postes"
  | "historique"
  | "synthese";

const ECRANS: { id: Ecran; label: string }[] = [
  { id: "checklist", label: "1. Checklist" },
  { id: "creer", label: "2. Créer" },
  { id: "fiche", label: "3. Circuit & activer" },
  { id: "postes", label: "4. Postes & équipe" },
  { id: "historique", label: "5. Historique & acte" },
  { id: "synthese", label: "6. Synthèse carrière" },
];

const POSTES = [
  { value: "Directeur Général", label: "Directeur Général → Direction" },
  { value: "Directeur Central", label: "Directeur Central → Direction" },
  { value: "Directeur Départemental", label: "Directeur Départemental → Direction" },
  { value: "Chef de Service", label: "Chef de Service → Service" },
  { value: "Chef de Bureau", label: "Chef de Bureau → Bureau" },
];

export default function MaquetteNominationFe() {
  const [ecran, setEcran] = useCanvasState<Ecran>("nom-ecran", "creer");

  return (
    <Stack gap={20}>
      <Stack gap={6}>
        <H1>Maquette FE — Nominations (carrière)</H1>
        <Text tone="secondary">
          Idée d’écrans, pas le design final. Module carrière : la nomination
          n’est pas une étape de dossier.
        </Text>
      </Stack>

      <Row gap={8} wrap>
        {ECRANS.map((e) => (
          <span key={e.id}>
            <Pill active={ecran === e.id} onClick={() => setEcran(e.id)}>
              {e.label}
            </Pill>
          </span>
        ))}
      </Row>

      {ecran === "checklist" && (
        <EcranChecklist onGoto={() => setEcran("creer")} />
      )}
      {ecran === "creer" && <EcranCreer onCreated={() => setEcran("fiche")} />}
      {ecran === "fiche" && <EcranFiche />}
      {ecran === "postes" && <EcranPostes />}
      {ecran === "historique" && <EcranHistorique />}
      {ecran === "synthese" && <EcranSynthese />}
    </Stack>
  );
}

function FieldLabel({ children }: { children: string }) {
  return (
    <Text size="small" tone="secondary" weight="medium">
      {children}
    </Text>
  );
}

function EcranChecklist({ onGoto }: { onGoto: () => void }) {
  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <H2>Dossier #7 — Thierry LOUPPE</H2>
        <Row gap={8} align="center">
          <Pill active>INTEGRE</Pill>
          <Text size="small" tone="tertiary">
            Nommer l’agent ne change plus ce statut
          </Text>
        </Row>
      </Stack>

      <Callout tone="info" title="Étape 15 = lien carrière, pas un verrou">
        Obligatoire reste false (absente si stage). Le compteur « restantes »
        filtre sur obligatoire === true. Préremplir agent_id.
      </Callout>

      <H3>Tâches post-intégration</H3>
      <Table
        headers={["Étape", "Tâche", "Obligatoire", "État", "Action"]}
        striped
        rows={[
          ["14", "Affecter l’agent (module carrière)", "Non", "Fait", "—"],
          [
            "15",
            "Nommer l’agent (module carrière)",
            "Non",
            "Non fait",
            <Button variant="primary" onClick={onGoto}>
              Ouvrir la nomination
            </Button>,
          ],
          ["17", "Matériel", "Non", "Non fait", "—"],
        ]}
        rowTone={["success", "warning", "neutral"]}
      />

      <Text size="small" tone="tertiary">
        Étape 15 « fait » seulement s’il existe une nomination active · GET
        /integration/dossiers/7/taches-post-integration
      </Text>
    </Stack>
  );
}

function EcranCreer({ onCreated }: { onCreated: () => void }) {
  const [poste, setPoste] = useCanvasState("nom-poste", "Chef de Service");
  const [type, setType] = useCanvasState(
    "nom-type",
    "App\\Models\\Service",
  );
  const incoherent = poste === "Chef de Bureau" && type !== "App\\Models\\Bureau";

  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <H2>Nouvelle nomination</H2>
        <Text tone="secondary">
          Menu Carrière · ou depuis la checklist (agent prérempli)
        </Text>
      </Stack>

      <Grid columns={2} gap={16}>
        <Stack gap={10}>
          <FieldLabel>Agent</FieldLabel>
          <Select
            value="42"
            options={[
              { value: "42", label: "LOUPPE Thierry — ARTF-2026-000042" },
            ]}
          />
          <FieldLabel>Poste</FieldLabel>
          <Select value={poste} onChange={setPoste} options={POSTES} />
          <FieldLabel>Type de structure</FieldLabel>
          <Select
            value={type}
            onChange={setType}
            options={[
              { value: "App\\Models\\Direction", label: "Direction" },
              { value: "App\\Models\\Service", label: "Service" },
              { value: "App\\Models\\Bureau", label: "Bureau" },
            ]}
          />
        </Stack>
        <Stack gap={10}>
          <FieldLabel>Structure</FieldLabel>
          <Select
            value="3"
            options={[
              { value: "3", label: "Service RH — Direction DRHL" },
              { value: "1", label: "Direction DRHL" },
              { value: "8", label: "Bureau Courrier" },
            ]}
          />
          <FieldLabel>Date de début</FieldLabel>
          <TextInput value="2026-09-01" />
          <FieldLabel>Type d’acte</FieldLabel>
          <Select
            value="decision"
            options={[
              { value: "decision", label: "Décision de nomination" },
              { value: "arrete", label: "Arrêté de nomination" },
              { value: "note_service", label: "Note de service" },
            ]}
          />
        </Stack>
      </Grid>

      {incoherent && (
        <Callout tone="danger" title="422 sur poste">
          Chef de Bureau n’est cohérent qu’avec un Bureau. Même règle : Chef
          de Service → Service, Directeur* → Direction.
        </Callout>
      )}

      <Row gap={8}>
        <Button variant="primary" disabled={incoherent} onClick={onCreated}>
          Créer la nomination
        </Button>
        <Button variant="ghost">Annuler</Button>
      </Row>

      <Text size="small" tone="tertiary">
        POST /carriere/nominations · statut initial en_attente · circuit
        initialisé
      </Text>
    </Stack>
  );
}

function EcranFiche() {
  const [statut, setStatut] = useCanvasState(
    "nom-fiche-statut",
    "en_attente",
  );

  const label =
    statut === "en_attente"
      ? "En attente de validation"
      : statut === "approuvee"
        ? "Approuvée"
        : "Active";

  return (
    <Stack gap={16}>
      <Row align="center">
        <Stack gap={4}>
          <H2>Nomination #4</H2>
          <Text tone="secondary">LOUPPE Thierry · Chef de Service · Service RH</Text>
        </Stack>
        <Spacer />
        <Pill active>{label}</Pill>
      </Row>

      <Grid columns={3} gap={12}>
        <Stat value="2026-09-01" label="Date de début" />
        <Stat value="Décision" label="Type d’acte" />
        <Stat value="Service" label="Structure" />
      </Grid>

      <H3>Circuit (moteur partagé)</H3>
      <Table
        headers={["Niveau", "Validateur", "État"]}
        rows={[
          ["Chef de bureau", "MBEMBA Jean", statut === "en_attente" ? "En cours" : "Approuvé"],
          ["Chef de service", "NGOMA Paul", statut === "en_attente" ? "En attente" : "Approuvé"],
          ["Directeur", "—", statut === "en_attente" ? "En attente" : "Approuvé"],
          ["DRHL", "—", statut === "en_attente" ? "En attente" : "Approuvé"],
          ["Directeur général", "—", statut === "en_attente" ? "En attente" : "Approuvé"],
        ]}
        rowTone={
          statut === "en_attente"
            ? ["info", "neutral", "neutral", "neutral", "neutral"]
            : ["success", "success", "success", "success", "success"]
        }
      />

      <Row gap={8} wrap>
        {statut === "en_attente" && (
          <Button variant="primary" onClick={() => setStatut("approuvee")}>
            Simuler circuit terminé
          </Button>
        )}
        <Button variant="secondary">Rejeter (commentaire ≥ 5 car.)</Button>
        <Button
          variant="primary"
          disabled={statut !== "approuvee"}
          onClick={() => setStatut("active")}
        >
          Activer
        </Button>
      </Row>

      {statut === "en_attente" && (
        <Callout tone="warning" title="Activer grisé">
          Uniquement depuis approuvee. PUT possible tant que en_attente.
        </Callout>
      )}
      {statut === "approuvee" && (
        <Callout tone="info" title="Prêt à activer">
          dossier_integration_id accepté, ignoré. Clôture l’active de la
          structure et celle de l’agent. Le dossier reste INTEGRE.
        </Callout>
      )}
      {statut === "active" && (
        <Callout tone="success" title="Nomination active">
          Une structure = un responsable. Un agent = une nomination active.
          Ensuite : clôturer, télécharger l’acte PDF.
        </Callout>
      )}

      <Text size="small" tone="tertiary">
        POST /integration/validations/{"{id}"}/approuver · POST
        /carriere/nominations/4/activer
      </Text>
    </Stack>
  );
}

function EcranPostes() {
  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <H2>Postes à pourvoir</H2>
        <Text tone="secondary">
          Structures sans nomination active · sert à préremplir le formulaire
        </Text>
      </Stack>

      <Table
        headers={["Type", "Structure", "Postes possibles"]}
        striped
        rows={[
          ["Direction", "Direction Juridique", "DG / Directeur Central / Départemental"],
          ["Service", "Service Paie", "Chef de Service"],
          ["Bureau", "Bureau Courrier", "Chef de Bureau"],
        ]}
      />

      <Text size="small" tone="tertiary">
        GET /carriere/nominations/postes-vacants
      </Text>

      <Divider />

      <Stack gap={4}>
        <H2>Agents sous autorité</H2>
        <Text tone="secondary">
          {"{id}"} = agent chef. Liste via affectation.superieur_hierarchique_id
        </Text>
      </Stack>

      <Card>
        <CardHeader trailing={<Pill size="sm" active>Nomination active</Pill>}>
          NGOMA Paul · Chef de Service · Service RH
        </CardHeader>
        <CardBody>
          <Table
            headers={["Agent", "Affectation", "Date"]}
            striped
            rows={[
              ["LOUPPE Thierry", "Bureau Courrier", "2026-07-01"],
              ["KAYA Aline", "Service RH", "2026-06-15"],
            ]}
          />
        </CardBody>
      </Card>

      <Text size="small" tone="tertiary">
        GET /carriere/nominations/chefs/5/agents-sous-autorite
      </Text>
    </Stack>
  );
}

function EcranHistorique() {
  return (
    <Stack gap={16}>
      <Row align="center">
        <H2>Nominations — LOUPPE Thierry</H2>
        <Spacer />
        <Button variant="secondary">Liste complète</Button>
        <Button variant="primary">Historique (hors active)</Button>
      </Row>

      <Table
        headers={["ID", "Poste", "Structure", "Début", "Fin", "Statut"]}
        striped
        rows={[
          ["2", "Chef de Bureau", "Bureau Courrier", "2025-01-01", "2026-08-31", "Clôturée"],
          ["1", "Chef de Bureau", "Bureau Archives", "2024-03-01", "2024-12-31", "Clôturée"],
        ]}
        rowTone={["neutral", "neutral"]}
      />

      <Callout tone="neutral" title="Deux listes">
        GET …/nominations = tout. GET …/nominations/historique = tout sauf
        active. Ne pas fusionner les deux appels.
      </Callout>

      <H3>Acte PDF</H3>
      <Text tone="secondary">
        Téléchargement <Code>NOM-NOM-2026-0004.pdf</Code> (décision),{" "}
        <Code>ARR-NOM-…</Code> ou <Code>NDS-NOM-…</Code> selon type_acte.
      </Text>
      <Row gap={8}>
        <Button variant="primary">Télécharger l’acte</Button>
      </Row>
      <Text size="small" tone="tertiary">
        GET /carriere/nominations/4/acte
      </Text>
    </Stack>
  );
}

function EcranSynthese() {
  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <H2>Situation de carrière</H2>
        <Text tone="secondary">
          LOUPPE Thierry · ARTF-2026-000042 · actif — pas d’alias /integration
        </Text>
      </Stack>

      <Grid columns={2} gap={12}>
        <Card>
          <CardHeader>Nomination active</CardHeader>
          <CardBody>
            <Stack gap={6}>
              <Text weight="medium">Chef de Service</Text>
              <Text size="small" tone="secondary">
                Service RH · depuis 2026-09-01
              </Text>
            </Stack>
          </CardBody>
        </Card>
        <Card>
          <CardHeader>Affectation active</CardHeader>
          <CardBody>
            <Stack gap={6}>
              <Text weight="medium">Bureau Courrier</Text>
              <Text size="small" tone="secondary">
                Supérieur : NGOMA Paul
              </Text>
            </Stack>
          </CardBody>
        </Card>
        <Card>
          <CardHeader>Contrat actif</CardHeader>
          <CardBody>
            <Text size="small" tone="secondary">
              CDI · 2026-06-01
            </Text>
          </CardBody>
        </Card>
        <Card>
          <CardHeader>Salaire actuel</CardHeader>
          <CardBody>
            <Text size="small" tone="secondary">
              Échelon 2 · montant de base selon grille
            </Text>
          </CardBody>
        </Card>
      </Grid>

      <Callout tone="info" title="GET /carriere/agents/42 uniquement">
        Ne pas appeler GET /integration/agents/42 pour cette synthèse : c’est
        la fiche d’intégration, autre contrat JSON.
      </Callout>
    </Stack>
  );
}
