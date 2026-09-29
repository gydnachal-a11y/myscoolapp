@extends('layouts.admin')

@section('page_title', 'Détail du mois')
@section('page_subtitle', $moisScolaire->libelle_complet)

@section('content')
<div class="index-container" style="max-width:900px;">

    <div style="margin-bottom:1.5rem;">
        <a href="{{ route('admin.mois-scolaires.index') }}" class="btn-primary" style="background:#64748b;">
            <i class="fa-solid fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="table-card" style="padding:2rem;">
        <h2 style="margin-bottom:1.5rem;font-size:1.5rem;font-weight:700;color:#1e293b;">
            {{ $moisScolaire->libelle_complet }}
        </h2>

        <dl style="display:grid;grid-template-columns:180px 1fr;gap:1rem;color:#475569;">
            <dt style="font-weight:600;">Code</dt>
            <dd>{{ $moisScolaire->mois }}</dd>

            <dt style="font-weight:600;">Période</dt>
            <dd>{{ $moisScolaire->periode }}</dd>

            <dt style="font-weight:600;">Année scolaire</dt>
            <dd>{{ $moisScolaire->anneeScolaire?->libelle ?? '—' }}</dd>

            <dt style="font-weight:600;">Paiements liés</dt>
            <dd>{{ $moisScolaire->paiementSalaires->count() }}</dd>
        </dl>

        <div style="margin-top:2rem;display:flex;gap:0.75rem;">
            <a href="{{ route('admin.mois-scolaires.edit', $moisScolaire) }}" class="btn-primary">
                <i class="fa-solid fa-pen-to-square"></i> Modifier
            </a>
        </div>
    </div>
</div>
@endsection