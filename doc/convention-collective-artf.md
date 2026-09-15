# Convention collective de l’ARTF

> **Source juridique unique** pour l’implémentation des modules RH.  
> En cas de conflit avec un plan, un référentiel logiciel ou un extrait antérieur : **cette convention prime**.  
> Extrait avancement déjà exploité : [`convention-artf-avancement.md`](./convention-artf-avancement.md) (art. 60–75).  
> Grille technique actuelle : [`SPEC-GRILLE-SALARIALE.md`](./SPEC-GRILLE-SALARIALE.md).

| | |
|---|---|
| **Employeur** | Agence de régulation des transferts de fonds (ARTF), République du Congo |
| **Date** | Fait à Brazzaville, le **10 janvier 2019** |
| **Signataires** | Sections syndicales (CSTC — secrétaire général) · Administration (directeur général) |
| **Pagination source** | 44 pages numérotées + annexe grille (PDF 51 p.) |
| **Saisie** | 2026-09-15, d’après scans fournis |

---

## Règle d’usage pour l’implémentation

1. Toute règle métier (délais, montants, éligibilité, circuit, position, sanction, prime, congé) se lit **d’abord ici**.
2. Un module ne doit pas inventer un barème « pratique » s’il existe dans la CCN.
3. Les montants « fixés par délibération du comité de direction » restent **paramétrables** (pas de constante magique) ; les **formules et plafonds** de la CCN sont du code métier.
4. Les articles **hors système d’information** (liberté syndicale, CHST, affichage) ne bloquent pas un module, mais ne doivent pas être contredits.

### Lacunes des scans (à compléter si les pages arrivent)

| Page CCN | Contenu manquant |
|---|---|
| **1/44** | Préambule (parties, visas, objet solennel). Les art. 1–4 sont complets. |
| **12/44** | Fin du titre IV CHST : liste des **membres** (art. 45 amorcé) et articles éventuels entre 45 et 46. |
| **46–51** du PDF | Suites éventuelles de l’annexe 2 (une page paysage de la grille a été fournie). |

---

## Index articles → modules API

| Articles | Sujet | Module / préfixe | Notes d’implémentation |
|---|---|---|---|
| 1–4 | Champ, durée, avantages acquis | — | Contexte ; pas d’endpoint. |
| 5–24 | Droit syndical | Hors SI immédiat | Ne pas bloquer un agent pour opinion / syndicat (art. 5, 15, 87). |
| 25–36 | Délégués du personnel | Hors SI immédiat | Protection licenciement art. 36 (à brancher si module rupture). |
| 37–45 | CHST | Hors SI immédiat | Accès registres (hygiène, employeur, visites médicales, MP). |
| 46–50 | Recrutement, réembauche, essai | `/integration`, `/carriere/contrats` | Pièces, essai **1 / 2 / 3 mois** selon classe, renouvelable 1 fois. |
| 51–54 | CDI / CDD / stage | Contrats + `/integration/stages` | CDD pour nécessité de service ; prime transport stagiaires. |
| 55–56 | Salaire de base, primes | `/salaires`, paie (à venir) | Grille indiciaire ; DG/DC/DD hors grille (comité de direction). |
| 57 | Indemnités | Paie / missions | Transport, déplacement, intérim, formation, retraite, missions. |
| 58–59 | Allocations, enfants à charge | Affaires sociales / paie | Max **2** enfants à charge ; arbre de Noël max **3** enfants (0–16 ans). |
| 60–72 | Avancement, notation | `/avancements` | **Livré** — voir extrait dédié. |
| 73–75 | Reclassement, hors classe, reconversion | `/carriere/reclassements` | **Livré**. |
| 76–80 | Positions conventionnelles | `agent.statut` | actif, détachement, disponibilité, position exceptionnelle, drapeau. |
| 81–82 | Rapprochement de conjoints | Carrière (mutation) | Dossier type ; employeur juge l’opportunité. |
| 83–88 | Obligations, secret, non-discrimination | Discipline / dossier | Secret pro = faute disciplinaire possible. |
| 89–91 | Sanctions | `/discipline` | **Livré** — 4 sanctions CCN ; DG seule autorité ; conservation 5 ans. |
| 92–104 | Formation professionnelle | Formation (à venir) | Éligible dès **3 ans** d’ancienneté ; CAMRTF ; débit formation. |
| 105–117 | Résiliation, abandon, démission, licenciement | Carrière / rupture (à venir) | Préavis **1 / 2 / 3 mois** selon classes ; indemnité lic. plafonnée 30 mois. |
| 118–120 | Retraite | Carrière / paie | Âges 57 / 60 / 65 ; report possible 60 / 65 / 70 (loi 22-2010). |
| 121 | Décès | Affaires sociales | Capital décès + 100 000 F/enfant + frais funéraires max 2 000 000 F. |
| 122–127 | Frais médicaux, hospitalisation, évacuation | Affaires sociales (D.3) | Pharma 20 % / 80 % ; hosp. 100 % ; évacuation max 6 mois. |
| 128–137 | Accidents et maladies | Affaires sociales | Suspension contrat ; allocations selon ancienneté. |
| 138–139 | Assurances | Affaires sociales | Santé, vies, invalidité AT ; individuelle mission étranger. |
| 140–149 | Différends | Contentieux (plus tard) | Commission paritaire ; commission de réclamation (emploi / classement). |
| Annexe 1 | Classification 10 classes | Référentiel grades / emplois | Conditions d’accès par diplôme. |
| Annexe 2 | Grille indiciaire 12 échelons | `/salaires` | Point d’indice ; DG/DC/DD hors grille. |

---

## Sommaire

- Titre I — Dispositions générales (art. 1–4)
- Titre II — Liberté d’opinion, droit syndical, bureau de la section syndicale (art. 5–24)
- Titre III — Délégués du personnel (art. 25–36)
- Titre IV — CHST (art. 37–45)
- Titre V — Contrat de travail (art. 46–82)
- Titre VI — Conditions de travail (art. 83–91)
- Titre VII — Formation professionnelle (art. 92–104)
- Titre VIII — Résiliation du contrat (art. 105–121)
- Titre IX — Frais médicaux, pharmaceutiques et hospitalisation (art. 122–127)
- Titre X — Accidents et maladies (art. 128–137)
- Titre XI — Assurances (art. 138–139)
- Titre XII — Différends (art. 140–149)
- Annexe 1 — Classification et conditions d’accès
- Annexe 2 — Grille salariale

---

# TITRE PREMIER — DISPOSITIONS GÉNÉRALES

## Chapitre 1 — Objet et champ d’application

**Article 1 — Objet.**  
La présente convention collective fixe les conditions d’emploi et de travail ainsi que les garanties sociales des salariés de l’ARTF.

**Article 2 — Champ d’application.**  
La présente convention s’applique à tous les salariés de l’ARTF sur toute l’étendue du territoire de la République du Congo, quelles que soient la nature de leur contrat de travail, leur sexe, religion, opinion politique, nationalité ou lieu de recrutement.  
Elle s’applique également de plein droit aux contrats de travail en cours d’exécution au jour de sa prise d’effet, et à ceux conclus postérieurement.

## Chapitre 2 — Date de prise d’effet, durée, dénonciation, révision, dispositions communes, avantages acquis

**Article 3.** La date de prise d’effet, la durée, la dénonciation, la révision, les dispositions communes et les avantages acquis de la présente convention sont déterminés comme suit :

- **Date de prise d’effet.** La présente convention collective prend effet à partir du jour où son dépôt aura été effectué au greffe du tribunal du travail.
- **Durée.** Conclue pour une **durée indéterminée**.
- **Dénonciation.** Peut être dénoncée au plus tôt **deux (2) ans** après sa signature, sous réserve d’un préavis de **trois (3) mois** donné par lettre recommandée par la partie qui en a l’initiative.
- **Révision.** Peut être révisée au plus tôt un an après sa signature, sous réserve d’un préavis de **trois (3) mois** donné par lettre recommandée à l’autre partie estimant porter des modifications.
- **Dispositions communes.** Les parties s’interdisent d’avoir recours au lock-out ou à la grève pendant le préavis de dénonciation, le préavis de révision, ainsi que pendant les pourparlers qui y sont consécutifs pour les motifs touchant à l’objet même de la dénonciation ou de la révision. Qu’il s’agisse de dénonciation ou de révision, la présente convention collective restera en vigueur jusqu’à la date d’application de la nouvelle convention ou des nouvelles dispositions conclues à la suite de la dénonciation ou de la révision formalisée par l’une des parties.

**Article 4 — Avantages acquis.**  
La présente convention ne peut en aucun cas être la cause de restriction aux avantages collectifs ou individuels acquis par les travailleurs en service à la date de son application.

Le maintien de ces avantages jouera pour le personnel en service à l’agence de régulation des transferts de fonds ainsi que pour les travailleurs retraités dont les situations administratives ne sont pas régularisées.

Les contrats individuels de travail qui interviendront postérieurement à sa signature seront soumis à ces dispositions et aucune restriction ne pourra être insérée valablement dans lesdits contrats individuels.

---

# TITRE II — LIBERTÉ D’OPINION, EXERCICE DU DROIT SYNDICAL, BUREAU DE LA SECTION SYNDICALE

## Chapitre 1 — Liberté d’opinion

**Article 5.** Le respect réciproque de la liberté syndicale obéit aux dispositions suivantes :

1. Les parties contractantes reconnaissent la liberté d’opinion ainsi que le droit d’adhérer librement et d’appartenir à un syndicat professionnel constitué en vertu de l’article 184 nouveau de la loi n° 6-96 du 6 mars 1996, modifiant et complétant certaines dispositions de la loi n° 45/75 du 15 mars 1975 instituant un code du travail de la République Populaire du Congo.
2. En vue de permettre le libre exercice de ce droit, l’employeur s’engage à ne pas prendre en considération le fait d’appartenir ou non à un syndicat, les opinions politiques ou philosophiques, les croyances religieuses ou les origines du travailleur, pour arrêter les décisions en ce qui concerne l’embauche, la conduite ou la répartition du travail, les mesures de discipline, le congédiement et l’avancement.
3. Dans le même but, les travailleurs s’engagent à ne pas prendre en considération dans l’exécution du travail, l’appartenance ou la non appartenance syndicale des autres travailleurs.
4. Les parties contractantes qui considèrent que l’établissement est essentiellement un lieu de travail, veilleront à la stricte observation des engagements ci-dessus et s’emploieront auprès de leurs adhérents à en assurer le respect intégral.

## Chapitre 2 — Exercice du droit syndical

**Article 6.** L’exercice du droit syndical ne doit pas avoir pour conséquence des actes contraires aux lois, règlements et usages, notamment ceux auxquels se réfèrent la présente convention et la profession. Au sein de l’agence de régulation des transferts de fonds, le libre exercice du droit syndical est reconnu aux salariés, dans le respect des droits et libertés garantis par la constitution.

**Article 7.** L’activité syndicale à l’agence de régulation des transferts de fonds s’exerce par l’intermédiaire des sections syndicales.

**Article 8.** Les travailleurs peuvent participer aux travaux des commissions paritaires et autres activités syndicales dont la date de réunion, le nom des membres et l’objet auront été arrêtés d’un commun accord par les parties intéressées, obtiendront des autorisations d’absence payées comme temps de travail effectif dans la limite stricte de l’objet des travaux. Quand la date d’une réunion sera fixée, le bureau de la section syndicale et les délégués du personnel feront connaître les noms des participants.

