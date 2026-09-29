@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- En-tête --}}
    <div class="detail-header">
        <div>
            <h1 class="detail-title">Détail du <strong>cours</strong></h1>
            <p class="detail-subtitle">{{ $cour->nom }} · {{ $cour->categorie->nom ?? 'Sans catégorie' }}</p>
        </div>
        <div class="detail-actions">
            <a href="{{ route('admin.cours.index') }}" class="back-link">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <button onclick="window.print()" class="print-btn">
                <i class="bi bi-printer"></i> Imprimer
            </button>
        </div>
    </div>

    {{-- Carte principale --}}
    <div class="detail-card">
        <div class="detail-card-header">
            <div>
                <h3 class="detail-card-title">{{ $cour->nom }}</h3>
                <span class="badge-category">
                    <i class="bi bi-folder"></i> {{ $cour->categorie->nom ?? 'Sans catégorie' }}
                </span>
            </div>
            <div class="detail-card-icon">
                <i class="bi bi-book"></i>
            </div>
        </div>

        <div class="detail-body">
            <h4 class="section-title">
                <i class="bi bi-building"></i> Salles assignées
            </h4>

            @if($assignations->isNotEmpty())
                <div class="assign-grid">
                    @foreach($assignations as $assign)
                        <div class="assign-card">
                            <div class="assign-card-top">
                                <span class="salle-name">
                                    <i class="bi bi-door-open"></i> {{ $assign->salle->nom }}
                                </span>
                                @if($assign->titulaire)
                                    <span class="titulaire-badge">
                                        <i class="bi bi-person"></i> {{ $assign->titulaire->name }}
                                    </span>
                                @endif
                            </div>
                            <div class="assign-details">
                                <span class="detail-item">
                                    <i class="bi bi-tag"></i> {{ $assign->libelle->nom ?? '—' }}
                                </span>
                                <span class="detail-item">
                                    <i class="bi bi-clock"></i> {{ $assign->nombreHeure->libelle ?? '—' }}
                                </span>
                                <span class="detail-item">
                                    <i class="bi bi-percent"></i> {{ $assign->ponderation->nom ?? '—' }} ({{ $assign->ponderation->valeur ?? '' }})
                                </span>
                                @if($assign->jours)
                                    <span class="detail-item">
                                        <i class="bi bi-calendar-week"></i> {{ $assign->jours }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="bi bi-inbox"></i>
                    <p>Aucune salle assignée à ce cours.</p>
                </div>
            @endif
        </div>

        <div class="detail-footer">
            <a href="{{ route('admin.cours.assign', $cour) }}" class="btn-action orange">
                <i class="bi bi-gear"></i> Gérer les assignations
            </a>
            <a href="{{ route('admin.cours.edit', $cour) }}" class="btn-action primary">
                <i class="bi bi-pencil"></i> Modifier
            </a>
            <form action="{{ route('admin.cours.destroy', $cour) }}" method="POST" onsubmit="return confirm('Supprimer ce cours ?')" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn-action danger">
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    /* ==== Styles locaux pour la page détail (premium) ==== */
    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        animation: fadeUp 0.6s 0.1s ease forwards;
        opacity: 0;
    }
    .detail-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.2rem;
        letter-spacing: -0.5px;
    }
    .detail-title strong { font-weight: 800; }
    .detail-subtitle { color: #94a3b8; font-size: 0.9rem; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .detail-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #667eea;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: color 0.2s;
    }
    .back-link:hover { color: #4f46e5; }
    .print-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 0.5rem 1rem;
        background: #f1f5f9;
        border: none;
        border-radius: 10px;
        font-size: 0.85rem;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s;
    }
    .print-btn:hover { background: #e2e8f0; }

    .detail-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.05);
        overflow: hidden;
        animation: cardIn 0.8s cubic-bezier(0.16,1,0.3,1) forwards;
        opacity: 0;
    }
    @keyframes cardIn {
        from { opacity: 0; transform: translateY(25px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .detail-card-header {
        background: linear-gradient(135deg, #f5f7ff 0%, #faf5ff 100%);
        padding: 1.5rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #eef2ff;
    }
    .detail-card-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.3rem;
    }
    .badge-category {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #eef2ff;
        color: #667eea;
        padding: 0.3rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .detail-card-icon {
        width: 48px;
        height: 48px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        box-shadow: 0 8px 16px rgba(102,126,234,0.2);
    }

    .detail-body {
        padding: 2rem;
    }
    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i { color: #94a3b8; }

    .assign-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .assign-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .assign-card {
        background: #f8fafc;
        border-radius: 16px;
        padding: 1.25rem;
        transition: all 0.3s;
        border: 1px solid #f1f5f9;
    }
    .assign-card:hover {
        box-shadow: 0 8px 16px rgba(0,0,0,0.06);
        transform: translateY(-3px);
        border-color: #e2e8f0;
    }
    .assign-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }
    .salle-name {
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .titulaire-badge {
        background: #e0f2fe;
        color: #0284c7;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .assign-details {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .detail-item {
        background: white;
        padding: 0.3rem 0.6rem;
        border-radius: 8px;
        font-size: 0.8rem;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border: 1px solid #f1f5f9;
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #94a3b8;
        font-size: 0.9rem;
    }
    .empty-state i { font-size: 2rem; display: block; margin-bottom: 0.5rem; }

    .detail-footer {
        padding: 1.25rem 2rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .btn-action {
        padding: 0.6rem 1.25rem;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        border: none;
    }
    .btn-action i { font-size: 1rem; }
    .btn-action.primary { background: #667eea; color: white; }
    .btn-action.primary:hover { background: #5a67d8; transform: translateY(-2px); box-shadow: 0 8px 16px rgba(102,126,234,0.3); }
    .btn-action.orange { background: #f59e0b; color: white; }
    .btn-action.orange:hover { background: #d97706; transform: translateY(-2px); box-shadow: 0 8px 16px rgba(245,158,11,0.3); }
    .btn-action.danger { background: #ef4444; color: white; }
    .btn-action.danger:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 8px 16px rgba(239,68,68,0.3); }

    /* Responsive */
    @media (max-width: 768px) {
        .detail-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
        .detail-footer { justify-content: flex-start; }
        .detail-card-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
        .detail-card-icon { position: absolute; right: 1.5rem; top: 1.5rem; }
    }

    /* Impression */
    @media print {
        body * { visibility: hidden; }
        .max-w-4xl, .max-w-4xl * { visibility: visible; }
        .max-w-4xl { position: absolute; left: 0; top: 0; width: 100%; }
        .detail-actions, .detail-footer, .print-btn { display: none !important; }
        .detail-card { box-shadow: none; }
    }
</style>
@endsection