@extends('layouts.admin')

@section('content')
<div class="max-w-3xl mx-auto">
    {{-- En-tête --}}
    <div class="detail-header">
        <div>
            <h1 class="detail-title">Détail de la <strong>salle de classe</strong></h1>
            <p class="detail-subtitle">{{ $salleDeClasse->nom }} · {{ $salleDeClasse->section->nom ?? 'Section inconnue' }}</p>
        </div>
        <div class="detail-actions">
            <a href="{{ route('admin.salles-de-classe.index') }}" class="back-link">
                <i class="bi bi-arrow-left"></i> Retour
            </a>
            <button onclick="window.print()" class="print-btn">
                <i class="bi bi-printer"></i> Imprimer
            </button>
        </div>
    </div>

    {{-- Carte principale --}}
    <div class="detail-card">
        {{-- En-tête de carte --}}
        <div class="detail-card-header">
            <div class="detail-card-header-left">
                <div class="detail-card-icon">
                    <i class="bi bi-door-open"></i>
                </div>
                <div>
                    <h3 class="detail-card-title">{{ $salleDeClasse->nom }}</h3>
                    <div class="badge-group">
                        <span class="badge badge-section">
                            <i class="bi bi-building"></i> {{ $salleDeClasse->section->nom ?? 'Section inconnue' }}
                        </span>
                        <span class="badge badge-option">
                            <i class="bi bi-tag"></i> {{ $salleDeClasse->option->nom ?? 'Option indéfinie' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Corps --}}
        <div class="detail-body">
            <h4 class="section-title">
                <i class="bi bi-info-circle"></i> Informations principales
            </h4>
            <div class="info-grid">
                <div class="info-card">
                    <i class="bi bi-people"></i>
                    <div>
                        <span>Capacité maximale</span>
                        <p>{{ $salleDeClasse->capacite_max }} élèves</p>
                    </div>
                </div>
                <div class="info-card">
                    <i class="bi bi-calendar-range"></i>
                    <div>
                        <span>Tranche d'âge</span>
                        <p>{{ $salleDeClasse->age_min }} - {{ $salleDeClasse->age_max }} ans</p>
                    </div>
                </div>
                <div class="info-card">
                    <i class="bi bi-cash-coin"></i>
                    <div>
                        <span>Frais d'inscription</span>
                        <p>{{ number_format($salleDeClasse->frais_inscription, 0) }} $</p>
                    </div>
                </div>
                <div class="info-card">
                    <i class="bi bi-wallet2"></i>
                    <div>
                        <span>Frais annuel</span>
                        <p>{{ number_format($salleDeClasse->frais_annuel, 0) }} $</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Configuration des paiements --}}
        <div class="detail-body payment-section">
            <h4 class="section-title">
                <i class="bi bi-credit-card"></i> Configuration des paiements
            </h4>
            <div class="payment-grid">
                <div class="info-card">
                    <i class="bi bi-gear"></i>
                    <div>
                        <span>Mode de paiement</span>
                        <p class="capitalize">{{ $salleDeClasse->mode_paiement }}</p>
                    </div>
                </div>
                @if($salleDeClasse->mode_paiement === 'mensuel' && $salleDeClasse->frais_scolarite_mensuel)
                    <div class="info-card">
                        <i class="bi bi-cash"></i>
                        <div>
                            <span>Frais scolarité mensuel</span>
                            <p>{{ number_format($salleDeClasse->frais_scolarite_mensuel, 0) }} $ / mois</p>
                        </div>
                    </div>
                @elseif($salleDeClasse->mode_paiement === 'tranche' && $salleDeClasse->nombre_tranches)
                    <div class="info-card">
                        <i class="bi bi-list-ol"></i>
                        <div>
                            <span>Nombre de tranches</span>
                            <p>{{ $salleDeClasse->nombre_tranches }} tranche(s)</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Salle supérieure --}}
        @if($salleDeClasse->salleSuperieure)
            <div class="detail-body related-section">
                <div class="related-card">
                    <i class="bi bi-arrow-up-circle"></i>
                    <div>
                        <span>Salle supérieure</span>
                        <p>{{ $salleDeClasse->salleSuperieure->nom }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Description --}}
        @if($salleDeClasse->description)
            <div class="detail-body description-section">
                <h4 class="section-title">
                    <i class="bi bi-card-text"></i> Description
                </h4>
                <p class="description-text">{{ $salleDeClasse->description }}</p>
            </div>
        @endif

        {{-- Actions --}}
        <div class="detail-footer">
            <a href="{{ route('admin.salles-de-classe.edit', $salleDeClasse) }}" class="btn-action primary">
                <i class="bi bi-pencil"></i> Modifier
            </a>
            <form action="{{ route('admin.salles-de-classe.destroy', $salleDeClasse) }}" method="POST" onsubmit="return confirm('Supprimer cette salle ?')" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn-action danger">
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    /* ==== Styles locaux premium pour la page détail salle ==== */
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
    .detail-card-header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
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
    .detail-card-title {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.3rem;
    }
    .badge-group {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-section { background: #e0f2fe; color: #0284c7; }
    .badge-option { background: #fce7f3; color: #be185d; }

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
    .section-title i { color: #667eea; }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .info-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .info-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1rem;
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        transition: all 0.3s;
        border: 1px solid #f1f5f9;
    }
    .info-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.04);
        border-color: #e2e8f0;
    }
    .info-card i {
        color: #94a3b8;
        font-size: 1.2rem;
        margin-top: 0.1rem;
    }
    .info-card span {
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 500;
    }
    .info-card p {
        font-weight: 600;
        color: #1e293b;
        margin-top: 0.25rem;
        text-transform: capitalize;
    }

    .payment-section {
        border-top: 1px solid #f1f5f9;
    }
    .related-section {
        background: #f5f7ff;
        border-top: 1px solid #eef2ff;
    }
    .related-card {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        background: #eef2ff;
        border-radius: 12px;
        padding: 1rem 1.25rem;
    }
    .related-card i {
        color: #667eea;
        font-size: 1.2rem;
        margin-top: 0.1rem;
    }
    .related-card span {
        font-size: 0.75rem;
        color: #667eea;
        font-weight: 500;
    }
    .related-card p {
        font-weight: 600;
        color: #1e293b;
        margin-top: 0.25rem;
    }

    .description-section {
        border-top: 1px solid #f1f5f9;
    }
    .description-text {
        color: #475569;
        font-size: 0.95rem;
        line-height: 1.6;
    }

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
        .max-w-3xl, .max-w-3xl * { visibility: visible; }
        .max-w-3xl { position: absolute; left: 0; top: 0; width: 100%; }
        .detail-actions, .detail-footer, .print-btn { display: none !important; }
        .detail-card { box-shadow: none; }
    }
</style>
@endsection