**Article 9.** Des autorisations d’absence seront accordées dans les mêmes conditions aux travailleurs appelés en qualité de consultants à participer aux travaux des organismes prévus en vertu des textes législatifs ou réglementaires (commission nationale consultative du travail, commission nationale technique d’hygiène, de sécurité du travail et de prévention des risques professionnels, etc.) ou devant siéger comme assesseurs au tribunal du travail.

## Chapitre 3 — Bureau de la section syndicale

**Article 10.** Les membres du bureau de la section syndicale sont élus conformément aux textes en vigueur.

**Article 11.** Seuls les syndicats ayant au moins un délégué du personnel sont autorisés à disposer d’une section syndicale au sein de l’agence de régulation des transferts de fonds, conformément aux dispositions de l’arrêté n° 1109/MTFPSS-DGT du 24 juin 1996 relatif aux représentants syndicaux et à l’exercice de l’activité syndicale dans les entreprises.

### Section 1 — Fonctionnement du bureau

**Article 12.** Pour l’exercice de leurs fonctions, les membres du bureau de la section syndicale disposent de **20 heures** par mois considérées et rémunérées comme temps de service. Les adhérents de chaque section syndicale peuvent se réunir dans l’enceinte de l’agence de régulation des transferts de fonds, une fois par mois, en dehors des heures de travail, suivant des modalités fixées en accord avec le directeur général ou son représentant.

Toutefois, ils peuvent se réunir plus d’une fois lorsque la situation l’exige, notamment pendant les périodes des élections professionnelles, après en avoir informé l’employeur.

**Article 13.** La section syndicale peut inviter toute personnalité extérieure à participer à des réunions syndicales qu’elle organise dans le local mis à sa disposition, sous réserve de l’accord du chef d’établissement.

**Article 14.** Tout licenciement d’un membre du bureau de la section syndicale doit être soumis à la décision de la commission des litiges prévue à l’article 39 du code du travail et obéir à la procédure édictée à l’article 176 dudit code.

Ces dispositions sont applicables aux anciens membres de la section syndicale pendant une durée de **six (6) mois** à partir de l’expiration du mandat.

**Article 15.** La qualité de membre du bureau de la section syndicale ne peut constituer un obstacle à l’avancement ou à l’amélioration de la rémunération du travailleur.

**Article 16.** La direction est tenue de mettre à la disposition des sections syndicales pour l’exercice de leurs activités un local meublé.

### Section 2 — Missions de la section syndicale

**Article 17.** La section syndicale assure la représentation du syndicat au sein de l’établissement. Elle a pour missions :

- l’affichage des communications syndicales ;
- la publication et la diffusion des informations syndicales ;
- la collecte des cotisations à l’intérieur de l’établissement ;
- la tenue des réunions périodiques avec ses adhérents dans l’établissement ;
- l’organisation de la campagne électorale aux fins des élections syndicales.

**Article 18.** Le représentant syndical bénéficie de la protection légale définie par l’article 176 (nouveau) de la loi 06-96 du 06 mars 1996, à l’exception des dispositions relatives au maintien du salaire pendant la procédure judiciaire.

### Section 3 — Représentants syndicaux

**Article 19.** Seules les organisations syndicales ayant au moins un délégué du personnel bénéficient du droit de désignation des représentants syndicaux au sein de l’établissement.

**Article 20.** Les représentants syndicaux sont désignés par les organisations syndicales. Le syndicat notifie les noms de ses représentants à l’employeur, soit par lettre recommandée avec accusé de réception, soit par lettre remise au chef d’entreprise contre récépissé. Le nombre des représentants syndicaux est déterminé par les dispositions réglementaires en vigueur.

**Article 21.** Les représentants syndicaux siègent au comité de direction selon le quota alloué aux syndicats et avec la qualité qui leur est reconnue par les statuts de l’établissement.

### Section 4 — Modalités et moyens d’exercice de l’activité syndicale

**Article 22.** L’affichage des communications syndicales s’effectue librement, soit sur des emplacements obligatoirement prévus par l’employeur, soit sur les panneaux destinés aux communications des délégués du personnel. Ces emplacements ou ces panneaux sont à la disposition de toutes les sections syndicales. Les conditions de leur utilisation par elles sont fixées en accord avec le chef d’établissement.

**Article 23.** Le contenu de la communication syndicale doit correspondre aux objectifs des syndicats professionnels tels que définis à l’article 184 nouveau de la loi n° 6-96 du 6 mars 1996.

**Article 24.** L’employeur ne peut s’opposer préalablement à l’affichage des communications dont le contenu lui paraît contraire aux objectifs du syndicat, ou présente un caractère injurieux. Il lui est cependant reconnu le droit d’intenter un recours devant le juge des référés en vue d’obtenir le retrait de l’affiche litigieuse.

---

# TITRE III — DÉLÉGUÉS DU PERSONNEL

## Chapitre 1 — Élection des délégués

**Article 25.** Conformément à l’article 173 (nouveau) de la loi n° 6-96 du 6 mars 1996, la représentation des travailleurs au sein de l’agence de régulation des transferts de fonds « ARTF » est assurée par les délégués du personnel.

**Article 26.** Les délégués du personnel sont élus au sein de chaque établissement sur les listes établies par les organisations syndicales, à défaut par les membres du personnel eux-mêmes.

**Article 27.** Le nombre des collèges électoraux et la répartition des sièges entre les différentes catégories font l’objet d’un accord entre le chef d’établissement et les organisations syndicales.

**Article 28.** Une commission des opérations électorales des délégués du personnel est instituée par l’employeur.  
Les conditions du mode d’élection sont fixées par l’arrêté n° 1110/MTFPSS/DGT du 24 juin 1996.

## Chapitre 2 — Exercice de la fonction de délégué du personnel

**Article 29.** Le chef d’établissement est tenu de laisser aux délégués titulaires **20 heures** de liberté par mois et de mettre à leur disposition un local meublé pour l’exercice de leurs fonctions. Ce temps peut être augmenté en cas de circonstances exceptionnelles.

**Article 30.** Les heures de liberté accordées aux délégués du personnel sont payées comme temps de travail et rémunérées au tarif normal s’il est pris d’accord parties en dehors de la durée légale. Elles doivent être utilisées exclusivement aux tâches afférentes à l’activité du délégué du personnel telles qu’elles ont été définies à l’article 177 (nouveau) de la loi n° 6-96 du 6 mars 1996.

**Article 31.** Les délégués du personnel peuvent pendant les heures de délégation se déplacer librement à l’intérieur de l’établissement. Cependant, ils ne sont pas autorisés à provoquer un arrêt de travail des autres salariés.

**Article 32.** Les délégués du personnel ont le droit de sortir de l’établissement pour remplir leur mandat. Toutefois, ils doivent informer le chef d’établissement de leur sortie et justifier de l’accomplissement de leurs missions pour obtenir le paiement des heures passées à l’extérieur.

Lorsque le crédit d’heures est utilisé par le délégué personnel à des fins étrangères à son mandat, l’employeur est en droit de lui exiger le remboursement des sommes indûment perçues.

**Article 33.** Les conditions d’exercice des fonctions de délégué du personnel sont régies par l’arrêté n° 1110/MTFPSS-DGT du 24 juin 1996.

## Chapitre 3 — Missions des délégués du personnel

**Article 34.** Les délégués du personnel ont pour missions :

- de présenter à l’employeur toutes les réclamations individuelles ou collectives qui n’auraient pas été directement satisfaites concernant les conditions de travail et la protection des travailleurs, l’application de la présente convention, des classifications professionnelles et des salaires ;
- de donner leur préalable avis pour tout licenciement collectif ou individuel motivé par une diminution de l’activité de l’établissement ou par une réorganisation intérieure et selon la procédure fixée par l’article 39 du code du travail ;
- de saisir l’inspection du travail et des lois sociales de toute plainte ou réclamation concernant l’application des prescriptions légales ou réglementaires dont elle est chargée d’assurer le contrôle ;
- de veiller à l’application des prescriptions relatives à l’hygiène et à la santé des travailleurs et à la sécurité sociale et de proposer toutes mesures utiles à ce sujet ;
- de communiquer à l’employeur toute suggestion utile tendant à l’amélioration de l’organisation et du rendement de l’établissement.

## Chapitre 4 — Révocation et radiation des délégués du personnel

**Article 35.** Tout délégué du personnel peut être révoqué en cours de mandat sur proposition de l’organisation syndicale qui l’a présenté, approuvée au scrutin secret par la majorité du collège électoral auquel il appartient. S’il n’a pas été présenté par une organisation syndicale, il peut être révoqué en cours de mandat sur pétition écrite signée de la majorité du collège électoral auquel il appartient et confirmée au scrutin secret par la majorité du collège.

Dans ce cas, le délégué titulaire est remplacé par le délégué suppléant.

**Article 36.** Tout licenciement d’un délégué du personnel envisagé par l’employeur doit être soumis à la commission des litiges prévue à l’article 39 du code du travail, et obéir à la procédure édictée à l’article 176 dudit code.

La protection ci-dessus s’applique :

- aux anciens délégués du personnel pendant une durée de **six (6) mois** à partir de l’expiration du mandat ;
- aux candidats aux fonctions de délégué du personnel pendant la durée comprise entre la date de remise des listes de candidature au chef d’entreprise ou d’établissement et celle du scrutin ;
- aux candidats déclarés non élus pendant les **trois (3) mois** qui suivent la date du scrutin.

---

# TITRE IV — COMITÉ D’HYGIÈNE ET DE SÉCURITÉ AU TRAVAIL

**Article 37.** Il est institué au sein de l’agence de régulation des transferts de fonds un comité d’hygiène et de sécurité au travail, en sigle « **CHST** ».

## Chapitre 1 — Champ d’action

**Article 38 — Hygiène et sécurité au travail.**  
Le CHST a pour mission de contribuer à la prévention des risques liés à la santé, à la sécurité au sein de l’agence de régulation des transferts de fonds. À cet effet, le CHST :

- définit la politique de prévention des risques professionnels dans l’agence et celle de l’amélioration du milieu de travail ;
- procède à l’analyse des risques professionnels ;
- procède à intervalles réguliers à des inspections ;
- effectue des enquêtes en matière d’accidents de travail ou de maladies professionnelles ou à caractère professionnel ;
- contribue à la promotion de la prévention des risques professionnels et suscite toute initiative qu’il estime utile dans cette perspective ;
- veille à l’observation des prescriptions légales et réglementaires prises en ces matières ;
- développe une politique de qualité avec pour objet de déterminer, d’organiser, d’évaluer et d’améliorer de manière systématique, la qualité des services, la qualité de vie au travail, ainsi que le fonctionnement des services de l’agence.

**Article 39 — Conditions de travail.**  
Le CHST a en outre pour mission, de contribuer à l’amélioration des conditions de vie au travail.  
À ce titre, il est associé à la recherche des solutions concernant :

- l’organisation matérielle du travail (charge de travail, rythme, pénibilité des tâches, élargissement et enrichissement des tâches) ;
- l’environnement de travail (éclairage, aération, nuisance sonore, poussière, vibration) ;
- l’aménagement des postes de travail et leur adaptation à l’homme en vue notamment de réduire le travail monotone ou sous cadence ;
- l’aménagement des lieux de travail et leurs annexes ;
- la durée et les horaires de travail ;
- l’aménagement du temps de travail (travail de nuit, travail posté) ;
- la lutte contre le tabagisme ;
- la lutte contre la consommation de drogues ;
- la protection contre le harcèlement sexuel.

