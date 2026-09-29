@extends('layouts.admin')

@section('page_title', 'Paramètres du site')
@section('page_subtitle', 'Gérer les informations et le carrousel')

@section('content')
<div class="edit-container">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">

    {{-- Messages flash --}}
    @if(session('success'))
        <div class="flash flash-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">
            <i class="bi bi-exclamation-circle-fill"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- En-tête --}}
    <header class="edit-header">
        <div>
            <h1 class="edit-title">Paramètres <strong>du site</strong></h1>
            <p class="edit-subtitle">Gérez les informations publiques et le carrousel d'images</p>
        </div>
        <a href="{{ route('home') }}" class="link-out" target="_blank" rel="noopener">
            <i class="bi bi-box-arrow-up-right"></i> Voir le site
        </a>
    </header>

    {{-- =========================================================
         FORMULAIRE PRINCIPAL — INFOS GÉNÉRALES
         ========================================================= --}}
    <form action="{{ route('admin.site-settings.update') }}"
          method="POST"
          enctype="multipart/form-data"
          class="form-card"
          novalidate>
        @csrf
        @method('PUT')

        <section class="form-section">
            <h2 class="section-title">
                <i class="bi bi-info-circle"></i> Informations générales
            </h2>

            {{-- Ligne 1 : Nom + Slogan --}}
            <div class="form-grid">
                <div class="field">
                    <div class="input-wrap">
                        <input type="text" name="site_name" id="site_name"
                               value="{{ old('site_name', $settings->site_name) }}"
                               placeholder=" " required maxlength="255"
                               class="@error('site_name') is-invalid @enderror">
                        <label for="site_name" class="float-label">
                            Nom du site <span class="required">*</span>
                        </label>
                        <i class="bi bi-globe icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('site_name')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <div class="input-wrap">
                        <input type="text" name="site_slogan" id="site_slogan"
                               value="{{ old('site_slogan', $settings->site_slogan) }}"
                               placeholder=" " required maxlength="255"
                               class="@error('site_slogan') is-invalid @enderror">
                        <label for="site_slogan" class="float-label">
                            Slogan <span class="required">*</span>
                        </label>
                        <i class="bi bi-chat-quote icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('site_slogan')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Ligne 2 : Email + Téléphone --}}
            <div class="form-grid">
                <div class="field">
                    <div class="input-wrap">
                        <input type="email" name="site_email" id="site_email"
                               value="{{ old('site_email', $settings->site_email) }}"
                               placeholder=" " required maxlength="255"
                               class="@error('site_email') is-invalid @enderror">
                        <label for="site_email" class="float-label">
                            Email du site <span class="required">*</span>
                        </label>
                        <i class="bi bi-envelope icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('site_email')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <div class="input-wrap">
                        <input type="tel" name="site_phone" id="site_phone"
                               value="{{ old('site_phone', $settings->site_phone) }}"
                               placeholder=" " required maxlength="50"
                               class="@error('site_phone') is-invalid @enderror">
                        <label for="site_phone" class="float-label">
                            Téléphone principal <span class="required">*</span>
                        </label>
                        <i class="bi bi-telephone icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('site_phone')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Ligne 3 : Téléphone secondaire + Responsable --}}
            <div class="form-grid">
                <div class="field">
                    <div class="input-wrap">
                        <input type="tel" name="site_secondary_phone" id="site_secondary_phone"
                               value="{{ old('site_secondary_phone', $settings->site_secondary_phone) }}"
                               placeholder=" " maxlength="50"
                               class="@error('site_secondary_phone') is-invalid @enderror">
                        <label for="site_secondary_phone" class="float-label">Téléphone secondaire</label>
                        <i class="bi bi-telephone-plus icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('site_secondary_phone')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <div class="input-wrap">
                        <input type="text" name="responsable_name" id="responsable_name"
                               value="{{ old('responsable_name', $settings->responsable_name) }}"
                               placeholder=" " required maxlength="255"
                               class="@error('responsable_name') is-invalid @enderror">
                        <label for="responsable_name" class="float-label">
                            Responsable <span class="required">*</span>
                        </label>
                        <i class="bi bi-person-badge icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('responsable_name')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Ligne 4 : Date + Adresse --}}
            <div class="form-grid">
                <div class="field">
                    <div class="input-wrap">
                        <input type="date" name="creation_date" id="creation_date"
                               value="{{ old('creation_date', $settings->creation_date?->format('Y-m-d')) }}"
                               placeholder=" "
                               class="@error('creation_date') is-invalid @enderror">
                        <label for="creation_date" class="float-label">Date de création</label>
                        <i class="bi bi-calendar-event icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('creation_date')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <div class="input-wrap textarea-wrap">
                        <textarea name="site_address" id="site_address" rows="1"
                                  placeholder=" " maxlength="500"
                                  class="@error('site_address') is-invalid @enderror">{{ old('site_address', $settings->site_address) }}</textarea>
                        <label for="site_address" class="float-label">Adresse</label>
                        <i class="bi bi-geo-alt icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('site_address')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Ligne 5 : Description pleine largeur --}}
            <div class="form-grid single">
                <div class="field">
                    <div class="input-wrap textarea-wrap">
                        <textarea name="site_description" id="site_description" rows="3"
                                  placeholder=" " maxlength="2000"
                                  data-counter-target="desc-counter"
                                  class="@error('site_description') is-invalid @enderror">{{ old('site_description', $settings->site_description) }}</textarea>
                        <label for="site_description" class="float-label">Description courte</label>
                        <i class="bi bi-card-text icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    <div class="field-meta">
                        @error('site_description')
                            <p class="error-text">{{ $message }}</p>
                        @else
                            <span></span>
                        @enderror
                        <span class="counter" id="desc-counter">
                            {{ strlen(old('site_description', $settings->site_description ?? '')) }} / 2000
                        </span>
                    </div>
                </div>
            </div>

            {{-- Réseaux sociaux --}}
            <h3 class="subtitle-section">
                <i class="bi bi-share"></i> Réseaux sociaux
            </h3>

            <div class="form-grid">
                <div class="field">
                    <div class="input-wrap">
                        <input type="url" name="facebook_url" id="facebook_url"
                               value="{{ old('facebook_url', $settings->facebook_url) }}"
                               placeholder=" " maxlength="255"
                               class="@error('facebook_url') is-invalid @enderror">
                        <label for="facebook_url" class="float-label">Facebook</label>
                        <i class="bi bi-facebook icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('facebook_url')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <div class="input-wrap">
                        <input type="url" name="twitter_url" id="twitter_url"
                               value="{{ old('twitter_url', $settings->twitter_url) }}"
                               placeholder=" " maxlength="255"
                               class="@error('twitter_url') is-invalid @enderror">
                        <label for="twitter_url" class="float-label">Twitter / X</label>
                        <i class="bi bi-twitter-x icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('twitter_url')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="form-grid">
                <div class="field">
                    <div class="input-wrap">
                        <input type="url" name="instagram_url" id="instagram_url"
                               value="{{ old('instagram_url', $settings->instagram_url) }}"
                               placeholder=" " maxlength="255"
                               class="@error('instagram_url') is-invalid @enderror">
                        <label for="instagram_url" class="float-label">Instagram</label>
                        <i class="bi bi-instagram icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('instagram_url')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <div class="input-wrap">
                        <input type="url" name="youtube_url" id="youtube_url"
                               value="{{ old('youtube_url', $settings->youtube_url) }}"
                               placeholder=" " maxlength="255"
                               class="@error('youtube_url') is-invalid @enderror">
                        <label for="youtube_url" class="float-label">YouTube</label>
                        <i class="bi bi-youtube icon"></i>
                        <div class="line-focus"></div>
                    </div>
                    @error('youtube_url')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════════════
                 VISIBILITÉ DES LIENS PUBLICS — INTERRUPTEUR
                 ═══════════════════════════════════════════════════════ --}}
            <h3 class="subtitle-section">
                <i class="bi bi-eye"></i> Visibilité des liens publics
            </h3>

            <div class="form-grid single">
                <div class="field">
                    <div class="toggle-card {{ old('show_public_links', $settings->show_public_links ?? true) ? 'is-on' : '' }}"
                         id="toggle-card">
                        <label class="toggle-switch" for="show_public_links">
                            {{-- Champ caché : garantit qu'un 0 est envoyé si décoché --}}
                            <input type="hidden" name="show_public_links" value="0">

                            <input type="checkbox"
                                   name="show_public_links"
                                   value="1"
                                   id="show_public_links"
                                   @checked(old('show_public_links', $settings->show_public_links ?? true))>

                            <span class="toggle-slider" aria-hidden="true"></span>
                        </label>

                        <div class="toggle-content">
                            <label for="show_public_links" class="toggle-title">
                                Afficher les liens publics sur la page d'accueil
                            </label>
                            <p class="toggle-desc">
                                Lorsque désactivé, les liens
                                <strong>Liste des élèves</strong>,
                                <strong>Ayant Payé</strong>,
                                <strong>Proclamation</strong>
                                et
                                <strong>Enregistrer</strong>
                                seront masqués sur la page d'accueil publique et dans le menu.
                            </p>
                            <span class="toggle-status" id="toggle-status">
                                {{ old('show_public_links', $settings->show_public_links ?? true) ? 'Liens visibles' : 'Liens masqués' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Logo --}}
            <div class="form-grid single">
                <div class="field">
                    <label class="field-label">Logo du site</label>
                    <div class="logo-upload">
                        {{-- Aperçu actuel --}}
                        <div class="logo-preview" id="logo-preview">
                            @if(!empty($settings->site_logo) && !empty($settings->logo_url))
                                <img src="{{ $settings->logo_url }}" alt="Logo actuel" id="logo-img">
                            @else
                                <div class="logo-placeholder">
                                    <i class="bi bi-image"></i>
                                </div>
                            @endif
                        </div>

                        {{-- Upload --}}
                        <div class="logo-upload-zone">
                            <input type="file" name="site_logo" id="site_logo"
                                   accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                   class="file-input">
                            <label for="site_logo" class="file-label">
                                <i class="bi bi-cloud-upload"></i>
                                <span>Choisir un logo</span>
                                <small>JPG, PNG, GIF, WebP — max 2 Mo</small>
                            </label>
                            <p class="file-hint" id="logo-file-hint"></p>
                        </div>
                    </div>
                    @error('site_logo')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- Bouton submit — sticky sur mobile --}}
        <div class="submit-bar">
            <button type="submit" class="btn-submit">
                <i class="bi bi-check-lg"></i> Enregistrer les modifications
            </button>
        </div>
    </form>

    {{-- =========================================================
         CARROUSEL D'IMAGES
         ========================================================= --}}
    <section class="carousel-card">
        <header class="carousel-header">
            <h2 class="section-title">
                <i class="bi bi-images"></i> Carrousel d'images
            </h2>
            @if($carouselImages->isNotEmpty())
                <span class="count-badge">
                    {{ $carouselImages->count() }}
                    image{{ $carouselImages->count() > 1 ? 's' : '' }}
                </span>
            @endif
        </header>

        {{-- Formulaire d'ajout --}}
        <form action="{{ route('admin.site-settings.carousel.store') }}"
              method="POST"
              enctype="multipart/form-data"
              class="carousel-add-form">
            @csrf

            <div class="add-grid">
                <div class="field">
                    <label for="image" class="field-label">
                        Image <span class="required">*</span>
                    </label>
                    <div class="file-upload-wrap">
                        <input type="file" name="image" id="image"
                               accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                               class="file-input" required>
                        <label for="image" class="file-label">
                            <i class="bi bi-cloud-upload"></i>
                            <span>Choisir une image</span>
                        </label>
                    </div>
                    @error('image')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="alt_text" class="field-label">Texte alternatif</label>
                    <div class="input-wrap simple">
                        <input type="text" name="alt_text" id="alt_text"
                               maxlength="255"
                               placeholder="Description de l'image..."
                               value="{{ old('alt_text') }}">
                        <i class="bi bi-tag icon"></i>
                    </div>
                    @error('alt_text')<p class="error-text">{{ $message }}</p>@enderror
                </div>

                <div class="field">
                    <label for="ordre" class="field-label">Ordre</label>
                    <div class="input-wrap simple">
                        <input type="number" name="ordre" id="ordre" min="0" max="9999"
                               value="{{ old('ordre', $carouselImages->max('ordre') + 1) }}">
                        <i class="bi bi-list-ol icon"></i>
                    </div>
                    @error('ordre')<p class="error-text">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="add-actions">
                <button type="submit" class="btn-add">
                    <i class="bi bi-plus-lg"></i> Ajouter l'image
                </button>
            </div>
        </form>

        {{-- Grille des images --}}
        @if($carouselImages->isEmpty())
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-images"></i></div>
                <h3 class="empty-title">Aucune image dans le carrousel</h3>
                <p class="empty-text">
                    Ajoutez votre première image pour qu'elle apparaisse sur la page d'accueil.
                </p>
            </div>
        @else
            <div class="carousel-grid">
                @foreach($carouselImages as $image)
                    <article class="carousel-item">
                        <div class="carousel-thumb">
                            <img src="{{ $image->image_url }}"
                                 alt="{{ $image->alt_text ?: 'Image du carrousel' }}"
                                 loading="lazy">
                            <span class="order-badge">#{{ $image->ordre }}</span>
                        </div>

                        <div class="carousel-meta">
                            <p class="carousel-alt" title="{{ $image->alt_text }}">
                                {{ $image->alt_text ?: 'Sans texte alternatif' }}
                            </p>
                        </div>

                        <form action="{{ route('admin.site-settings.carousel.destroy', $image) }}"
                              method="POST"
                              class="carousel-delete-form"
                              onsubmit="return confirm('Supprimer cette image du carrousel ?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete" aria-label="Supprimer l'image">
                                <i class="bi bi-trash"></i>
                                <span>Supprimer</span>
                            </button>
                        </form>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>

<style>
    /* =========================================================
       BASE
       ========================================================= */
    .edit-container {
        max-width: 1100px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }
    .edit-container * { box-sizing: border-box; }
    .edit-container img { max-width: 100%; height: auto; }

    /* =========================================================
       FLASH
       ========================================================= */
    .flash {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    .flash-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
    .flash-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
    .flash i { margin-top: 2px; }

    /* =========================================================
       EN-TÊTE
       ========================================================= */
    .edit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.75rem;
        flex-wrap: wrap;
    }
    .edit-title {
        font-size: 1.75rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 0.2rem;
        letter-spacing: -0.5px;
    }
    .edit-title strong { font-weight: 800; color: #4f46e5; }
    .edit-subtitle { color: #94a3b8; font-size: 0.95rem; margin: 0; }
    .link-out {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #667eea;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        padding: 0.5rem 0.9rem;
        border-radius: 10px;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .link-out:hover { color: #4f46e5; background: #eef2ff; }

    /* =========================================================
       CARTES
       ========================================================= */
    .form-card,
    .carousel-card {
        background: white;
        padding: 2rem 2.5rem;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.04);
        border: 1px solid #f1f5f9;
        margin-bottom: 1.5rem;
    }

    .section-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 1.5rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i { color: #667eea; }

    .subtitle-section {
        font-size: 0.95rem;
        font-weight: 600;
        color: #475569;
        margin: 1.5rem 0 1rem;
        display: flex;
        align-items: center;
        gap: 6px;
        padding-top: 1rem;
        border-top: 1px dashed #e2e8f0;
    }
    .subtitle-section i { color: #94a3b8; }

    /* =========================================================
       GRILLES DE FORMULAIRE
       ========================================================= */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
        margin-bottom: 1.25rem;
    }
    .form-grid.single { grid-template-columns: 1fr; }

    .field { position: relative; min-width: 0; }
    .field-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
    }
    .field-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.35rem;
        font-size: 0.75rem;
    }
    .counter { color: #94a3b8; font-variant-numeric: tabular-nums; }
    .required { color: #ef4444; }

    /* =========================================================
       INPUTS "FLOAT LABEL" (form principal)
       ========================================================= */
    .input-wrap { position: relative; }

    .input-wrap input,
    .input-wrap textarea {
        width: 100%;
        padding: 0.9rem 2.5rem 0.9rem 0;
        border: none;
        border-bottom: 2px solid #e2e8f0;
        background: transparent;
        font-size: 1rem;
        color: #1e293b;
        font-weight: 500;
        transition: border-color 0.3s;
        outline: none;
        font-family: inherit;
    }
    .input-wrap textarea { resize: vertical; min-height: 44px; }

    .input-wrap input::placeholder,
    .input-wrap textarea::placeholder { color: transparent; }

    .input-wrap input:focus,
    .input-wrap textarea:focus { border-bottom-color: #667eea; }

    .input-wrap input.is-invalid,
    .input-wrap textarea.is-invalid { border-bottom-color: #ef4444; }

    /* Label flottant */
    .input-wrap .float-label {
        position: absolute;
        left: 0;
        top: 0.9rem;
        color: #94a3b8;
        font-size: 1rem;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: calc(100% - 2.5rem);
    }
    .input-wrap input:focus ~ .float-label,
    .input-wrap input:not(:placeholder-shown) ~ .float-label,
    .input-wrap textarea:focus ~ .float-label,
    .input-wrap textarea:not(:placeholder-shown) ~ .float-label {
        top: -0.6rem;
        font-size: 0.7rem;
        font-weight: 700;
        color: #667eea;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-wrap input.is-invalid:focus ~ .float-label,
    .input-wrap input.is-invalid:not(:placeholder-shown) ~ .float-label,
    .input-wrap textarea.is-invalid:focus ~ .float-label,
    .input-wrap textarea.is-invalid:not(:placeholder-shown) ~ .float-label {
        color: #ef4444;
    }

    /* Icône à droite */
    .input-wrap i.icon {
        position: absolute;
        right: 0;
        top: 1rem;
        color: #cbd5e1;
        font-size: 1.15rem;
        transition: all 0.3s;
        pointer-events: none;
    }
    .input-wrap input:focus ~ i.icon,
    .input-wrap textarea:focus ~ i.icon {
        color: #667eea;
        transform: scale(1.1);
    }
    .input-wrap input.is-invalid ~ i.icon,
    .input-wrap textarea.is-invalid ~ i.icon { color: #ef4444; }

    /* Ligne de focus */
    .input-wrap .line-focus {
        position: absolute;
        bottom: 0;
        left: 50%;
        width: 0;
        height: 2px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        transform: translateX(-50%);
        pointer-events: none;
    }
    .input-wrap input:focus ~ .line-focus,
    .input-wrap textarea:focus ~ .line-focus { width: 100%; }

    /* Variante "simple" sans float label (pour le carrousel) */
    .input-wrap.simple input,
    .input-wrap.simple textarea {
        padding: 0.7rem 2.4rem 0.7rem 0.9rem;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        background: #f8fafc;
    }
    .input-wrap.simple input:focus,
    .input-wrap.simple textarea:focus {
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }
    .input-wrap.simple i.icon {
        right: 0.7rem;
        top: 50%;
        transform: translateY(-50%);
    }
    .input-wrap.simple input:focus ~ i.icon,
    .input-wrap.simple textarea:focus ~ i.icon {
        transform: translateY(-50%) scale(1.1);
    }

    /* =========================================================
       UPLOAD DE FICHIER
       ========================================================= */
    .file-upload-wrap {
        position: relative;
        display: inline-block;
    }
    .file-input {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }
    .file-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 0.75rem 1.25rem;
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        color: #475569;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 0.9rem;
    }
    .file-input:hover + .file-label,
    .file-input:focus + .file-label {
        border-color: #667eea;
        color: #667eea;
        background: #eef2ff;
    }
    .file-label small {
        display: block;
        font-size: 0.72rem;
        color: #94a3b8;
        font-weight: 400;
        margin-top: 2px;
    }
    .file-hint {
        font-size: 0.75rem;
        color: #667eea;
        margin: 0.4rem 0 0;
        min-height: 1rem;
    }

    /* =========================================================
       INTERRUPTEUR — VISIBILITÉ LIENS PUBLICS
       ========================================================= */
    .toggle-card {
        display: flex;
        align-items: flex-start;
        gap: 1.25rem;
        padding: 1.25rem 1.5rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        transition: all 0.3s ease;
    }
    .toggle-card.is-on {
        background: #eef2ff;
        border-color: #c7d2fe;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.08);
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 52px;
        height: 28px;
        flex-shrink: 0;
        cursor: pointer;
        margin-top: 0.1rem;
    }

    .toggle-switch input[type="checkbox"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        inset: 0;
        background-color: #cbd5e1;
        border-radius: 999px;
        transition: background-color 0.25s ease;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }

    .toggle-slider::before {
        content: '';
        position: absolute;
        width: 22px;
        height: 22px;
        left: 3px;
        top: 3px;
        background: white;
        border-radius: 50%;
        transition: transform 0.25s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }

    .toggle-switch input[type="checkbox"]:checked + .toggle-slider {
        background-color: #4f46e5;
    }
    .toggle-switch input[type="checkbox"]:checked + .toggle-slider::before {
        transform: translateX(24px);
    }
    .toggle-switch input[type="checkbox"]:focus-visible + .toggle-slider {
        box-shadow: 0 0 0 3px rgba(79,70,229,0.3);
    }

    .toggle-content {
        flex: 1;
        min-width: 0;
    }
    .toggle-title {
        display: block;
        font-weight: 700;
        color: #1e293b;
        font-size: 0.95rem;
        margin-bottom: 0.35rem;
        cursor: pointer;
        line-height: 1.35;
    }
    .toggle-desc {
        font-size: 0.85rem;
        color: #64748b;
        margin: 0 0 0.6rem;
        line-height: 1.55;
    }
    .toggle-desc strong {
        color: #334155;
        font-weight: 700;
    }
    .toggle-status {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.65rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        background: #e2e8f0;
        color: #475569;
        letter-spacing: 0.3px;
        text-transform: uppercase;
    }
    .toggle-card.is-on .toggle-status {
        background: #c7d2fe;
        color: #3730a3;
    }

    /* =========================================================
       LOGO UPLOAD
       ========================================================= */
    .logo-upload {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        flex-wrap: wrap;
    }
    .logo-preview {
        width: 100px;
        height: 100px;
        border-radius: 14px;
        border: 2px dashed #e2e8f0;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
    }
    .logo-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
    .logo-placeholder {
        font-size: 2rem;
        color: #cbd5e1;
    }
    .logo-upload-zone { flex: 1; min-width: 200px; }

    /* =========================================================
       BOUTONS
       ========================================================= */
    .btn-submit,
    .btn-add,
    .btn-delete {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0.8rem 1.75rem;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        white-space: nowrap;
        font-family: inherit;
    }
    .btn-submit,
    .btn-add {
        background: #1e293b;
        color: white;
    }
    .btn-submit:hover,
    .btn-add:hover {
        background: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102,126,234,0.3);
    }
    .btn-delete {
        background: #fef2f2;
        color: #dc2626;
        padding: 0.55rem 1rem;
        font-size: 0.85rem;
    }
    .btn-delete:hover {
        background: #dc2626;
        color: white;
    }

    .submit-bar {
        display: flex;
        justify-content: flex-end;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid #f1f5f9;
    }

    /* =========================================================
       CARROUSEL
       ========================================================= */
    .carousel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .carousel-header .section-title { margin: 0; }
    .count-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        background: #eef2ff;
        color: #4f46e5;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .carousel-add-form {
        background: #f8fafc;
        padding: 1.25rem;
        border-radius: 14px;
        border: 1px dashed #cbd5e1;
        margin-bottom: 1.75rem;
    }
    .add-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }
    .add-actions { display: flex; justify-content: flex-end; }

    .carousel-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .carousel-item {
        background: white;
        border-radius: 14px;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        transition: all 0.25s;
        display: flex;
        flex-direction: column;
    }
    @media (hover: hover) {
        .carousel-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.08);
        }
    }

    .carousel-thumb {
        position: relative;
        width: 100%;
        padding-bottom: 56.25%; /* 16:9 */
        background: #f1f5f9;
        overflow: hidden;
    }
    .carousel-thumb img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .order-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        padding: 0.2rem 0.55rem;
        background: rgba(15, 23, 42, 0.75);
        color: white;
        font-size: 0.7rem;
        font-weight: 700;
        border-radius: 6px;
        backdrop-filter: blur(4px);
        font-variant-numeric: tabular-nums;
    }

    .carousel-meta { padding: 0.85rem 1rem 0.5rem; }
    .carousel-alt {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .carousel-delete-form {
        padding: 0 1rem 1rem;
        display: flex;
        justify-content: flex-end;
    }

    /* État vide */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    .empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        background: #f1f5f9;
        color: #94a3b8;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    .empty-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    .empty-text {
        font-size: 0.9rem;
        color: #94a3b8;
        max-width: 420px;
        margin: 0;
    }

    /* =========================================================
       ERREURS
       ========================================================= */
    .error-text {
        color: #ef4444;
        font-size: 0.8rem;
        margin: 0.3rem 0 0;
    }

    /* =========================================================
       RESPONSIVE — TABLETTE (≥ 768px)
       ========================================================= */
    @media (min-width: 768px) {
        .form-grid { grid-template-columns: repeat(2, 1fr); }
        .form-grid.single { grid-template-columns: 1fr; }
        .add-grid { grid-template-columns: 1fr 1fr auto; }
        .carousel-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 1024px) {
        .carousel-grid { grid-template-columns: repeat(3, 1fr); }
    }

    /* =========================================================
       RESPONSIVE — TABLETTE PORTRAIT (≤ 992px)
       ========================================================= */
    @media (max-width: 992px) {
        .edit-container { max-width: 100%; }
        .form-card,
        .carousel-card { padding: 1.75rem 1.5rem; }
    }

    /* =========================================================
       RESPONSIVE — MOBILE (≤ 768px)
       ========================================================= */
    @media (max-width: 768px) {
        .edit-container { padding: 1rem 0.75rem; }
        .edit-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
        }
        .edit-title { font-size: 1.4rem; }
        .edit-subtitle { font-size: 0.85rem; }
        .link-out {
            width: 100%;
            justify-content: center;
            background: #eef2ff;
        }

        .form-card,
        .carousel-card { padding: 1.25rem 1rem; border-radius: 16px; }

        .section-title { font-size: 1rem; margin-bottom: 1.25rem; }
        .subtitle-section { font-size: 0.9rem; }

        .input-wrap input,
        .input-wrap textarea { font-size: 16px; }
        .input-wrap.simple input,
        .input-wrap.simple textarea { font-size: 16px; }

        .input-wrap i.icon { font-size: 1rem; }

        .submit-bar {
            position: sticky;
            bottom: 0;
            background: white;
            margin: 1rem -1rem 0;
            padding: 0.85rem 1rem;
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -4px 12px rgba(0,0,0,0.05);
            z-index: 10;
            border-radius: 0 0 16px 16px;
        }
        .btn-submit,
        .btn-add { width: 100%; }

        .logo-upload { flex-direction: column; align-items: stretch; }
        .logo-preview { align-self: center; }

        .add-actions { justify-content: stretch; }

        .carousel-delete-form { justify-content: stretch; }
        .btn-delete {
            width: 100%;
            justify-content: center;
            padding: 0.65rem 1rem;
            font-size: 0.9rem;
        }

        /* Toggle card compact sur mobile */
        .toggle-card {
            padding: 1rem;
            gap: 0.9rem;
        }
        .toggle-title { font-size: 0.9rem; }
        .toggle-desc { font-size: 0.8rem; }
    }

    /* =========================================================
       RESPONSIVE — PETIT MOBILE (≤ 480px)
       ========================================================= */
    @media (max-width: 480px) {
        .edit-container { padding: 0.75rem 0.5rem; }
        .edit-title { font-size: 1.2rem; }
        .form-card,
        .carousel-card { padding: 1rem 0.85rem; border-radius: 14px; }

        .section-title { font-size: 0.95rem; }
        .section-title i { font-size: 0.9rem; }

        .form-grid { gap: 1rem; }
        .input-wrap input,
        .input-wrap textarea { padding: 0.8rem 2.2rem 0.8rem 0; }

        .logo-preview { width: 80px; height: 80px; }
        .logo-placeholder { font-size: 1.5rem; }

        .file-label {
            padding: 0.65rem 1rem;
            font-size: 0.85rem;
        }

        .empty-icon { width: 60px; height: 60px; font-size: 1.6rem; }
        .empty-title { font-size: 0.95rem; }
        .empty-text { font-size: 0.82rem; }

        .order-badge { font-size: 0.65rem; padding: 0.15rem 0.45rem; }

        /* Toggle card très compact */
        .toggle-card { padding: 0.85rem; gap: 0.75rem; flex-direction: row; }
        .toggle-switch { width: 44px; height: 24px; }
        .toggle-slider::before { width: 18px; height: 18px; }
        .toggle-switch input[type="checkbox"]:checked + .toggle-slider::before {
            transform: translateX(20px);
        }
        .toggle-title { font-size: 0.85rem; }
        .toggle-desc { font-size: 0.75rem; line-height: 1.5; }
    }

    /* =========================================================
       ACCESSIBILITÉ
       ========================================================= */
    @media (prefers-reduced-motion: reduce) {
        .btn-submit,
        .btn-add,
        .btn-delete,
        .carousel-item,
        .file-label,
        .link-out,
        .toggle-slider,
        .toggle-slider::before,
        .toggle-card { transition: none; }
        .carousel-item:hover { transform: none; }
    }

    .btn-submit:focus-visible,
    .btn-add:focus-visible,
    .btn-delete:focus-visible,
    .link-out:focus-visible,
    .toggle-switch input:focus-visible + .toggle-slider {
        outline: 2px solid #667eea;
        outline-offset: 2px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    /* ---- Compteur de caractères pour site_description ---- */
    const desc = document.getElementById('site_description');
    const counter = document.getElementById('desc-counter');
    if (desc && counter) {
        const max = desc.getAttribute('maxlength') || 2000;
        const update = () => {
            counter.textContent = `${desc.value.length} / ${max}`;
        };
        desc.addEventListener('input', update);
        update();
    }

    /* ---- Preview du logo avant upload ---- */
    const logoInput = document.getElementById('site_logo');
    const logoImg = document.getElementById('logo-img');
    const logoPreview = document.getElementById('logo-preview');
    const logoHint = document.getElementById('logo-file-hint');

    if (logoInput && logoPreview) {
        logoInput.addEventListener('change', function () {
            const file = this.files && this.files[0];
            if (!file) {
                if (logoHint) logoHint.textContent = '';
                return;
            }

            if (logoHint) {
                const sizeKb = (file.size / 1024).toFixed(0);
                logoHint.textContent = `${file.name} — ${sizeKb} Ko`;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                let img = logoPreview.querySelector('img');
                if (!img) {
                    logoPreview.innerHTML = '';
                    img = document.createElement('img');
                    img.alt = 'Aperçu du logo';
                    img.id = 'logo-img';
                    logoPreview.appendChild(img);
                }
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    /* ---- Feedback visuel du file input carrousel ---- */
    document.querySelectorAll('.file-upload-wrap .file-input').forEach(input => {
        input.addEventListener('change', function () {
            const label = this.parentElement.querySelector('.file-label span');
            if (!label) return;
            const file = this.files && this.files[0];
            label.textContent = file
                ? file.name.length > 30
                    ? file.name.substring(0, 27) + '...'
                    : file.name
                : 'Choisir une image';
        });
    });

    /* ---- Interrupteur liens publics : feedback visuel + statut ---- */
    const toggleInput  = document.getElementById('show_public_links');
    const toggleCard   = document.getElementById('toggle-card');
    const toggleStatus = document.getElementById('toggle-status');

    if (toggleInput && toggleCard && toggleStatus) {
        const updateToggleUI = () => {
            const isOn = toggleInput.checked;
            toggleCard.classList.toggle('is-on', isOn);
            toggleStatus.textContent = isOn ? 'Liens visibles' : 'Liens masqués';
        };

        toggleInput.addEventListener('change', updateToggleUI);
        updateToggleUI();
    }
});
</script>
@endsection