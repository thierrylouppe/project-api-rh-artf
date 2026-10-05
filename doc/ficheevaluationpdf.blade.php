<!DOCTYPE html>
<html>

<head>
    <title>ARTF - FENA</title>
</head>
<style type="text/css">
    body {
        font-family: 'Times New Roman', serif;
        font-size: 20px;
    }

    .m-0 {
        margin: 0px;
    }

    .m-5 {
        margin: 5px;
    }

    .ml-50 {
        margin-left: 50px;
    }

    .p-0 {
        padding: 0px;
    }

    .pt-5 {
        padding-top: 5px;
    }

    .mt-10 {
        margin-top: 10px;
    }

    .text-center {
        text-align: center !important;
    }

    .text-center {
        text-align: center !important;
    }

    .f-30 {
        font-size: 30px;
    }

    .f-15 {
        font-size: 15px;
    }

    .w-100 {
        width: 100%;
    }

    .w-150 {
        width: 150%;
    }

    .w-50 {
        width: 50%;
    }

    .w-85 {
        width: 85%;
    }

    .w-15 {
        width: 15%;
    }

    .logo img {
        width: 45px;
        height: 45px;
        padding-top: 30px;
    }

    .logo span {
        margin-left: 8px;
        top: 19px;
        position: absolute;
        font-weight: bold;
        font-size: 25px;
    }

    .gray-color {
        color: #5D5D5D;
    }

    .text-bold {
        font-weight: bold;
    }

    .border {
        border: 1px solid black;
    }

    .border-top {
        border-top: 1px solid black;
    }

    .border-right {
        border-right: 1px solid black;
    }

    .border-bottom {
        border-bottom: 1px solid black;
    }

    .border-left {
        border-left: 1px solid black;
    }

    table tr,
    th,
    td {
        border: 1px solid #d2d2d2;
        border-collapse: collapse;
        padding: 7px 8px;
    }

    .bordertransp tr,
    th,
    td {
        border: 1px solid #ffffff00;
        border-collapse: collapse;
        padding: 7px 8px;
    }

    table tr th {
        background: #F4F4F4;
        font-size: 15px;
    }

    table tr td {
        font-size: 15px;
    }

    table {
        border-collapse: collapse;
    }

    .box-text p {
        line-height: 10px;
    }

    .float-left {
        float: left;
    }

    .float-right {
        float: right;
    }

    .total-part {
        font-size: 16px;
        line-height: 12px;
    }

    .total-right p {
        padding-right: 20px;
    }

    .text-underline {
        text-decoration: underline;
    }

    .page_break {
        page-break-before: always;
    }

    .page_number:before {
        content: "Page " counter(page);
    }
</style>