## Chapitre 2 — Rôle de prévention

**Article 40.** Le CHST doit recevoir de l’employeur toutes les informations qui lui sont nécessaires pour exercer ses missions.

L’employeur doit informer les membres du CHST des visites de l’inspecteur du travail. Ceux-ci doivent présenter leurs observations.  
Sur rapport du CHST, l’employeur peut saisir l’autorité compétente sur les activités d’un établissement voisin qui exposent les salariés à des nuisances particulières.

**Article 41.** Les membres du CHST ont accès à différents registres tenus et conservés dans l’agence, à savoir :

- le registre d’hygiène et de sécurité ;
- le registre d’employeur ;
- le registre des visites médicales ;
- le registre des maladies professionnelles.

**Article 42.** Le CHST peut confier à ses membres des missions particulières en relation avec l’hygiène, la sécurité et les conditions de travail dans l’agence.

**Article 43.** Le CHST peut faire effectuer par un ou plusieurs de ses membres, des analyses relatives aux risques professionnels et aux conditions de travail dans l’agence.

**Article 44.** Le CHST peut proposer à l’employeur des actions en matière de prévention.

## Chapitre 3 — Composition et fonctionnement

**Article 45.** Le CHST est composé comme suit :

- président (l’employeur ou son représentant) ;
- membres : *(liste non fournie — page 12/44 manquante)*

---

# TITRE V — CONTRAT DE TRAVAIL

## Chapitre 1 — Embauche

**Article 46 — Conditions de recrutement.**  
Les conditions de recrutement sont définies par l’employeur conformément aux dispositions légales en vigueur.

Le postulant à un emploi à l’ARTF fournit les pièces justificatives suivantes :

- une demande manuscrite ;
- un extrait d’acte de naissance ;
- un casier judiciaire ;
- un certificat de nationalité ;
- un extrait d’acte de mariage (le cas échéant) ;
- un curriculum vitae ;
- les diplômes ou équivalences reconnus par les pouvoirs publics à l’emploi sollicité ;
- un certificat médical ;
- un récépissé de l’agence congolaise pour l’emploi (ex office national de l’emploi et de la main d’œuvre).

Le candidat ayant déjà servi chez un précédent employeur doit joindre en plus :

- la carte de travail ;
- le certificat de travail délivré par le précédent employeur ;
- le numéro d’immatriculation à l’organisme de sécurité sociale.

**Article 47.** L’employeur est tenu de faire immatriculer le salarié à l’organisme de sécurité sociale et de lui faire délivrer une carte de travail.

**Article 48 — Réembauche.**  
Le salarié licencié par suite d’une diminution de l’activité économique ou d’une réorganisation interne est prioritaire pendant **deux (2) ans** suivant la date de licenciement, pour être réembauché dans les mêmes conditions qu’au moment de son licenciement.

À cet effet, lors de ce licenciement, le salarié doit communiquer par écrit à son employeur, les adresses successives de sa résidence et les moyens de le joindre.  
Passé ce délai, il continue à bénéficier d’une nouvelle année sous réserve d’un nouvel essai professionnel.

**Article 49 — Essai.**  
Une période d’essai stipulée obligatoirement par écrit est prévue à l’engagement des salariés. La durée est variable suivant les classes ci-après :

| Classes | Durée d’essai |
|---|---|
| 1 à 4 | **1 mois** |
| 5 et 6 | **2 mois** |
| 7 à 10 | **3 mois** |

Pendant la période d’essai, les parties ont la faculté réciproque de rompre le contrat sans préavis ni indemnité. Durant cette période, le salarié perçoit le traitement minimum de la classe dont relève l’emploi.

Cette période d’essai est **renouvelable une fois**.

Lorsque l’essai est concluant, l’engagement du salarié est définitif et fait l’objet de la signature par les parties au contrat de travail.

En cas de rupture du contrat pendant la période d’essai par la volonté de l’une des parties, le coût du voyage retour du salarié du lieu d’emploi à la localité de provenance, stipulé au contrat de travail, est dû par l’employeur conformément à la législation en vigueur.

**Article 50 — Essai en vue de l’occupation d’un emploi supérieur.**  
En cas de poste à pourvoir au sein de l’agence, l’employeur fait appel en priorité aux salariés en service.

Si le poste vacant relève d’une catégorie supérieure, le postulant peut être soumis à la période d’essai correspondante ; si l’essai n’est pas concluant, le salarié est rétabli dans son précédent emploi aux conditions antérieures, sans que cela soit considéré comme une rétrogradation.

## Chapitre 2 — Contrat de travail

**Article 51.** Pour l’exercice de son activité et selon les besoins, l’employeur entend faire usage de différents types de contrats prévus par la législation en vigueur.

**Article 52 — Contrat à durée indéterminée.**  
L’embauche doit être constatée par une lettre d’engagement qui sera suivie d’un contrat de travail en bonne et due forme dans un délai de **30 jours** ouvrables à compter de la prise de service.

Le contrat de travail doit spécifier l’emploi, la qualification et le classement du salarié, sa rémunération ainsi que divers avantages individuels dont il peut éventuellement bénéficier.

Le contrat de travail portera les mentions suivantes :

- noms et prénoms ;
- nationalité ;
- date et lieu de naissance ;
- sexe ;
- situation matrimoniale ;
- date et lieu de recrutement ;
- conditions et durée de la période d’essai ;
- emploi ;
- rémunération ;
- lieu de travail.

En l’absence d’écrit, le contrat de travail est réputé être conclu pour une durée indéterminée et l’engagement du travailleur est considéré comme définitif dès le jour de sa prise de service.

Lors de sa prise de fonction, tout salarié doit obligatoirement prendre connaissance du règlement intérieur de l’agence et de la présente convention. À cet effet, le règlement intérieur doit être affiché à une place convenable et accessible, notamment dans les lieux où le travail est effectué, ainsi que dans le local administratif où s’effectue l’embauche.

**Article 53 — Contrat à durée déterminée.**  
L’employeur peut, pour des nécessités de service, embaucher des salariés suivant le régime du contrat de travail à durée déterminée.

**Article 54 — Stage.**  
Des mesures appropriées seront déterminées par l’employeur en ce qui concerne le stage, en conformité avec les textes en vigueur.  
Une prime de transport d’un montant défini par l’employeur peut être allouée aux stagiaires soumis au régime de travail d’un salarié.

## Chapitre 3 — Rémunération

**Article 55 — Salaire de base.**  
Le salaire est la contrepartie du travail fourni. À ce titre, le salaire n’est pas dû en cas d’absence, sauf pour les cas reconnus par la réglementation en vigueur et/ou par la présente convention.

La rémunération des salariés correspond à chaque classe conformément à la **grille indiciaire en annexe**.

La rémunération du **directeur général**, des **directeurs centraux** et des **directeurs départementaux** est fixée par délibération prise en comité de direction.

**Article 56 — Primes.**  
Les primes ci-après sont allouées aux salariés en activité visés par la présente convention.

### Prime de responsabilité

Montant fixé par délibération du comité de direction. Attribuée aux :

- chefs de bureau ;
- chefs de service ;
- chefs de service rattaché ;
- directeurs départementaux ;
- directeurs centraux ;
- directeur général.

### Prime d’ancienneté

Majoration attribuée dans les conditions suivantes :

- **2 %** du salaire de base du salarié après **deux (2) ans** de présence ;
- **+ 1 %** du salaire de base de la classe du salarié **par année** de présence.

L’ensemble de la majoration pour ancienneté **ne peut excéder 40 %**.

### Prime de représentation

Montant fixé par délibération du comité de direction. Attribuée aux **directeurs départementaux**.

### Prime de fin d’année

Attribuée en totalité aux salariés de l’agence de régulation des transferts de fonds ayant travaillé jusqu’au **31 décembre** de l’année considérée.

Le montant de cette prime est égal au **salaire de base du dernier mois majoré de la prime d’ancienneté** pour le personnel ayant au moins **un an** de présence dans l’établissement.

Les salariés admis à la retraite en cours d’année bénéficient de la totalité de cette prime.

Le salarié dont la position de détachement, de disponibilité ou la position spéciale intervient au cours de l’année, a droit à cette prime calculée **au prorata temporis**. Il en est de même du salarié dont la rupture de contrat intervient au cours de l’année, **sauf cas de licenciement pour faute lourde**.

Les salariés qui auront écopé d’une **mise à pied** au cours de l’année pourront se voir refuser cette prime.

### Prime de risque

Montant fixé par délibération du comité de direction. Allouée aux :

- chauffeurs ;
- plantons ;
- veilleurs de nuit ;
- convoyeurs des fonds.

### Prime vestimentaire

Montant fixé par délibération du comité de direction. Attribuée **semestriellement** à tous les salariés en service.

### Prime de caisse

Montant fixé par délibération du comité de direction. Attribuée **mensuellement** au caissier.

### Prime de panier

Tout salarié retenu à son poste de travail au-delà des heures normales de service perçoit une prime journalière de panier dont le montant est fixé par délibération du comité de direction.

### Prime d’astreinte

Montant fixé par délibération du comité de direction. Allouée à tout salarié retenu pour nécessités de service.  
**Cette prime ne peut être cumulée aux heures supplémentaires.**

### Prime de logement

Montant fixé par délibération du comité de direction. Attribuée à tout salarié affecté pour nécessités de service.

### Primes exceptionnelles

Peuvent être attribuées aux salariés. Elles ont un caractère d’encouragement pour un travail réalisé ou un comportement particulièrement méritant dans une situation non ordinaire.  
La détermination de leur montant est fixée par **note de service du directeur général**.

**Article 57 — Indemnités.**  
Les indemnités ci-après sont allouées aux salariés visés par la présente convention.

### Indemnité de transport

Lorsque le transport du personnel n’est pas assuré de façon collective par l’employeur, une indemnité de transport est allouée mensuellement aux salariés en service. Le montant est fixé par délibération du comité de direction.

### Indemnité de déplacement

**1) Indemnité de déplacement pour affectation ou cessation d’activité** — franchise de bagages :

| | Salarié | Conjoint(e) | Enfant à charge |
|---|---|---|---|
| Voie aérienne | 100 kg | 50 kg | 30 kg |
| Voie fluviale | 250 kg | 100 kg | 100 kg |
| Voie ferrée | 1 000 kg | 250 kg | 100 kg |
| Voie routière | 1 000 kg | 250 kg | 100 kg |

Lorsque l’affectation est effectuée pour convenance personnelle du salarié, l’employeur prend en charge les titres de transport de sa famille.  
Les frais de transport des bagages sont à sa propre charge.

**2) Indemnité de déplacement pour congés annuels.**  
Le salarié bénéficiaire de congés annuels perçoit une indemnité de transport dont les conditions et les montants sont fixés par délibération du comité de direction.

### Intérim

Tout salarié, à quelque classe qu’il appartienne, assurant sur décision du directeur général l’intérim d’une fonction, percevra à partir du **premier mois** de l’intérim une indemnité mensuelle due à ladite fonction.

Cette indemnité ne peut être cumulée à celle perçue pour une fonction similaire.

À l’expiration d’une période maximum de **six (6) mois**, sauf si l’intérim résulte d’une maladie ou d’un accident de travail, le salarié sera réintégré dans ses fonctions d’origine, ou confirmé au poste dont il assure l’intérim.

