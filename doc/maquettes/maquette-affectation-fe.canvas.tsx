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
  TextArea,
  TextInput,
  useCanvasState,
  useHostTheme,
} from "cursor/canvas";

type Ecran =
  | "checklist"
  | "unitaire"
  | "groupee"
  | "fiche"
  | "liste";

const ECRANS: { id: Ecran; label: string }[] = [
  { id: "checklist", label: "1. Checklist intégration" },
  { id: "unitaire", label: "2. Un agent" },
  { id: "groupee", label: "3. Plusieurs agents" },
  { id: "fiche", label: "4. Circuit & activation" },
  { id: "liste", label: "5. Liste & PDF" },
];

export default function MaquetteAffectationFe() {
  const [ecran, setEcran] = useCanvasState<Ecran>("ecran", "groupee");

  return (
    <Stack gap={20}>
      <Stack gap={6}>
        <H1>Maquette FE — Affectations & note de service</H1>
        <Text tone="secondary">
          Idée d’écrans, pas le design final. Cliquez les étapes pour voir
          comment RH navigue depuis l’intégration jusqu’au PDF.
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

      {ecran === "checklist" && <EcranChecklist onGoto={() => setEcran("unitaire")} />}
      {ecran === "unitaire" && <EcranUnitaire />}
      {ecran === "groupee" && <EcranGroupee />}
      {ecran === "fiche" && <EcranFiche />}
      {ecran === "liste" && <EcranListe />}
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

function FakeFile({ name }: { name: string }) {
  const theme = useHostTheme();
  return (
    <div
      style={{
        border: `1px dashed ${theme.stroke.primary}`,
        background: theme.fill.tertiary,
        padding: "10px 12px",
      }}
    >
      <Text size="small" weight="medium">
        {name}
      </Text>
      <Text size="small" tone="tertiary">
        PDF · 240 Ko · max 10 Mo
      </Text>
    </div>
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
            L’affectation ne change plus ce statut
          </Text>
        </Row>
      </Stack>

      <Callout tone="info" title="Étape 14 = raccourci, pas un verrou">
        Ne pas bloquer la fin du wizard. Filtrer le compteur sur
        obligatoire === true. Le bouton ouvre Carrière avec agent_id déjà
        renseigné.
      </Callout>

      <H3>Tâches post-intégration</H3>
      <Table
        headers={["Étape", "Tâche", "Obligatoire", "État", "Action"]}
        striped
        rows={[
          ["11", "Générer l’acte", "Oui", "Fait", "—"],
          ["13", "Matricule", "Oui", "Fait", "—"],
          [
            "14",
            "Affecter l’agent (module carrière)",
            "Non",
            "Non fait",
            <Button variant="primary" onClick={onGoto}>
              Ouvrir l’affectation
            </Button>,
          ],
          ["15", "Nommer l’agent (module carrière)", "Non", "Non fait", "Lien"],
          ["17", "Matériel", "Non", "Non fait", "—"],
        ]}
        rowTone={[
          "success",
          "success",
          "warning",
          "neutral",
          "neutral",
        ]}
      />

      <Text size="small" tone="tertiary">
        Source API · GET /integration/dossiers/7/taches-post-integration
      </Text>
    </Stack>
  );
}

function EcranUnitaire() {
  const [type, setType] = useCanvasState("u-type", "App\\Models\\Bureau");
  const [motif, setMotif] = useCanvasState(
    "u-motif",
    "Première affectation suite à recrutement",
  );

  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <H2>Nouvelle affectation</H2>
        <Text tone="secondary">
          Menu Carrière · ou depuis la checklist (agent prérempli)
        </Text>
      </Stack>

      <Row gap={8}>
        <Pill active>Un agent</Pill>
        <Pill>Plusieurs agents</Pill>
      </Row>

      <Grid columns={2} gap={16}>
        <Stack gap={10}>
          <FieldLabel>Agent</FieldLabel>
          <Select
            value="42"
            options={[
              { value: "42", label: "LOUPPE Thierry — ARTF-2026-000042" },
            ]}
          />
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
          <FieldLabel>Structure</FieldLabel>
          <Select
            value="2"
            options={[
              { value: "2", label: "Bureau Courrier — Service RH" },
              { value: "7", label: "Service Paie" },
            ]}
          />
        </Stack>
        <Stack gap={10}>
          <FieldLabel>Date d’effet</FieldLabel>
          <TextInput value="2026-07-01" />
          <FieldLabel>Supérieur hiérarchique (optionnel)</FieldLabel>
          <Select
            value=""
            placeholder="Résolu automatiquement"
            options={[
              { value: "", label: "Résolu automatiquement" },
              { value: "5", label: "MBEMBA Jean — Chef de bureau" },
            ]}
          />
          <FieldLabel>Motif</FieldLabel>
          <TextArea value={motif} onChange={setMotif} rows={3} />
        </Stack>
      </Grid>

      <Stack gap={8}>
        <H3>Note de service (optionnel)</H3>
        <Text size="small" tone="secondary">
          Scan déjà signé. Le PDF officiel se génère plus tard, depuis la
          fiche.
        </Text>
        <FakeFile name="Joindre un PDF, JPG ou PNG" />
      </Stack>

      <Row gap={8}>
        <Button variant="primary">Créer l’affectation</Button>
        <Button variant="ghost">Annuler</Button>
      </Row>

      <Text size="small" tone="tertiary">
        POST /carriere/affectations · multipart · statut initial
        en_attente_validation
      </Text>
    </Stack>
  );
}

function EcranGroupee() {
  const [created, setCreated] = useCanvasState("g-created", false);
  const [motif, setMotif] = useCanvasState(
    "g-motif",
    "Réorganisation trimestrielle — note de service n° 12/DRHL/2026",
  );

  if (created) {
    return (
      <Stack gap={16}>
        <Callout tone="success" title="3 affectations créées">
          Une ligne = une affectation, chacune avec son circuit. La note
          jointe est le même fichier pour tout le lot.
        </Callout>
        <Table
          headers={["ID", "Agent", "Destination", "Supérieur", "Statut"]}
          striped
          rows={[
            ["10", "LOUPPE Thierry", "Bureau Courrier", "MBEMBA Jean", "En attente"],
            ["11", "KAYA Aline", "Service Paie", "NGOMA Paul", "En attente"],
            ["12", "BOUKAKA Marc", "Direction Juridique", "DG", "En attente"],
          ]}
          rowTone={["info", "info", "info"]}
        />
        <Row gap={8}>
          <Button variant="primary" onClick={() => setCreated(false)}>
            Revenir au formulaire
          </Button>
        </Row>
      </Stack>
    );
  }

  return (
    <Stack gap={16}>
      <Stack gap={4}>
        <H2>Affectation groupée</H2>
        <Text tone="secondary">
          Une note de service commune · chaque agent va vers sa propre
          structure
        </Text>
      </Stack>

      <Row gap={8}>
        <Pill>Un agent</Pill>
        <Pill active>Plusieurs agents</Pill>
      </Row>

      <Card>
        <CardHeader trailing={<Pill size="sm">Commun au lot</Pill>}>
          En-tête — date, motif, note
        </CardHeader>
        <CardBody>
          <Grid columns={2} gap={16}>
            <Stack gap={10}>
              <FieldLabel>Date d’effet (tous)</FieldLabel>
              <TextInput value="2026-07-01" />
              <FieldLabel>Motif commun</FieldLabel>
              <TextArea value={motif} onChange={setMotif} rows={3} />
            </Stack>
            <Stack gap={10}>
              <FieldLabel>Note de service (1 fichier pour tous)</FieldLabel>
              <FakeFile name="note-service-lot-12-DRHL.pdf" />
              <Text size="small" tone="tertiary">
                Stockage unique recopié sur chaque affectation
              </Text>
            </Stack>
          </Grid>
        </CardBody>
      </Card>

      <Stack gap={8}>
        <Row align="center">
          <H3>Agents du lot</H3>
          <Spacer />
          <Button variant="secondary">Ajouter un agent</Button>
        </Row>
        <Table
          headers={["Agent", "Type", "Structure", "Supérieur", ""]}
          striped
          rows={[
            [
              "LOUPPE Thierry",
              "Bureau",
              "Bureau Courrier",
              "Auto",
              "Retirer",
            ],
            [
              "KAYA Aline",
              "Service",
              "Service Paie",
              "NGOMA Paul",
              "Retirer",
            ],
            [
              "BOUKAKA Marc",
              "Direction",
              "Direction Juridique",
              "Auto",
              "Retirer",
            ],
          ]}
        />
        <Text size="small" tone="tertiary">
          Même agent deux fois → 422. Structure inexistante → 422 sur
          structurable_id.
        </Text>
      </Stack>

      <Row gap={8}>
        <Button variant="primary" onClick={() => setCreated(true)}>
          Créer 3 affectations
        </Button>
        <Button variant="ghost">Annuler</Button>
      </Row>

      <Text size="small" tone="tertiary">
        POST /carriere/affectations/groupee · multipart agents[i][…]
      </Text>
    </Stack>
  );
}

function EcranFiche() {
  return (
    <Stack gap={16}>
      <Row align="center">
        <Stack gap={4}>
          <H2>Affectation #10</H2>
          <Text tone="secondary">LOUPPE Thierry · Bureau Courrier</Text>
        </Stack>
        <Spacer />
        <Pill active>En attente de validation</Pill>
      </Row>

      <Grid columns={3} gap={12}>
        <Stat value="2026-07-01" label="Date d’effet" />
        <Stat value="MBEMBA Jean" label="Supérieur (auto)" />
        <Stat value="note-service-lot-12-DRHL.pdf" label="Fichier joint" />
      </Grid>

      <H3>Circuit (même mécanique que les dossiers)</H3>
      <Table
        headers={["Niveau", "Validateur", "État"]}
        rows={[
          ["Chef de bureau", "MBEMBA Jean", "Approuvé"],
          ["Chef de service", "NGOMA Paul", "En cours"],
          ["Directeur", "—", "En attente"],
          ["DRHL", "—", "En attente"],
          ["Directeur général", "—", "En attente"],
        ]}
        rowTone={["success", "info", "neutral", "neutral", "neutral"]}
      />

      <Row gap={8} wrap>
        <Button variant="primary">Approuver ce niveau</Button>
        <Button variant="secondary">Rejeter (commentaire obligatoire)</Button>
        <Button disabled>Activer</Button>
      </Row>

      <Callout tone="warning" title="Bouton Activer grisé">
        Visible seulement au statut approuvee (dernier niveau validé).
        dossier_integration_id peut encore être envoyé : l’API l’ignore, le
        dossier reste INTEGRE.
      </Callout>

      <Divider />

      <H3>Après activation</H3>
      <Text tone="secondary">
        L’affectation précédente active de l’agent est clôturée. Actions
        alors : Terminer, télécharger le PDF officiel
        <Code>NS-AFF-2026-0010.pdf</Code>.
      </Text>
    </Stack>
  );
}

function EcranListe() {
  return (
    <Stack gap={16}>
      <Row align="center">
        <H2>Affectations</H2>
        <Spacer />
        <Button variant="secondary">Nouvelle (un agent)</Button>
        <Button variant="primary">Groupée + note</Button>
      </Row>

      <Table
        headers={[
          "",
          "ID",
          "Agent",
          "Structure",
          "Date",
          "Note",
          "Statut",
        ]}
        striped
        rows={[
          ["☑", "10", "LOUPPE Thierry", "Bureau Courrier", "2026-07-01", "Jointe", "En attente"],
          ["☑", "11", "KAYA Aline", "Service Paie", "2026-07-01", "Jointe", "En attente"],
          ["☑", "12", "BOUKAKA Marc", "Dir. Juridique", "2026-07-01", "Jointe", "En attente"],
          ["☐", "8", "NKOUNKOU Léa", "DRHL", "2026-06-12", "PDF généré", "Active"],
        ]}
        rowTone={["info", "info", "info", "success"]}
      />

      <Row gap={8} wrap>
        <Button variant="primary">Générer ZIP (sélection)</Button>
        <Button variant="secondary">PDF unitaire (ligne ouverte)</Button>
      </Row>

      <Callout tone="neutral" title="Deux documents à ne pas confondre">
        Jointe à la création = scan métier commun au lot. Génération =
        un PDF officiel par affectation (écrase le chemin stocké). ZIP max
        50 ids.
      </Callout>

      <Text size="small" tone="tertiary">
        GET /carriere/affectations/{"{id}"}/note-service · POST
        /carriere/affectations/notes-service/lot
      </Text>
    </Stack>
  );
}