<body>
    <table width="100%">
        <tr>
            <td class="text-bold" valign="top" align="center" style=" width: 280px;">
                MINISTERE DE L'ECONOMIE ET DES FINANCES
                <br>
                ----------------
                <br>
                AGENCE DE REGULATION DES TRANSFERTS DE FONDS
                <br>
                <br>
                <div>
                    <img src="{{ base_path() . '/public/assets/logo/logotop.png' }}" alt="" width="100"
                        height="100">
                </div>
                <br>
                DIRECTION DES RESSOURCES HUMAINES ET DE LA LOGISTIQUE
                <br>
                ----------------
                <br>
                SERVICE DES RESSOURCES HUMAINES
                <br>
                ----------------
                <br>
                BUREAU DU PERSONNEL
                <br>
                ----------------
                <br>
            </td>
            <td valign="top" style=" width: 100px;"></td>
            <td class="text-bold" valign="top" align="center" style=" width: 160px;">REPUBLIQUE DU CONGO
                <br> Unité -:- Travail -:- Progrès
                <br>----------------<br>
                <br> <br>
                @if ($infoAgent->photoAgent)
                {{-- {{ asset('/storage/public/images/photos/' . $auth_user->photoAgent) }} --}}
                    <img src="{{ base_path() . '/public/storage/images/photos/' . $infoAgent->photoAgent }}" alt=""
                        width="150" class="profile-user-img img-fluid img-circle">
                @else
                    <img src="{{ base_path() . '/public/images/user.png' }}" alt="" width="150"
                        class="img-circle img-fluid">
                @endif
            </td>
        </tr>

    </table>
    <br> <br>
    <table width="100%">
        <tr>
            <td class="border" align="center" style=" width: 280px;">
                <h2>
                    {{ $titre_fiche }}
                </h2>
            </td>
        </tr>
    </table>
    <br> <br>

    <table width="100%">
        <tr>
            <td valign="top" align="center" style=" width: 280px;">

            </td>
            <td valign="top" style=" width: 150px;"></td>
            <td valign="top" style=" width: 150px;">
                <p class="m-0 pt-5 text-bold w-150">Date de l'évaluation
                    <span>{{ $resultatEvaluation->created_at->format('d/m/Y') }}</span></p>
            </td>
        </tr>
    </table>

    <h4 class="ml-50">
        I. <span class="text-underline">RENSEIGNEMENTS GENERAUX (partie réservée à la DRHL)</span>
    </h4>

    {{-- Identification de l'agent à noter --}}
    <table class="table w-100">
        <tr>
            <td colspan="2" class="text-bold f-15">1. <span class="text-underline">Identification de l'agent à
                    noter</span></td>
        </tr>
        <tr>
            <td>Nom (s) : <samp>{{ $infoAgent->nom }}</samp></td>
            <td>Prénom (s) : <samp>{{ $infoAgent->prenom }}</samp></td>
            <td>Matricule : <samp>{{ $infoAgent->recrutement->matricule }}</samp></td>
        </tr>
        <tr>
            <td>Grade : <samp>{{ $infoAgent->dernierTexte ? $infoAgent->dernierTexte->classe->grade : $infoAgent->dernierTexte->classe->grade }}</samp></td>
            <td>Fonction : <samp>{{ $infoAgent->fonction->nom }}</samp></td>
            <td>Ancienneté : <samp>{{ $infoAgent->getAgeAgent($infoAgent->dernierTexte->date_decision) }}</samp></td>
            {{-- <td>Ancienneté :getLocaliteNameAttribute <samp>{{   date('d-m-Y', strtotime($infoAgent->dateIntegration)) }}</samp></td> --}}
        </tr>
        @if ($infoAgent->structureable_type == 'App\Models\Bureau')
            <tr>
                <td>Direction : <samp>{{ $infoAgent->structureable->getDirectionNameAttribute() }}</samp></td>
                <td>Service : <samp>{{ $infoAgent->structureable->getParentStructureAttribute()->sigle }}</samp></td>
                <td>Bureau : <samp>{{ $infoAgent->structureable->sigle }}</samp></td>
            </tr>
        @elseif ($infoAgent->structureable_type == 'App\Models\Service')
            <tr>
                <td>Direction : <samp>{{ $infoAgent->structureable->direction->nom }}</samp></td>
                <td>Service : <samp>{{ $infoAgent->structureable->sigle }}</samp></td>
                <td>Bureau : <samp>-</samp></td>
            </tr>
        @elseif ($infoAgent->structureable_type == 'App\Models\Direction')
            <tr>
                <td>Direction : <samp>{{ $infoAgent->structureable->direction->nom }}</samp></td>
                <td>Service : <samp>-</samp></td>
                <td>Bureau : <samp>-</samp></td>
            </tr>
        @endif
    </table>
    <br>
    {{-- Identification du supérieur hiérarchique --}}
    <table class="table w-100">
        <tr>
            <td colspan="2" class="text-bold f-15">2. <span class="text-underline">Identification du supérieur
                    hiérarchique</span></td>
        </tr>
        <tr>
            <td>Nom (s) et Prénom (s) : <samp>{{ $infoAgentEvaluateur->nom }}
                    {{ $infoAgentEvaluateur->prenom }}</samp></td>
        </tr>
        <tr>
            <td>Fonction / Grade : <samp>{{ $infoAgentEvaluateur->fonction->nom }} / {{ $infoAgentEvaluateur->dernierTexte ? $infoAgentEvaluateur->dernierTexte->classe->grade : $infoAgentEvaluateur->dernierTexte->classe->grade }}</samp></td>
        </tr>
    </table>
    <div class="page_break"></div>
    <div class="table-section bill-tbl w-100 mt-10">
        <table class="table w-100 mt-10">
            <tr class="">
                <td colspan="2" class="text-bold f-15">3. <span class="text-underline">Suivi administratif (partie
                        réservée à la DRHL)</span></td>
            </tr>
            <tr>
                <td>
                    <table class="table w-100" style="height: 200px;">
                        <tr class="border">
                            <th class="border text-center">Nombre de jours d'absences non justifiées</th>
                        </tr>
                        <tr class="border">
                            <td class="border text-center">{{ $totalJoursAbsence }}</td>
                        </tr>

                    </table>
                </td>
                <td>
                    <table class="table w-100" style="height: 200px;">
                        <tr class="border">
                            <th class="border" colspan="2">Sanctions</th>
                        </tr>
                        {{-- <tr class="border">
                      <td class="border w-50">Nature</samp></td>
                      <td class="border w-50">Nombre de fois</samp></td>
                  </tr>
                  <tr class="border">
                      <td class="border w-50">Nature</samp></td>
                      <td class="border w-50">Nombre de fois</samp></td>
                  </tr> --}}
                        <tr class="border">
                            <td class="border w-50 text-center" colspan="2">Aucune sanction</samp></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <br>
    <h4 class="ml-50">
        II. <span class="text-underline">CRITERES GENERAUX D'EVALUATION</span>
    </h4>

    <table class="table w-100">
        <tr class="">
            <td class="w-85 text-bold f-15">1. <span class="text-underline">Compétences professionnelles et
                    techniques</span></td>
            <td class="w-15 text-bold f-15 border-bottom"></td>
            <td class="w-15 text-bold f-15 border-bottom"></td>
        </tr>
        <tr class="border-bottom">
            <td class="w-85 border-right border-bottom border-top"></td>
            <td class="w-15 border border-top text-center text-bold">Note</td>
            <td class="w-15 border border-top text-center text-bold">Barème</td>
        </tr>
        @foreach ($resultat_evaluation as $reslutat)
            @if ($reslutat->question->groupequestion_id == 1)
                <tr class="">
                    <td class="w-85 border">{{ $reslutat->question->title }}</td>
                    <td class="w-15 border text-center">{{ $reslutat->value }}</td>
                    <td class="w-15 border text-center">{{$reslutat->question->points[0]}} à {{$reslutat->question->points[count($reslutat->question->points) - 1]}}</td>
                </tr>
            @endif
        @endforeach
        <tr class="border-bottom">
            <td class="w-85"></td>
            <td class="w-15 border-bottom"></td>
            <td class="w-15 border-bottom"></td>
        </tr>
        <tr class="border-bottom">
            <td class="w-85 border-right"></td>
            <td class="w-15 border-top border-bottom text-center text-bold">Total</td>
            <td class="w-15 border border-top text-center text-bold">{{$resultatEvaluation->note_competences ? $resultatEvaluation->note_competences : ""}} / 10</td>
        </tr>
    </table>
    <div class="page_break"></div>
    <table class="table w-100">
        <tr class="">
            <td class="w-85 text-bold f-15">2. <span class="text-underline">Assiduité au travail</span></td>
            <td class="w-15 text-bold f-15 border-bottom"></td>
            <td class="w-15 text-bold f-15 border-bottom"></td>
        </tr>
        <tr class="border-bottom">
            <td class="w-85 border-right border-bottom border-top"></td>
            <td class="w-15 border border-top text-center text-bold">Note</td>
            <td class="w-15 border border-top text-center text-bold">Barème</td>
        </tr>
        @foreach ($resultat_evaluation as $reslutat)
            @if ($reslutat->question->groupequestion_id == 2)
                <tr class="">
                    <td class="w-85 border">{{ $reslutat->question->title }}</td>
                    <td class="w-15 border text-center">{{ $reslutat->value }}</td>
                    <td class="w-15 border text-center">{{$reslutat->question->points[0]}} à {{$reslutat->question->points[count($reslutat->question->points) - 1]}}</td>
                </tr>
            @endif
        @endforeach
        <tr class="border-bottom">
            <td class="w-85"></td>
            <td class="w-15 border-bottom"></td>
            <td class="w-15 border-bottom"></td>
        </tr>
        <tr class="border-bottom">
            <td class="w-85 border-right"></td>
            <td class="w-15 border-top border-bottom text-center text-bold">Total</td>
            <td class="w-15 border border-top text-center text-bold">{{$resultatEvaluation->note_assiduite ? $resultatEvaluation->note_assiduite : ""}} / 10</td>
        </tr>
    </table>
    <br>
    <table class="table w-100">
        <tr class="">
            <td class="w-85 text-bold f-15">3. <span class="text-underline">Relations sociales</span></td>
            <td class="w-15 text-bold f-15 border-bottom"></td>
            <td class="w-15 text-bold f-15 border-bottom"></td>
        </tr>
        <tr class="border-bottom">
            <td class="w-85 border-right border-bottom border-top"></td>
            <td class="w-15 border border-top text-center text-bold">Note</td>
            <td class="w-15 border border-top text-center text-bold">Barème</td>
        </tr>
        @foreach ($resultat_evaluation as $reslutat)
            @if ($reslutat->question->groupequestion_id == 3)
                <tr class="">
                    <td class="w-85 border">{{ $reslutat->question->title }}</td>
                    <td class="w-15 border text-center">{{ $reslutat->value }}</td>
                    <td class="w-15 border text-center">{{$reslutat->question->points[0]}} à {{$reslutat->question->points[count($reslutat->question->points) - 1]}}</td>
                </tr>
            @endif
        @endforeach
        <tr class="border-bottom">
            <td class="w-85"></td>
            <td class="w-15 border-bottom"></td>
            <td class="w-15 border-bottom"></td>
        </tr>
        <tr class="border-bottom">
            <td class="w-85 border-right"></td>
            <td class="w-15 border-top border-bottom text-center text-bold">Total</td>
            <td class="w-15 border border-top text-center text-bold">{{$resultatEvaluation->note_relations ? $resultatEvaluation->note_relations : ""}} / 10</td>
        </tr>
    </table>

    <h3>NOTE GLOBALE : {{ $note_globale }} /20</h3>

    <br>
    @if (count($connaissanceComp) > 0)
    <table width="100%">
        <tr class="">
            <td class="text-bold f-15" align="center"><span class="text-underline">Connaissances complémentaires à
                    acquérir (à énumérer)</span></td>
        </tr>
        <ul class="border">
            @foreach ($connaissanceComp as $connaissance)
                <li>{{ $connaissance->libelle }}</li>
            @endforeach
        </ul>
    </table>
    <div class="page_break"></div>
    @endif
    <div class="page_break"></div>
    <p class="text-bold f-15" align="center"><span class="text-underline">Avis</span></p>

    @foreach ($avis as $avi)
        <table width="100%">
            <ul class="border" style="">
                @if ($avi->user_avis->fonction_id == 2)
                    <p class="text-bold text-underline" align="center">Le Chef de bureau</p>
                @elseif ($avi->user_avis->fonction_id == 3)
                    <p class="text-bold text-underline" align="center">Le Chef de service</p>
                @elseif ($avi->user_avis->fonction_id == 4 || $avi->user_avis->fonction_id == 5)
                    <p class="text-bold text-underline" align="center">Le Directeur</p>
                @endif

                <p>{{ $avi->libelle }}</p>
                <p class="m-5" align="right">({{ $avi->user_avis->nom }} {{ $avi->user_avis->prenom }},
                    {{ $avi->created_at->format('d/m/Y') }} et signature)</p>

            </ul>
        </table>
    @endforeach
    @if ($reclamation)
    <div class="page_break"></div>
    <table width="100%">
        <tr class="">
            <td class="text-bold f-15" align="center"><span class="text-underline">Réclamations éventuelles (s'il y a
                    lieu)</span></td>
        </tr>
        <ul class="border">
            @if ($reclamation)
                <p>{{ $reclamation }}</p>
            @else
                <p>Aucune réclamations</p>
            @endif
        </ul>
    </table>
    @endif
    <table class="table w-100 mt-10">
        <tr class="">
            <td class="w-50 border-bottom">
                Signature de l'évaluateur :
            </td>
            <td>

            </td>
            <td class="w-50 border-bottom" align="right">
                Signature de l'évalué(e) :
            </td>
        </tr>
        <tr>
            <td class="border w-50" style="height: 70px">

            </td>
            <td class="border-right">

            </td>
            <td class="border border-left border-top w-50" align="right" style="height: 70px">

            </td>
        </tr>
    </table>

    @if (count($result_evaluation_generale) > 0)
    <div class="page_break"></div>
    <h4 class="ml-50">
        III. <span class="text-underline">EVALUATION GENERALE PAR LA COMMISSION</span>
    </h4>
    <table class="table w-100">
        <tr class="border-bottom">
            <td class="w-85 border-right border-bottom "></td>
            <td class="w-15 border border-top text-center text-bold">Note</td>
            <td class="w-15 border border-top text-center text-bold">Barème</td>
        </tr>
        @foreach ($result_evaluation_generale as $reslutat)
            @if ($reslutat->question)
                <tr class="">
                    <td class="w-85 border">{{ $reslutat->question->title }}</td>
                    <td class="w-15 border text-center">{{ $reslutat->value }}</td>
                    {{-- @foreach ($reslutat->question->points as $point)
                    <td class="w-15 border text-center">{{$point}}</td>
                    @endforeach --}}
                    <td class="w-15 border text-center">{{$reslutat->question->points[0]}} à {{$reslutat->question->points[count($reslutat->question->points) - 1]}}</td>
                </tr>
            @endif
        @endforeach
        <tr class="border-bottom">
            <td class="w-85"></td>
            <td class="w-15 border-bottom"></td>
            <td class="w-15 border-bottom"></td>
        </tr>
        <tr class="border-bottom">
            <td class="w-85 border-right"></td>
            <td class="w-15 border-top border-bottom text-center text-bold">Total</td>
            <td class="w-15 border border-top text-center text-bold">{{$evaluation_generale->note_globale ? $evaluation_generale->note_globale : ""}} / 10</td>
        </tr>


        {{-- @foreach ($result_evaluation_generale as $resultCommissionGeneralAgent)
            <tr class="">
                <td class="w-85 border">Connaissance technique</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->connaissance_technique }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Capacité à anticiper et programmer le travail</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->capacite_anticiper }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Autonomie et sens de responsabilité</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->autonomie_responsabilite }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Capacité à déléguer</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->capacite_deleguer }}</td>
                <td class="w-15 border text-center">0 à 0.5</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Qualité rédactionnelle</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->qualite_redactionnelle }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Prise d'initiatives</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->prise_initiative }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Fiabilité et qualité d'éxécution des taches</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->fiabilite_qualite }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Respect des délais et sens de l'organisation</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->respect_delais }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Rigueur et respect des procédures</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->rigueur_respect }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Capacité à partager l'information et rendre compte</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->capacite_partager }}</td>
                <td class="w-15 border text-center">0 à 0.5</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Curiosité professionnele</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->curiosite_professionnele }}</td>
                <td class="w-15 border text-center">0 à 0.5</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Capacité à identifier et à hiérarchiser les priorités</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->capacite_identifier }}</td>
                <td class="w-15 border text-center">0 à 0.5</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Ponctualité</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->ponctualite }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Disponibilité</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->disponibilite }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Disponibilité</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->disponibilite }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Serviabilité</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->serviabilite }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Capacité à animer et motiver l'équipe</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->capacite_animer }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Adaptabilité</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->adaptabilite }}</td>
                <td class="w-15 border text-center">0 à 0.5</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Communication</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->communication }}</td>
                <td class="w-15 border text-center">0 à 0.5</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Rapport avec la hiérarchie</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->rapport_hierarchie }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Rapport avec les collègues</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->rapport_collegue }}</td>
                <td class="w-15 border text-center">0 à 1</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Qualité de l'accueil</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->qualite_accueil }}</td>
                <td class="w-15 border text-center">0 à 0.5</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Faculté d'écoute et de réponse</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->faculte_ecoute }}</td>
                <td class="w-15 border text-center">0 à 0.5</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Capacité à travailler en équipe</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->capacite_equipe }}</td>
                <td class="w-15 border text-center">0 à 0.5</td>
            </tr>
            <tr class="">
                <td class="w-85 border">Respect du code vestimentaire</td>
                <td class="w-15 border text-center">{{ $resultCommissionGeneralAgent->respect_vestimentaire }}</td>
                <td class="w-15 border text-center">0 à 1.5</td>
            </tr>
            <tr class="">
                <td class="w-85 border-right"></td>
                <td colspan="2" class="w-15 border text-center text-bold">TOTAL :
                    {{ $resultCommissionGeneralAgent->note_total }} /10</td>
            </tr>
        @endforeach --}}
    </table>
    <div class="page_break"></div>
    @endif

    @if (count($avisPresident) > 0)
    <table width="100%">
        <tr class="">
            <td class="text-bold f-15" align="center"><span class="text-underline">Evaluation du Président de la
                    commission</span></td>
        </tr>
        <ul class="border">
            @foreach ($avisPresident as $avi)
                <p>{{ $avi->libelle }}</p>
            @endforeach
        </ul>
    </table>
    @endif

    @if ($decisionCommissionAvancement)
    <h4 class="ml-50">
        IV. <span class="text-underline">DECISION DE LA COMMISSION D'AVANCEMENT</span>
    </h4>

    <table width="100%">
        <ul class="border" style="">
            <p class="text-bold">Décision de la commission d'avancement : </p>

            <br>
            <p>{{ $decisionCommissionAvancement }}</p>
            <br>
            <p class="m-5" align="center">Fait à Brazzaville, le</p>
            <p class="m-5" align="center">LE PRESIDENT.</p>

        </ul>
    </table>
    @endif
</html>