### Indemnité de formation

Le salarié admis à suivre une formation à l’étranger perçoit en sus de son salaire une indemnité mensuelle forfaitaire fixée comme suit :

- **Afrique :** 85 % du traitement de base ;
- **Reste du monde :** SMIG du pays d’accueil.

Toutefois, les taux fixés ci-dessus peuvent faire l’objet d’un réaménagement par l’employeur en fonction du niveau de vie du pays d’accueil.

Le salarié bénéficiaire d’un stage de formation sur le territoire national perçoit une indemnité forfaitaire de stage fixée par délibération du comité de direction.

### Indemnité d’admission à la retraite

Au moment de son admission à la retraite, le salarié bénéficie d’une indemnité en considération de son ancienneté à l’ARTF. Cette indemnité est calculée selon les conditions fixées par la présente convention (art. 119).

### Indemnités de mission et de séminaire

**1) Missions et séminaires locaux.**  
Des frais de missions sont alloués à tout salarié astreint par des obligations professionnelles à un déplacement occasionnel et temporaire hors de son lieu de travail habituel.

Taux journaliers de déplacement à l’intérieur du pays (Francs CFA) :

| Catégorie | Bénéficiaires | Centres urbains et chefs-lieux des départements | Localités secondaires |
|---|---|---|---|
| 1re | Directeur général | 250 000 | 200 000 |
| 2e | Directeurs centraux, directeurs départementaux | 200 000 | 150 000 |
| 3e | Chefs de service, chefs de bureau, salarié dont l’indice ≥ 1 150 | 150 000 | 100 000 |
| 4e | Salarié dont l’indice &lt; 1 150 | 100 000 | 80 000 |

La location des véhicules pour le directeur général, les directeurs centraux et départementaux ainsi que les chefs de service et de bureau sera à la charge de l’ARTF si les conditions de réalisation de la mission l’exigent.

La durée d’une mission normale **ne peut excéder quinze (15) jours**, sauf prolongation expresse du directeur général.

**2) Missions à l’étranger.**  
Les salariés en mission ou admis à suivre un séminaire à l’étranger perçoivent une indemnité journalière forfaitaire :

| Catégorie | Bénéficiaires | Afrique | Autres continents |
|---|---|---|---|
| 1re | Directeur général | 400 000 | 500 000 |
| 2e | Directeurs centraux, directeurs départementaux | 350 000 | 450 000 |
| 3e | autres salariés | 300 000 | 400 000 |

La durée d’une mission à l’extérieur est régie par les textes en vigueur.

**Article 58 — Allocations.**

- **Allocation rentrée scolaire.** Tout salarié bénéficie avant la rentrée scolaire d’une prime dite « allocation rentrée scolaire » dont le montant est fixé par délibération du comité de direction.
- **Allocation de consommation domestique.** Montant fixé par délibération du comité de direction. Attribuée aux **directeurs centraux et départementaux**.
- **Arbre de Noël.** Tout salarié bénéficie à la fin de l’année d’une prime dite « arbre de Noël » dont le montant est fixé par délibération du comité de direction. Le nombre d’enfants est limité à **trois (3)** ; leur âge est compris entre **0 et 16 ans**. Une prime forfaitaire correspondant à la part d’un enfant est attribuée aux salariés n’ayant pas d’enfant.

**Article 59 — Allocations familiales.**

- **Taux.** Le taux des allocations est celui fixé par les textes en vigueur. Elles sont versées mensuellement aux enfants pris en charge par l’ARTF.
- **Supplément familial.** Il est alloué à chaque salarié un supplément familial de traitement fixé par les textes en vigueur.
- **Enfants à charge.** Il s’agit des enfants de moins de **16 ans** visés ci-dessous dont le salarié assume d’une manière générale le logement, la nutrition, l’habillement et l’éducation, à savoir :
  - les enfants issus du mariage de l’état civil du salarié ;
  - les enfants naturels du salarié reconnus lors de l’inscription à l’état civil ou par décision de justice ;
  - les enfants ayant fait l’objet d’une adoption par le salarié conformément aux dispositions du code de la famille congolaise ou d’une légitimation en conformité avec les règles dudit code ;
  - les enfants ayant fait l’objet d’un jugement de tutelle confiant leur garde au salarié, dont le nombre maximum est fixé à **deux (2) enfants**.

La limite d’âge de 16 ans est portée à **17 ans** pour l’enfant placé en apprentissage, à **21 ans** s’il poursuit ses études secondaires ou supérieures ou si, par suite d’infirmité ou de maladie incurable, il est dans l’impossibilité permanente de se livrer à l’exercice d’une activité professionnelle.

## Chapitre 4 — Avancement

**Article 60.** L’avancement est le passage d’un **échelon inférieur à un échelon supérieur au sein d’une même classe**.

**Article 61.** L’avancement récompense le mérite et l’expérience acquis du fait de l’ancienneté et de la formation.

Le processus d’avancement des salariés inscrits au tableau d’avancement à l’ARTF connaît **trois (3) étapes concomitantes** :

- l’évaluation des salariés par la notation ;
- la commission préparatoire à l’avancement ;
- la commission d’avancement.

**Article 62 — Évaluation / notation.**  
Tout salarié en activité et affecté à un poste de travail est noté **tous les 24 mois** en fonction de sa manière de servir, d’être et de ses performances.

Le salarié muté ou affecté en cours d’année est noté au poste de travail où il a **servi le plus longtemps**.

**Article 63.** Une fiche individuelle d’évaluation instituée matérialise la notation. Elle est signée d’une part par les différents notateurs indiqués à l’article 64 ci-dessous et d’autre part par le salarié.

**Article 64.** Les autorités investies du pouvoir de notation sont les chefs hiérarchiques, à savoir :

- les chefs de bureau ;
- les chefs de service ;
- les directeurs départementaux ;
- les directeurs centraux ;
- le directeur général.

**Article 65.** Est exempté de la notation tout salarié en position de **stage**, de **détachement** et en **position exceptionnelle**.

La note attribuée doit être mise à la connaissance du salarié pour des réclamations éventuelles.  
Les réclamations se feront dans les **72 heures** qui suivront.

**Article 66 — Commission préparatoire à l’avancement.**  
Il est institué une commission préparatoire à l’avancement composée des représentants de l’administration, des délégués du personnel, des représentants syndicaux et des représentants de la direction départementale du travail.

Une note de service du directeur général institue la commission préparatoire à l’avancement.

**Article 67.** La commission préparatoire à l’avancement a pour mission :

- de rechercher les moyens d’améliorer les procédures de décisions de la commission d’avancement ;
- d’apprécier les motivations des notateurs ;
- de veiller à la recherche et au maintien de la cohérence entre les appréciations et les notes ;
- d’adresser à la commission d’avancement une note de synthèse sur les points ayant fait l’objet d’un débat général.

**Article 68.** La commission préparatoire à l’avancement est présidée par le directeur général.

**Article 69 — Commission d’avancement.**  
Une commission d’avancement placée sous la présidence du directeur général, composée des représentants de l’administration, des partenaires sociaux et des représentants de la direction départementale du travail.

La structuration de la commission d’avancement relève d’une note de service du directeur général.

**Article 70.** La note à l’avancement est déterminée à chaque session de la commission d’avancement.

**Article 71 — Avancement automatique.**  
Une bonification de **deux (2) échelons** est accordée à tout salarié ayant suivi un stage d’au moins **neuf (9) mois** autorisé par l’employeur sur présentation d’un certificat ou d’une attestation de fin de stage.

**Article 72 — Avancement exceptionnel.**  
La commission d’avancement, sur proposition du directeur général, conserve toute latitude de faire bénéficier à un salarié un avancement exceptionnel.

Cet avancement ne peut porter que sur le bénéfice de **deux (2) échelons** au plus.

## Chapitre 5 — Reclassement, hors classe et reconversion

**Article 73.** Tout salarié est reclassé à une classe supérieure à l’issue d’une formation autorisée par l’employeur et sanctionnée par un diplôme reconnu par l’État.

**Article 74 — Reclassement exceptionnel.**  
Tout salarié ayant atteint **50 ans** d’âge, **15 ans** d’ancienneté et **trois (3) ans** dans la même classe bénéficie d’un reclassement exceptionnel.

Tout salarié ayant atteint une ancienneté de **25 ans** au moins, et ayant atteint le grade d’inspecteur principal de changes de **huitième (8e) échelon**, bénéficie d’un reclassement **hors classe**.

**Article 75 — Reconversion.**  
La reconversion a pour effet de placer un salarié à un emploi différent de celui occupé précédemment. Elle n’intervient que dans les cas suivants :

- diminution de l’activité de l’agence ;
- réorganisation interne ;
- maladie dûment constatée par un médecin agréé.

## Chapitre 6 — Positions conventionnelles

**Article 76.** Tout salarié peut être placé dans l’une des positions suivantes :

- en activité ;
- en détachement ;
- en disponibilité ;
- en position spéciale.

**Article 77 — Activité.**  
L’activité est la situation du salarié qui se trouve dans l’une des positions suivantes :

- en service ;
- en congé ;
- en stage.

**En service.** Le salarié en service est celui qui exerce sa prestation de travail dans les conditions et cas prévus par les textes en vigueur.

**En congé.** Est en congé, le salarié autorisé à suspendre pendant un temps déterminé l’exercice de sa prestation de travail dans les conditions et cas prévus par les textes en vigueur.

Des congés énumérés ci-après peuvent être accordés aux salariés en activité visés par la présente convention.

### a) Congés annuels

Les salariés ont droit au congé après **douze (12) mois** de service effectif conformément à l’article 120 du code du travail.

Le cumul des congés n’est pas autorisé, sauf lorsqu’il résulte d’une nécessité de service. Toutefois ce cumul **ne peut excéder deux (2) mois**.

La durée des congés payés est augmentée en fonction de l’ancienneté dans l’agence ainsi qu’il suit :

| Ancienneté | Jours ouvrables supplémentaires |
|---|---|
| 5 ans | 6 |
| 10 ans | 8 |
| 15 ans | 10 |
| 20 ans | 12 |
| 25 ans | 14 |
| 30 ans | 16 |
| 35 ans | 18 |

### b) Congés de maternité

À la suite d’un accouchement, toute salariée a le droit de suspendre son travail pendant **quinze (15) semaines** consécutives, dont neuf (9) semaines postérieures à la délivrance.  
Cette suspension peut être prolongée de **trois (3) semaines** en cas de maladie dûment constatée et résultant de la grossesse ou des couches. Pendant cette période, l’employeur ne peut lui donner congé.

Pendant cette période, l’employeur lui versera son salaire intégral, quitte à lui de se faire rembourser par l’organisme de sécurité sociale les sommes dues à la salariée.

À la reprise du travail, la salariée bénéficie d’une heure dans la journée pour allaitement durant **quinze (15) mois** à compter de la date de naissance de l’enfant ; cette heure peut être fractionnée en deux demi-heures à la demande de la salariée.

La salariée n’ayant pas eu son enfant à la naissance, bénéficie de **dix (10) jours** de repos et de **trois (3) semaines** en cas de maladie dûment constatée.

**Congés maladie.**  
Le salarié atteint d’une maladie dûment constatée, le mettant dans l’impossibilité temporaire d’exercer son activité professionnelle est mis en congé maladie conformément aux dispositions de l’article 47 du code du travail.

### c) Congés exceptionnels

