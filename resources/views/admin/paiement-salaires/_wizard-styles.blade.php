<style>
    .page { max-width: 1200px; margin: 0 auto; padding: 2rem 1rem; }

    .page-header {
        display: flex; flex-direction: column; align-items: flex-start;
        gap: 1.25rem; margin-bottom: 2rem;
    }
    @media (min-width: 768px) {
        .page-header { flex-direction: row; justify-content: space-between; align-items: center; }
    }
    .page-title {
        display: flex; align-items: center; gap: 0.6rem;
        font-size: 1.75rem; font-weight: 800; color: #0f172a;
        letter-spacing: -0.5px; margin: 0 0 0.25rem;
    }
    .title-icon { color: #6366f1; }
    .page-subtitle { color: #64748b; font-size: 0.9rem; margin: 0; }
    .header-actions { display: flex; gap: 0.6rem; flex-wrap: wrap; }

    .btn {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.7rem 1.25rem; border-radius: 12px;
        font-weight: 600; font-size: 0.9rem; text-decoration: none;
        border: none; cursor: pointer; transition: all 0.2s ease;
        white-space: nowrap;
    }
    .btn-primary {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
    }
    .btn-primary:hover:not(:disabled) {
        transform: translateY(-1px); box-shadow: 0 6px 18px rgba(79, 70, 229, 0.35);
    }
    .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-ghost {
        background: #fff; color: #64748b;
        border: 1.5px solid #e2e8f0;
    }
    .btn-ghost:hover {
        border-color: #6366f1; color: #4f46e5; background: #f8fafc;
    }
    .btn-success {
        background: #059669; color: #fff;
        box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
    }
    .btn-success:hover:not(:disabled) {
        background: #047857;
        box-shadow: 0 6px 18px rgba(5, 150, 105, 0.35);
    }

    .flash {
        display: flex; gap: 0.65rem; align-items: flex-start;
        padding: 0.9rem 1.1rem; border-radius: 12px;
        margin-bottom: 1.25rem; font-size: 0.9rem;
    }
    .flash-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
    .flash-error   { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
    .flash-list {
        list-style: disc; margin: 0.4rem 0 0 1.25rem;
        padding: 0; font-size: 0.85rem;
    }

    .wizard-progress {
        display: flex; gap: 0.5rem; align-items: center;
        margin-bottom: 1.5rem; padding: 1rem 1.25rem;
        background: #fff; border-radius: 16px; border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .wizard-step { display: flex; align-items: center; gap: 0.5rem; flex: 1; }
    .wizard-step-circle {
        width: 36px; height: 36px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        background: #f1f5f9; color: #94a3b8;
        font-weight: 700; font-size: 0.85rem;
        border: 2px solid transparent; cursor: pointer;
        transition: all 0.2s;
    }
    .wizard-step.is-active .wizard-step-circle {
        background: linear-gradient(135deg, #4f46e5, #6366f1);
        color: #fff; border-color: #c7d2fe;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }
    .wizard-step.is-completed .wizard-step-circle {
        background: #ecfdf5; color: #059669;
        border-color: #86efac;
    }
    .wizard-step-circle:disabled { cursor: not-allowed; opacity: 0.5; }
    .wizard-step-label {
        font-size: 0.8rem; font-weight: 600; color: #94a3b8;
        display: none;
    }
    @media (min-width: 768px) { .wizard-step-label { display: inline; } }
    .wizard-step.is-active .wizard-step-label    { color: #4f46e5; }
    .wizard-step.is-completed .wizard-step-label { color: #059669; }
    .wizard-step-line {
        flex: 1; height: 2px; background: #e2e8f0;
        border-radius: 2px; margin-left: 0.5rem;
    }
    .wizard-step.is-completed .wizard-step-line { background: #86efac; }

    .wizard-layout {
        display: grid; grid-template-columns: 1fr; gap: 1.5rem;
    }
    @media (min-width: 1024px) {
        .wizard-layout { grid-template-columns: 2fr 1fr; }
    }
    .wizard-main  { display: flex; flex-direction: column; gap: 1.25rem; }
    .wizard-aside { display: flex; flex-direction: column; gap: 1rem; }

    .wizard-card {
        background: #fff; border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        padding: 1.5rem;
    }
    .wizard-card-header {
        display: flex; align-items: flex-start; gap: 0.9rem;
        margin-bottom: 1.5rem;
    }
    .wizard-card-icon {
        width: 44px; height: 44px; border-radius: 12px;
        background: #eef2ff; color: #4f46e5;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; flex-shrink: 0;
    }
    .wizard-card-icon-success { background: #ecfdf5; color: #059669; }
    .wizard-card-icon-warning { background: #fffbeb; color: #d97706; }
    .wizard-card-title {
        font-size: 1.1rem; font-weight: 800; color: #0f172a;
        margin: 0 0 0.2rem; letter-spacing: -0.3px;
    }
    .wizard-card-subtitle { font-size: 0.85rem; color: #64748b; margin: 0; }

    .wizard-field { display: flex; flex-direction: column; gap: 0.4rem; margin-bottom: 1.25rem; }
    .wizard-label {
        font-size: 0.75rem; font-weight: 700; color: #475569;
        text-transform: uppercase; letter-spacing: 0.3px;
    }
    .req { color: #ef4444; }

    .wizard-select { position: relative; }
    .wizard-select select {
        width: 100%; padding: 0.75rem 2.5rem 0.75rem 1rem;
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        background: #f8fafc; font-size: 0.95rem; color: #0f172a;
        appearance: none; cursor: pointer; outline: none;
        transition: all 0.2s;
    }
    .wizard-select select:focus {
        border-color: #6366f1; background: #fff;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .wizard-select-icon {
        position: absolute; right: 0.9rem; top: 50%;
        transform: translateY(-50%); color: #94a3b8;
        pointer-events: none; font-size: 0.85rem;
    }

    .wizard-input,
    .wizard-textarea {
        width: 100%; padding: 0.75rem 1rem;
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        background: #f8fafc; font-size: 0.95rem; color: #0f172a;
        outline: none; transition: all 0.2s;
        font-family: inherit;
    }
    .wizard-input:focus,
    .wizard-textarea:focus {
        border-color: #6366f1; background: #fff;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .wizard-input.is-invalid,
    .wizard-textarea.is-invalid {
        border-color: #ef4444; background: #fef2f2;
    }
    .wizard-textarea { resize: vertical; min-height: 60px; }

    .wizard-grid-2 {
        display: grid; grid-template-columns: 1fr; gap: 1rem;
    }
    @media (min-width: 640px) { .wizard-grid-2 { grid-template-columns: repeat(2, 1fr); } }

    .wizard-toolbar {
        display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1rem;
    }
    @media (min-width: 640px) {
        .wizard-toolbar { flex-direction: row; align-items: center; justify-content: space-between; }
    }
    .wizard-search { position: relative; flex: 1; }
    .wizard-search i {
        position: absolute; left: 0.9rem; top: 50%;
        transform: translateY(-50%); color: #94a3b8;
        pointer-events: none; font-size: 0.85rem;
    }
    .wizard-search input {
        width: 100%; padding: 0.6rem 1rem 0.6rem 2.4rem;
        border: 1.5px solid #e2e8f0; border-radius: 10px;
        background: #f8fafc; font-size: 0.9rem; outline: none;
        transition: all 0.2s;
    }
    .wizard-search input:focus {
        border-color: #6366f1; background: #fff;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    .wizard-toolbar-actions { display: flex; align-items: center; gap: 0.75rem; }
    .wizard-count { font-size: 0.8rem; color: #64748b; }
    .wizard-link {
        background: none; border: none; cursor: pointer;
        font-size: 0.8rem; font-weight: 600; color: #4f46e5;
        padding: 0; text-decoration: none;
    }
    .wizard-link.muted { color: #94a3b8; }
    .wizard-link:hover { text-decoration: underline; }

    .employe-list,
    .mois-list {
        display: flex; flex-direction: column; gap: 0.5rem;
        max-height: 400px; overflow-y: auto; padding-right: 0.25rem;
    }
    .employe-item,
    .mois-item {
        display: flex; align-items: center; gap: 0.75rem;
        padding: 0.75rem 1rem;
        border: 1.5px solid #e2e8f0; border-radius: 12px;
        background: #fff; cursor: pointer; text-align: left;
        transition: all 0.15s;
    }
    .employe-item:hover,
    .mois-item:hover { border-color: #c7d2fe; }
    .employe-item.is-selected,
    .mois-item.is-selected {
        border-color: #6366f1; background: #eef2ff;
        box-shadow: 0 2px 8px rgba(99, 102, 241, 0.12);
    }
    .employe-avatar {
        width: 36px; height: 36px; border-radius: 10px;
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: #fff; display: flex; align-items: center;
        justify-content: center; font-weight: 700;
        font-size: 0.8rem; flex-shrink: 0;
    }
    .employe-info { flex: 1; min-width: 0; }
    .employe-name {
        font-weight: 600; color: #0f172a; font-size: 0.9rem;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .employe-detail { font-size: 0.75rem; color: #64748b; margin: 0.15rem 0 0; }

    .wizard-info {
        display: flex; flex-direction: column; gap: 0.5rem;
        padding: 1rem; margin-bottom: 1.25rem;
        background: #f8fafc; border-radius: 12px;
        border: 1px solid #f1f5f9;
    }
    .wizard-info-row {
        display: flex; justify-content: space-between;
        align-items: center; font-size: 0.85rem;
    }
    .wizard-info-row span   { color: #64748b; }
    .wizard-info-row strong { color: #0f172a; font-weight: 700; }

    .wizard-summary {
        display: grid; grid-template-columns: 1fr; gap: 0.75rem;
        margin-bottom: 1.5rem;
    }
    @media (min-width: 640px) { .wizard-summary { grid-template-columns: repeat(2, 1fr); } }
    .summary-item {
        padding: 0.9rem 1rem; background: #f8fafc;
        border-radius: 12px; border: 1px solid #f1f5f9;
    }
    .summary-label {
        display: block; font-size: 0.7rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.4px;
        color: #94a3b8; margin-bottom: 0.25rem;
    }
    .summary-value {
        font-size: 1rem; font-weight: 700; color: #0f172a;
        margin: 0; word-break: break-word;
    }
    .summary-value-accent { color: #4f46e5; }

    .wizard-list {
        padding: 1rem; margin-bottom: 1.25rem;
        background: #f8fafc; border-radius: 12px;
        border: 1px solid #f1f5f9;
    }
    .wizard-list-title {
        font-size: 0.8rem; font-weight: 700; color: #475569;
        margin: 0 0 0.5rem; text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .wizard-list-items {
        list-style: none; padding: 0; margin: 0;
        display: flex; flex-wrap: wrap; gap: 0.4rem;
    }
    .wizard-list-items li {
        background: #fff; padding: 0.25rem 0.7rem;
        border-radius: 9999px; font-size: 0.8rem;
        color: #334155; border: 1px solid #e2e8f0;
    }

    .wizard-footer {
        display: flex; justify-content: space-between;
        gap: 0.6rem; padding-top: 1.25rem;
        margin-top: 1.25rem; border-top: 1px solid #f1f5f9;
    }
    @media (max-width: 640px) {
        .wizard-footer { flex-direction: column-reverse; }
        .wizard-footer .btn { width: 100%; justify-content: center; }
    }

    .aside-card {
        background: #fff; border-radius: 14px;
        border: 1px solid #f1f5f9; padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .aside-card-highlight {
        background: linear-gradient(135deg, #eef2ff 0%, #f5f3ff 100%);
        border-color: #c7d2fe;
    }
    .aside-title {
        display: flex; align-items: center; gap: 0.5rem;
        font-size: 0.9rem; font-weight: 700; color: #0f172a;
        margin: 0 0 1rem;
    }
    .aside-title i { color: #6366f1; }
    .aside-dl { margin: 0; }
    .aside-row {
        display: flex; justify-content: space-between; gap: 0.5rem;
        padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9;
        font-size: 0.85rem;
    }
    .aside-row:last-child { border-bottom: none; }
    .aside-row dt { color: #64748b; margin: 0; }
    .aside-row dd { color: #0f172a; font-weight: 600; margin: 0; text-align: right; }
    .aside-row-total dd strong { color: #4f46e5; font-size: 1rem; }
    .aside-row-total dd small  { display: block; color: #94a3b8; font-weight: 500; font-size: 0.72rem; }

    .pill {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.25rem 0.65rem; border-radius: 9999px;
        font-size: 0.72rem; font-weight: 600; white-space: nowrap;
    }
    .pill i { font-size: 0.7rem; }
    .pill-success { background: #ecfdf5; color: #047857; }
    .pill-warning { background: #fffbeb; color: #b45309; }
    .pill-danger  { background: #fef2f2; color: #be123c; }
    .pill-neutral { background: #f1f5f9; color: #64748b; }
    .pill-indigo  { background: #eef2ff; color: #4338ca; }

    [x-cloak] { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        * { animation-duration: 0.01ms !important; transition-duration: 0.01ms !important; }
    }
</style>