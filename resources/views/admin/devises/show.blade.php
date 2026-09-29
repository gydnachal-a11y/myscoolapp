@extends('layouts.admin')

@section('page_title', 'Détail devise')
@section('page_subtitle', $devise->code . ' — ' . $devise->nom)

@section('content')
<div class="page">
    <header class="page-header">
        <div>
            <h1 class="page-title">
                <i class="fa-solid fa-coins title-icon"></i>
                {{ $devise->code }}
                @if($devise->est_defaut)
                    <span class="pill pill-success">
                        <i class="fa-solid fa-star"></i> Par défaut
                    </span>
                @endif
            </h1>
            <p class="page-subtitle">{{ $devise->nom }}</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('admin.devises.index') }}" class="btn btn-ghost">
                <i class="fa-solid fa-arrow-left"></i> Retour
            </a>
            <a href="{{ route('admin.devises.edit', $devise) }}" class="btn btn-primary">
                <i class="fa-solid fa-pen-to-square"></i> Modifier
            </a>
        </div>
    </header>

    <section class="content-card">
        <dl class="detail-list">
            <div class="detail-row">
                <dt>Code</dt>
                <dd><span class="devise-code">{{ $devise->code }}</span></dd>
            </div>
            <div class="detail-row">
                <dt>Nom</dt>
                <dd>{{ $devise->nom }}</dd>
            </div>
            <div class="detail-row">
                <dt>Symbole</dt>
                <dd>{{ $devise->symbole ?? '—' }}</dd>
            </div>
            <div class="detail-row">
                <dt>Par défaut</dt>
                <dd>{{ $devise->est_defaut ? 'Oui' : 'Non' }}</dd>
            </div>
        </dl>
    </section>
</div>

<style>
    .page { max-width: 800px; margin: 0 auto; padding: 2rem 1rem; }
    .page-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
    .page-title { display: flex; align-items: center; flex-wrap: wrap; gap: 0.6rem; font-size: 1.75rem; font-weight: 800; color: #0f172a; margin: 0 0 0.25rem; }
    .title-icon { color: #6366f1; }
    .page-subtitle { color: #64748b; font-size: 0.9rem; margin: 0; }
    .header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }
    .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.7rem 1.25rem; border-radius: 12px; font-weight: 600; font-size: 0.9rem; text-decoration: none; border: none; cursor: pointer; }
    .btn-primary { background: linear-gradient(135deg, #4f46e5, #6366f1); color: #fff; }
    .btn-ghost { background: #fff; color: #64748b; border: 1.5px solid #e2e8f0; }
    .content-card { background: #fff; border-radius: 16px; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 1.5rem; }
    .detail-list { margin: 0; display: flex; flex-direction: column; gap: 0.75rem; }
    .detail-row { display: flex; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid #f1f5f9; }
    .detail-row:last-child { border-bottom: none; }
    .detail-row dt { color: #64748b; font-weight: 600; font-size: 0.9rem; }
    .detail-row dd { color: #0f172a; font-weight: 600; margin: 0; text-align: right; }
    .devise-code { display: inline-flex; padding: 0.25rem 0.65rem; background: #eef2ff; color: #4338ca; font-weight: 800; border-radius: 8px; font-size: 0.9rem; }
    .pill { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 600; }
    .pill-success { background: #ecfdf5; color: #047857; }
</style>
@endsection