Le salarié bénéficie au moment des événements familiaux énumérés ci-après des congés de courte durée :

| Événement | Durée |
|---|---|
| Mariage du salarié | 5 jours |
| Mariage d’un enfant | 2 jours |
| Congé de paternité | 3 jours |
| Baptême d’un enfant | 1 jour |
| Déménagement | 1 jour |
| Décès du conjoint du salarié | 2 jours |
| Décès du père, de la mère, du frère, de la sœur ou de l’enfant du salarié | 10 jours |
| Retrait de deuil | 5 jours |
| Construction de la pierre tombale | 2 jours |

> Scan p. 23 peu contrasté sur les deux lignes « décès » : conjoint **2 j.** et père/mère/frère/sœur/enfant **10 j.** selon la liste alignée en bas de page. Recouper l’original avant de figer le barème congés exceptionnels en production.

Ces congés doivent être pris au moment des événements et ne peuvent être ni reportés ni cumulés.  
Si l’événement se produit hors du lieu de l’emploi et nécessite le déplacement du salarié, les délais ci-dessus ne comprennent pas la durée du voyage.

### d) Maladie des enfants, conjoints et ascendants

Il est accordé aux salariés ayant des enfants, conjoints et ascendants malades dont leur assistance est obligatoire :

- **4 jours** pour les ascendants ;
- **7 jours** pour le conjoint ;
- **5 jours** pour un enfant ;
- **9 jours** pour deux enfants ;
- **12 jours** pour trois enfants et plus par an de congé payé à plein traitement pour soigner leurs enfants à charge, et ce, sur production d’un certificat médical spécifiant que leur présence est indispensable jusqu’au rétablissement.

### e) Congés pour convenances personnelles

Le droit aux congés pour convenances personnelles permet au salarié, dans la limite de **six (6) mois** par année civile, d’obtenir, pour quelques motifs que ce soit, une ou plusieurs suspensions de ses obligations de service d’une durée ne pouvant être inférieure à **15 jours**.

Le salarié en congé pour convenances personnelles **perd ses droits à la rémunération** à l’exception des droits aux allocations familiales.

### f) Congés pour concours

Le droit aux congés pour concours permet au salarié autorisé par l’administration à s’inscrire à un concours professionnel, d’obtenir une suspension de ses obligations de service d’une durée maximale d’**un mois** en vue de la préparation de ce concours.

Le salarié en congé pour concours perçoit la **totalité de sa rémunération** d’activité.

### g) Congés d’éducation

Le droit aux congés d’éducation ou de formation syndicale permet au salarié syndicaliste de participer à un séminaire, ou tout autre stage organisé dans ce cadre, ou d’entrer dans une école syndicale.

Le salarié en congé d’éducation ou de formation perçoit, pendant la durée de son congé, la totalité de sa rémunération.

### En stage

Est en position de stage, le salarié qui se trouve dans les conditions ci-après :

- admis en formation à la suite d’un concours professionnel organisé en vertu des textes en vigueur ;
- admis en formation dans une école ou dans un institut spécialisé à la suite d’une admission à un concours d’entrée ou d’une admission sur titre ;
- soumis à un stage de perfectionnement ou de recyclage après une année d’ancienneté au sein de l’agence.

**Article 78 — Détachement.**  
Le détachement est la position du salarié qui, placé temporairement hors de son cadre d’origine, cesse durant cette période de bénéficier de sa rémunération, mais continue d’avancer et de bénéficier dans son cadre d’origine de ses droits à la retraite, sous réserve qu’il verse ses cotisations et qu’il en soit de même de la part de l’administration ou de l’organisme auprès duquel il est détaché, pour la cotisation qui incombe à l’employeur.

Le salarié peut être détaché auprès d’une administration ou d’un organisme dont l’activité intéresse directement ou indirectement l’ARTF.

Le détachement ne peut être prononcé que pour des salariés ayant accompli une ancienneté minimale de **cinq (5) ans** à l’ARTF. Il ne peut intervenir qu’avec le **consentement de l’intéressé**.

Le détachement est de **cinq (5) ans** en principe. Il peut toutefois être indéfiniment renouvelé par période de cinq (5) ans au maximum. Le renouvellement est prononcé selon les mêmes modalités que le détachement.

Le détachement prend fin à l’expiration d’un délai de préavis de **trois (3) mois** émanant soit du salarié, soit de l’ARTF, soit de l’organisme auprès duquel le salarié est détaché. Le salarié est alors réintégré dans son cadre d’origine et réaffecté à un emploi de sa classe.

Le détachement est prononcé par une décision du directeur général de l’ARTF.

Toutefois, dans le cadre d’un détachement prononcé d’office dans les conditions prévues par la loi, cette mesure est entérinée par le directeur général de l’ARTF.

À la fin de la période de détachement, le salarié est astreint à un training d’imprégnation au sein de l’ARTF.

**Article 79 — Mise en disponibilité.**  
La mise en disponibilité est la position du salarié qui, placé temporairement hors de son cadre d’origine, cesse durant cette période de bénéficier de sa rémunération, des avantages de toute nature, de ses droits à l’avancement, à l’ancienneté et à la retraite.

La mise en disponibilité est prononcée par une décision du directeur général de l’ARTF.

La mise en disponibilité est accordée de droit à tout salarié qui en fait la demande.

Ainsi, sous réserve d’un préavis de **trois (3) mois**, le salarié ayant totalisé au moins **trois (3) ans** de présence effective peut demander sa mise en disponibilité pour une période n’excédant pas **deux (2) ans**, renouvelable **deux (2) fois**.

À l’expiration de la période de mise en disponibilité le salarié est réintégré après un préavis de **trois (3) mois** à son classement d’origine.

Toutefois, il ne peut prétendre à occuper les fonctions qu’il exerçait au moment de la mise en disponibilité.

Le salarié en disponibilité peut contribuer volontairement à la constitution de ses droits à pension auprès d’une institution légale de retraite.

**Article 80 — En position spéciale.**  
Un salarié peut être placé dans l’une des deux (2) positions spéciales suivantes :

- en position exceptionnelle ;
- sous le drapeau.

**Position exceptionnelle.**  
Les salariés appelés à servir dans les cabinets du chef de l’État, ministériels et autres institutions de la République sont placés en position exceptionnelle.  
Le salarié en position exceptionnelle bénéficie de ses droits à la rémunération et à l’avancement.

**Sous le drapeau.**  
Les salariés sous le drapeau se trouvent dans la position d’appelé pour le service national.  
L’appelé pour le service national est placé sous le régime juridique des congés administratifs.

**Article 81 — Rapprochement de conjoints.**  
Il s’entend du rapprochement de conjoints, le mécanisme par lequel un salarié n’est pas contraint à vivre longtemps séparé de sa famille en raison de son lieu de travail.

Le rapprochement de conjoints renvoie à une possibilité : la mutation pour rapprochement de conjoints.

**Article 82 — Mutation pour rapprochement de conjoints.**  
La mutation pour rapprochement de conjoints, c’est la possibilité par laquelle un salarié sollicite auprès de l’employeur une affectation dans le département du territoire national dans lequel se trouve sa famille.

Le dossier de demande de mutation doit contenir :

- une demande manuscrite adressée à l’employeur ;
- un acte de mariage ;
- une note d’affectation du (de la) conjoint(e) ;
- une attestation de résidence du (de la) conjoint(e).

Toutefois, il revient à l’employeur de juger la pertinence de la demande au regard des besoins liés au bon fonctionnement des services.

---

# TITRE VI — CONDITIONS DE TRAVAIL

**Article 83.** Les droits, garanties et obligations des salariés sont fixés par le règlement intérieur de l’ARTF.

## Chapitre 1 — Obligations du salarié

**Article 84.** Le salarié doit toute son activité professionnelle à l’agence, sauf dérogation stipulée au contrat.

Toutefois, il lui est loisible, sauf convention contraire, d’exercer en dehors de son temps de travail, toute activité à caractère professionnel non susceptible de concurrencer l’agence ou de nuire à la bonne exécution des services.

Est nulle de plein droit, toute clause d’un contrat de travail portant interdiction pour le salarié d’exercer une activité quelconque à l’expiration ou en cas de rupture de contrat.

**Article 85 — Discipline.**  
Toute faute commise par un salarié à l’occasion de l’exécution de son contrat de travail l’expose à une sanction disciplinaire, sans préjudice, le cas échéant, des poursuites judiciaires dont il peut être l’objet.

Les décisions portant sanction sont versées au dossier individuel de l’intéressé.

**Article 86 — Secret professionnel.**  
Tout salarié en activité ou ayant servi à l’ARTF est astreint au secret professionnel.

Toute divulgation l’expose à une sanction disciplinaire et peut donner lieu à des poursuites judiciaires.

## Chapitre 2 — Obligations de l’employeur

**Article 87.** L’employeur ne doit pas tenir compte dans ses relations avec les salariés, de leurs opinions politiques, philosophiques, croyances et pratiques religieuses, origines tribales, sociales, raciales ou autres, pour arrêter ses décisions en ce qui concerne l’embauche, la conduite ou la répartition, les mesures disciplinaires, la rémunération, le congédiement ou l’avancement, l’octroi des avantages sociaux et la formation professionnelle.

Les deux parties s’engagent à adopter une attitude digne et responsable et à n’exercer ni pression, ni mesures discriminatoires dans leurs rapports professionnels.

## Chapitre 3 — Récompenses

**Article 88.** Il est décerné au personnel de l’agence de régulation des transferts de fonds les récompenses dans l’ordre suivant :

1. encouragement ;
2. témoignage de satisfaction ;
3. mention honorable ;
4. décorations.

Ces récompenses sont décernées par l’employeur. Les récompenses peuvent être accompagnées d’une gratification.

- **L’encouragement** est accordé aux salariés qui, dans des circonstances normales, ont fait preuve de zèle, de probité, d’intelligence professionnelle.
- **Le témoignage de satisfaction** est décerné pour des faits plus importants des actes de courage, de dévouement ou d’humanité.
- **La mention honorable** est décernée au salarié qui, dans les circonstances exceptionnelles, difficiles ou dangereuses, a obtenu un résultat de services importants ou à celui qui a exposé sa vie en accomplissant ses obligations ou pour sauver son semblable.
- **La décoration** s’entend comme toute distinction honorifique remise par l’employeur à un salarié en reconnaissance d’un mérite dans le cadre professionnel.

## Chapitre 4 — Sanctions et instances disciplinaires

**Article 89.** Le salarié pénalement condamné peut, dans les cas et les conditions prévus par la loi, faire l’objet de sanctions disciplinaires.

**Article 90 — Sanctions.**  
Toute faute commise à l’occasion de l’exécution de son contrat de travail par le salarié de l’ARTF expose ce dernier à des sanctions disciplinaires.

Les sanctions disciplinaires pouvant être infligées à un salarié sont les suivantes :

1. l’**avertissement écrit** ;
2. le **blâme écrit** ;
3. la **mise à pied sans rémunération de 1 à 8 jours** ;
4. le **licenciement avec ou sans indemnité**.

Ces sanctions ne sont pas nécessairement successives, mais doivent être adaptées à la gravité de la faute commise.

Les infractions et leurs sanctions sont fixées par le règlement intérieur.

Toute sanction prononcée contre un salarié est consignée dans son dossier individuel.

**Article 91 — Procédure et autorités disciplinaires.**  
Tout manquement d’un salarié à ses obligations doit être notifié par voie hiérarchique sous forme d’un rapport disciplinaire au directeur général qui est la **seule autorité compétente** à prononcer une sanction.

