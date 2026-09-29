@once
<style>
    /* ════════════════════════════════════════════════════════
       STYLES PARTAGÉS — create + edit des permissions
       Scopés sous .permission-create-page / .permission-edit-page
       ════════════════════════════════════════════════════════ */

    .permission-create-page,
    .permission-edit-page {
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

        --t: 0.2s cubic-bezier(.4,0,.2,1);

        max-width: 720px;
        margin: 0 auto;
        padding: 2rem 1rem;
        color: var(--c-slate-800);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .permission-create-page *,
    .permission-create-page *::before,
    .permission-create-page *::after,
    .permission-edit-page *,
    .permission-edit-page *::before,
    .permission-edit-page *::after { box-sizing: border-box; }

    .permission-create-page [x-cloak],
    .permission-edit-page [x-cloak] { display: none !important; }

    /* ─── HEADER ─── */
    .permission-create-page .page-header,
    .permission-edit-page .page-header {
        display: flex; flex-direction: column; gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    @media (min-width: 640px) {
        .permission-create-page .page-header,
        .permission-edit-page .page-header {
            flex-direction: row; justify-content: space-between; align-items: center;
        }
    }
    .permission-create-page .page-header-text,
    .permission-edit-page .page-header-text { min-width: 0; }

    .permission-create-page .page-title,
    .permission-edit-page .page-title {
        display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;
        font-size: 1.55rem; font-weight: 800; color: var(--c-slate-900);
        letter-spacing: -0.5px; margin: 0 0 0.25rem;
    }
    .permission-create-page .title-icon,
    .permission-edit-page .title-icon { color: #6366f1; font-size: 1.35rem; }

    .permission-create-page .page-subtitle,
    .permission-edit-page .page-subtitle {
        color: var(--c-slate-500); font-size: 0.9rem; margin: 0;
    }
    .permission-create-page .header-actions,
    .permission-edit-page .header-actions { display: flex; gap: 0.6rem; }
    @media (max-width: 640px) {
        .permission-create-page .header-actions,
        .permission-edit-page .header-actions { width: 100%; }
        .permission-create-page .header-actions .btn,
        .permission-edit-page .header-actions .btn { width: 100%; }
    }

    /* ─── BOUTONS ─── */
    .permission-create-page .btn,
    .permission-edit-page .btn {
        display: inline-flex; align-items: center; justify-content: center;
        gap: 0.5rem; padding: 0.7rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600; font-size: 0.875rem; font-family: inherit;
        text-decoration: none; border: none; cursor: pointer;
        transition: all var(--t); white-space: nowrap; min-height: 44px;
    }
    .permission-create-page .btn-primary,
    .permission-edit-page .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff; box-shadow: 0 4px 12px rgba(79,70,229,0.25);
    }
    .permission-create-page .btn-primary:hover:not(:disabled),
    .permission-edit-page .btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79,70,229,0.35);
    }
    .permission-create-page .btn-primary:disabled,
    .permission-edit-page .btn-primary:disabled {
        opacity: 0.55; cursor: not-allowed; transform: none;
    }
    .permission-create-page .btn-ghost,
    .permission-edit-page .btn-ghost {
        background: #fff; color: var(--c-slate-500);
        border: 1.5px solid var(--c-slate-200);
    }
    .permission-create-page .btn-ghost:hover,
    .permission-edit-page .btn-ghost:hover {
        border-color: #6366f1; color: var(--c-primary);
        background: var(--c-slate-50);
    }
    .permission-create-page .btn:focus-visible,
    .permission-edit-page .btn:focus-visible {
        outline: 2px solid var(--c-primary); outline-offset: 2px;
    }
    .permission-create-page .btn-content,
    .permission-edit-page .btn-content {
        display: inline-flex; align-items: center; gap: 0.5rem;
    }

    /* ─── FORM CARD ─── */
    .permission-create-page .form-card,
    .permission-edit-page .form-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-slate-100);
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 4px 14px rgba(0,0,0,0.03);
        padding: 1.75rem;
        animation: formFadeIn 0.4s ease-out both;
    }
    @keyframes formFadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    @media (max-width: 640px) {
        .permission-create-page .form-card,
        .permission-edit-page .form-card { padding: 1.15rem; }
    }

    .permission-create-page .form-grid,
    .permission-edit-page .form-grid {
        display: flex; flex-direction: column; gap: 1.5rem;
    }

    /* ─── CHAMPS ─── */
    .permission-create-page .form-field,
    .permission-edit-page .form-field { min-width: 0; }

    .permission-create-page .form-label,
    .permission-edit-page .form-label {
        display: flex; align-items: baseline; gap: 0.4rem;
        font-size: 0.85rem; font-weight: 600;
        color: var(--c-slate-700); margin-bottom: 0.45rem;
    }
    .permission-create-page .req,
    .permission-edit-page .req { color: var(--c-rose); font-weight: 700; }
    .permission-create-page .optional,
    .permission-edit-page .optional {
        font-size: 0.72rem; font-weight: 500; color: var(--c-slate-400);
    }

    .permission-create-page .input-wrapper,
    .permission-edit-page .input-wrapper { position: relative; }

    .permission-create-page .input-icon,
    .permission-edit-page .input-icon {
        position: absolute; left: 0.9rem; top: 50%;
        transform: translateY(-50%);
        color: var(--c-slate-400); font-size: 0.9rem;
        pointer-events: none; transition: color var(--t);
    }
    .permission-create-page .input-icon-top,
    .permission-edit-page .input-icon-top { top: 1rem; transform: none; }

    .permission-create-page .form-input,
    .permission-create-page .form-select,
    .permission-create-page .form-textarea,
    .permission-edit-page .form-input,
    .permission-edit-page .form-select,
    .permission-edit-page .form-textarea {
        width: 100%;
        padding: 0.7rem 0.9rem 0.7rem 2.5rem;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        background: var(--c-slate-50);
        font-size: 0.9rem; font-family: inherit;
        color: var(--c-slate-900); outline: none;
        transition: all var(--t); min-height: 44px;
    }
    .permission-create-page .form-select,
    .permission-edit-page .form-select {
        padding-right: 2.5rem; cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2394a3b8'%3E%3Cpath fill-rule='evenodd' d='M10 12a1 1 0 01-.7-.3l-4-4a1 1 0 011.4-1.4L10 9.6l3.3-3.3a1 1 0 011.4 1.4l-4 4a1 1 0 01-.7.3z' clip-rule='evenodd'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1rem;
    }
    .permission-create-page .form-textarea,
    .permission-edit-page .form-textarea {
        min-height: 60px; resize: vertical; line-height: 1.5;
        padding-left: 2.5rem;
    }
    .permission-create-page .form-input:focus,
    .permission-create-page .form-select:focus,
    .permission-create-page .form-textarea:focus,
    .permission-edit-page .form-input:focus,
    .permission-edit-page .form-select:focus,
    .permission-edit-page .form-textarea:focus {
        border-color: #6366f1; background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }
    .permission-create-page .input-wrapper:focus-within .input-icon,
    .permission-edit-page .input-wrapper:focus-within .input-icon {
        color: #6366f1;
    }
    .permission-create-page .form-input.is-invalid,
    .permission-create-page .form-select.is-invalid,
    .permission-create-page .form-textarea.is-invalid,
    .permission-edit-page .form-input.is-invalid,
    .permission-edit-page .form-select.is-invalid,
    .permission-edit-page .form-textarea.is-invalid {
        border-color: var(--c-rose); background: var(--c-rose-soft);
    }
    .permission-create-page .form-input.is-valid,
    .permission-edit-page .form-input.is-valid {
        border-color: var(--c-emerald); background: #fff;
    }

    /* ─── HELPERS ─── */
    .permission-create-page .form-help,
    .permission-edit-page .form-help {
        display: flex; align-items: flex-start; gap: 0.4rem;
        font-size: 0.75rem; color: var(--c-slate-500);
        margin: 0.4rem 0 0; line-height: 1.5;
    }
    .permission-create-page .form-help i,
    .permission-edit-page .form-help i {
        color: var(--c-amber); flex-shrink: 0; margin-top: 2px;
        font-size: 0.75rem;
    }
    .permission-create-page .form-help-warn,
    .permission-edit-page .form-help-warn { color: var(--c-amber); }
    .permission-create-page .form-help-warn i,
    .permission-edit-page .form-help-warn i { color: var(--c-amber); }

    .permission-create-page .error-text,
    .permission-edit-page .error-text {
        display: block; font-size: 0.78rem; color: var(--c-rose);
        margin: 0.35rem 0 0; font-weight: 500;
    }

    /* ─── APERÇU ─── */
    .permission-create-page .preview-card,
    .permission-edit-page .preview-card {
        display: flex; align-items: flex-start; gap: 0.9rem;
        padding: 1rem 1.15rem;
        background: linear-gradient(135deg, var(--c-primary-soft), #f5f3ff);
        border: 1.5px solid var(--c-primary-mid);
        border-radius: var(--radius-md);
        animation: previewIn 0.3s ease-out both;
    }
    @keyframes previewIn {
        from { opacity: 0; transform: scale(0.97); }
        to   { opacity: 1; transform: scale(1); }
    }
    .permission-create-page .preview-icon,
    .permission-edit-page .preview-icon {
        width: 40px; height: 40px;
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.05rem; flex-shrink: 0;
        box-shadow: 0 4px 10px rgba(79,70,229,0.25);
    }
    .permission-create-page .preview-content,
    .permission-edit-page .preview-content { min-width: 0; flex: 1; }
    .permission-create-page .preview-title,
    .permission-edit-page .preview-title {
        font-size: 0.72rem; font-weight: 700;
        color: #4338ca; text-transform: uppercase; letter-spacing: 0.5px;
        margin: 0 0 0.25rem;
    }
    .permission-create-page .preview-name,
    .permission-edit-page .preview-name {
        display: inline-block;
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
        font-size: 0.85rem; font-weight: 600;
        color: var(--c-slate-900);
        background: rgba(255,255,255,0.7);
        padding: 0.15rem 0.5rem;
        border-radius: 5px;
        word-break: break-all;
    }
    .permission-create-page .preview-meta,
    .permission-edit-page .preview-meta {
        font-size: 0.75rem; color: #6366f1; margin: 0.4rem 0 0;
    }
    .permission-create-page .preview-meta .dot,
    .permission-edit-page .preview-meta .dot {
        opacity: 0.5; margin: 0 0.4rem;
    }

    /* ─── BULK CARD ─── */
    .permission-create-page .bulk-card,
    .permission-edit-page .bulk-card {
        padding: 1rem 1.15rem;
        background: var(--c-slate-50);
        border: 1px solid var(--c-slate-200);
        border-radius: var(--radius-md);
    }
    .permission-create-page .bulk-header,
    .permission-edit-page .bulk-header {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.85rem; color: var(--c-slate-700);
        margin-bottom: 0.75rem;
    }
    .permission-create-page .bulk-header i,
    .permission-edit-page .bulk-header i { color: #6366f1; }
    .permission-create-page .bulk-header strong,
    .permission-edit-page .bulk-header strong { color: var(--c-slate-900); }

    .permission-create-page .bulk-pills,
    .permission-edit-page .bulk-pills {
        display: flex; flex-wrap: wrap; gap: 0.35rem;
        margin-bottom: 0.35rem;
    }
    .permission-create-page .bulk-pill,
    .permission-edit-page .bulk-pill {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.2rem 0.6rem;
        background: #fff; border: 1px solid var(--c-slate-200);
        border-radius: 9999px;
        font-size: 0.72rem; font-weight: 600;
        color: var(--c-slate-600);
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
    }
    .permission-create-page .bulk-pill i,
    .permission-edit-page .bulk-pill i {
        color: var(--c-emerald); font-size: 0.6rem;
    }

    /* ─── LINK BTN (bulk) ─── */
    .permission-create-page .link-btn,
    .permission-edit-page .link-btn {
        display: inline-flex; align-items: center; gap: 0.4rem;
        padding: 0.5rem 0.85rem;
        background: transparent;
        border: 1px solid var(--c-emerald-mid);
        border-radius: 8px;
        font-weight: 600; font-size: 0.78rem;
        font-family: inherit;
        color: var(--c-emerald);
        cursor: pointer;
        transition: all var(--t);
        min-height: 36px;
    }
    .permission-create-page .link-btn:hover:not(:disabled),
    .permission-edit-page .link-btn:hover:not(:disabled) {
        background: var(--c-emerald-soft);
    }
    .permission-create-page .link-btn:disabled,
    .permission-edit-page .link-btn:disabled {
        opacity: 0.5; cursor: not-allowed;
        color: var(--c-slate-500); border-color: var(--c-slate-200);
    }

    .permission-create-page .bulk-message,
    .permission-edit-page .bulk-message {
        display: flex; align-items: center; gap: 0.4rem;
        font-size: 0.78rem; font-weight: 500;
        margin: 0.75rem 0 0;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
    }
    .permission-create-page .bulk-message.is-success,
    .permission-edit-page .bulk-message.is-success {
        background: var(--c-emerald-soft); color: var(--c-emerald);
    }
    .permission-create-page .bulk-message.is-error,
    .permission-edit-page .bulk-message.is-error {
        background: var(--c-rose-soft); color: var(--c-rose);
    }

    /* ─── FORM ACTIONS ─── */
    .permission-create-page .form-actions,
    .permission-edit-page .form-actions {
        display: flex; justify-content: flex-end; gap: 0.6rem;
        margin-top: 2rem; padding-top: 1.25rem;
        border-top: 1px solid var(--c-slate-100);
    }
    @media (max-width: 640px) {
        .permission-create-page .form-actions,
        .permission-edit-page .form-actions { flex-direction: column-reverse; }
        .permission-create-page .form-actions .btn,
        .permission-edit-page .form-actions .btn { width: 100%; }
    }

    /* ─── SPÉCIFIQUE EDIT ─── */
    .permission-edit-page .perm-badge {
        display: inline-block;
        font-family: ui-monospace, 'SF Mono', Monaco, monospace;
        font-size: 0.72rem; font-weight: 600;
        color: #4338ca;
        background: var(--c-primary-soft);
        padding: 0.25rem 0.6rem;
        border-radius: 6px;
        max-width: 100%;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        vertical-align: middle;
        margin-left: 0.35rem;
    }
    .permission-edit-page .bulk-pill.is-self {
        background: var(--c-primary-soft);
        border-color: var(--c-primary-mid);
        color: #4338ca;
    }
    .permission-edit-page .bulk-pill.is-self i {
        color: #f59e0b;
    }
    .permission-edit-page .bulk-pill .self-badge {
        font-size: 0.6rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.3px;
        color: #4338ca;
        background: rgba(99,102,241,0.15);
        padding: 0.05rem 0.35rem;
        border-radius: 4px;
        margin-left: 0.25rem;
    }

    /* ─── RESPONSIVE ─── */
    @media (max-width: 640px) {
        .permission-create-page,
        .permission-edit-page { padding: 1.25rem 0.85rem; }
        .permission-create-page .page-title,
        .permission-edit-page .page-title { font-size: 1.3rem; }
        .permission-create-page .title-icon,
        .permission-edit-page .title-icon { font-size: 1.15rem; }
        .permission-create-page .perm-badge,
        .permission-edit-page .perm-badge {
            font-size: 0.65rem; padding: 0.2rem 0.5rem;
        }

        /* Anti-zoom iOS */
        .permission-create-page .form-input,
        .permission-create-page .form-select,
        .permission-create-page .form-textarea,
        .permission-edit-page .form-input,
        .permission-edit-page .form-select,
        .permission-edit-page .form-textarea { font-size: 16px; }
    }
    @media (max-width: 480px) {
        .permission-create-page,
        .permission-edit-page { padding: 1rem 0.65rem; }
        .permission-create-page .page-title,
        .permission-edit-page .page-title { font-size: 1.15rem; }
        .permission-create-page .form-card,
        .permission-edit-page .form-card { padding: 1rem; }
    }

    /* ─── A11Y + PRINT ─── */
    @media (prefers-reduced-motion: reduce) {
        .permission-create-page *,
        .permission-create-page *::before,
        .permission-create-page *::after,
        .permission-edit-page *,
        .permission-edit-page *::before,
        .permission-edit-page *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }
    @media print {
        .permission-create-page .form-actions,
        .permission-create-page .header-actions,
        .permission-create-page .bulk-card,
        .permission-create-page .preview-card,
        .permission-edit-page .form-actions,
        .permission-edit-page .header-actions,
        .permission-edit-page .bulk-card,
        .permission-edit-page .preview-card { display: none !important; }
        .permission-create-page .form-card,
        .permission-edit-page .form-card {
            box-shadow: none; border: 1px solid #ccc;
        }
    }
</style>
@endonce