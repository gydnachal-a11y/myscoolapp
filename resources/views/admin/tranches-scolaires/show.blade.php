@extends('layouts.admin')

@section('page_title', 'Détail de la tranche')
@section('page_subtitle', $trancheScolaire->tranche)

@section('content')
<div class="index-container" style="max-width:900px;">

    <div style="margin-bottom:1.5rem;">
        <a href="{{ route('admin.tranches-scolaires.index') }}"
           class="btn-primary" style="background:#64748b;padding:0.7rem 1.25rem;border-radius:12px;text-decoration:none;color:#fff;display:inline-flex;gap:8px;font-weight:600;">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="table-card" style="padding:2rem;background:#fff;border-radius:16px;box-shadow:0 10px 20px rgba(0,0,0,0.05);">
        <h2 style="margin-bottom:1.5rem;font-size:1.5rem;font-weight:700;color:#1e293b;">
            {{ $trancheScolaire->tranche }}
        </h2>

        <dl style="display:grid;grid-template-columns:180px 1fr;gap:1rem;color:#475569;">
            <dt style="font-weight:600;">Période</dt>
            <dd>{{ $trancheScolaire->periode }}</dd>

            <dt style="font-weight:600;">Durée</dt>
            <dd>{{ $trancheScolaire->duree_jours }} jours</dd>

            <dt style="font-weight:600;">Année scolaire</dt>
            <dd>{{ $trancheScolaire->anneeScolaire?->libelle ?? '—' }}</dd>

            <dt style="font-weight:600;">Statut</dt>
            <dd>
                @if($trancheScolaire->est_en_cours)
                    <span style="color:#16a34a;font-weight:600;">● En cours</span>
                @else
                    <span style="color:#94a3b8;">—</span>
                @endif
            </dd>
        </dl>

        <div style="margin-top:2rem;display:flex;gap:0.75rem;">
            <a href="{{ route('admin.tranches-scolaires.edit', $trancheScolaire) }}"
               class="btn-primary" style="background:#1e293b;padding:0.7rem 1.25rem;border-radius:12px;text-decoration:none;color:#fff;display:inline-flex;gap:8px;font-weight:600;">
                <i class="fa-solid fa-pen-to-square"></i> Modifier
            </a>
        </div>
    </div>
</div>
@endsection