Tout expéditeur ainsi que tout destinataire d’un rapport disciplinaire doit tenir un registre et en conserver trace écrite pendant **cinq (5) années** au moins.

Toute proposition de sanction doit comprendre un rapport détaillé sur les faits motivant ladite proposition ainsi que toutes les pièces s’y rapportant.

---

# TITRE VII — FORMATION PROFESSIONNELLE

## Chapitre 1 — Politique de formation

**Article 92.** Une politique de formation cohérente, en étroite corrélation avec les besoins de l’ARTF et les objectifs à atteindre est définie par l’employeur.

La formation permanente des salariés a pour but d’accroître leur efficacité dans l’accomplissement des tâches qui leur sont confiées et de contribuer à une meilleure utilisation des ressources humaines, en favorisant la promotion des plus compétents.

La formation permanente constitue à la fois un droit et un devoir pour le salarié et pour l’employeur.

Est éligible à une formation professionnelle, le salarié ayant atteint **3 ans d’ancienneté** après son embauche.

## Chapitre 2 — Actions et formation

**Article 93.** La formation permanente est assurée par :

- la formation sur le tas ;
- les séminaires ;
- le moyen des stages organisés dans les institutions agréées ;
- les écoles et instituts spécialisés ;
- le centre de formation professionnelle de l’ARTF.

**Article 94 — Formation sur le tas.**  
Il est de la responsabilité permanente du personnel d’encadrement, à tous les niveaux de la hiérarchie, de se préoccuper de la formation des salariés qui leur sont subordonnés.

En vue de faciliter le développement de la formation professionnelle et la promotion du personnel, l’employeur pourra favoriser la rotation permanente des salariés dans les différents services de la direction.

**Article 95 — Formation par les séminaires.**  
L’employeur organise et fait participer les salariés à des séminaires aussi bien sur le territoire national qu’à l’étranger.  
Les frais de transport des participants aux séminaires de formation sont à la charge de l’employeur.

**Article 96 — Stages organisés dans les institutions agréées.**  
En vue d’une formation permanente des salariés, peuvent être organisés :

- les stages de perfectionnement et de recyclage qui ont pour but d’améliorer l’aptitude des salariés à remplir les tâches impliquées par leur emploi ;
- les stages de spécialisation qui ont pour but de faire acquérir aux salariés des compétences supplémentaires et permettre leur accession éventuelle à des emplois supérieurs.

**Article 97 — Écoles et instituts spécialisés.**  
Les écoles et instituts spécialisés assurent la formation professionnelle initiale et la formation en cours de carrière des salariés. Leur accès n’est ouvert que par voie de concours ou par admission sur titre.

**Article 98 — Centre d’application des métiers de régulation des transferts de fonds.**  
Il est institué au sein de l’ARTF, un centre de formation professionnel en sigle « **CAMRTF** ».  
Les modalités de fonctionnement sont définies par note de service du directeur général.

## Chapitre 3 — Conditions de formation

**Article 99 — Stages de perfectionnement et de recyclage.**  
Les besoins de stage de perfectionnement et de recyclage dans le cadre de la politique de formation de l’ARTF sont exprimés au niveau des différentes directions. Ces stages portent exclusivement sur les techniques dont la maîtrise est nécessaire dans les emplois auxquels la direction ouvre accès. Leur durée **ne peut excéder neuf (9) mois**.

**Article 100.** Tout salarié bénéficiaire d’un stage de perfectionnement ou de recyclage doit obéir aux conditions suivantes :

- se soumettre aux conditions de l’école d’accueil ;
- produire un rapport de fin de stage.

**Article 101 — Stages de qualification ou de spécialisation.**  
L’accès aux stages de qualification ou de spécialisation s’effectue par voie de concours, de tests professionnels ou sur titre.

**Article 102.** La durée des stages de qualification ou de spécialisation ne peut excéder **trois (3) ans**. Ils comportent à la fois une formation pratique et un enseignement théorique. Ceux-ci sont dispensés dans des écoles et instituts spécialisés ou par des institutions agréées par l’État.

**Article 103.** L’employeur met en formation sur titre le salarié ayant atteint le dernier échelon de sa classe et dont l’âge est inférieur ou égal à **50 ans** suivant les conditions ci-après :

- avoir 50 ans au plus le jour de l’examen du dossier de mise en formation ;
- avoir au moins **trois (3) années** d’ancienneté dans le grade ;
- appartenir à la classe immédiatement inférieure au niveau du stage auquel l’admission sur titre donne accès.

**Article 104 — Des contraintes adossées aux formations.**  
Pour toutes les formations accordées, l’employeur est tenu d’élaborer un cahier des charges précisant ses attentes et objectifs en terme de savoir-faire et d’expertise opérationnelle que d’une part, la structure enseignante aura tenue de transmettre et d’autre part, les impétrants devront acquérir, mettre en pratique et partager avec leurs collègues.

L’employeur prend l’option de capitaliser systématiquement l’expérience, les compétences et l’expertise de ces cadres en les utilisant comme enseignants dans le cadre de la formation permanente de ses salariés au sein du centre d’application des métiers de régulation des transferts de fonds (CAMRTF).

Cette disposition ne sera mise en œuvre qu’avec l’accord explicite des cadres sollicités ; conformément à cette disposition, l’une des parties saisira l’autre pour solliciter, par lettre recommandée avec accusé de réception éventuellement, la mise en application de la présente.

Une clause de **débit formation** incluse dans le contrat de travail engage le salarié ayant bénéficié d’une formation payée par l’employeur, à ne pas rompre son contrat pendant une durée déterminée selon les modalités d’une convention préalablement prise.

---

# TITRE VIII — RÉSILIATION DU CONTRAT

**Article 105.** La résiliation du contrat de travail intervient dans les cas suivants :

- l’abandon de poste dûment constaté ;
- la démission ;
- le licenciement ;
- l’admission à la retraite ;
- le décès ;
- l’invalidité dûment constatée par un médecin agréé, survenue à la suite d’un accident non professionnel et d’une longue maladie.

Toutes les conditions de résiliation du contrat de travail susvisées sont soumises aux dispositions en vigueur.

**Article 106.** La partie qui prend l’initiative de la rupture du contrat doit notifier sa décision par écrit à l’autre partie. La lettre de notification doit indiquer expressément le motif de la rupture.

Sauf cas de faute lourde, la rupture du contrat doit respecter un préavis dont la durée est fixée comme suit :

| Classes | Préavis |
|---|---|
| 1 à 4 | **1 mois** |
| 5 et 6 | **2 mois** |
| 7 à 10 | **3 mois** |

## Chapitre 1 — Abandon de poste

**Article 107.** Toute absence doit être justifiée à l’administration.

Au bout de **cinq (5) jours** successifs d’absence non justifiée, l’abandon de poste est constaté par l’employeur.

L’abandon de poste est notifié par écrit au salarié avec accusé de réception.

**Article 108 — Conséquences immédiates.**  
En l’absence de réaction du salarié après notification par l’employeur dans un délai de **cinq (5) jours**, le contrat dudit salarié est **suspendu**.

Toutefois, si au cours du mois le salarié a été présent à son poste, il ne perçoit son salaire qu’**au prorata temporis**.

## Chapitre 2 — Démission

**Article 109.** La démission doit faire l’objet d’une demande écrite du salarié moyennant un préavis dont la durée est fixée comme suit :

| Classes | Préavis |
|---|---|
| 1 à 4 | **1 mois** |
| 5 et 6 | **2 mois** |
| 7 à 10 | **3 mois** |

## Chapitre 3 — Licenciement

**Article 110.** Les licenciements collectifs ou individuels motivés par une diminution d’activité ou par une réorganisation interne s’opèrent conformément aux dispositions en vigueur.

Le salarié licencié par suite d’une suppression d’emploi ou de compression d’effectif conserve la priorité d’embauche dans la même classe d’emploi pendant **deux (2) ans**.

La présente disposition ne s’adresse qu’aux salariés licenciés par suite de compression. Ceux-ci sont tenus de communiquer à leur ancien employeur tout changement d’adresse survenu après leur départ de l’établissement.

En cas de vacance, l’employeur avise le salarié par lettre recommandée avec accusé de réception envoyée à sa dernière adresse connue.

Il est délivré au salarié, au moment de son départ, un certificat de travail portant exclusivement le nom et l’adresse de l’employeur, la date d’embauche et de cessation d’activités, la nature de l’emploi, et éventuellement les emplois successivement occupés ainsi que les dates y afférentes.

**Article 111 — Indemnité de licenciement.**  
En cas de licenciement par l’employeur, hormis le cas de faute lourde, le travailleur bénéficie d’une indemnité de licenciement distincte du préavis.

**Article 112.** L’indemnité versée en cas de licenciement est ainsi calculée :

- **un (1) mois** de salaire par année de présence pour les **trois premières années** ;
- **deux (2) mois** de salaire par année de présence de **4 à 10 ans** ;
- **trois (3) mois** de salaire par année de présence de **11 à 15 ans** ;
- **quatre (4) mois** de salaire par année de présence **au-delà de 15 ans**.

L’indemnité de licenciement ainsi calculée **ne peut dépasser trente (30) mois** de traitement.

**Article 113.** L’indemnité de licenciement est calculée sur le traitement sans supplément d’aucune sorte (gratification, allocations familiales) à l’exception de la prime d’ancienneté. Seules les années de service entrent en compte pour sa détermination.

**Article 114.** L’indemnité de licenciement est calculée sur le traitement fini du salarié licencié et non sur la moyenne des traitements correspondant au dernier mois de salaire perçu.

Toutefois, en cas de suppression d’emploi, l’indemnité de licenciement est calculée sur la base du traitement conventionnel annuel, y compris les gratifications, si ce mode de calcul est plus favorable.

**Article 115.** L’indemnité de licenciement ne dispense pas l’employeur au versement des gratifications dues.

**Article 116.** Pour le calcul de la durée des services, les fractions d’années au moins égales à **30 jours** sont seules prises en considération. Les journées de vacances et de congés payés entrent en compte comme les journées de travail.

**Article 117.** Au cas où l’établissement estimerait que le paiement de l’indemnité a donné lieu à des abus, la question devrait faire l’objet d’un règlement établi en commission paritaire.

## Chapitre 4 — Admission à la retraite

**Article 118 — Conditions d’admission à la retraite.**  
Le salarié est obligatoirement affilié dès son entrée à l’ARTF aux régimes de retraite légale et à une caisse de retraite complémentaire. Sous réserve des dispositions légales contraires, la limite d’âge d’activité est fixée comme suit :

- **57 ans** pour les manœuvres et les autres travailleurs assimilés ;
- **60 ans** pour les salariés de maîtrise et les cadres ;
- **65 ans** pour les cadres hors classe.

Conformément aux dispositions de la loi n° **22-2010 du 30 décembre 2010** fixant l’âge d’admission à la retraite des travailleurs relevant du code du travail, notamment en son article 3, à la demande de l’employeur et avec le consentement du travailleur, l’admission à la retraite peut être reportée dans les limites suivantes :

- **60 ans** pour les manœuvres, les ouvriers et les autres travailleurs assimilés ;
- **65 ans** pour les salariés de maîtrise et les cadres ;
- **70 ans** pour les cadres hors classe.

