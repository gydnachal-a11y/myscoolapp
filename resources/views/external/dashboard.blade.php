@extends('layouts.contact')

@section('title', 'Fil d\'actualité')

@section('content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

@php
    // Utilisateur connecté (guard contact)
    $contact = auth('contact')->user();

    // Comptes du règlement en une seule requête
    $reglementCounts = \App\Models\ReglementInterieur::actif()
        ->selectRaw('categorie, COUNT(*) as total')
        ->groupBy('categorie')
        ->pluck('total', 'categorie');

    $reglesCount        = $reglementCounts['regle'] ?? 0;
    $obligationsCount   = $reglementCounts['obligation'] ?? 0;
    $interdictionsCount = $reglementCounts['interdiction'] ?? 0;

    // Initiale sécurisée (UTF-8)
    $initialContact = $contact && $contact->nom
        ? mb_strtoupper(mb_substr(trim($contact->nom), 0, 1))
        : '?';
@endphp

<div class="social-network-bg">
    <div class="social-container">

        {{-- RACCOURCIS MOBILES (Règlement intérieur) --}}
        <div class="d-lg-none mb-3">
            <div class="quick-shortcuts-wrapper d-flex gap-2 overflow-x-auto pb-2">
                @foreach([
                    ['label' => 'Règles',       'count' => $reglesCount,        'icon' => 'fa-check', 'color' => 'success'],
                    ['label' => 'Obligations',  'count' => $obligationsCount,   'icon' => 'fa-hand',  'color' => 'warning'],
                    ['label' => 'Interdictions','count' => $interdictionsCount, 'icon' => 'fa-ban',   'color' => 'danger'],
                ] as $shortcut)
                    <a href="{{ route('external.reglement') }}"
                       class="btn btn-sm bg-white border shadow-sm rounded-pill flex-shrink-0 d-flex align-items-center gap-2 px-3 py-2">
                        <span class="badge bg-{{ $shortcut['color'] }}-subtle text-{{ $shortcut['color'] }} rounded-circle p-1">
                            <i class="fa-solid {{ $shortcut['icon'] }}"></i>
                        </span>
                        <span class="small fw-semibold text-dark">
                            {{ $shortcut['label'] }} ({{ $shortcut['count'] }})
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="row g-3 g-lg-4">

            {{-- COLONNE GAUCHE : Profil & Raccourcis --}}
            <div class="col-lg-3 d-none d-lg-block">
                <div class="social-sidebar sticky-sidebar">

                    {{-- Carte Profil --}}
                    <div class="social-card profile-card mb-4">
                        <div class="profile-cover"></div>
                        <div class="profile-info text-center px-3 pb-4">
                            <div class="profile-avatar mx-auto">
                                {{-- Contact n'a pas de photo_url, on affiche l'initiale --}}
                                {{ $initialContact }}
                            </div>

                            <h5 class="profile-name mt-3 mb-1">
                                {{ $contact->nom ?? 'Utilisateur' }}
                            </h5>

                            <span class="badge bg-primary-soft text-primary px-3 py-1 rounded-pill mb-3">
                                {{ $contact->est_responsable ? 'Responsable' : 'Abonné' }}
                            </span>

                            <div class="profile-details text-start bg-light rounded-3 p-3">
                                <div class="detail-item mb-2 d-flex align-items-center">
                                    <div class="detail-icon">
                                        <i class="fa-solid fa-envelope"></i>
                                    </div>
                                    <span class="small text-truncate ms-2">{{ $contact->email }}</span>
                                </div>
                                @if($contact->telephone)
                                    <div class="detail-item d-flex align-items-center">
                                        <div class="detail-icon">
                                            <i class="fa-solid fa-phone"></i>
                                        </div>
                                        <span class="small ms-2">{{ $contact->telephone }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Règlement intérieur --}}
                    <div class="social-card p-4">
                        <h6 class="sidebar-title mb-3 d-flex align-items-center fw-bold">
                            <i class="fa-solid fa-book-open text-primary me-2"></i>
                            Règlement intérieur
                        </h6>
                        <ul class="social-menu list-unstyled mb-0">
                            @foreach([
                                ['label' => 'Règles',       'count' => $reglesCount,        'icon' => 'fa-check', 'color' => 'success'],
                                ['label' => 'Obligations',  'count' => $obligationsCount,   'icon' => 'fa-hand',  'color' => 'warning'],
                                ['label' => 'Interdictions','count' => $interdictionsCount, 'icon' => 'fa-ban',   'color' => 'danger'],
                            ] as $item)
                                <li class="{{ !$loop->last ? 'mb-2' : '' }}">
                                    <a href="{{ route('external.reglement') }}"
                                       class="d-flex align-items-center justify-content-between text-decoration-none py-2 px-2 rounded hover-bg">
                                        <div class="d-flex align-items-center">
                                            <div class="menu-icon bg-{{ $item['color'] }}-subtle text-{{ $item['color'] }}">
                                                <i class="fa-solid {{ $item['icon'] }}"></i>
                                            </div>
                                            <span class="ms-3 small fw-semibold text-dark">{{ $item['label'] }}</span>
                                        </div>
                                        <span class="badge bg-light text-dark border rounded-pill">
                                            {{ $item['count'] }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            {{-- COLONNE CENTRALE : Fil d'actualité --}}
            <div class="col-lg-6 col-md-12">

                {{-- Alertes --}}
                @if(session('success') || $errors->any())
                    <div class="social-alerts mb-3 mb-md-4">
                        @if(session('success'))
                            <div class="alert custom-alert alert-success alert-dismissible fade show shadow-sm border-0 d-flex align-items-center p-3" role="alert">
                                <i class="fa-solid fa-circle-check fs-5 me-3 flex-shrink-0"></i>
                                <div class="small fw-medium">{{ session('success') }}</div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            </div>
                        @endif
                        @if($errors->any())
                            <div class="alert custom-alert alert-danger alert-dismissible fade show shadow-sm border-0 d-flex align-items-center p-3" role="alert">
                                <i class="fa-solid fa-circle-exclamation fs-5 me-3 flex-shrink-0"></i>
                                <ul class="mb-0 ps-3 m-0 small fw-medium">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                            </div>
                        @endif
                    </div>
                @endif

                {{-- En-tête du Feed --}}
                <div class="feed-header d-flex justify-content-between align-items-center mb-3 mb-md-4 px-1">
                    <h5 class="feed-section-title fw-bold mb-0 text-dark">Fil d'actualité</h5>
                    <a href="{{ route('annonces') }}" class="btn btn-sm btn-light rounded-pill fw-semibold shadow-sm px-3 hover-primary">
                        <span>Tout voir</span> <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>

                {{-- Liste des Annonces --}}
                @if ($annonces->isEmpty())
                    <div class="social-card p-4 p-md-5 text-center empty-feed d-flex flex-column align-items-center justify-content-center">
                        <div class="empty-feed-icon mb-3 mb-md-4 rounded-circle bg-light d-flex align-items-center justify-content-center">
                            <i class="fa-regular fa-newspaper text-muted fs-2"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-1">Rien à afficher</h5>
                        <p class="text-muted small mb-0">Les nouvelles annonces apparaîtront ici.</p>
                    </div>
                @else
                    <div class="feed-container">
                        @foreach ($annonces as $annonce)
                            <article class="social-card feed-post mb-3 mb-md-4">
                                {{-- Header --}}
                                <div class="post-header p-3 p-md-4 d-flex align-items-center justify-content-between gap-2">
                                    <div class="d-flex align-items-center min-w-0">
                                        <div class="post-author-avatar me-2 me-md-3 bg-primary text-white shadow-sm flex-shrink-0">
                                            <i class="fa-solid fa-building"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <h6 class="post-author-name mb-0 text-truncate">Administration</h6>
                                            <div class="post-meta text-muted small d-flex align-items-center flex-wrap gap-1">
                                                <span>{{ $annonce->date_debut?->format('d M Y') ?? 'Information' }}</span>
                                                @if($annonce->date_fin)
                                                    <span>• Jusqu'au {{ $annonce->date_fin->format('d M Y') }}</span>
                                                @endif
                                                <span>•</span>
                                                <i class="fa-solid fa-earth-americas" style="font-size: 0.7rem;"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="post-options flex-shrink-0">
                                        @if($annonce->type == 'prive')
                                            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1">
                                                <i class="fa-solid fa-lock me-1"></i>Privé
                                            </span>
                                        @else
                                            <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">
                                                <i class="fa-solid fa-globe me-1"></i>Public
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Contenu --}}
                                <div class="post-body px-3 px-md-4 pb-3">
                                    <h5 class="post-title fw-bold text-dark mb-2">{{ $annonce->titre }}</h5>
                                    <p class="post-text text-secondary mb-0">{{ $annonce->contenu }}</p>
                                </div>

                                {{-- Image --}}
                                @if($annonce->image_url)
                                    <div class="post-media px-3 px-md-4 pb-3">
                                        <div class="media-frame overflow-hidden rounded-3 shadow-sm">
                                            <img src="{{ $annonce->image_url }}"
                                                 alt="{{ $annonce->titre }}"
                                                 class="img-fluid w-100 post-image"
                                                 loading="lazy"
                                                 decoding="async">
                                        </div>
                                    </div>
                                @endif

                                {{-- Actions --}}
                                <div class="post-actions p-3 bg-light border-top text-center">
                                    <a href="{{ route('annonces') }}"
                                       class="btn btn-primary-soft w-100 rounded-pill fw-semibold py-2 transition-all d-flex align-items-center justify-content-center">
                                        <i class="fa-solid fa-list-ul me-2"></i> Voir toutes les annonces
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="social-pagination mt-3 mt-md-4 d-flex justify-content-center">
                        {{ $annonces->links() }}
                    </div>
                @endif
            </div>

            {{-- COLONNE DROITE : Formulaire & Conversations --}}
            <div class="col-lg-3 col-md-12">
                <div class="social-sidebar sticky-sidebar right-sidebar">

                    {{-- Formulaire Message --}}
                    <div class="social-card p-3 p-md-4 mb-3 mb-md-4">
                        <h6 class="sidebar-title mb-3 mb-md-4 d-flex align-items-center text-dark fw-bold">
                            <i class="fa-regular fa-paper-plane text-primary me-2 fs-5"></i>
                            Écrire un message
                        </h6>
                        <form action="{{ route('external.message.store') }}" method="POST" class="floating-form">
                            @csrf
                            <div class="form-floating mb-3 mb-md-4 position-relative">
                                <input type="text"
                                       class="form-control floating-line-input @error('sujet') is-invalid @enderror"
                                       id="sujet"
                                       name="sujet"
                                       placeholder="Objet"
                                       value="{{ old('sujet') }}"
                                       maxlength="255"
                                       required>
                                <label for="sujet">Objet du message</label>
                            </div>
                            <div class="form-floating mb-3 mb-md-4 position-relative">
                                <textarea class="form-control floating-line-input @error('message') is-invalid @enderror"
                                          id="message"
                                          name="message"
                                          placeholder="Message"
                                          maxlength="5000"
                                          style="height: 85px; resize: none;"
                                          required>{{ old('message') }}</textarea>
                                <label for="message">Votre message...</label>
                            </div>
                            <button type="submit"
                                    class="btn btn-primary w-100 social-btn rounded-pill fw-semibold py-2 shadow-sm d-flex align-items-center justify-content-center gap-2">
                                <span>Envoyer</span>
                                <i class="fa-solid fa-arrow-right fs-6"></i>
                            </button>
                        </form>
                    </div>

                    {{-- Conversations --}}
                    <div class="social-card p-3 p-md-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="sidebar-title mb-0 text-dark fw-bold">Conversations</h6>
                                @if(isset($mesMessages) && $mesMessages->count() > 0)
                                    <span class="badge bg-primary-soft text-primary rounded-pill px-2 py-1"
                                          style="font-size: 0.725rem;">
                                        {{ $mesMessages->count() }}
                                    </span>
                                @endif
                            </div>
                            <a href="{{ route('external.messages') }}"
                               class="btn btn-sm btn-light rounded-circle chat-link-btn"
                               title="Ouvrir la messagerie">
                                <i class="fa-solid fa-up-right-from-square text-primary"></i>
                            </a>
                        </div>

                        <div class="conversations-wrapper overflow-auto pe-1" style="max-height: 320px;">
                            @if(isset($mesMessages) && $mesMessages->isNotEmpty())
                                <div class="conversation-list d-flex flex-column gap-2">
                                    @foreach($mesMessages as $msg)
                                        <a href="{{ route('external.messages') }}"
                                           class="conversation-item text-decoration-none p-2 rounded-3 d-flex align-items-start gap-2 transition-all">
                                            <div class="conversation-avatar flex-shrink-0 rounded-circle d-flex align-items-center justify-content-center text-white {{ $msg->from_admin ? 'bg-primary' : 'bg-dark' }}">
                                                <i class="fa-solid {{ $msg->from_admin ? 'fa-user-shield' : 'fa-user' }}"></i>
                                            </div>
                                            <div class="conversation-info flex-grow-1 min-w-0">
                                                <div class="d-flex justify-content-between align-items-baseline mb-1">
                                                    <h6 class="conversation-sender mb-0 text-truncate fw-bold {{ $msg->from_admin ? 'text-primary' : 'text-dark' }}">
                                                        {{ $msg->from_admin ? 'Administration' : 'Moi' }}
                                                    </h6>
                                                    <span class="conversation-time text-muted flex-shrink-0 ms-1">
                                                        {{ $msg->created_at->format('d/m H:i') }}
                                                    </span>
                                                </div>
                                                @if($msg->sujet)
                                                    <p class="conversation-subject fw-semibold text-dark mb-0 text-truncate">
                                                        {{ $msg->sujet }}
                                                    </p>
                                                @endif
                                                <p class="conversation-preview text-muted mb-0 text-truncate">
                                                    {{ $msg->message }}
                                                </p>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center text-muted py-4 px-2">
                                    <div class="bg-light rounded-circle d-inline-flex p-3 mb-2">
                                        <i class="fa-regular fa-comments fs-4 text-secondary"></i>
                                    </div>
                                    <p class="small fw-medium mb-0 text-dark">Aucune conversation</p>
                                    <p class="text-muted mb-0" style="font-size: 0.75rem;">Vos échanges s'afficheront ici.</p>
                                </div>
                            @endif
                        </div>

                        @if(isset($mesMessages) && $mesMessages->isNotEmpty())
                            <div class="mt-3 pt-2 border-top text-center">
                                <a href="{{ route('external.messages') }}"
                                   class="text-decoration-none fw-semibold text-primary hover-underline small d-inline-flex align-items-center gap-1">
                                    <span>Voir tout l'historique</span>
                                    <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Le CSS reste identique à celui de votre fichier d'origine */
/* ============================================================
   OBEAC — FEED DESIGN SYSTEM
   ============================================================ */
:root{
    --feed-bg:#f5f7fb;
    --surface:#fff;
    --border:#e7ebf2;
    --text:#172033;
    --muted:#748096;
    --primary:#2563eb;
    --primary-dark:#1d4ed8;
    --primary-soft:#eff6ff;
    --success:#16a34a;
    --warning:#d97706;
    --danger:#dc2626;
    --radius:18px;
    --radius-sm:12px;
    --shadow:0 8px 30px rgba(23,32,51,.06);
    --shadow-hover:0 14px 38px rgba(23,32,51,.10);
    --font:'Inter','Segoe UI',Arial,sans-serif;
}

*{box-sizing:border-box}

.social-network-bg{
    background:
        radial-gradient(circle at 0 0,rgba(37,99,235,.05),transparent 28rem),
        var(--feed-bg);
    border-radius: 16px;
    padding: 1rem;
}

.social-container{ max-width:1320px; margin:auto; }

.social-card{
    background:var(--surface);
    border:1px solid var(--border);
    border-radius:var(--radius);
    box-shadow:var(--shadow);
    overflow:hidden;
    transition:box-shadow .2s ease,transform .2s ease;
}
.social-card:hover{ box-shadow:var(--shadow-hover); }

@media(min-width:992px){
    .sticky-sidebar{ position:sticky; top:20px; }
}

.quick-shortcuts-wrapper{ scrollbar-width:none; }
.quick-shortcuts-wrapper::-webkit-scrollbar{ display:none; }
.quick-shortcuts-wrapper .btn{
    border-color:var(--border)!important;
    background:rgba(255,255,255,.92)!important;
    box-shadow:0 5px 16px rgba(23,32,51,.05)!important;
    min-height:42px;
}

.profile-cover{
    height:108px;
    position:relative;
    background:linear-gradient(135deg,#2563eb 0%,#4f46e5 55%,#7c3aed 100%);
}
.profile-cover:after{
    content:"";
    position:absolute;
    inset:auto -20px -55px auto;
    width:170px;
    height:170px;
    border-radius:50%;
    background:rgba(255,255,255,.10);
}
.profile-info{ position:relative; }

.profile-avatar{
    width:82px;
    height:82px;
    margin-top:-41px;
    border:5px solid #fff;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fff;
    color:var(--primary);
    font-size:1.8rem;
    font-weight:800;
    box-shadow:0 8px 24px rgba(23,32,51,.15);
    overflow: hidden;
}
.profile-avatar img{
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-name{ font-size:1.05rem; font-weight:800; }

.bg-primary-soft{ background:var(--primary-soft)!important; }
.text-primary{ color:var(--primary)!important; }

.detail-item{ padding:8px 0; }
.detail-icon{
    width:30px;
    height:30px;
    flex:0 0 30px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:9px;
    background:var(--primary-soft);
    color:var(--primary);
    font-size:.78rem;
}

.sidebar-title{ font-size:.95rem; letter-spacing:-.01em; }
.social-menu a{ color:var(--text); transition:.18s ease; }
.social-menu a:hover{ background:#f7f9fc; transform:translateX(2px); }
.menu-icon{
    width:36px;
    height:36px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:10px;
}

.feed-header{ padding:2px 4px; }
.feed-section-title{ font-size:1.25rem; letter-spacing:-.025em; }

.custom-alert{ border-radius:14px; }
.feed-post{ overflow:hidden; }
.post-header{ padding-bottom:14px!important; }

.post-author-avatar{
    width:44px;
    height:44px;
    flex:0 0 44px;
    display:flex;
    align-items:center;
    justify-content:center;
    border-radius:14px;
    font-size:1rem;
    box-shadow:0 5px 14px rgba(37,99,235,.18);
}
.post-author-name{ font-size:.92rem; font-weight:800; }
.post-meta{ font-size:.73rem!important; color:var(--muted)!important; margin-top:3px; }
.post-title{ font-size:1.16rem; line-height:1.35; letter-spacing:-.015em; }
.post-text{
    color:#536074!important;
    font-size:.93rem;
    line-height:1.7;
    white-space:pre-wrap;
    word-break:break-word;
}
.post-media{ padding-top:4px; }
.media-frame{
    position:relative;
    background:#eef2f7;
    border-radius:14px!important;
    overflow:hidden;
}
.post-image{
    display:block;
    width:100%;
    max-height:480px;
    object-fit:cover;
    transition:transform .35s ease;
}
.feed-post:hover .post-image{ transform:scale(1.012); }

.post-actions{
    background:#fafbfc!important;
    border-top:1px solid var(--border)!important;
}

.btn-primary-soft{
    background:var(--primary-soft);
    color:var(--primary);
    border:1px solid transparent;
}
.btn-primary-soft:hover{
    background:#dbeafe;
    color:var(--primary-dark);
}
.btn-primary{ background:var(--primary); border-color:var(--primary); }
.btn-primary:hover{ background:var(--primary-dark); border-color:var(--primary-dark); }

.hover-primary:hover{ color:var(--primary)!important; background:var(--primary-soft)!important; }
.hover-bg:hover{ background:#f7f9fc; }
.hover-underline:hover{ text-decoration:underline!important; }
.transition-all{ transition:all .2s ease; }
.min-w-0{ min-width:0; }

.empty-feed{ min-height:310px; }
.empty-feed-icon{ width:76px; height:76px; }

.floating-form .form-floating{ margin-bottom:22px!important; }
.form-floating>.floating-line-input{
    border:0!important;
    border-bottom:2px solid #dbe1ea!important;
    border-radius:0!important;
    background:transparent!important;
    padding-left:0!important;
    padding-right:0!important;
    box-shadow:none!important;
    color:var(--text);
    font-size:.91rem;
    transition:border-color .2s ease;
}
.form-floating>.floating-line-input:focus{ border-bottom-color:var(--primary)!important; }
.form-floating>label{ padding-left:0!important; color:var(--muted); font-size:.86rem; }
.form-floating>.floating-line-input:focus~label,
.form-floating>.floating-line-input:not(:placeholder-shown)~label{
    color:var(--primary);
    font-weight:700;
    transform:scale(.84) translateY(-.9rem) translateX(0);
}

.social-btn{
    min-height:46px;
    border:0;
    border-radius:13px!important;
    font-size:.9rem;
    transition:.2s ease;
    box-shadow:0 8px 18px rgba(37,99,235,.20)!important;
}
.social-btn:hover{
    transform:translateY(-1px);
    box-shadow:0 10px 22px rgba(37,99,235,.25)!important;
}

.chat-link-btn{
    width:34px;
    height:34px;
    display:flex;
    align-items:center;
    justify-content:center;
    border:1px solid var(--border);
}

.conversation-item{
    background:#f8fafc;
    border:1px solid transparent;
    border-radius:12px!important;
}
.conversation-item:hover,
.conversation-item:active{
    background:var(--primary-soft);
    border-color:#dbeafe;
    transform:translateY(-1px);
}
.conversation-avatar{ width:38px; height:38px; flex:0 0 38px; font-size:.78rem; }
.conversation-sender{ font-size:.82rem; }
.conversation-time{ font-size:.66rem!important; }
.conversation-subject{ font-size:.77rem!important; }
.conversation-preview{ font-size:.74rem!important; line-height:1.35!important; }

.conversations-wrapper::-webkit-scrollbar{ width:5px; }
.conversations-wrapper::-webkit-scrollbar-track{ background:transparent; }
.conversations-wrapper::-webkit-scrollbar-thumb{ background:#d8dee8; border-radius:20px; }

@media(max-width:991.98px){
    .social-network-bg { padding: .5rem; }
}

@media(max-width:575.98px){
    :root{ --radius:14px; }
    .feed-header{ margin-bottom:12px!important; }
    .feed-section-title{ font-size:1.08rem; }
    .feed-post{ border-radius:14px; }
    .post-header{ padding:14px!important; }
    .post-body{ padding-left:14px!important; padding-right:14px!important; }
    .post-media{ padding-left:14px!important; padding-right:14px!important; }
    .post-author-avatar{
        width:38px;
        height:38px;
        flex-basis:38px;
        border-radius:11px;
        font-size:.88rem;
    }
    .post-title{ font-size:1.02rem; }
    .post-text{ font-size:.88rem; line-height:1.6; }
    .post-options .badge{ font-size:.66rem; padding:.32rem .48rem!important; }
    .post-image{ max-height:360px; }
    .social-card{ border-radius:14px; }
    .empty-feed{ min-height:250px; }
}
</style>
@endsection