@extends('layouts.admin')

@section('page_title', 'Détail de la période')
@section('page_subtitle', $periodeNote->nom)

@section('content')
<div class="container" style="max-width:900px; padding:2rem 1rem;">

    <div style="margin-bottom:1.5rem;">
        <a href="{{ route('admin.periode-notes.index') }}"
           style="display:inline-flex;align-items:center;gap:8px;padding:0.7rem 1.25rem;background:#64748b;color:#fff;border-radius:12px;text-decoration:none;font-weight:600;">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>

    <div style="background:#fff;border-radius:16px;box-shadow:0 10px 20px rgba(0,0,0,0.05);padding:2rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h2 style="font-size:1.5rem;font-weight:700;color:#1e293b;">{{ $periodeNote->nom }}</h2>
            <span style="padding:0.35rem 0.9rem;border-radius:9999px;font-size:0.85rem;font-weight:600;
                background:{{ $periodeNote->est_active ? '#dcfce7' : '#fee2e2' }};
                color:{{ $periodeNote->est_active ? '#166534' : '#991b1b' }};">
                {{ $periodeNote->statut_texte }}
            </span>
        </div>

        <dl style="display:grid;grid-template-columns:200px 1fr;gap:1rem;color:#475569;">
            <dt style="font-weight:600;">Période</dt>
            <dd>{{ $periodeNote->periode_formatee }}</dd>

            <dt style="font-weight:600;">Durée</dt>
            <dd>{{ $periodeNote->duree_jours ? $periodeNote->duree_jours . ' jours' : '—' }}</dd>

            <dt style="font-weight:600;">Année scolaire</dt>
            <dd>{{ $periodeNote->anneeScolaire?->libelle ?? '—' }}</dd>

            <dt style="font-weight:600;">Notes rattachées</dt>
            <dd>{{ $periodeNote->notes_count }}</dd>

            <dt style="font-weight:600;">Description</dt>
            <dd>{{ $periodeNote->description ?: '—' }}</dd>
        </dl>

        <div style="margin-top:2rem;display:flex;gap:0.75rem;">
            <a href="{{ route('admin.periode-notes.edit', $periodeNote) }}"
               style="display:inline-flex;align-items:center;gap:8px;padding:0.7rem 1.25rem;background:#1e293b;color:#fff;border-radius:12px;text-decoration:none;font-weight:600;">
                <i class="fa-solid fa-pen-to-square"></i> Modifier
            </a>
        </div>
    </div>
</div>
@endsection