Sous réserve de l’usage des dispositions et conditions relatives à la prolongation des activités fixées par la loi tantôt évoquée, les dates de cessation de plein droit du contrat de travail du salarié sont celles indiquées au premier paragraphe du présent article.

Les dates limites de cessation d’activités évoquées dans le deuxième paragraphe de l’article 118 sont des dates de cessation d’activités fixées à titre exceptionnel. Ladite cessation fixée aux dates indiquées sera effective de plein droit.

Le salarié dont le mois de naissance n’est pas connu atteint la limite d’âge au **1er janvier** de l’année où il atteint l’âge de la retraite.

Le directeur général de l’ARTF peut cependant, à la demande du salarié, l’autoriser à prendre sa retraite par anticipation **cinq (5) ans** avant l’âge présumé de départ à la retraite.

La retraite obtenue par anticipation prend effet le **1er jour du mois suivant** la date de cessation de service.

Tout salarié cessant définitivement ses activités professionnelles fait l’objet d’une récapitulation de carrière destinée à établir sa situation administrative au moment de son départ à la retraite.

Un relevé de services lui est délivré avec la notification « retraite ». Ce relevé de services, visé par l’intéressé et signé par le directeur général de l’ARTF, clos le dossier administratif du salarié.

Le relevé de services sert de base au calcul :

- des rappels de rémunérations éventuellement dus au salarié ;
- du montant des interventions effectuées sur les rémunérations perçues si la cessation des activités professionnelles retient avant que le salarié ait pu constituer un droit à pension.

Le relevé de services sert de base, le cas échéant, à la liquidation des droits à pension du salarié.

**Article 119 — Indemnité d’admission à la retraite.**  
Lors de son admission à la retraite, le salarié réunissant les conditions d’ancienneté requises, bénéficie de la pension légale de retraite, sous déduction des avances, acomptes et prêts de toute nature et d’une indemnité d’admission à la retraite fixée comme suit :

| Ancienneté de services ininterrompus | Indemnité (dernier traitement brut) |
|---|---|
| 5 ans | 3 mois |
| plus de 5 ans et jusqu’à 10 ans | 6 mois |
| plus de 10 ans et jusqu’à 15 ans | 9 mois |
| plus de 15 ans et jusqu’à 20 ans | 12 mois |
| plus de 20 ans et jusqu’à 25 ans | 15 mois |
| plus de 25 ans et jusqu’à 30 ans | 18 mois |
| plus de 30 ans et jusqu’à 35 ans | 21 mois |
| plus de 35 ans | 24 mois |

Le traitement à prendre en considération pour le calcul de l’indemnité de départ à la retraite comprend le **salaire de base et la prime d’ancienneté**.

Pour les salariés ayant bénéficié de détachement ou de mise en disponibilité, l’indemnité d’admission à la retraite est calculée en **déduction desdites périodes**.

**Article 120 — Autres avantages.**

**Bonification.**  
Une bonification exceptionnelle de **deux (2) échelons** est accordée à tout salarié, **vingt-quatre (24) mois** avant son départ à la retraite. Cette bonification exceptionnelle n’empêche pas le cours normal du traitement des avancements.

Lorsque le salarié se trouve au dernier échelon de sa classe, la bonification exceptionnelle de deux (2) échelons est **remplacée par l’allocation pendant la même période d’une indemnité équivalente à deux (2) fois la progression des salaires de base de ladite classe**.

Lorsque le salarié se trouve à l’avant-dernier échelon de sa classe, il bénéficie de la bonification d’un échelon et de l’allocation pendant la même période d’une indemnité équivalente à une fois la progression des salaires de base de ladite classe.

**Frais de déplacement.**  
Les frais de déplacement du salarié qui a exécuté son contrat de travail hors de son lieu de recrutement, admis à faire valoir ses droits à la retraite, de son conjoint et de ses enfants à charge sont supportés par l’employeur conformément à l’article 57 de la présente convention.

**Allocation spéciale.**  
Un montant de **cinq cent mille (500 000) francs CFA** est alloué aux ayants droit au décès du salarié retraité.

## Chapitre 5 — Décès

**Article 121.** Il est versé aux ayants-droit d’un salarié décédé une indemnité appelée **capital de décès**, calculée en fonction du dernier salaire brut comme suit :

| Ancienneté | Capital de décès |
|---|---|
| moins d’un an de présence | 5 mois de salaire |
| 1 à 5 ans | 9 mois |
| 6 à 15 ans | 12 mois |
| 16 à 20 ans | 15 mois |
| plus de 20 ans | 18 mois |

Ces dispositions ne s’appliquent pas aux salariés se trouvant en période d’essai.

En outre, il est versé une prime de **cent mille (100 000) francs CFA par enfant à charge** au moment du décès du salarié.

L’employeur prend en charge les frais funéraires suivants : cercueil, couronne, corbillard, chapelle ardente, habillement, fosse, construction caveau.  
Ces frais ne peuvent excéder la somme de **deux millions (2 000 000) de francs CFA**.

L’employeur prend également à sa charge les frais inhérents à la participation des salariés aux funérailles.

Si le salarié avait été déplacé par le fait de l’employeur, celui-ci assure à ses frais le transport du corps au lieu de sa résidence habituelle.

Cette disposition s’applique également aux salariés en congé.

Les dispositions sus mentionnées sont également applicables à tout salarié se trouvant en mission ou en stage en dehors du territoire national.

En cas de décès du conjoint(e) ou de l’enfant à charge du salarié, les frais funéraires visés ci-dessus sont supportés intégralement par l’ARTF.

En cas de décès du conjoint(e) ou de l’enfant à charge d’un salarié déplacé par le fait de l’employeur, celui-ci assure également le transport de la dépouille au lieu de la résidence habituelle du salarié.

---

# TITRE IX — FRAIS MÉDICAUX, PHARMACEUTIQUES ET HOSPITALISATION

**Article 122.** L’employeur s’assure par convention le concours d’un ou de plusieurs médecins, structures ou formations sanitaires qui procèdent aux opérations suivantes :

- visites annuelles obligatoires de l’ensemble du personnel ;
- visites des salariés nouvellement recrutés ;
- consultations des salariés non hospitalisés.

## Chapitre 1 — Honoraires et frais médicaux

**Article 123.** L’employeur prend en charge à **100 %**, les honoraires et les soins médicaux du salarié, du conjoint et des enfants à charge, sur présentation d’une facture d’un médecin ou d’une formation sanitaire agréée par l’établissement.

En cas de maladie du salarié se trouvant à l’étranger, de son conjoint ou d’un enfant à charge, il sera fait application des dispositions légales ci-dessous.

**Article 124 — Frais pharmaceutiques.**  
Les frais pharmaceutiques sont à la charge du salarié à **20 %** et **80 %** restant à la charge de l’employeur.

En cas de prescription médicale relative à l’usage des verres correcteurs concernant le salarié lui-même, l’ARTF, sur présentation de l’ordonnance médicale et de la facture de l’opticien agréé, rembourse le prix des verres.

## Chapitre 2 — Frais d’hospitalisation et évacuation sanitaire

**Article 125.** L’employeur prend en charge à **100 %** les frais d’hospitalisation du salarié. Il est entendu que les frais d’hospitalisation comprennent entre autres les frais des produits pharmaceutiques et les prestations non fournies par la formation sanitaire.

Les frais d’hospitalisation du conjoint et des enfants à charge du salarié en activité sont pris en charge à **100 %** selon les tarifs de la même catégorie que celle qui est appliquée au salarié lui-même, sous réserve que cette hospitalisation ait lieu dans une formation hospitalière agréée par l’établissement.

**Article 126 — Évacuation sanitaire.**  
L’employeur ordonne et prend en charge, en cas de nécessité et après avis du médecin agréé, l’évacuation du salarié, de son conjoint ou de l’enfant à charge à l’intérieur ou hors du territoire national.

Les modalités d’évacuation sont définies par les textes en vigueur. Toutefois, l’employeur a la faculté de souscrire une police d’assurance maladie.

**Article 127 — Durée de l’évacuation sanitaire.**  
La durée de prise en charge d’une évacuation sanitaire ne peut excéder **six (6) mois** sauf en cas d’accident de travail et/ou de maladie professionnelle.

Toutefois, cette période est renouvelable sur rapport motivé du médecin approuvé par le médecin agréé de l’Agence.

---

# TITRE X — ACCIDENTS ET MALADIES

## Chapitre 1 — Accidents et maladies professionnelles

**Article 128.** Les dispositions de l’article 136 ci-dessous ne s’appliquent qu’aux salariés en activité tels que définis par les dispositions de l’article 77 de la présente convention.

**Article 129.** Les accidents de travail et les maladies professionnelles relèvent des dispositions législatives et réglementaires en vigueur.

**Article 130.** Le contrat de travail du salarié malade est suspendu conformément aux dispositions de l’article 47 du code du travail.

**Article 131.** La maladie doit être constatée par un médecin agréé et notifiée à l’employeur dans les **72 heures**.

**Article 132.** Pendant la période de suspension du contrat de travail pour cause de maladie, ou d’accident de travail, le salarié perçoit les allocations ci-après :

| Ancienneté de service | Allocation |
|---|---|
| 1 à 5 ans | 6 mois de salaire |
| 6 à 10 ans | 7 mois de salaire |
| 11 à 15 ans | 8 mois de salaire |
| 16 à 20 ans | 9 mois de salaire |
| plus de 20 ans | 10 mois de salaire |

**Article 133.** Pour les salariés ayant au moins **deux (2) enfants à charge**, les durées d’indemnisation ci-dessus fixées sont augmentées comme suit :

| Ancienneté de service | Majoration |
|---|---|
| moins d’un an | néant |
| 1 à 5 ans | 3 mois |
| 5 à 15 ans | 4 mois |
| plus de 15 ans | 5 mois |

**Article 134.** Au cas où un salarié ne pourrait reprendre son emploi lors de la consolidation de sa blessure ou en raison d’une incapacité physique due à la maladie, l’employeur doit rechercher avec les délégués du personnel ou les représentants syndicaux les moyens de sa reconversion dans un autre emploi, sans préjudice des avantages de classification acquis antérieurement par le salarié malade.

## Chapitre 2 — Accidents non professionnels

**Article 135.** Lorsqu’un salarié est victime d’un accident non couvert par la législation sur les accidents de travail qui le met dans l’incapacité d’assurer son service, il perçoit une allocation dans les conditions ci-dessous énumérées :

- **6 mois** de plein salaire de base, plus les allocations familiales et la prime d’ancienneté, à l’exclusion de toutes les autres primes ;
- à partir du **7e mois**, **6 mois** de demi-salaire de base, plus les allocations familiales et la prime d’ancienneté, à l’exclusion de toutes les autres primes.

**Article 136.** Les salariés ayant épuisé leurs droits aux versements prévus ci-dessus et dont l’état de santé nécessiterait certains soins complémentaires ou une convalescence peuvent, sur leur demande, être mis en congé sur présentation d’un certificat médical ; ce congé est renouvelable pour une période n’excédant pas **deux (2) ans**.

**Article 137.** Au cas où un salarié ne pourrait reprendre son emploi lors de la consolidation de sa blessure ou en raison d’une incapacité physique due à l’accident, l’employeur doit rechercher avec les délégués du personnel ou les représentants syndicaux les moyens de sa reconversion dans un autre emploi.

---

# TITRE XI — ASSURANCES

**Article 138.** Les polices d’assurance santé, vies, invalidités découlant des accidents de travail et autres seront souscrites par l’employeur aux noms des salariés.

