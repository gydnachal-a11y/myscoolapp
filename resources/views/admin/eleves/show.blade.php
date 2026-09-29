@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto">
    {{-- En-tête --}}
    <div class="detail-header">
        <div>
            <h1 class="detail-title">Détail de l'<strong>élève</strong></h1>
            <p class="detail-subtitle">{{ $eleve->nom }} {{ $eleve->postnom }} {{ $eleve->prenom }}</p>
        </div>
        <div class="detail-actions">
            <a href="{{ route('admin.eleves.index') }}" class="back-link">
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
                @if($eleve->photo)
                    <img src="{{ asset('storage/' . $eleve->photo) }}" alt="Photo de l'élève" class="eleve-photo">
                @else
                    <div class="eleve-avatar">
                        {{ strtoupper(substr($eleve->nom, 0, 1) . substr($eleve->prenom, 0, 1)) }}
                    </div>
                @endif
                <div class="eleve-identity">
                    <h3 class="eleve-name">{{ $eleve->nom }} {{ $eleve->postnom }} {{ $eleve->prenom }}</h3>
                    <div class="eleve-badges">
                        @if($eleve->sexe === 'M')
                            <span class="badge badge-male"><i class="bi bi-gender-male"></i> Garçon</span>
                        @else
                            <span class="badge badge-female"><i class="bi bi-gender-female"></i> Fille</span>
                        @endif
                        <span class="badge badge-age"><i class="bi bi-cake"></i> {{ $eleve->date_naissance->age ?? '?' }} ans</span>
                    </div>
                </div>
            </div>
            <div class="detail-card-icon">
                <i class="bi bi-person-badge"></i>
            </div>
        </div>

        {{-- Section Informations personnelles --}}
        <div class="detail-body">
            <h4 class="section-title">
                <i class="bi bi-person"></i> Informations personnelles
            </h4>
            <div class="info-grid">
                <div class="info-card">
                    <i class="bi bi-calendar"></i>
                    <div>
                        <span>Date de naissance</span>
                        <p>{{ $eleve->date_naissance->format('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="info-card">
                    <i class="bi bi-geo-alt"></i>
                    <div>
                        <span>Lieu de naissance</span>
                        <p>{{ $eleve->lieu_naissance }}</p>
                    </div>
                </div>
                <div class="info-card">
                    <i class="bi bi-house"></i>
                    <div>
                        <span>Adresse</span>
                        <p>{{ $eleve->adresse }}</p>
                    </div>
                </div>
                <div class="info-card">
                    <i class="bi bi-heart-pulse"></i>
                    <div>
                        <span>Maladie chronique</span>
                        <p>{{ $eleve->maladie_chronique ?? 'Aucune' }}</p>
                    </div>
                </div>
                <div class="info-card">
                    <i class="bi bi-exclamation-triangle"></i>
                    <div>
                        <span>Allergies</span>
                        <p>{{ $eleve->allergies ?? 'Aucune' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section Responsables --}}
        <div class="detail-body responsables-section">
            <h4 class="section-title">
                <i class="bi bi-people"></i> Responsables
            </h4>
            @if($eleve->responsables->isNotEmpty())
                <div class="responsables-grid">
                    @foreach($eleve->responsables as $resp)
                        <div class="responsable-card">
                            <div class="responsable-card-top">
                                <span class="responsable-type {{ $resp->type }}">
                                    {{ ucfirst($resp->type) }}
                                </span>
                                <span class="status-badge {{ $resp->vivant ? 'status-vivant' : 'status-decede' }}">
                                    {{ $resp->vivant ? 'Vivant' : 'Décédé' }}
                                </span>
                            </div>
                            <p class="responsable-nom">{{ $resp->nom }}</p>
                            <p class="responsable-details">
                                {{ $resp->profession ?? '—' }} · {{ $resp->telephone }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="empty-text">Aucun responsable enregistré.</p>
            @endif
        </div>

        {{-- Actions --}}
        <div class="detail-footer">
            <a href="{{ route('admin.eleves.edit', $eleve) }}" class="btn-action primary">
                <i class="bi bi-pencil"></i> Modifier
            </a>
            <form action="{{ route('admin.eleves.destroy', $eleve) }}" method="POST" onsubmit="return confirm('Supprimer cet élève ?')" class="inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn-action danger">
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    /* ==== Styles locaux premium pour la page détail élève ==== */
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
    .eleve-photo {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        object-fit: cover;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .eleve-avatar {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 700;
        box-shadow: 0 4px 12px rgba(102,126,234,0.3);
    }
    .eleve-name {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }
    .eleve-badges {
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
    .badge-male { background: #dbeafe; color: #1d4ed8; }
    .badge-female { background: #fce7f3; color: #be185d; }
    .badge-age { background: #fef3c7; color: #b45309; }
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
    }

    .responsables-section {
        border-top: 1px solid #f1f5f9;
    }
    .responsables-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .responsables-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 1024px) {
        .responsables-grid { grid-template-columns: repeat(3, 1fr); }
    }
    .responsable-card {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1rem;
        transition: all 0.3s;
        border: 1px solid #f1f5f9;
    }
    .responsable-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.04);
        border-color: #e2e8f0;
    }
    .responsable-card-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }
    .responsable-type {
        font-weight: 700;
        text-transform: capitalize;
        color: #1e293b;
    }
    .responsable-type.pere { color: #2563eb; }
    .responsable-type.mere { color: #db2777; }
    .responsable-type.tuteur { color: #7c3aed; }
    .status-badge {
        font-size: 0.7rem;
        padding: 0.2rem 0.6rem;
        border-radius: 12px;
        font-weight: 600;
    }
    .status-vivant { background: #dcfce7; color: #16a34a; }
    .status-decede { background: #fee2e2; color: #dc2626; }
    .responsable-nom {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.25rem;
    }
    .responsable-details {
        font-size: 0.85rem;
        color: #64748b;
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

    .empty-text { color: #94a3b8; font-size: 0.9rem; }

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