@once
<style>
    /* ════════════════════════════════════════════════════════
       BASE — scopé sous .contact-form-page
       ════════════════════════════════════════════════════════ */
    .contact-form-page {
        --c-primary:      #4f46e5;
        --c-primary-soft: #eef2ff;
        --c-primary-mid:  #c7d2fe;

        --c-emerald:      #059669;
        --c-emerald-soft: #ecfdf5;
        --c-emerald-mid:  #a7f3d0;

        --c-rose:      #dc2626;
        --c-rose-soft: #fef2f2;

        --c-amber:      #d97706;
        --c-amber-soft: #fffbeb;

        --c-purple:      #7c3aed;
        --c-purple-soft: #f5f3ff;

        --c-slate-50:  #f8fafc;
        --c-slate-100: #f1f5f9;
        --c-slate-200: #e2e8f0;
        --c-slate-300: #cbd5e1;
        --c-slate-400: #94a3b8;
        --c-slate-500: #64748b;
        --c-slate-600: #475569;
        --c-slate-700: #334155;
        --c-slate-800: #1e293b;
        --c-slate-900: #0f172a;

        --radius-sm: 10px;
        --radius-md: 14px;
        --radius-lg: 18px;

        --shadow-xs: 0 1px 3px rgba(0,0,0,0.04);

        --t: 0.2s cubic-bezier(.4,0,.2,1);

        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
        color: var(--c-slate-800);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .contact-form-page *,
    .contact-form-page *::before,
    .contact-form-page *::after { box-sizing: border-box; }

    .contact-form-page [x-cloak] { display: none !important; }

    /* ─── HEADER ─── */
    .contact-form-page .page-header {
        display: flex; flex-direction: column; gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    @media (min-width: 768px) {
        .contact-form-page .page-header {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }
    .contact-form-page .page-header-text { min-width: 0; }
    .contact-form-page .page-title {
        display: flex; align-items: center; gap: 0.6rem;
        font-size: 1.6rem; font-weight: 800; color: var(--c-slate-900);
        letter-spacing: -0.5px; margin: 0 0 0.25rem;
    }
    .contact-form-page .title-icon { color: #6366f1; font-size: 1.35rem; }
    .contact-form-page .page-subtitle {
        color: var(--c-slate-500); font-size: 0.9rem; margin: 0;
    }
    .contact-form-page .header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }
    @media (max-width: 640px) {
        .contact-form-page .header-actions { width: 100%; }
        .contact-form-page .header-actions .btn { width: 100%; }
    }

    /* ─── ALERT ─── */
    .contact-form-page .alert {
        display: flex; align-items: flex-start; gap: 0.85rem;
        padding: 1rem 1.15rem;
        border-radius: var(--radius-md);
        margin-bottom: 1.5rem;
        border: 1.5px solid;
    }
    .contact-form-page .alert-error {
        background: var(--c-rose-soft);
        border-color: #fecaca;
        color: #991b1b;
    }
    .contact-form-page .alert-icon {
        font-size: 1.15rem;
        color: var(--c-rose);
        flex-shrink: 0;
        margin-top: 2px;
    }
    .contact-form-page .alert-body { min-width: 0; flex: 1; }
    .contact-form-page .alert-body strong {
        display: block; font-size: 0.9rem;
        font-weight: 700; color: #7f1d1d;
        margin-bottom: 0.35rem;
    }
    .contact-form-page .alert-list {
        margin: 0; padding-left: 1.1rem;
        font-size: 0.85rem; line-height: 1.6;
    }

    /* ─── BOUTONS ─── */
    .contact-form-page .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem; padding: 0.7rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600; font-size: 0.875rem; font-family: inherit;
        text-decoration: none; border: none; cursor: pointer;
        transition: all var(--t); white-space: nowrap; min-height: 44px;
    }
    .contact-form-page .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79,70,229,0.25);
    }
    .contact-form-page .btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79,70,229,0.35);
    }
    .contact-form-page .btn-primary:disabled {
        opacity: 0.55; cursor: not-allowed; transform: none;
    }
    .contact-form-page .btn-ghost {
        background: #fff; color: var(--c-slate-500);
        border: 1.5px solid var(--c-slate-200);
    }
    .contact-form-page .btn-ghost:hover {
        border-color: #6366f1; color: var(--c-primary);
        background: var(--c-slate-50);
    }
    .contact-form-page .btn:focus-visible {
        outline: 2px solid var(--c-primary); outline-offset: 2px;
    }
    .contact-form-page .btn-content {
        display: inline-flex; align-items: center; gap: 0.5rem;
    }

    /* ─── LAYOUT ─── */
    .contact-form-page .form-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    @media (min-width: 1024px) {
        .contact-form-page .form-layout {
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            align-items: start;
        }
    }

    .contact-form-page .form-main {
        display: flex; flex-direction: column; gap: 1.5rem;
        min-width: 0;
    }

    .contact-form-page .form-aside {
        display: flex; flex-direction: column; gap: 1.25rem;
        min-width: 0;
    }
    @media (min-width: 1024px) {
        .contact-form-page .form-aside {
            position: sticky;
            top: calc(var(--admin-header-height, 70px) + 1rem);
        }
    }

    /* ─── CARTES ─── */
    .contact-form-page .form-card {
        background: #fff;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-lg);
        padding: 1.5rem;
        box-shadow: var(--shadow-xs);
    }
    @media (max-width: 640px) {
        .contact-form-page .form-card { padding: 1.15rem; }
    }

    .contact-form-page .card-header {
        display: flex; align-items: flex-start; gap: 0.85rem;
        margin-bottom: 1.25rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--c-slate-100);
    }
    .contact-form-page .card-header-text { min-width: 0; }

    .contact-form-page .card-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: var(--c-primary-soft);
        color: var(--c-primary);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .contact-form-page .card-icon-security {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 10px rgba(79,70,229,0.25);
    }
    .contact-form-page .card-title {
        font-size: 1.05rem; font-weight: 700;
        color: var(--c-slate-900);
        margin: 0 0 0.15rem;
        letter-spacing: -0.2px;
    }
    .contact-form-page .card-subtitle {
        font-size: 0.82rem; color: var(--c-slate-500); margin: 0;
    }

    /* ─── FORM GRID ─── */
    .contact-form-page .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.15rem;
    }
    @media (min-width: 640px) {
        .contact-form-page .form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .contact-form-page .form-field-full {
            grid-column: 1 / -1;
        }
    }

    /* ─── CHAMPS ─── */
    .contact-form-page .form-field { min-width: 0; }

    .contact-form-page .form-label {
        display: flex; align-items: baseline; gap: 0.4rem;
        font-size: 0.85rem; font-weight: 600;
        color: var(--c-slate-700);
        margin-bottom: 0.45rem;
    }
    .contact-form-page .req { color: var(--c-rose); font-weight: 700; }
    .contact-form-page .optional {
        font-size: 0.72rem; font-weight: 500;
        color: var(--c-slate-400);
    }

    .contact-form-page .input-wrapper { position: relative; }

    .contact-form-page .input-icon {
        position: absolute; left: 0.9rem; top: 50%;
        transform: translateY(-50%);
        color: var(--c-slate-400); font-size: 0.9rem;
        pointer-events: none;
        transition: color var(--t);
    }

    .contact-form-page .form-input {
        width: 100%;
        padding: 0.7rem 2.5rem 0.7rem 2.5rem;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        background: var(--c-slate-50);
        font-size: 0.9rem;
        font-family: inherit;
        color: var(--c-slate-900);
        outline: none;
        transition: all var(--t);
        min-height: 44px;
    }
    .contact-form-page .form-input:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }
    .contact-form-page .input-wrapper:focus-within .input-icon {
        color: #6366f1;
    }
    .contact-form-page .form-input.is-invalid {
        border-color: var(--c-rose);
        background: var(--c-rose-soft);
    }

    /* Bouton toggle password */
    .contact-form-page .input-toggle,
    .contact-form-page .input-status {
        position: absolute; right: 0.5rem; top: 50%;
        transform: translateY(-50%);
        width: 32px; height: 32px;
        background: transparent;
        border: none;
        border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        color: var(--c-slate-500);
        cursor: pointer;
        transition: all var(--t);
    }
    .contact-form-page .input-toggle:hover {
        background: var(--c-slate-100);
        color: var(--c-slate-800);
    }
    .contact-form-page .input-status {
        cursor: default;
        pointer-events: none;
    }
    .contact-form-page .input-status.is-success { color: var(--c-emerald); }
    .contact-form-page .input-status.is-error   { color: var(--c-rose); }

    /* ─── HELPERS ─── */
    .contact-form-page .form-help {
        display: flex; align-items: flex-start; gap: 0.4rem;
        font-size: 0.75rem; color: var(--c-slate-500);
        margin: 0.4rem 0 0; line-height: 1.5;
    }
    .contact-form-page .form-help i {
        color: var(--c-amber); flex-shrink: 0; margin-top: 2px;
        font-size: 0.75rem;
    }

    .contact-form-page .error-text {
        display: block;
        font-size: 0.78rem;
        color: var(--c-rose);
        margin: 0.35rem 0 0;
        font-weight: 500;
    }

    /* ─── CHECKBOX ─── */
    .contact-form-page .checkbox-field {
        display: flex; align-items: flex-start; gap: 0.75rem;
        padding: 0.85rem 1rem;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        background: var(--c-slate-50);
        cursor: pointer;
        transition: all var(--t);
        user-select: none;
    }
    .contact-form-page .checkbox-field:hover {
        border-color: var(--c-primary-mid);
        background: #fff;
    }

    .contact-form-page .checkbox-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
        width: 0; height: 0;
    }

    .contact-form-page .checkbox-mark {
        width: 22px; height: 22px;
        flex-shrink: 0;
        border: 2px solid var(--c-slate-300);
        border-radius: 6px;
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        color: #fff;
        font-size: 0.7rem;
        transition: all var(--t);
        margin-top: 1px;
    }
    .contact-form-page .checkbox-mark i {
        transform: scale(0);
        transition: transform var(--t);
    }
    .contact-form-page .checkbox-input:checked + .checkbox-mark {
        background: var(--c-primary);
        border-color: var(--c-primary);
    }
    .contact-form-page .checkbox-input:checked + .checkbox-mark i {
        transform: scale(1);
    }
    .contact-form-page .checkbox-input:focus-visible + .checkbox-mark {
        box-shadow: 0 0 0 3px rgba(99,102,241,0.3);
    }

    .contact-form-page .checkbox-text {
        display: flex; flex-direction: column; gap: 0.15rem;
        min-width: 0;
    }
    .contact-form-page .checkbox-text strong {
        font-size: 0.85rem; font-weight: 600;
        color: var(--c-slate-800);
    }
    .contact-form-page .checkbox-text span {
        font-size: 0.75rem; color: var(--c-slate-500);
        line-height: 1.4;
    }

    /* ─── PASSWORD STRENGTH ─── */
    .contact-form-page .password-strength {
        display: flex; align-items: center; gap: 0.6rem;
        margin-top: 0.5rem;
    }
    .contact-form-page .strength-bar {
        flex: 1;
        height: 5px;
        background: var(--c-slate-200);
        border-radius: 9999px;
        overflow: hidden;
    }
    .contact-form-page .strength-fill {
        height: 100%;
        border-radius: 9999px;
        transition: all 0.3s ease;
    }
    .contact-form-page .strength-weak   { background: var(--c-rose); }
    .contact-form-page .strength-medium { background: var(--c-amber); }
    .contact-form-page .strength-strong { background: var(--c-emerald); }

    .contact-form-page .strength-label {
        font-size: 0.72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.3px;
        flex-shrink: 0;
        min-width: 55px;
        text-align: right;
    }
    .contact-form-page .strength-text-weak   { color: var(--c-rose); }
    .contact-form-page .strength-text-medium { color: var(--c-amber); }
    .contact-form-page .strength-text-strong { color: var(--c-emerald); }

    /* ─── FORM ACTIONS ─── */
    .contact-form-page .form-actions {
        display: flex; justify-content: flex-end; gap: 0.6rem;
        padding: 1.15rem 1.25rem;
        background: #fff;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-xs);
    }
    @media (max-width: 640px) {
        .contact-form-page .form-actions { flex-direction: column-reverse; }
        .contact-form-page .form-actions .btn { width: 100%; }
    }

    /* ─── PREVIEW CARD ─── */
    .contact-form-page .preview-card {
        display: flex; align-items: center; gap: 0.85rem;
        padding: 1.25rem;
        background: linear-gradient(135deg, #4f46e5, #7c3aed);
        border-radius: var(--radius-lg);
        color: #fff;
        box-shadow: 0 10px 30px rgba(79,70,229,0.25);
    }
    .contact-form-page .preview-avatar {
        width: 56px; height: 56px;
        border-radius: 14px;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; font-weight: 800;
        flex-shrink: 0;
        letter-spacing: 0.5px;
    }
    .contact-form-page .preview-info {
        min-width: 0; flex: 1;
        display: flex; flex-direction: column; gap: 0.2rem;
    }
    .contact-form-page .preview-name {
        font-size: 1rem; font-weight: 700;
        margin: 0;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .contact-form-page .preview-subtitle {
        font-size: 0.78rem; color: rgba(255,255,255,0.8);
        margin: 0;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .contact-form-page .preview-badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        align-self: flex-start;
        margin-top: 0.35rem;
        padding: 0.2rem 0.55rem;
        background: rgba(255,255,255,0.2);
        border-radius: 9999px;
        font-size: 0.68rem; font-weight: 700;
    }
    .contact-form-page .preview-badge i { font-size: 0.6rem; }

    /* ─── INFO CARD ─── */
    .contact-form-page .info-card {
        background: #fff;
        border: 1px solid var(--c-slate-100);
        border-radius: var(--radius-lg);
        padding: 1.25rem;
        box-shadow: var(--shadow-xs);
    }
    .contact-form-page .info-header {
        display: flex; align-items: center; gap: 0.5rem;
        margin-bottom: 0.85rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--c-slate-100);
    }
    .contact-form-page .info-header i { color: #6366f1; font-size: 0.95rem; }
    .contact-form-page .info-header h3 {
        font-size: 0.9rem; font-weight: 700;
        color: var(--c-slate-900);
        margin: 0;
    }
    .contact-form-page .info-list {
        list-style: none;
        padding: 0; margin: 0;
        display: flex; flex-direction: column; gap: 0.7rem;
    }
    .contact-form-page .info-list li {
        display: flex; align-items: flex-start; gap: 0.5rem;
        font-size: 0.8rem; color: var(--c-slate-600);
        line-height: 1.5;
    }
    .contact-form-page .info-list i {
        color: var(--c-emerald);
        font-size: 0.7rem;
        margin-top: 3px;
        flex-shrink: 0;
    }
    .contact-form-page .info-list strong {
        color: var(--c-slate-800);
        font-weight: 600;
    }

    /* ─── RESPONSIVE ─── */
    @media (max-width: 992px) {
        .contact-form-page { padding: 1.5rem 1rem; }
    }
    @media (max-width: 768px) {
        .contact-form-page { padding: 1.25rem 0.85rem; }
        .contact-form-page .page-title { font-size: 1.35rem; }
        .contact-form-page .title-icon { font-size: 1.15rem; }
        .contact-form-page .page-subtitle { font-size: 0.85rem; }

        /* Anti-zoom iOS */
        .contact-form-page .form-input { font-size: 16px; }
    }
    @media (max-width: 480px) {
        .contact-form-page { padding: 1rem 0.65rem; }
        .contact-form-page .page-title { font-size: 1.15rem; }
        .contact-form-page .form-card { padding: 1rem; }
        .contact-form-page .card-title { font-size: 0.95rem; }
        .contact-form-page .card-subtitle { font-size: 0.75rem; }
        .contact-form-page .preview-avatar { width: 48px; height: 48px; font-size: 1.05rem; }
    }

    /* ─── A11Y + PRINT ─── */
    @media (prefers-reduced-motion: reduce) {
        .contact-form-page *,
        .contact-form-page *::before,
        .contact-form-page *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }
    @media print {
        .contact-form-page .form-aside,
        .contact-form-page .header-actions,
        .contact-form-page .form-actions { display: none !important; }
        .contact-form-page .form-card,
        .contact-form-page .form-actions {
            box-shadow: none; border: 1px solid #ccc;
        }
    }
</style>
@endonce