<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche d'évaluation</title>
    <style>
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        .page { padding: 28px 36px; }
        .header { text-align: center; border-bottom: 3px double #003366; padding-bottom: 12px; margin-bottom: 18px; }
        .org { font-size: 12px; font-weight: bold; text-transform: uppercase; color: #003366; }
        .titre { font-size: 16px; font-weight: bold; text-transform: uppercase; margin-top: 8px; color: #003366; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; }
        th { background: #e8eef5; color: #003366; }
        .label { width: 38%; font-weight: bold; color: #444; }
        h2 { font-size: 12px; color: #003366; margin: 16px 0 8px; text-transform: uppercase; }
        .mention { font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
<div class="page">
    <div class="header">
        <div class="org">Autorité de Régulation des Transports Ferroviaires (ARTF)</div>
        <div class="titre">Fiche individuelle d'évaluation</div>
        <div>CCN art. 63 — Session {{ $fiche->session?->debut_session?->format('d/m/Y') }}</div>
    </div>

    <table>
        <tr><td class="label">Agent</td><td>{{ $fiche->agent?->prenom }} {{ $fiche->agent?->nom }} @if($fiche->agent?->matricule) ({{ $fiche->agent->matricule }}) @endif</td></tr>
        <tr><td class="label">Notateur (N+1)</td><td>{{ $fiche->superieur?->prenom }} {{ $fiche->superieur?->nom }}</td></tr>
        @if($fiche->affectationNotation)
        <tr>
            <td class="label">Poste de notation (art. 62)</td>
            <td>
                {{ $fiche->affectationNotation->structure?->nom ?? class_basename($fiche->affectationNotation->structurable_type) }}
                — du {{ $fiche->affectationNotation->date_affectation?->format('d/m/Y') }}
                au {{ $fiche->affectationNotation->date_fin?->format('d/m/Y') ?? 'en cours' }}
            </td>
        </tr>
        @endif
        <tr><td class="label">Statut</td><td>{{ $fiche->statut->label() }}</td></tr>
        <tr><td class="label">Note globale /20</td><td class="mention">{{ $fiche->note_globale !== null ? number_format($fiche->note_globale, 2, ',', ' ') : '—' }} — {{ $fiche->mention ?? '—' }}</td></tr>
    </table>

    <h2>Notation par critère</h2>
    <table>
        <thead>
        <tr><th>Critère</th><th>Note</th><th>Barème</th><th>Commentaire</th></tr>
        </thead>
        <tbody>
        @forelse($fiche->notes as $note)
            <tr>
                <td>{{ $note->question?->libelle ?? '#' . $note->question_id }}</td>
                <td>{{ number_format((float) $note->note_obtenue, 2, ',', ' ') }}</td>
                <td>{{ $note->question?->bareme_max !== null ? number_format((float) $note->question->bareme_max, 2, ',', ' ') : '—' }}</td>
                <td>{{ $note->commentaire ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Aucune note saisie.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Avis du notateur</h2>
    <p>{{ $fiche->avis_superieur ?: '—' }}</p>
    <p>Signé notateur : {{ $fiche->signe_par_evaluateur_at?->format('d/m/Y H:i') ?? '—' }}
        — Signé agent : {{ $fiche->signe_par_evalue_at?->format('d/m/Y H:i') ?? '—' }}</p>

    @if($fiche->avisHierarchiques->isNotEmpty())
        <h2>Avis hiérarchiques (art. 64)</h2>
        <table>
            <thead><tr><th>Niveau</th><th>Avis</th><th>Signé</th></tr></thead>
            <tbody>
            @foreach($fiche->avisHierarchiques as $avis)
                <tr>
                    <td>{{ $avis->niveau?->label() }}</td>
                    <td>{{ $avis->avis ?: '—' }}</td>
                    <td>{{ $avis->signe ? ($avis->date_signature?->format('d/m/Y') ?? 'oui') : 'non' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    @if($fiche->reclamation)
        <h2>Réclamation (art. 65)</h2>
        <p>{{ $fiche->reclamation->motif }}</p>
    @endif

    @if($fiche->commission_note || $fiche->note_synthese || $fiche->note_avancement || $fiche->commission_decision)
        <h2>Commissions</h2>
        <table>
            <tr><td class="label">Note préparatoire</td><td>{{ $fiche->commission_note !== null ? number_format($fiche->commission_note, 2, ',', ' ') : '—' }}</td></tr>
            <tr><td class="label">Note de synthèse</td><td>{{ $fiche->note_synthese ?: '—' }}</td></tr>
            <tr><td class="label">Note à l'avancement</td><td>{{ $fiche->note_avancement !== null ? number_format($fiche->note_avancement, 2, ',', ' ') : '—' }}</td></tr>
            <tr><td class="label">Décision</td><td>{{ $fiche->commission_decision?->label() ?? '—' }} @if($fiche->nombre_echelons) ({{ $fiche->nombre_echelons }} échelon(s)) @endif</td></tr>
        </table>
    @endif
</div>
</body>
</html>
