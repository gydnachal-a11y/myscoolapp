@push('styles')
<style>
    /* ════════════════════════════════════════════════════════
       DESIGN TOKENS — scopés sous .role-form-page
       ════════════════════════════════════════════════════════ */
    .role-form-page {
        --c-primary:      #4f46e5;
        --c-primary-soft: #eef2ff;
        --c-primary-mid:  #c7d2fe;

        --c-emerald:      #059669;
        --c-emerald-soft: #ecfdf5;
        --c-emerald-mid:  #a7f3d0;

        --c-rose:      #e11d48;
        --c-rose-soft: #fff1f2;

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
        --radius-lg: 16px;

        --shadow-xs: 0 1px 3px rgba(0,0,0,0.04);
        --shadow-md: 0 8px 20px rgba(79,70,229,0.10);

        --t: 0.2s cubic-bezier(.4,0,.2,1);

        /* ✅ CORRECTION #5 : hauteur header admin adaptative */
        --admin-header-height: 70px;
        --toolbar-gap: 8px;
        --footer-space: 90px;

        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--c-slate-800);
    }

    @media (min-width: 1024px) {
        .role-form-page { --admin-header-height: 72px; }
    }
    @media (max-width: 768px) {
        .role-form-page {
            --admin-header-height: 60px;
            --footer-space: 140px;
        }
    }
    @media (max-width: 480px) {
        .role-form-page { --footer-space: 150px; }
    }

    .role-form-page *,
    .role-form-page *::before,
    .role-form-page *::after { box-sizing: border-box; }

    .role-form-page [x-cloak] { display: none !important; }

    /* ════════════════════════════════════════════════════════
       HEADER
       ════════════════════════════════════════════════════════ */
    .role-form-page .page-header {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .role-form-page .page-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .role-form-page .page-header-text { min-width: 0; }

    .role-form-page .page-title {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.6rem;
        font-size: 1.6rem;
        font-weight: 800;
        color: var(--c-slate-900);
        letter-spacing: -0.5px;
        margin: 0 0 0.25rem;
    }
    .role-form-page .title-icon { color: #6366f1; font-size: 1.4rem; }

    .role-form-page .page-subtitle {
        color: var(--c-slate-500);
        font-size: 0.9rem;
        margin: 0;
    }

    .role-form-page .header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }
    @media (max-width: 767px) {
        .role-form-page .header-actions { width: 100%; }
        .role-form-page .header-actions .btn { flex: 1 1 100%; }
    }

    /* ════════════════════════════════════════════════════════
       BOUTONS
       ════════════════════════════════════════════════════════ */
    .role-form-page .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.7rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.9rem;
        font-family: inherit;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: all var(--t);
        white-space: nowrap;
        min-height: 44px;
    }

    .role-form-page .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79,70,229,0.25);
    }
    .role-form-page .btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(79,70,229,0.35);
    }
    .role-form-page .btn-primary:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .role-form-page .btn-ghost {
        background: #fff;
        color: var(--c-slate-500);
        border: 1.5px solid var(--c-slate-200);
    }
    .role-form-page .btn-ghost:hover {
        border-color: #6366f1;
        color: var(--c-primary);
        background: var(--c-slate-50);
    }

    .role-form-page .btn:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }

    .role-form-page .btn-content {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* ════════════════════════════════════════════════════════
       CONTENT CARD
       ════════════════════════════════════════════════════════ */
    .role-form-page .content-card {
        background: #fff;
        border-radius: var(--radius-lg);
        border: 1px solid var(--c-slate-100);
        box-shadow: var(--shadow-xs);
        overflow: hidden;
    }

    /* ════════════════════════════════════════════════════════
       SECTIONS
       ════════════════════════════════════════════════════════ */
    .role-form-page .form-section {
        padding: 1.5rem;
        border-bottom: 1px solid var(--c-slate-100);
    }
    .role-form-page .form-section:last-of-type { border-bottom: none; }

    @media (max-width: 640px) {
        .role-form-page .form-section { padding: 1.15rem 1rem; }
    }

    .role-form-page .section-header {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        margin-bottom: 1.25rem;
    }
    .role-form-page .section-header-text { min-width: 0; }

    .role-form-page .section-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--c-primary-soft);
        color: var(--c-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .role-form-page .section-icon-perm {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        box-shadow: 0 4px 10px rgba(79,70,229,0.25);
    }

    .role-form-page .section-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--c-slate-900);
        margin: 0 0 0.15rem;
        letter-spacing: -0.2px;
    }
    .role-form-page .section-subtitle {
        font-size: 0.85rem;
        color: var(--c-slate-500);
        margin: 0;
    }
    .role-form-page .text-muted { color: var(--c-slate-400); }

    /* ════════════════════════════════════════════════════════
       FORMULAIRE — champs
       ════════════════════════════════════════════════════════ */
    .role-form-page .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
    @media (min-width: 768px) {
        .role-form-page .form-grid { grid-template-columns: repeat(2, 1fr); }
        .role-form-page .form-field-full { grid-column: 1 / -1; }
    }

    .role-form-page .form-field { min-width: 0; }

    .role-form-page .form-label {
        display: block;
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--c-slate-700);
        margin-bottom: 0.4rem;
    }
    .role-form-page .req { color: var(--c-rose); font-weight: 700; }

    .role-form-page .form-input,
    .role-form-page .form-textarea {
        width: 100%;
        padding: 0.65rem 0.9rem;
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
    .role-form-page .form-textarea {
        min-height: auto;
        resize: vertical;
        line-height: 1.5;
    }

    .role-form-page .form-input:focus,
    .role-form-page .form-textarea:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }
    .role-form-page .form-input.is-invalid,
    .role-form-page .form-textarea.is-invalid {
        border-color: var(--c-rose);
        background: var(--c-rose-soft);
    }

    .role-form-page .form-help {
        display: flex;
        align-items: flex-start;
        gap: 0.35rem;
        font-size: 0.75rem;
        color: var(--c-slate-500);
        margin: 0.4rem 0 0;
        line-height: 1.4;
    }
    .role-form-page .form-help i {
        color: #f59e0b;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .role-form-page .error-text {
        display: block;
        font-size: 0.78rem;
        color: var(--c-rose);
        margin: 0.35rem 0 0;
        font-weight: 500;
    }
    .role-form-page .error-perms {
        padding: 0.5rem 1.5rem 0;
    }

    /* ════════════════════════════════════════════════════════
       TABS
       ════════════════════════════════════════════════════════ */
    .role-form-page .tabs {
        display: flex;
        gap: 0.4rem;
        flex-wrap: nowrap;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        margin-bottom: 1rem;
        padding-bottom: 2px;
    }
    .role-form-page .tabs::-webkit-scrollbar { display: none; }

    .role-form-page .tab {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.55rem 1rem;
        background: transparent;
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-sm);
        font-weight: 600;
        font-size: 0.85rem;
        font-family: inherit;
        color: var(--c-slate-500);
        cursor: pointer;
        transition: all var(--t);
        white-space: nowrap;
        flex-shrink: 0;
        min-height: 40px;
    }
    .role-form-page .tab:hover {
        background: var(--c-slate-50);
        color: var(--c-slate-900);
        border-color: var(--c-slate-300);
    }
    .role-form-page .tab.is-active {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 4px 10px rgba(79,70,229,0.25);
    }
    .role-form-page .tab:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }

    .role-form-page .tab-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 48px;
        padding: 0.1rem 0.4rem;
        background: rgba(255,255,255,0.25);
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    .role-form-page .tab:not(.is-active) .tab-badge {
        background: var(--c-slate-100);
        color: var(--c-slate-500);
    }

    /* ════════════════════════════════════════════════════════
       TOOLBAR PERMISSIONS
       ════════════════════════════════════════════════════════ */
    .role-form-page .perm-toolbar {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }
    @media (min-width: 768px) {
        .role-form-page .perm-toolbar {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .role-form-page .search-field {
        position: relative;
        width: 100%;
        max-width: 400px;
    }
    @media (min-width: 768px) {
        .role-form-page .search-field { width: 340px; }
    }

    .role-form-page .search-icon {
        position: absolute;
        left: 0.9rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--c-slate-400);
        font-size: 0.85rem;
        pointer-events: none;
    }
    .role-form-page .search-field input {
        width: 100%;
        padding: 0.65rem 2.5rem 0.65rem 2.4rem;
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
    .role-form-page .search-field input:focus {
        border-color: #6366f1;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }

    .role-form-page .search-clear {
        position: absolute;
        right: 0.5rem;
        top: 50%;
        transform: translateY(-50%);
        width: 28px;
        height: 28px;
        background: var(--c-slate-100);
        border: none;
        border-radius: 6px;
        color: var(--c-slate-500);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all var(--t);
    }
    .role-form-page .search-clear:hover {
        background: var(--c-slate-200);
        color: var(--c-slate-900);
    }

    .role-form-page .toolbar-buttons {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .role-form-page .link-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.75rem;
        background: transparent;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.8rem;
        font-family: inherit;
        cursor: pointer;
        transition: all var(--t);
        min-height: 38px;
    }
    .role-form-page .link-btn:hover:not(:disabled) { background: var(--c-slate-100); }
    .role-form-page .link-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .role-form-page .link-success { color: var(--c-emerald); }
    .role-form-page .link-success:hover:not(:disabled) { background: var(--c-emerald-soft); }
    .role-form-page .link-muted { color: var(--c-slate-500); }
    .role-form-page .link-muted:hover:not(:disabled) {
        background: var(--c-slate-100);
        color: var(--c-slate-900);
    }

    /* ════════════════════════════════════════════════════════
       GRILLE DE PERMISSIONS
       ════════════════════════════════════════════════════════ */
    .role-form-page .permissions-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 640px) {
        .role-form-page .permissions-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (min-width: 1200px) {
        .role-form-page .permissions-grid { grid-template-columns: repeat(3, 1fr); }
    }

    .role-form-page .resource-card {
        background: var(--c-slate-50);
        border: 1.5px solid var(--c-slate-200);
        border-radius: var(--radius-md);
        padding: 1rem;
        transition: all var(--t);
        min-width: 0;
    }
    .role-form-page .resource-card.has-selected {
        background: #f5f7ff;
        border-color: var(--c-primary-mid);
        box-shadow: 0 2px 8px rgba(99,102,241,0.08);
    }

    .role-form-page .resource-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
    }
    .role-form-page .resource-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 0;
        flex: 1;
    }
    .role-form-page .resource-title h3 {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--c-slate-900);
        margin: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .role-form-page .resource-count {
        display: inline-flex;
        align-items: center;
        padding: 0.1rem 0.5rem;
        background: var(--c-slate-200);
        color: var(--c-slate-600);
        border-radius: 9999px;
        font-size: 0.68rem;
        font-weight: 700;
        flex-shrink: 0;
        font-variant-numeric: tabular-nums;
    }
    .role-form-page .has-selected .resource-count {
        background: var(--c-primary-mid);
        color: #4338ca;
    }

    .role-form-page .resource-actions {
        display: inline-flex;
        gap: 0.25rem;
        flex-shrink: 0;
    }
    .role-form-page .link-mini {
        width: 30px;
        height: 30px;
        background: transparent;
        border: 1px solid var(--c-slate-200);
        border-radius: 6px;
        color: var(--c-emerald);
        cursor: pointer;
        transition: all var(--t);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.7rem;
    }
    .role-form-page .link-mini:hover:not(:disabled) {
        background: var(--c-emerald-soft);
        border-color: var(--c-emerald-mid);
    }
    .role-form-page .link-mini.muted { color: var(--c-slate-500); }
    .role-form-page .link-mini.muted:hover:not(:disabled) {
        background: var(--c-slate-100);
        border-color: var(--c-slate-300);
    }
    .role-form-page .link-mini:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }
    .role-form-page .link-mini:focus-visible {
        outline: 2px solid var(--c-primary);
        outline-offset: 2px;
    }

    /* ───── Liste permissions ───── */
    .role-form-page .resource-list {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        max-height: 260px;
        overflow-y: auto;
        padding-right: 0.25rem;
        scrollbar-width: thin;
    }
    .role-form-page .resource-list::-webkit-scrollbar { width: 6px; }
    .role-form-page .resource-list::-webkit-scrollbar-thumb {
        background: var(--c-slate-300);
        border-radius: 3px;
    }

    .role-form-page .perm-item {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.55rem 0.65rem;
        border-radius: 8px;
        font-size: 0.85rem;
        color: var(--c-slate-600);
        cursor: pointer;
        transition: background var(--t);
        user-select: none;
        min-height: 40px;
    }
    .role-form-page .perm-item:hover { background: #fff; }
    .role-form-page .perm-item.is-selected {
        background: var(--c-primary-soft);
        color: var(--c-slate-900);
        font-weight: 600;
    }

    .role-form-page .perm-checkbox {
        position: absolute;
        opacity: 0;
        pointer-events: none;
        width: 0;
        height: 0;
    }
    .role-form-page .perm-checkmark {
        width: 18px;
        height: 18px;
        flex-shrink: 0;
        border: 2px solid var(--c-slate-300);
        border-radius: 5px;
        background: #fff;
        position: relative;
        transition: all var(--t);
    }
    .role-form-page .perm-checkmark::after {
        content: '';
        position: absolute;
        left: 4px;
        top: 1px;
        width: 5px;
        height: 9px;
        border: solid #fff;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg) scale(0);
        transition: transform var(--t);
    }
    .role-form-page .perm-item.is-selected .perm-checkmark {
        background: var(--c-primary);
        border-color: var(--c-primary);
    }
    .role-form-page .perm-item.is-selected .perm-checkmark::after {
        transform: rotate(45deg) scale(1);
    }
    .role-form-page .perm-checkbox:focus-visible + .perm-checkmark {
        box-shadow: 0 0 0 3px rgba(99,102,241,0.3);
        border-color: var(--c-primary);
    }

    .role-form-page .perm-label {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* ════════════════════════════════════════════════════════
       ÉTAT VIDE
       ════════════════════════════════════════════════════════ */
    .role-form-page .empty-perm {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem 1.5rem;
        text-align: center;
        color: var(--c-slate-400);
        background: var(--c-slate-50);
        border-radius: var(--radius-md);
        border: 1.5px dashed var(--c-slate-200);
    }
    .role-form-page .empty-perm i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: var(--c-slate-300);
    }
    .role-form-page .empty-perm p {
        margin: 0;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    .role-form-page .empty-perm strong { color: var(--c-slate-600); }

    /* ════════════════════════════════════════════════════════
       FOOTER STICKY
       ════════════════════════════════════════════════════════ */
    .role-form-page .content-footer {
        position: sticky;
        bottom: 0;
        z-index: 20;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 1rem 1.25rem;
        padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0px));
        background: #fff;
        border-top: 1px solid var(--c-slate-200);
        box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
    }
    @media (min-width: 768px) {
        .role-form-page .content-footer {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .role-form-page .footer-info {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.9rem;
        color: var(--c-slate-500);
    }
    .role-form-page .footer-count {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .role-form-page .footer-count i { color: #6366f1; }
    .role-form-page .footer-count strong {
        color: var(--c-primary);
        font-size: 1rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
    }

    .role-form-page .pending-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.65rem;
        background: #fffbeb;
        color: #b45309;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .role-form-page .footer-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    @media (max-width: 640px) {
        .role-form-page .footer-actions {
            width: 100%;
            flex-direction: column-reverse;
        }
        .role-form-page .footer-actions .btn { width: 100%; }
    }

    /* ════════════════════════════════════════════════════════
       RESPONSIVE
       ════════════════════════════════════════════════════════ */
    @media (max-width: 992px) {
        .role-form-page { padding: 1.5rem 1rem; }
    }

    @media (max-width: 768px) {
        .role-form-page { padding: 1.25rem 0.85rem; }
        .role-form-page .page-title { font-size: 1.35rem; }
        .role-form-page .title-icon { font-size: 1.2rem; }
        .role-form-page .page-subtitle { font-size: 0.85rem; }

        .role-form-page .content-card { border-radius: var(--radius-md); }
        .role-form-page .section-icon { width: 36px; height: 36px; font-size: 0.9rem; }

        /* Anti-zoom iOS */
        .role-form-page .search-field input,
        .role-form-page .form-input,
        .role-form-page .form-textarea { font-size: 16px; }
    }

    @media (max-width: 480px) {
        .role-form-page { padding: 1rem 0.6rem; }
        .role-form-page .page-title { font-size: 1.15rem; }

        .role-form-page .form-section { padding: 1rem 0.85rem; }
        .role-form-page .section-title { font-size: 0.95rem; }
        .role-form-page .section-subtitle { font-size: 0.78rem; }

        .role-form-page .tabs { gap: 0.3rem; }
        .role-form-page .tab {
            padding: 0.5rem 0.75rem;
            font-size: 0.8rem;
        }
        .role-form-page .tab-badge {
            min-width: 40px;
            font-size: 0.65rem;
        }

        .role-form-page .resource-card {
            padding: 0.75rem;
            border-radius: var(--radius-sm);
        }
        .role-form-page .resource-title h3 { font-size: 0.85rem; }

        .role-form-page .perm-item {
            padding: 0.5rem 0.55rem;
            font-size: 0.82rem;
            min-height: 42px;
        }
        .role-form-page .perm-checkmark { width: 17px; height: 17px; }

        .role-form-page .footer-info { font-size: 0.85rem; }
    }

    @media (max-width: 360px) {
        .role-form-page .tab {
            padding: 0.45rem 0.6rem;
            font-size: 0.75rem;
            gap: 0.35rem;
        }
        .role-form-page .perm-item {
            padding: 0.45rem 0.5rem;
            font-size: 0.78rem;
        }
        .role-form-page .footer-count span { display: none; }
    }

    /* ════════════════════════════════════════════════════════
       PRINT + A11Y
       ════════════════════════════════════════════════════════ */
    @media print {
        .role-form-page .content-footer,
        .role-form-page .header-actions { display: none !important; }
        .role-form-page .content-card { box-shadow: none; border: 1px solid #ccc; }
        .role-form-page .resource-card { break-inside: avoid; }
        .role-form-page .permissions-grid {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .role-form-page *,
        .role-form-page *::before,
        .role-form-page *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
    }
</style>
@endpush