**Article 139.** Une police d’assurance individuelle accident et maladie est également souscrite par l’employeur pour le salarié en mission à l’étranger.

---

# TITRE XII — DIFFÉRENDS

## Chapitre 1 — Commission paritaire d’interprétation et de conciliation

**Article 140.** Il est institué une commission paritaire d’interprétation et de conciliation pour rechercher une solution à l’amiable des différends pouvant résulter de l’interprétation et de l’application de la présente convention, de ses annexes et avenants.

**Article 141.** La commission n’a pas à connaître les litiges individuels qui ne mettent pas en cause le sens et la portée de la présente convention.

**Article 142.** La composition de la commission est la suivante :

- président : le directeur départemental du travail ;
- 4 membres représentant l’employeur ;
- 4 représentants syndicaux.

**Article 143.** Les noms des composants de la commission sont envoyés à la direction départementale du travail qui la convoque pour statuer sur le différend.

**Article 144.** La partie signataire qui désire soumettre un différend à la commission doit le porter par écrit à la connaissance de toutes les autres parties ainsi qu’à l’autorité administrative (le directeur départemental du travail).  
L’autorité administrative est tenue de réunir la commission dans les plus brefs délais.

**Article 145.** Lorsque la commission donne un avis à l’unanimité des parties, les avis signés par les membres de la commission ont les mêmes effets juridiques que les clauses de la présente convention.

Ces avis font l’objet d’un dépôt au secrétariat du tribunal du travail à la diligence de l’autorité qui a réuni la commission.

## Chapitre 2 — Commission de réclamation

**Article 146.** Tout salarié a le droit de demander à l’employeur de faire vérifier si l’emploi qu’il occupe effectivement correspond bien à la définition du poste de travail retenue comme base de classement.

La réclamation est introduite auprès du chef d’établissement, soit directement par le salarié, soit par l’intermédiaire des délégués du personnel.

**Article 147.** En cas de désaccord après examen de cette réclamation par l’employeur, le différend peut être soumis à la commission de réclamation.

**Article 148.** La commission de réclamation est composée comme suit :

- président : l’inspecteur du travail ;
- 2 représentants de l’employeur ;
- 2 délégués du personnel.

**Article 149.** Les noms des composants de la commission sont envoyés à l’inspection du travail qui la convoque pour statuer sur le différend.

Toutefois, en cas de besoin, d’autres commissions pourront être instituées.

---

**Fait à Brazzaville, le 10 janvier 2019.**

| Pour les Sections Syndicales | Pour l’Administration |
|---|---|
| Le secrétaire général de la CSTC | Le directeur général |

---

# ANNEXE 1 — CLASSIFICATION ET CONDITIONS D’ACCÈS

**Article premier.** Les salariés de l’agence de régulation des transferts de fonds sont classés selon les emplois et les qualifications ci-après.

| Classe | Conditions d’accès | Emplois et qualifications |
|---|---|---|
| **1 — Personnel de service** | Salariés capables d’effectuer les travaux de manutention, les travaux courants de nettoyage. | Gardien ou agent de sécurité · Garçon de bureau · Jardinier · Planton · Chauffeur · Concierge · Mécanicien (prestataire) · Ouvrier qualifié |
| **2 — Personnel de service spécialisé** | Salarié occupant un emploi exigeant des connaissances professionnelles et une expérience du métier pouvant être acquises par une formation et une pratique suffisante. | Chauffeur–mécanicien · Ouvrier spécialisé · Technicien spécialisé · Technicien de surface |
| **3 — Commis** | Salarié effectuant les travaux n’exigeant qu’une formation professionnelle simple et titulaire d’un CEPE. | Réceptionniste |
| **4 — Commis principal** | Salarié ayant des connaissances plus approfondies mais non appelé à prendre des initiatives, travaillant sous les directives d’un employé de la classe supérieure, titulaire d’un CAP. | Opérateur de saisie · Aide comptable |
| **5 — Contrôleur** | Salarié titulaire du BEPC, BET ou d’un diplôme équivalent. BEP ou diplôme équivalent (avec bonification d’un échelon). | Comptable · Agent administratif |
| **6 — Contrôleur principal** | Salarié titulaire du Bac ou d’un diplôme équivalent. | Secrétaire administratif · Programmeur d’application · Programmeur de système |
| **7 — Vérificateur** | Salarié titulaire d’un DUT, d’un BTS, d’un BENAM ou d’un diplôme équivalent. Salarié titulaire de la Licence, Bachelor ou d’un diplôme équivalent (avec une bonification d’un échelon). | Bibliothécaire · Archiviste ou documentaliste · Analyste programmeur · Relationniste · Analyste concepteur |
| **8 — Inspecteur** | Salarié titulaire d’une Maîtrise, d’un DEA ou d’un diplôme équivalent. Salarié titulaire d’un Master II, d’un DESS, d’un DSENAM, d’un MBA ou d’un diplôme équivalent (avec une bonification d’un échelon). | *(emplois de la classe inspecteur)* |
| **9 — Inspecteur principal** | Salarié de la classe 8 ayant rempli les conditions de reclassement. Salarié titulaire d’un doctorat (avec une bonification de 2 échelons). | |
| **10 — Hors-classe** | Salarié de la classe 9 ayant remplis les conditions de reclassement. | |

**Article 2.** Le directeur général, les directeurs centraux et les directeurs départementaux bénéficient d’un **salaire fonctionnel fixé par le comité de direction** (hors grille indiciaire des classes 1 à 10).

### Correspondance technique actuelle (référentiel `classegrillesalariales`)

| Classe CCN | Grade en base | Coefficient |
|---|---|---|
| I | Personnel de service | 45 |
| II | Personnel de service spécialisé | 50 |
| III | Commis | 55 |
| IV | Commis principal | 60 |
| V | Contrôleur | 75 |
| VI | Contrôleur principal | 90 |
| VII | Vérificateur | 105 |
| VIII | Inspecteur | 120 |
| IX | Inspecteur principal | 145 |
| X | Hors-classe | 170 |

---

# ANNEXE 2 — GRILLE SALARIALE

La grille officielle (paysage) porte l’intitulé : *Grille salariale de l’agence de régulation des transferts de fonds*.  
**12 échelons** × **10 classes**. Salaire = indice × valeur du point. La classe I photographiée confirme : échelon 1 = indice **490** / **147 000 F** ⇒ point = **300 F**.

Formule retenue (alignée sur la CCN et [`SPEC-GRILLE-SALARIALE.md`](./SPEC-GRILLE-SALARIALE.md)) :

```
indice(classe, échelon)  = indice_base[coefficient] + (échelon × coefficient)
salaire                  = indice × 300
```

| Coefficient | Indice de base |
|---|---|
| 45 | 445 |
| 50 | 540 |
| 55 | 645 |
| 60 | 760 |
| 75 | 895 |
| 90 | 1 060 |
| 105 | 1 255 |
| 120 | 1 480 |
| 145 | 2 035 |
| 170 | 2 690 |

Vérification classe I (personnel de service, coeff. 45) — identique au scan :

| Échelon | Indice | Salaire (F CFA) |
|---|---|---|
| 1 | 490 | 147 000 |
| 2 | 535 | 160 500 |
| 3 | 580 | 174 000 |
| 4 | 625 | 187 500 |
| 5 | 670 | 201 000 |
| 6 | 715 | 214 500 |
| 7 | 760 | 228 000 |
| 8 | 805 | 241 500 |
| 9 | 850 | 255 000 |
| 10 | 895 | 268 500 |
| 11 | 940 | 282 000 |
| 12 | 985 | 295 500 |

Les 120 cellules (10 × 12) se génèrent par `POST /api/salaires/generation`. Ne pas recoder une autre formule.

---

## Barèmes numériques à coder (synthèse)

À implémenter comme règles métier ou paramètres plafonnés — jamais comme « à peu près ».

| Règle | Valeur CCN |
|---|---|
| Essai classes 1–4 / 5–6 / 7–10 | 1 / 2 / 3 mois, renouvelable 1 fois |
| Préavis rupture (sauf faute lourde) | 1 / 2 / 3 mois selon les mêmes groupes de classes |
| Prime d’ancienneté | 2 % à 2 ans, +1 %/an, plafond **40 %** |
| Prime de fin d’année | dernier mois (base + ancienneté), ≥ 1 an ; prorata si détachement/dispo/position spéciale ; refus possible si mise à pied ; exclue si licenciement faute lourde |
| Intérim | dès le 1er mois ; max 6 mois sauf maladie / AT |
| Missions internes | 15 jours max sauf prolongation DG ; barème art. 57 |
| Enfants à charge (allocations) | max **2** ; 16 ans (17 apprentissage, 21 études / infirmité) |
| Arbre de Noël | max **3** enfants, 0–16 ans |
| Notation | tous les **24 mois** ; réclamation **72 h** |
| Exemptés de notation | stage, détachement, position exceptionnelle |
| Avancement auto / exceptionnel | +2 échelons max (stage ≥ 9 mois / décision commission) |
| Reclassement exceptionnel | 50 ans + 15 ans ancienneté + 3 ans dans la classe |
| Hors-classe | 25 ans ancienneté + inspecteur principal + 8e échelon |
| Détachement | ≥ 5 ans ARTF ; 5 ans ; préavis 3 mois ; consentement |
| Disponibilité | ≥ 3 ans présence ; 2 ans renouvelable 2 fois ; préavis 3 mois |
| Sanctions | avertissement écrit, blâme écrit, mise à pied 1–8 j, licenciement |
| Autorité disciplinaire | **DG uniquement** ; conservation rapports **5 ans** |
| Formation | éligible à **3 ans** ; perfectionnement ≤ 9 mois ; qualification ≤ 3 ans |
| Abandon de poste | 5 jours d’absence injustifiée → constat ; +5 jours sans réaction → suspension |
| Priorité réembauche | 2 ans (licenciement économique / compression) |
| Indemnité licenciement | 1 / 2 / 3 / 4 mois par an selon tranches ; **plafond 30 mois** |
| Congés annuels | 12 mois de service ; cumul max 2 mois ; jours d’ancienneté art. 77 a) |
| Maternité | 15 semaines (9 post-partum) ; +3 semaines maladie ; 1 h allaitement 15 mois |
| Convenances personnelles | max 6 mois / an, ≥ 15 jours, **sans salaire** (sauf alloc. familiales) |
| Âge retraite | 57 / 60 / 65 ; report 60 / 65 / 70 |
| Anticipation retraite | 5 ans sur demande, effet 1er du mois suivant |
| Bonification pré-retraite | +2 échelons **24 mois** avant le départ |
| Capital décès | 5 / 9 / 12 / 15 / 18 mois selon ancienneté + 100 000 F/enfant |
| Frais funéraires | max **2 000 000 F** |
| Pharma | 20 % salarié / 80 % employeur |
| Honoraires / hospitalisation | 100 % (agréés) |
| Évacuation | max 6 mois (sauf AT/MP), renouvelable |
| Maladie (AT/MP) allocations | 6 à 10 mois selon ancienneté |
| Accident non professionnel | 6 mois plein + 6 mois demi (base + AF + ancienneté) |
| Assurance mission étranger | obligatoire (art. 139) |

---

*Document de travail interne — transcription de la convention collective ARTF du 10 janvier 2019. En cas de doute sur un chiffre issu d’un scan peu lisible, confronter la page originale avant de figer un barème en production.*
