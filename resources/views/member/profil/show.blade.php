@extends('layouts.admin')

@section('page_title', 'Mon profil')
@section('page_subtitle', 'Consultez vos informations personnelles')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    /* ============================================================
       LAYOUT
       ============================================================ */
    .profil-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
    }

    .profil-card {
        background: #fff;
        border-radius: 1.5rem;
        overflow: hidden;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
        border: 1px solid #f1f5f9;
    }

    /* ============================================================
       COVER + AVATAR
       ============================================================ */
    .profil-cover {
        height: 120px;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        position: relative;
        overflow: hidden;
    }
    .profil-cover::before {
        content: '';
        position: absolute;
        top: -30%; right: -10%;
        width: 240px; height: 240px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
    }
    .profil-cover::after {
        content: '';
        position: absolute;
        bottom: -60%; left: -5%;
        width: 180px; height: 180px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    .profil-header {
        text-align: center;
        padding: 0 2rem 1.5rem;
        position: relative;
    }

    .profil-avatar {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        border: 5px solid #fff;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 800;
        color: #fff;
        margin: -55px auto 1rem;
        position: relative;
        box-shadow: 0 10px 30px rgba(79, 70, 229, 0.25);
        overflow: hidden;
        flex-shrink: 0;
    }
    .profil-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
    }
    .profil-avatar .online-dot {
        position: absolute;
        bottom: 4px;
        right: 4px;
        width: 20px;
        height: 20px;
        background: #10b981;
        border: 3px solid #fff;
        border-radius: 50%;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
    }

    .profil-name {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1e293b;
        letter-spacing: -0.02em;
        margin: 0 0 0.5rem;
    }

    .profil-badges {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.85rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .badge-role {
        background: #eef2ff;
        color: #4f46e5;
    }
    .badge-fonction {
        background: #f1f5f9;
        color: #475569;
    }

    .profil-actions-top {
        display: flex;
        justify-content: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .btn-primary,
    .btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.7rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1.5px solid transparent;
        cursor: pointer;
    }

    .btn-primary {
        background: #4f46e5;
        color: #fff;
        box-shadow: 0 4px 14px rgba(79, 70, 229, 0.25);
    }
    .btn-primary:hover {
        background: #3730a3;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(79, 70, 229, 0.35);
    }

    .btn-outline {
        background: #fff;
        color: #475569;
        border-color: #e2e8f0;
    }
    .btn-outline:hover {
        border-color: #4f46e5;
        color: #4f46e5;
        background: #f8fafc;
    }

    /* ============================================================
       SECTIONS
       ============================================================ */
    .profil-body {
        padding: 0 2rem 2rem;
    }

    .info-section {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #f1f5f9;
    }
    .info-section:first-child {
        margin-top: 0;
        padding-top: 0;
        border-top: none;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1.25rem;
    }
    .section-title .section-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #eef2ff;
        color: #4f46e5;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .info-item {
        padding: 1rem;
        background: #f8fafc;
        border-radius: 12px;
        border: 1px solid #f1f5f9;
        transition: all 0.25s;
    }
    .info-item:hover {
        border-color: #c7d2fe;
        background: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.06);
    }

    .info-item-label {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.72rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.4rem;
    }
    .info-item-label i {
        color: #4f46e5;
        font-size: 0.75rem;
    }

    .info-item-value {
        font-size: 1rem;
        font-weight: 600;
        color: #1e293b;
        word-break: break-word;
    }
    .info-item-value.empty {
        color: #cbd5e1;
        font-style: italic;
        font-weight: 500;
    }

    .info-item.full-width {
        grid-column: 1 / -1;
    }

    /* ============================================================
       SITUATION FINANCIÈRE (optionnelle)
       ============================================================ */
    .finance-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
    }

    .finance-card {
        padding: 1.1rem;
        border-radius: 12px;
        border: 1px solid #f1f5f9;
        background: #fff;
        text-align: center;
    }
    .finance-card.finance-salaire {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border-color: #bbf7d0;
    }
    .finance-card.finance-dette {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        border-color: #fecaca;
    }
    .finance-card.finance-limite {
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        border-color: #bfdbfe;
    }

    .finance-label {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 0.4rem;
    }
    .finance-value {
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }
    .finance-card.finance-salaire .finance-value { color: #15803d; }
    .finance-card.finance-dette .finance-value { color: #dc2626; }
    .finance-card.finance-limite .finance-value { color: #1e40af; }

    .finance-fc {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 0.2rem;
    }

    /* ============================================================
       RESPONSIVE
       ============================================================ */
    @media (max-width: 768px) {
        .profil-container { padding: 1.25rem 0.75rem; }
        .profil-body { padding: 0 1.25rem 1.5rem; }
        .profil-header { padding: 0 1.25rem 1.25rem; }
        .profil-avatar { width: 90px; height: 90px; margin-top: -45px; font-size: 2rem; }
        .profil-name { font-size: 1.25rem; }

        .info-grid,
        .finance-grid { grid-template-columns: 1fr; }

        .profil-actions-top { flex-direction: column; }
        .profil-actions-top .btn-primary,
        .profil-actions-top .btn-outline {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush

@section('content')
<div class="profil-container">
    <div class="profil-card">

        {{-- Cover gradient --}}
        <div class="profil-cover"></div>

        {{-- En-tête : avatar + nom + badges + actions --}}
        <div class="profil-header">
            <div class="profil-avatar">
                @if($user->photo_url)
                    <img src="{{ $user->photo_url }}" alt="{{ $user->name }}">
                @else
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                @endif
                <span class="online-dot"></span>
            </div>

            <h1 class="profil-name">{{ $user->name }}</h1>

            <div class="profil-badges">
                <span class="badge badge-role">
                    <i class="fa-solid fa-shield-halved"></i>
                    {{ $user->role_label ?? 'Membre' }}
                </span>
                @if($user->fonction)
                    <span class="badge badge-fonction">
                        <i class="fa-solid fa-briefcase"></i>
                        {{ $user->fonction->nom }}
                    </span>
                @endif
            </div>

            <div class="profil-actions-top">
                <a href="{{ route('member.profil.edit') }}" class="btn-primary">
                    <i class="fa-regular fa-pen-to-square"></i> Modifier mon profil
                </a>
                <a href="{{ route('dashboard.index') }}" class="btn-outline">
                    <i class="fa-solid fa-arrow-left"></i> Retour au tableau de bord
                </a>
            </div>
        </div>

        {{-- Corps : informations --}}
        <div class="profil-body">

            {{-- Messages flash --}}
            @if(session('success'))
                <div style="margin-bottom: 1.5rem; padding: 1rem 1.25rem; background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; border-radius: 12px; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-circle-check" style="font-size: 1.1rem;"></i>
                    <span style="font-size: 0.9rem; font-weight: 500;">{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div style="margin-bottom: 1.5rem; padding: 1rem 1.25rem; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; border-radius: 12px; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fa-solid fa-circle-exclamation" style="font-size: 1.1rem;"></i>
                    <span style="font-size: 0.9rem; font-weight: 500;">{{ session('error') }}</span>
                </div>
            @endif

            {{-- ============================================
                 IDENTITÉ
                 ============================================ --}}
            <div class="info-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <span>Identité</span>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-item-label">
                            <i class="fa-regular fa-user"></i> Nom complet
                        </div>
                        <div class="info-item-value">{{ $user->name }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-item-label">
                            <i class="fa-solid fa-venus-mars"></i> Sexe
                        </div>
                        <div class="info-item-value {{ !$user->sexe ? 'empty' : '' }}">
                            @if($user->sexe === 'M')
                                Masculin
                            @elseif($user->sexe === 'F')
                                Féminin
                            @else
                                Non renseigné
                            @endif
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-item-label">
                            <i class="fa-regular fa-calendar"></i> Date de naissance
                        </div>
                        <div class="info-item-value {{ !$user->date_naissance ? 'empty' : '' }}">
                            {{ $user->date_naissance?->translatedFormat('d F Y') ?? 'Non renseignée' }}
                        </div>
                    </div>

                    @if($user->matricule)
                        <div class="info-item">
                            <div class="info-item-label">
                                <i class="fa-regular fa-id-card"></i> Matricule
                            </div>
                            <div class="info-item-value">{{ $user->matricule }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ============================================
                 CONTACT
                 ============================================ --}}
            <div class="info-section">
                <div class="section-title">
                    <div class="section-icon">
                        <i class="fa-solid fa-address-book"></i>
                    </div>
                    <span>Coordonnées</span>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-item-label">
                            <i class="fa-regular fa-envelope"></i> Email
                        </div>
                        <div class="info-item-value">{{ $user->email }}</div>
                    </div>

                    <div class="info-item">
                        <div class="info-item-label">
                            <i class="fa-regular fa-phone"></i> Téléphone
                        </div>
                        <div class="info-item-value {{ !$user->telephone ? 'empty' : '' }}">
                            {{ $user->telephone ?? 'Non renseigné' }}
                        </div>
                    </div>

                    <div class="info-item full-width">
                        <div class="info-item-label">
                            <i class="fa-solid fa-location-dot"></i> Adresse
                        </div>
                        <div class="info-item-value {{ !$user->adresse ? 'empty' : '' }}">
                            {{ $user->adresse ?? 'Non renseignée' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================
                 INFORMATIONS PROFESSIONNELLES
                 ============================================ --}}
            @if($user->fonction || $user->section)
                <div class="info-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <span>Informations professionnelles</span>
                    </div>

                    <div class="info-grid">
                        @if($user->fonction)
                            <div class="info-item">
                                <div class="info-item-label">
                                    <i class="fa-solid fa-id-badge"></i> Fonction
                                </div>
                                <div class="info-item-value">{{ $user->fonction->nom }}</div>
                            </div>
                        @endif

                        @if($user->section)
                            <div class="info-item">
                                <div class="info-item-label">
                                    <i class="fa-solid fa-building"></i> Section
                                </div>
                                <div class="info-item-value">{{ $user->section->nom }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ============================================
                 SITUATION FINANCIÈRE (si salaire configuré)
                 ============================================ --}}
            @if($user->salaire_actuel_usd > 0 || $user->salaire_actuel_fc > 0)
                <div class="info-section">
                    <div class="section-title">
                        <div class="section-icon">
                            <i class="fa-solid fa-wallet"></i>
                        </div>
                        <span>Situation financière</span>
                    </div>

                    <div class="finance-grid">
                        <div class="finance-card finance-salaire">
                            <div class="finance-label">Salaire actuel</div>
                            <div class="finance-value">
                                {{ number_format($user->salaire_actuel_usd, 0, ',', ' ') }} $
                            </div>
                            <div class="finance-fc">
                                ≈ {{ number_format($user->salaire_actuel_fc, 0, ',', ' ') }} FC
                            </div>
                        </div>

                        <div class="finance-card finance-limite">
                            <div class="finance-label">Type de salaire</div>
                            <div class="finance-value" style="font-size: 1rem;">
                                @switch($user->type_salaire)
                                    @case('manuel') Manuel @break
                                    @case('automatique') Automatique @break
                                    @default Non défini
                                @endswitch
                            </div>
                            @if($user->date_fixation_salaire)
                                <div class="finance-fc">
                                    Fixé le {{ $user->date_fixation_salaire->translatedFormat('d M Y') }}
                                </div>
                            @endif
                        </div>

                        <div class="finance-card finance-dette">
                            <div class="finance-label">Compte</div>
                            <div class="finance-value" style="font-size: 1rem;">
                                {{ $user->isSuperAdmin() ? 'Super Admin' : ($user->isAdmin() ? 'Administrateur' : 'Membre') }}
                            </div>
                            <div class="finance-fc">
                                Depuis le {{ $user->created_at->translatedFormat('d M Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection