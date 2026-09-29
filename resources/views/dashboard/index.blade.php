@extends('layouts.admin')

@section('page_title', 'Tableau de bord')
@section('page_subtitle', 'Aperçu de votre activité')

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    /* ============================================================
       DESIGN TOKENS
       ============================================================ */
    :root {
        --primary: #4f46e5;
        --primary-light: #818cf8;
        --primary-dark: #3730a3;
        --primary-soft: #eef2ff;
        --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);

        --success: #10b981;
        --success-soft: #d1fae5;
        --warning: #f59e0b;
        --warning-soft: #fef3c7;
        --danger: #ef4444;
        --danger-soft: #fee2e2;
        --info: #3b82f6;
        --info-soft: #dbeafe;

        --gray-50: #f8fafc;
        --gray-100: #f1f5f9;
        --gray-200: #e2e8f0;
        --gray-300: #cbd5e1;
        --gray-400: #94a3b8;
        --gray-500: #64748b;
        --gray-600: #475569;
        --gray-700: #334155;
        --gray-800: #1e293b;
        --gray-900: #0f172a;

        --radius-card: 20px;
        --radius-md: 14px;
        --radius-sm: 10px;

        --shadow-card: 0 1px 3px rgba(0,0,0,0.04), 0 4px 12px rgba(0,0,0,0.03);
        --shadow-hover: 0 10px 30px rgba(0,0,0,0.08);
        --shadow-focus: 0 0 0 3px rgba(79, 70, 229, 0.15);

        --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        --transition-fast: all 0.15s ease;

        --safe-top: env(safe-area-inset-top, 0px);
        --safe-bottom: env(safe-area-inset-bottom, 0px);
        --safe-left: env(safe-area-inset-left, 0px);
        --safe-right: env(safe-area-inset-right, 0px);
    }

    /* ============================================================
       RESET
       ============================================================ */
    *, *::before, *::after { box-sizing: border-box; }

    .dashboard-wrap * { min-width: 0; }

    .dashboard-wrap {
        max-width: 1440px;
        margin: 0 auto;
        padding: 1.5rem;
        padding-left: max(1.5rem, var(--safe-left));
        padding-right: max(1.5rem, var(--safe-right));
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        color: var(--gray-800);
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }

    /* ============================================================
       HERO
       ============================================================ */
    .hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .hero-left {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        min-width: 0;
        flex: 1 1 auto;
    }
    .hero-greeting {
        font-size: clamp(1.35rem, 4vw, 1.75rem);
        font-weight: 800;
        color: var(--gray-900);
        letter-spacing: -0.02em;
        line-height: 1.2;
        margin: 0;
        word-break: break-word;
    }
    .hero-greeting .name {
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    .hero-sub {
        font-size: 0.95rem;
        color: var(--gray-500);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        line-height: 1.5;
    }
    .session-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.2rem 0.65rem;
        background: var(--success-soft);
        color: #065f46;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .session-tag::before {
        content: '';
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: currentColor;
        animation: pulse 2s infinite;
        flex-shrink: 0;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50%      { opacity: 0.4; }
    }

    .hero-actions {
        display: flex;
        gap: 0.6rem;
        align-items: center;
        flex-shrink: 0;
    }

    /* ============================================================
       NOTIFICATION BELL — 🔔 Cloche corrigée
       ============================================================ */
    .notif-bell {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: var(--radius-md);
        background: #fff;
        border: 1.5px solid var(--gray-200);
        color: var(--gray-500);
        text-decoration: none;
        transition: var(--transition);
        flex-shrink: 0;
    }
    .notif-bell:hover,
    .notif-bell:focus-visible {
        border-color: var(--primary);
        color: var(--primary);
        background: var(--primary-soft);
        transform: translateY(-1px);
        box-shadow: var(--shadow-focus);
    }

    /* ✅ État "notification présente" */
    .notif-bell.has-notif {
        color: var(--danger);
        background: #fef2f2;
        border-color: #fecaca;
    }
    .notif-bell.has-notif:hover {
        background: #fee2e2;
        border-color: var(--danger);
    }

    .notif-bell .badge-count {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 20px;
        height: 20px;
        padding: 0 5px;
        background: var(--danger);
        color: #fff;
        border-radius: 999px;
        font-size: 0.65rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #fff;
        animation: pulse-danger 2s infinite;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }
    @keyframes pulse-danger {
        0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        50%      { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
    }

    /* ============================================================
       BUTTONS
       ============================================================ */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.65rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.88rem;
        font-family: inherit;
        text-decoration: none;
        transition: var(--transition);
        cursor: pointer;
        border: 1.5px solid transparent;
        white-space: nowrap;
        min-height: 42px;
    }
    .btn-outline {
        background: #fff;
        color: var(--gray-700);
        border-color: var(--gray-200);
    }
    .btn-outline:hover,
    .btn-outline:focus-visible {
        border-color: var(--primary);
        color: var(--primary);
        background: var(--gray-50);
        transform: translateY(-1px);
    }
    .btn-primary {
        background: var(--primary-gradient);
        color: #fff;
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }
    .btn-primary:hover,
    .btn-primary:focus-visible {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(79, 70, 229, 0.4);
    }

    /* ============================================================
       KPI GRID
       ============================================================ */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    .kpi-card {
        position: relative;
        background: #fff;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-card);
        padding: 1.25rem;
        overflow: hidden;
        transition: var(--transition);
        min-width: 0;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-hover);
    }
    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: var(--primary-gradient);
        opacity: 0.8;
    }
    .kpi-card.kpi-success::before { background: linear-gradient(90deg, #10b981, #34d399); }
    .kpi-card.kpi-warning::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .kpi-card.kpi-danger::before  { background: linear-gradient(90deg, #ef4444, #f87171); }

    .kpi-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
        gap: 0.5rem;
    }
    .kpi-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.05em;
        line-height: 1.3;
    }
    .kpi-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .kpi-icon-primary { background: var(--primary-soft); color: var(--primary); }
    .kpi-icon-success { background: var(--success-soft); color: var(--success); }
    .kpi-icon-warning { background: var(--warning-soft); color: var(--warning); }
    .kpi-icon-danger  { background: var(--danger-soft);  color: var(--danger); }

    .kpi-value {
        font-size: clamp(1.25rem, 3.5vw, 1.75rem);
        font-weight: 800;
        color: var(--gray-900);
        letter-spacing: -0.03em;
        line-height: 1.1;
        word-break: break-word;
        font-variant-numeric: tabular-nums;
    }
    .kpi-value small {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--gray-400);
        margin-left: 0.2rem;
    }
    .kpi-fc {
        font-size: 0.78rem;
        color: var(--gray-500);
        margin-top: 0.35rem;
        font-weight: 500;
        word-break: break-word;
        font-variant-numeric: tabular-nums;
    }

    /* ============================================================
       LAYOUT
       ============================================================ */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 340px minmax(0, 1fr);
        gap: 1.25rem;
        align-items: start;
    }

    /* ============================================================
       PROFILE CARD
       ============================================================ */
    .profile-card {
        background: #fff;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-card);
        overflow: hidden;
        position: sticky;
        top: 1.5rem;
        transition: var(--transition);
        min-width: 0;
    }
    .profile-card:hover { box-shadow: var(--shadow-hover); }

    .profile-cover {
        height: 90px;
        background: var(--primary-gradient);
        position: relative;
        overflow: hidden;
    }
    .profile-cover::after {
        content: '';
        position: absolute;
        top: -30%; right: -20%;
        width: 180px; height: 180px;
        border-radius: 50%;
        background: rgba(255,255,255,0.12);
    }
    .profile-cover::before {
        content: '';
        position: absolute;
        bottom: -60%; left: -10%;
        width: 140px; height: 140px;
        border-radius: 50%;
        background: rgba(255,255,255,0.08);
    }

    .profile-body { padding: 0 1.5rem 1.5rem; text-align: center; }

    .profile-avatar {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        border: 4px solid #fff;
        background: var(--primary-gradient);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 2rem;
        font-weight: 800;
        margin: -44px auto 0.75rem;
        position: relative;
        box-shadow: 0 8px 24px rgba(79, 70, 229, 0.25);
        overflow: hidden;
        flex-shrink: 0;
    }
    .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .online-dot {
        position: absolute;
        bottom: 2px; right: 2px;
        width: 16px; height: 16px;
        border-radius: 50%;
        background: var(--success);
        border: 3px solid #fff;
    }

    .profile-name {
        font-size: 1.15rem;
        font-weight: 800;
        color: var(--gray-900);
        letter-spacing: -0.02em;
        margin: 0 0 0.4rem;
        word-break: break-word;
    }
    .profile-badges {
        display: flex;
        justify-content: center;
        gap: 0.4rem;
        flex-wrap: wrap;
        margin-bottom: 1.25rem;
    }
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.25rem 0.7rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .badge-role     { background: var(--primary-soft); color: var(--primary); }
    .badge-fonction { background: var(--gray-100); color: var(--gray-700); }

    .profile-info {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        text-align: left;
        padding: 0.75rem;
        background: var(--gray-50);
        border-radius: var(--radius-md);
        margin-bottom: 1rem;
        min-width: 0;
    }
    .profile-info-row {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        font-size: 0.82rem;
        color: var(--gray-600);
        min-width: 0;
    }
    .profile-info-row i {
        width: 16px;
        text-align: center;
        color: var(--primary);
        font-size: 0.8rem;
        flex-shrink: 0;
    }
    .profile-info-row .value {
        color: var(--gray-700);
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        min-width: 0;
        flex: 1;
    }
    .profile-info-row .label {
        color: var(--gray-500);
        font-weight: 600;
    }

    .salary-card {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-md);
        padding: 1rem;
        margin-bottom: 0.75rem;
    }
    .salary-card-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 700;
        color: var(--gray-500);
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .salary-card-value {
        font-size: clamp(1.25rem, 3.5vw, 1.65rem);
        font-weight: 800;
        color: var(--gray-900);
        letter-spacing: -0.03em;
        margin-top: 0.35rem;
        line-height: 1.1;
        word-break: break-word;
        font-variant-numeric: tabular-nums;
    }
    .salary-card-value small { font-size: 0.85rem; color: var(--gray-400); font-weight: 600; }
    .salary-card-fc {
        font-size: 0.8rem;
        color: var(--gray-500);
        margin-top: 0.2rem;
        font-weight: 500;
        word-break: break-word;
        font-variant-numeric: tabular-nums;
    }

    .debt-card {
        border-radius: var(--radius-md);
        padding: 1rem;
        margin-bottom: 0.75rem;
        border: 1px solid;
    }
    .debt-card.has-debt {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        border-color: #fecaca;
    }
    .debt-card.no-debt {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        border-color: #a7f3d0;
    }
    .debt-header {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .debt-card.has-debt .debt-header { color: #991b1b; }
    .debt-card.no-debt  .debt-header { color: #065f46; }
    .debt-value {
        font-size: clamp(1.05rem, 3vw, 1.3rem);
        font-weight: 800;
        margin-top: 0.35rem;
        line-height: 1.1;
        word-break: break-word;
        font-variant-numeric: tabular-nums;
    }
    .debt-card.has-debt .debt-value { color: #991b1b; }
    .debt-card.no-debt  .debt-value { color: #065f46; }
    .debt-fc { font-size: 0.75rem; margin-top: 0.2rem; opacity: 0.8; }
    .debt-card.has-debt .debt-fc { color: #991b1b; }
    .debt-details {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        flex-wrap: wrap;
        font-size: 0.75rem;
        margin-top: 0.6rem;
        padding-top: 0.6rem;
        border-top: 1px dashed rgba(153, 27, 27, 0.2);
        color: #991b1b;
    }
    .debt-details strong { font-weight: 800; }

    .btn-profile {
        display: block;
        width: 100%;
        padding: 0.7rem;
        border-radius: var(--radius-md);
        background: var(--gray-100);
        color: var(--gray-700);
        font-weight: 600;
        font-size: 0.85rem;
        text-align: center;
        text-decoration: none;
        transition: var(--transition);
        border: 1px solid transparent;
        min-height: 42px;
    }
    .btn-profile:hover,
    .btn-profile:focus-visible {
        background: var(--primary);
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.25);
    }

    /* ============================================================
       RIGHT COLUMN / PANELS
       ============================================================ */
    .right-column {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        min-width: 0;
    }

    .panel {
        background: #fff;
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-card);
        overflow: hidden;
        transition: var(--transition);
        min-width: 0;
    }
    .panel:hover { box-shadow: var(--shadow-card); }

    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.15rem 1.5rem;
        border-bottom: 1px solid var(--gray-100);
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .panel-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 0.98rem;
        font-weight: 700;
        color: var(--gray-900);
        min-width: 0;
    }
    .panel-title i { color: var(--primary); font-size: 1rem; flex-shrink: 0; }
    .panel-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.65rem;
        background: var(--primary-soft);
        color: var(--primary);
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
    }
    .panel-link {
        font-size: 0.82rem;
        color: var(--primary);
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        transition: var(--transition);
        white-space: nowrap;
    }
    .panel-link:hover { color: var(--primary-dark); transform: translateX(3px); }

    /* ============================================================
       STATS SUPER ADMIN
       ============================================================ */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        padding: 1.25rem 1.5rem 1.5rem;
    }
    .stat-card {
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-md);
        padding: 1rem;
        text-align: center;
        transition: var(--transition);
        min-width: 0;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        border-color: var(--primary-light);
        background: #fff;
        box-shadow: 0 8px 20px rgba(79, 70, 229, 0.08);
    }
    .stat-icon {
        width: 40px;
        height: 40px;
        margin: 0 auto 0.6rem;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.05rem;
    }
    .i-indigo  { background: var(--primary-soft); color: var(--primary); }
    .i-success { background: var(--success-soft); color: var(--success); }
    .i-info    { background: var(--info-soft);    color: var(--info); }
    .i-warning { background: var(--warning-soft); color: var(--warning); }

    .stat-number {
        font-size: clamp(1.1rem, 3vw, 1.5rem);
        font-weight: 800;
        color: var(--gray-900);
        letter-spacing: -0.02em;
        line-height: 1.1;
        word-break: break-word;
        font-variant-numeric: tabular-nums;
    }
    .stat-label {
        font-size: 0.7rem;
        color: var(--gray-500);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-top: 0.25rem;
    }

    /* ============================================================
       TABLE PAIEMENTS
       ============================================================ */
    .table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .pay-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.87rem;
        min-width: 500px;
    }
    .pay-table thead th {
        text-align: left;
        padding: 0.75rem 1.5rem;
        font-size: 0.68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--gray-500);
        background: var(--gray-50);
        border-bottom: 1px solid var(--gray-200);
        white-space: nowrap;
    }
    .pay-table thead th.right  { text-align: right; }
    .pay-table thead th.center { text-align: center; }

    .pay-table tbody td {
        padding: 0.85rem 1.5rem;
        border-bottom: 1px solid var(--gray-100);
        color: var(--gray-700);
        vertical-align: middle;
    }
    .pay-table tbody tr:hover { background: var(--gray-50); }
    .pay-table tbody tr:last-child td { border-bottom: none; }

    .pay-month { display: flex; flex-direction: column; gap: 0.15rem; }
    .pay-month-name { font-weight: 700; color: var(--gray-900); font-size: 0.88rem; }
    .pay-month-date { font-size: 0.72rem; color: var(--gray-400); }

    .pay-amount {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.1rem;
    }
    .pay-amount-usd {
        font-weight: 700;
        color: var(--gray-900);
        font-variant-numeric: tabular-nums;
    }
    .pay-amount-fc {
        font-size: 0.7rem;
        color: var(--gray-400);
        font-weight: 500;
        font-variant-numeric: tabular-nums;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.28rem 0.7rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .status-badge.paid    { background: var(--success-soft); color: #065f46; }
    .status-badge.partial { background: var(--warning-soft); color: #92400e; }
    .status-badge.unpaid  { background: var(--danger-soft);  color: #991b1b; }
    .status-badge.pending { background: var(--info-soft);    color: #1e40af; }
    .status-badge.refused { background: var(--gray-200);     color: var(--gray-700); }

    .pay-table tfoot td {
        padding: 0.9rem 1.5rem;
        background: var(--gray-50);
        border-top: 2px solid var(--gray-200);
        font-weight: 800;
        color: var(--gray-900);
        font-size: 0.88rem;
        font-variant-numeric: tabular-nums;
    }
    .pay-table tfoot td.right { text-align: right; }

    /* ============================================================
       AVANCES
       ============================================================ */
    .avances-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 1rem;
        padding: 1.25rem 1.5rem 1.5rem;
    }
    .avance-item {
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-md);
        padding: 1rem;
        transition: var(--transition);
        min-width: 0;
    }
    .avance-item:hover {
        border-color: var(--primary-light);
        background: #fff;
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.08);
        transform: translateY(-2px);
    }
    .avance-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px dashed var(--gray-200);
    }
    .avance-date {
        font-size: 0.75rem;
        color: var(--gray-500);
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
    }
    .avance-status {
        font-size: 0.65rem;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }
    .avance-status.partial { background: var(--warning-soft); color: #92400e; }
    .avance-status.pending { background: var(--info-soft);    color: #1e40af; }

    .avance-row {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        font-size: 0.82rem;
        padding: 0.2rem 0;
    }
    .avance-row .label { color: var(--gray-500); }
    .avance-row .value {
        font-weight: 700;
        color: var(--gray-900);
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .avance-row.danger  .value { color: var(--danger); }
    .avance-row.success .value { color: var(--success); }

    .avance-progress {
        margin-top: 0.65rem;
        padding-top: 0.65rem;
        border-top: 1px dashed var(--gray-200);
    }
    .progress-bar {
        height: 6px;
        background: var(--gray-200);
        border-radius: 999px;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        background: var(--primary-gradient);
        border-radius: 999px;
        transition: width 0.4s ease;
    }
    .progress-label {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        font-size: 0.68rem;
        color: var(--gray-500);
        margin-top: 0.35rem;
        font-weight: 600;
        flex-wrap: wrap;
        font-variant-numeric: tabular-nums;
    }

    /* ============================================================
       CHART
       ============================================================ */
    .chart-container {
        padding: 1.25rem 1.5rem 1.5rem;
        height: 280px;
        position: relative;
        min-width: 0;
    }
    .chart-container canvas { max-width: 100%; }

    /* ============================================================
       DEMANDES
       ============================================================ */
    .demandes-list {
        display: flex;
        flex-direction: column;
        padding: 0.75rem 1.5rem 1.5rem;
        gap: 0.65rem;
    }
    .demande-item {
        display: flex;
        gap: 0.85rem;
        padding: 0.85rem 1rem;
        background: var(--gray-50);
        border: 1px solid var(--gray-200);
        border-radius: var(--radius-md);
        transition: var(--transition);
        align-items: flex-start;
        min-width: 0;
    }
    .demande-item:hover {
        background: #fff;
        border-color: var(--primary-light);
    }
    .demande-icon {
        width: 38px;
        height: 38px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.9rem;
        color: #fff;
    }
    .demande-icon.pending { background: linear-gradient(135deg, #3b82f6, #60a5fa); }
    .demande-icon.success { background: linear-gradient(135deg, #10b981, #34d399); }
    .demande-icon.refused { background: linear-gradient(135deg, #ef4444, #f87171); }
    .demande-icon.other   { background: linear-gradient(135deg, #94a3b8, #cbd5e1); }

    .demande-body { flex: 1; min-width: 0; }
    .demande-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .demande-title {
        font-weight: 700;
        color: var(--gray-900);
        font-size: 0.9rem;
        margin: 0;
        word-break: break-word;
    }
    .demande-meta {
        font-size: 0.72rem;
        color: var(--gray-500);
        margin-top: 0.15rem;
        line-height: 1.5;
    }
    .demande-motif {
        font-size: 0.8rem;
        color: var(--gray-600);
        margin-top: 0.4rem;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }
    .demande-refus {
        margin-top: 0.5rem;
        padding: 0.5rem 0.7rem;
        background: var(--danger-soft);
        border-radius: var(--radius-sm);
        border-left: 3px solid var(--danger);
        font-size: 0.78rem;
        color: #991b1b;
        word-break: break-word;
    }
    .demande-refus strong { display: block; margin-bottom: 0.15rem; }

    /* ============================================================
       ANNONCES
       ============================================================ */
    .announcements-list {
        padding: 0.5rem 1rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .announcement {
        padding: 1rem;
        border-radius: var(--radius-md);
        background: var(--gray-50);
        border: 1px solid transparent;
        transition: var(--transition);
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
        min-width: 0;
    }
    .announcement:hover {
        background: #fff;
        border-color: var(--primary-light);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(79, 70, 229, 0.08);
    }
    .announcement-icon {
        width: 38px;
        height: 38px;
        border-radius: var(--radius-sm);
        background: linear-gradient(135deg, #f59e0b, #fbbf24);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.95rem;
    }
    .announcement-body { flex: 1; min-width: 0; }
    .announcement-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0 0 0.25rem;
        line-height: 1.35;
        word-break: break-word;
    }
    .announcement-text {
        font-size: 0.8rem;
        color: var(--gray-500);
        margin: 0;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        word-break: break-word;
    }
    .announcement-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 0.5rem;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .announcement-date {
        font-size: 0.7rem;
        color: var(--gray-400);
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }
    .announcement-tag {
        font-size: 0.65rem;
        padding: 0.15rem 0.55rem;
        border-radius: 999px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        white-space: nowrap;
    }
    .announcement-tag.public  { background: var(--success-soft); color: #065f46; }
    .announcement-tag.private { background: var(--gray-200);     color: var(--gray-700); }

    /* ============================================================
       EMPTY STATES
       ============================================================ */
    .empty { padding: 3rem 1.5rem; text-align: center; }
    .empty-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: var(--gray-100);
        color: var(--gray-400);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.85rem;
        font-size: 1.5rem;
    }
    .empty-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--gray-700);
        margin: 0 0 0.25rem;
    }
    .empty-text { font-size: 0.82rem; color: var(--gray-500); margin: 0; }

    /* ============================================================
       FOCUS / ACCESSIBILITÉ
       ============================================================ */
    .dashboard-wrap a:focus-visible,
    .dashboard-wrap button:focus-visible {
        outline: 2px solid var(--primary);
        outline-offset: 2px;
        border-radius: 6px;
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }
        .kpi-card:hover,
        .stat-card:hover,
        .avance-item:hover,
        .announcement:hover,
        .profile-card:hover { transform: none; }
    }

    /* ============================================================
       RESPONSIVE — 1280px+ (grand desktop)
       ============================================================ */
    @media (min-width: 1280px) {
        .dashboard-grid { grid-template-columns: 360px minmax(0, 1fr); }
    }

    /* ============================================================
       RESPONSIVE — 1200px (tablette large)
       ============================================================ */
    @media (max-width: 1200px) {
        .kpi-grid   { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    /* ============================================================
       RESPONSIVE — 1024px (tablette paysage)
       ============================================================ */
    @media (max-width: 1024px) {
        .dashboard-grid { grid-template-columns: 1fr; }
        .profile-card { position: static; }
        .profile-body { padding: 0 1.25rem 1.25rem; }
    }

    /* ============================================================
       RESPONSIVE — 768px (tablette portrait)
       ============================================================ */
    @media (max-width: 768px) {
        .dashboard-wrap { padding: 1rem; }

        .hero { gap: 0.75rem; margin-bottom: 1.25rem; }
        .hero-actions { width: 100%; }
        .hero-actions .btn { flex: 1; }

        .kpi-grid { gap: 0.75rem; }
        .kpi-card { padding: 1rem; border-radius: var(--radius-md); }
        .kpi-icon { width: 34px; height: 34px; font-size: 0.9rem; }

        .profile-avatar { width: 76px; height: 76px; margin-top: -38px; font-size: 1.65rem; }
        .profile-name { font-size: 1.05rem; }
        .profile-cover { height: 70px; }

        .panel-header { padding: 1rem 1.15rem; }
        .panel-title { font-size: 0.92rem; }

        .stats-grid { padding: 1rem 1.15rem 1.15rem; gap: 0.6rem; }
        .avances-grid { grid-template-columns: 1fr; padding: 1rem 1.15rem 1.15rem; }
        .demandes-list { padding: 0.65rem 1.15rem 1.15rem; }
        .chart-container { height: 240px; padding: 1rem 1.15rem 1.15rem; }

        /* Table → cartes */
        .pay-table { min-width: unset; }
        .pay-table thead { display: none; }
        .pay-table tbody tr {
            display: block;
            padding: 1rem;
            border-bottom: 1px solid var(--gray-100);
        }
        .pay-table tbody tr:last-child { border-bottom: none; }
        .pay-table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 0.4rem 0;
            border: none;
        }
        .pay-table tbody td::before {
            content: attr(data-label);
            font-weight: 700;
            font-size: 0.7rem;
            color: var(--gray-500);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            flex-shrink: 0;
        }
        .pay-table tbody td .pay-amount { align-items: flex-end; }
        .pay-table tfoot { display: block; }
        .pay-table tfoot tr {
            display: block;
            padding: 0.85rem 1rem;
            border-top: 2px solid var(--gray-200);
        }
        .pay-table tfoot td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            padding: 0.35rem 0;
            background: transparent;
            border: none;
        }
        .pay-table tfoot td.right { text-align: left; }
    }

    /* ============================================================
       RESPONSIVE — 576px (mobile)
       ============================================================ */
    @media (max-width: 576px) {
        .dashboard-wrap { padding: 0.85rem; }

        .hero-greeting { font-size: 1.25rem; }
        .hero-sub { font-size: 0.85rem; }

        .kpi-card { padding: 0.85rem; }
        .kpi-top { gap: 0.35rem; }
        .kpi-icon { width: 32px; height: 32px; font-size: 0.85rem; }
        .kpi-label { font-size: 0.65rem; }
        .kpi-value { font-size: 1.25rem; }
        .kpi-fc { font-size: 0.72rem; }

        .profile-body { padding: 0 1rem 1rem; }
        .profile-avatar { width: 68px; height: 68px; margin-top: -34px; font-size: 1.5rem; }
        .profile-name { font-size: 0.98rem; }
        .profile-info { padding: 0.65rem; }
        .profile-info-row { font-size: 0.78rem; }

        .salary-card { padding: 0.85rem; }
        .salary-card-value { font-size: 1.3rem; }
        .salary-card-fc { font-size: 0.75rem; }

        .debt-card { padding: 0.85rem; }
        .debt-value { font-size: 1.1rem; }

        .panel-header { padding: 0.9rem 1rem; }
        .panel-title { font-size: 0.88rem; gap: 0.4rem; }
        .panel-title i { font-size: 0.9rem; }
        .panel-badge { font-size: 0.65rem; padding: 0.15rem 0.55rem; }
        .panel-link { font-size: 0.78rem; }

        .demandes-list { padding: 0.5rem 1rem 1rem; gap: 0.5rem; }
        .demande-item { padding: 0.75rem 0.85rem; gap: 0.65rem; }
        .demande-icon { width: 34px; height: 34px; font-size: 0.85rem; }
        .demande-title { font-size: 0.85rem; }
        .demande-meta { font-size: 0.68rem; }
        .demande-motif { font-size: 0.75rem; }

        .announcements-list { padding: 0.4rem 0.85rem 0.85rem; }
        .announcement { padding: 0.85rem; gap: 0.7rem; }
        .announcement-icon { width: 34px; height: 34px; font-size: 0.85rem; }
        .announcement-title { font-size: 0.85rem; }
        .announcement-text { font-size: 0.75rem; }

        .avances-grid { padding: 0.85rem 1rem 1rem; }
        .avance-item { padding: 0.85rem; }
        .avance-row { font-size: 0.78rem; }
        .progress-label { font-size: 0.65rem; }

        .chart-container { height: 220px; padding: 0.85rem 1rem 1rem; }

        .stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            padding: 0.85rem 1rem 1rem;
            gap: 0.5rem;
        }
        .stat-card { padding: 0.75rem 0.5rem; }
        .stat-icon { width: 34px; height: 34px; font-size: 0.9rem; margin-bottom: 0.4rem; }
        .stat-number { font-size: 1.15rem; }
        .stat-label { font-size: 0.63rem; }

        .btn-profile { padding: 0.65rem; font-size: 0.82rem; }
        .notif-bell { width: 40px; height: 40px; }
        .notif-bell .badge-count { min-width: 18px; height: 18px; font-size: 0.6rem; }
    }

    /* ============================================================
       RESPONSIVE — 480px (petit mobile)
       ============================================================ */
    @media (max-width: 480px) {
        .dashboard-wrap { padding: 0.75rem; }

        .hero-greeting { font-size: 1.15rem; }
        .hero-sub { font-size: 0.8rem; }
        .session-tag { font-size: 0.65rem; padding: 0.15rem 0.5rem; }

        .hero-actions { gap: 0.4rem; }
        .hero-actions .btn { font-size: 0.78rem; padding: 0.55rem 0.85rem; min-height: 38px; }

        .kpi-grid { gap: 0.6rem; }
        .kpi-card { padding: 0.75rem; border-radius: var(--radius-sm); }
        .kpi-icon { width: 28px; height: 28px; font-size: 0.78rem; border-radius: var(--radius-sm); }
        .kpi-value { font-size: 1.1rem; }
        .kpi-value small { font-size: 0.7rem; }
        .kpi-fc { font-size: 0.68rem; }

        .profile-body { padding: 0 0.85rem 0.85rem; }
        .profile-avatar { width: 60px; height: 60px; margin-top: -30px; font-size: 1.3rem; border-width: 3px; }
        .profile-name { font-size: 0.92rem; }
        .profile-badges { margin-bottom: 1rem; }
        .badge { font-size: 0.63rem; padding: 0.2rem 0.6rem; }
        .profile-info-row .value { font-size: 0.75rem; }

        .panel-header { padding: 0.75rem 0.85rem; gap: 0.5rem; }
        .panel-title { font-size: 0.82rem; }
        .panel-title i { font-size: 0.85rem; }

        .demandes-list { padding: 0.4rem 0.85rem 0.85rem; }
        .demande-item { padding: 0.65rem 0.75rem; gap: 0.55rem; }
        .demande-icon { width: 30px; height: 30px; font-size: 0.75rem; border-radius: 8px; }

        .announcements-list { padding: 0.35rem 0.75rem 0.75rem; }
        .announcement { padding: 0.75rem; gap: 0.6rem; }
        .announcement-icon { width: 30px; height: 30px; font-size: 0.78rem; border-radius: 8px; }

        .chart-container { height: 200px; padding: 0.75rem 0.85rem 0.85rem; }

        .stats-grid { padding: 0.75rem 0.85rem 0.85rem; }
        .stat-card { padding: 0.65rem 0.4rem; }
        .stat-icon { width: 30px; height: 30px; font-size: 0.82rem; }
        .stat-number { font-size: 1.05rem; }
        .stat-label { font-size: 0.58rem; }
    }

    /* ============================================================
       RESPONSIVE — 360px (très petit mobile)
       ============================================================ */
    @media (max-width: 360px) {
        .dashboard-wrap { padding: 0.6rem; }

        .hero-greeting { font-size: 1.05rem; }
        .hero-sub { font-size: 0.75rem; }

        .kpi-grid { grid-template-columns: 1fr; }
        .kpi-card { padding: 0.85rem; }

        .hero-actions .btn span { display: none; }
        .hero-actions .btn { padding: 0.6rem; }

        .profile-avatar { width: 54px; height: 54px; margin-top: -27px; font-size: 1.15rem; }

        .stats-grid { grid-template-columns: 1fr; }

        .demande-item { flex-direction: column; }
        .demande-icon { align-self: flex-start; }
    }

    /* ============================================================
       IMPRESSION
       ============================================================ */
    @media print {
        .dashboard-wrap { padding: 0; max-width: 100%; }
        .hero-actions, .btn, .btn-profile, .notif-bell, .panel-link { display: none !important; }
        .profile-card { position: static; }
        .kpi-card, .panel, .profile-card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
            page-break-inside: avoid;
        }
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .dashboard-grid { grid-template-columns: 1fr; }
        .chart-container { height: 200px; }
        @page { margin: 1cm; }
    }
</style>
@endpush

@section('content')
@php
    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');

    $salaireAttendu  = $salaireData['salaireBase'] ?? 0;
    $salaireFC       = $salaireAttendu * $tauxChange;
    $detteTotale     = $detteData['detteTotale'] ?? 0;
    $detteFC         = $detteTotale * $tauxChange;
    $resteAPercevoir = max(0, $salaireAttendu - $detteTotale);
    $resteFC         = $resteAPercevoir * $tauxChange;
    $totalPercu      = $salaireData['totalPerçu'] ?? 0;
    $totalPercuFC    = $totalPercu * $tauxChange;

    /* ============================================================
       🔔 CLOCHE — Uniquement les demandes d'avance non vues
       ============================================================
       Les annonces ne comptent plus dans la cloche car :
       - Elles sont déjà visibles dans la section "Dernières annonces"
       - Le clic sur la cloche va sur /member/demandes-avance
       ============================================================ */
    $totalNotifications = (int) ($demandesNonVues ?? 0);
@endphp

<div class="dashboard-wrap">

    {{-- HERO --}}
    <div class="hero">
        <div class="hero-left">
            <h1 class="hero-greeting">
                {{ $greeting }}, <span class="name">{{ explode(' ', $user->name)[0] }}</span> 👋
            </h1>
            <p class="hero-sub">
                Voici votre aperçu du jour
                @if($sessionAvanceActive)
                    <span class="session-tag">Session d'avance ouverte : {{ $sessionAvanceActive->libelle }}</span>
                @elseif($sessionActive)
                    <span class="session-tag">Session : {{ $sessionActive->libelle }}</span>
                @endif
            </p>
        </div>
        <div class="hero-actions">
            <a href="{{ route('member.demandes-avance.index') }}"
               class="notif-bell {{ $totalNotifications > 0 ? 'has-notif' : '' }}"
               title="{{ $totalNotifications > 0 ? $totalNotifications . ' nouvelle(s) notification(s)' : 'Aucune nouvelle notification' }}"
               aria-label="{{ $totalNotifications > 0 ? $totalNotifications . ' notification(s) non lue(s)' : 'Aucune notification non lue' }}">
                <i class="fa-{{ $totalNotifications > 0 ? 'solid' : 'regular' }} fa-bell" aria-hidden="true"></i>
                @if($totalNotifications > 0)
                    <span class="badge-count" aria-hidden="true">{{ $totalNotifications > 99 ? '99+' : $totalNotifications }}</span>
                @endif
            </a>

            <a href="{{ route('member.profil.edit') }}" class="btn btn-outline">
                <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                <span>Modifier</span>
            </a>
            <a href="{{ route('member.profil') }}" class="btn btn-primary">
                <i class="fa-regular fa-user" aria-hidden="true"></i>
                <span>Mon profil</span>
            </a>
        </div>
    </div>

    {{-- KPI --}}
    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-top">
                <span class="kpi-label">Salaire attendu</span>
                <div class="kpi-icon kpi-icon-primary"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($salaireAttendu, 0, ',', ' ') }}<small>USD</small></div>
            <div class="kpi-fc">≈ {{ number_format($salaireFC, 0, ',', ' ') }} FC</div>
        </div>

        <div class="kpi-card {{ $detteTotale > 0 ? 'kpi-danger' : 'kpi-success' }}">
            <div class="kpi-top">
                <span class="kpi-label">Dette active</span>
                <div class="kpi-icon {{ $detteTotale > 0 ? 'kpi-icon-danger' : 'kpi-icon-success' }}">
                    <i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i>
                </div>
            </div>
            <div class="kpi-value">{{ number_format($detteTotale, 0, ',', ' ') }}<small>USD</small></div>
            <div class="kpi-fc">≈ {{ number_format($detteFC, 0, ',', ' ') }} FC</div>
        </div>

        <div class="kpi-card kpi-success">
            <div class="kpi-top">
                <span class="kpi-label">Reste à percevoir</span>
                <div class="kpi-icon kpi-icon-success"><i class="fa-solid fa-wallet" aria-hidden="true"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($resteAPercevoir, 0, ',', ' ') }}<small>USD</small></div>
            <div class="kpi-fc">≈ {{ number_format($resteFC, 0, ',', ' ') }} FC</div>
        </div>

        <div class="kpi-card kpi-warning">
            <div class="kpi-top">
                <span class="kpi-label">Total perçu (6 mois)</span>
                <div class="kpi-icon kpi-icon-warning"><i class="fa-solid fa-coins" aria-hidden="true"></i></div>
            </div>
            <div class="kpi-value">{{ number_format($totalPercu, 0, ',', ' ') }}<small>USD</small></div>
            <div class="kpi-fc">≈ {{ number_format($totalPercuFC, 0, ',', ' ') }} FC</div>
        </div>
    </div>

    {{-- GRILLE PRINCIPALE --}}
    <div class="dashboard-grid">

        {{-- PROFIL --}}
        <div class="profile-card">
            <div class="profile-cover"></div>
            <div class="profile-body">
                <div class="profile-avatar">
                    @if($user->photo_url)
                        <img src="{{ $user->photo_url }}" alt="{{ $user->name }}">
                    @else
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    @endif
                    <span class="online-dot"></span>
                </div>

                <h2 class="profile-name">{{ $user->name }}</h2>

                <div class="profile-badges">
                    <span class="badge badge-role">
                        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        {{ $user->role_label ?? 'Membre' }}
                    </span>
                    @if($user->fonction)
                        <span class="badge badge-fonction">{{ $user->fonction->nom }}</span>
                    @endif
                </div>

                <div class="profile-info">
                    <div class="profile-info-row">
                        <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                        <span class="value" title="{{ $user->email }}">{{ $user->email }}</span>
                    </div>
                    @if($user->telephone)
                        <div class="profile-info-row">
                            <i class="fa-regular fa-phone" aria-hidden="true"></i>
                            <span class="value">{{ $user->telephone }}</span>
                        </div>
                    @endif
                    @if($user->matricule)
                        <div class="profile-info-row">
                            <i class="fa-regular fa-id-card" aria-hidden="true"></i>
                            <span class="value"><span class="label">Matricule :</span> {{ $user->matricule }}</span>
                        </div>
                    @endif
                    @if($user->section)
                        <div class="profile-info-row">
                            <i class="fa-regular fa-building" aria-hidden="true"></i>
                            <span class="value"><span class="label">Section :</span> {{ $user->section->nom }}</span>
                        </div>
                    @endif
                </div>

                <div class="salary-card">
                    <div class="salary-card-label">
                        <i class="fa-solid fa-money-bill" aria-hidden="true"></i> Salaire de base
                    </div>
                    <div class="salary-card-value">
                        {{ number_format($salaireAttendu, 0, ',', ' ') }} <small>USD</small>
                    </div>
                    <div class="salary-card-fc">≈ {{ number_format($salaireFC, 0, ',', ' ') }} FC</div>
                </div>

                @if($detteTotale > 0)
                    <div class="debt-card has-debt">
                        <div class="debt-header">
                            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Dette active
                        </div>
                        <div class="debt-value">{{ number_format($detteTotale, 0, ',', ' ') }} USD</div>
                        <div class="debt-fc">≈ {{ number_format($detteFC, 0, ',', ' ') }} FC</div>
                        <div class="debt-details">
                            <span>Déduction : <strong>{{ number_format($detteData['deductionMensuelle'], 0, ',', ' ') }} $</strong></span>
                            <span>Reste : <strong>{{ number_format($detteData['resteApresDeduction'], 0, ',', ' ') }} $</strong></span>
                        </div>
                    </div>
                @else
                    <div class="debt-card no-debt">
                        <div class="debt-header">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Aucune dette
                        </div>
                        <div class="debt-value" style="font-size: 0.95rem;">Vous êtes à jour 👍</div>
                    </div>
                @endif

                <a href="{{ route('member.profil.edit') }}" class="btn-profile">
                    <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i> Modifier mon profil
                </a>
            </div>
        </div>

        {{-- COLONNE DROITE --}}
        <div class="right-column">

            {{-- AVANCES --}}
            @if($avancesActives->count())
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-hand-holding-usd" aria-hidden="true"></i>
                            <span>Mes avances en cours</span>
                        </div>
                        <span class="panel-badge">{{ $avancesActives->count() }} avance(s)</span>
                    </div>
                    <div class="avances-grid">
                        @foreach($avancesActives as $avance)
                            @php
                                $montantInitial = (float) $avance->montant_avance_usd;
                                $rembourse      = (float) $avance->montant_rembourse_usd;
                                $detteRestante  = (float) $avance->dette_restante_usd;
                                $pct            = $montantInitial > 0 ? round(($rembourse / $montantInitial) * 100) : 0;
                                $isPartielle    = $avance->statut === 'partiellement_remboursee';
                            @endphp
                            <div class="avance-item">
                                <div class="avance-head">
                                    <span class="avance-date">
                                        <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                        {{ \Carbon\Carbon::parse($avance->date_avance)->translatedFormat('d M Y') }}
                                    </span>
                                    <span class="avance-status {{ $isPartielle ? 'partial' : 'pending' }}">
                                        {{ $isPartielle ? 'Partielle' : 'En cours' }}
                                    </span>
                                </div>

                                <div class="avance-row">
                                    <span class="label">Montant initial</span>
                                    <span class="value">{{ number_format($montantInitial, 0, ',', ' ') }} $</span>
                                </div>
                                <div class="avance-row success">
                                    <span class="label">Remboursé</span>
                                    <span class="value">{{ number_format($rembourse, 0, ',', ' ') }} $</span>
                                </div>
                                <div class="avance-row danger">
                                    <span class="label">Dette restante</span>
                                    <span class="value">{{ number_format($detteRestante, 0, ',', ' ') }} $</span>
                                </div>

                                <div class="avance-progress">
                                    <div class="progress-bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-fill" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <div class="progress-label">
                                        <span>{{ $pct }}% remboursé</span>
                                        <span>{{ number_format($detteRestante * $tauxChange, 0, ',', ' ') }} FC</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- PROJECTION --}}
            @if(!empty($projectionDette))
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
                            <span>Projection de remboursement</span>
                        </div>
                        @if($moisReprise)
                            <span class="panel-badge" style="background: var(--success-soft); color: #065f46;">
                                Salaire complet dès {{ $moisReprise['mois'] }}
                            </span>
                        @endif
                    </div>

                    <div class="chart-container">
                        <canvas id="debtChart" aria-label="Graphique de projection de remboursement"></canvas>
                    </div>

                    <div class="table-wrap">
                        <table class="pay-table">
                            <thead>
                                <tr>
                                    <th>Mois</th>
                                    <th class="right">Dette début</th>
                                    <th class="right">Déduction</th>
                                    <th class="right">Dette fin</th>
                                    <th class="right">Net à percevoir</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($projectionDette as $ligne)
                                    <tr>
                                        <td data-label="Mois"><strong>{{ $ligne['mois'] }}</strong></td>
                                        <td data-label="Dette début" class="right">
                                            <div class="pay-amount">
                                                <span class="pay-amount-usd">{{ number_format($ligne['dette_debut'], 0, ',', ' ') }} $</span>
                                                <span class="pay-amount-fc">{{ number_format($ligne['dette_debut'] * $tauxChange, 0, ',', ' ') }} FC</span>
                                            </div>
                                        </td>
                                        <td data-label="Déduction" class="right">
                                            <div class="pay-amount">
                                                <span class="pay-amount-usd" style="color: var(--danger);">-{{ number_format($ligne['deduction'], 0, ',', ' ') }} $</span>
                                                <span class="pay-amount-fc">{{ number_format($ligne['deduction'] * $tauxChange, 0, ',', ' ') }} FC</span>
                                            </div>
                                        </td>
                                        <td data-label="Dette fin" class="right">
                                            <div class="pay-amount">
                                                <span class="pay-amount-usd">{{ number_format($ligne['dette_fin'], 0, ',', ' ') }} $</span>
                                                <span class="pay-amount-fc">{{ number_format($ligne['dette_fin'] * $tauxChange, 0, ',', ' ') }} FC</span>
                                            </div>
                                        </td>
                                        <td data-label="Net à percevoir" class="right">
                                            <div class="pay-amount">
                                                <span class="pay-amount-usd" style="color: {{ $ligne['solde'] ? 'var(--success)' : 'var(--gray-900)' }};">
                                                    {{ number_format($ligne['net_a_percevoir'], 0, ',', ' ') }} $
                                                </span>
                                                <span class="pay-amount-fc">{{ number_format($ligne['net_a_percevoir'] * $tauxChange, 0, ',', ' ') }} FC</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- DEMANDES RÉCENTES --}}
            @if($demandesRecentes->count())
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                            <span>Mes demandes récentes</span>
                        </div>
                        <a href="{{ route('member.demandes-avance.index') }}" class="panel-link">
                            Voir toutes <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="demandes-list">
                        @foreach($demandesRecentes as $demande)
                            @php
                                $statut = $demande->statut;
                                $iconClass = match($statut) {
                                    'en_attente' => 'pending',
                                    'validee'    => 'success',
                                    'refusee'    => 'refused',
                                    default      => 'other',
                                };
                                $icon = match($statut) {
                                    'en_attente' => 'fa-hourglass-half',
                                    'validee'    => 'fa-circle-check',
                                    'refusee'    => 'fa-circle-xmark',
                                    default      => 'fa-circle',
                                };
                                $label = match($statut) {
                                    'en_attente' => 'En attente',
                                    'validee'    => 'Validée',
                                    'refusee'    => 'Refusée',
                                    default      => $statut,
                                };
                                $statusClass = match($statut) {
                                    'en_attente' => 'pending',
                                    'validee'    => 'paid',
                                    'refusee'    => 'refused',
                                    default      => 'unpaid',
                                };
                            @endphp
                            <div class="demande-item">
                                <div class="demande-icon {{ $iconClass }}">
                                    <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
                                </div>
                                <div class="demande-body">
                                    <div class="demande-head">
                                        <div>
                                            <h4 class="demande-title">
                                                Demande #{{ $demande->id }} —
                                                {{ number_format($demande->montant_demande_usd, 0, ',', ' ') }} $
                                            </h4>
                                            <div class="demande-meta">
                                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                                {{ $demande->created_at->translatedFormat('d M Y à H:i') }}
                                                @if($demande->session)
                                                    · Session : {{ $demande->session->libelle }}
                                                @endif
                                            </div>
                                        </div>
                                        <span class="status-badge {{ $statusClass }}">
                                            {{ $label }}
                                        </span>
                                    </div>

                                    <div class="demande-motif">
                                        <strong>Motif :</strong> {{ $demande->motif }}
                                    </div>

                                    @if($demande->statut === 'refusee' && $demande->motif_refus)
                                        <div class="demande-refus">
                                            <strong><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> Motif du refus</strong>
                                            {{ $demande->motif_refus }}
                                        </div>
                                    @endif

                                    @if($demande->traite_le)
                                        <div style="font-size: 0.72rem; color: var(--gray-500); margin-top: 0.5rem;">
                                            <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                                            Traitée par <strong>{{ $demande->traitePar->name ?? 'Admin' }}</strong>
                                            le {{ $demande->traite_le->translatedFormat('d M Y à H:i') }}
                                        </div>
                                    @endif

                                    @if($demande->statut === 'validee' && $demande->avance)
                                        <div style="font-size: 0.78rem; color: #065f46; margin-top: 0.4rem;">
                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                            Avance #{{ $demande->avance->id }} créée automatiquement.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- STATS SUPER ADMIN --}}
            @if($user->isSuperAdmin())
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
                            <span>Aperçu général</span>
                        </div>
                        <a href="{{ route('admin.statistiques.index') }}" class="panel-link">
                            Voir plus <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon i-indigo"><i class="fa-solid fa-users" aria-hidden="true"></i></div>
                            <div class="stat-number">{{ $stats['users'] ?? 0 }}</div>
                            <div class="stat-label">Utilisateurs</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon i-success"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i></div>
                            <div class="stat-number">{{ $stats['eleves'] ?? 0 }}</div>
                            <div class="stat-label">Élèves</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon i-info"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i></div>
                            <div class="stat-number">{{ $stats['inscriptions'] ?? 0 }}</div>
                            <div class="stat-label">Inscriptions</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon i-warning"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></div>
                            <div class="stat-number">{{ number_format($stats['paiements'] ?? 0, 0, ',', ' ') }}</div>
                            <div class="stat-label">Recettes</div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- PAIEMENTS SALAIRE --}}
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">
                        <i class="fa-regular fa-receipt" aria-hidden="true"></i>
                        <span>Derniers paiements</span>
                    </div>
                    @if($salaireData['paiements']->count())
                        <span class="panel-badge">{{ $salaireData['paiements']->count() }} paiement(s)</span>
                    @endif
                </div>

                @if($salaireData['paiements']->count())
                    <div class="table-wrap">
                        <table class="pay-table">
                            <thead>
                                <tr>
                                    <th>Mois</th>
                                    <th class="right">Attendu</th>
                                    <th class="right">Payé</th>
                                    <th class="right">Reste</th>
                                    <th class="center">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($salaireData['paiements'] as $paiement)
                                    @php
                                        $statut = $paiement->statut ?? 'non_paye';
                                        $statusClass = match($statut) {
                                            'paye'     => 'paid',
                                            'souspaye' => 'partial',
                                            default    => 'unpaid',
                                        };
                                        $statusLabel = match($statut) {
                                            'paye'     => 'Payé',
                                            'souspaye' => 'Partiel',
                                            default    => 'Non payé',
                                        };
                                    @endphp
                                    <tr>
                                        <td data-label="Mois">
                                            <div class="pay-month">
                                                <span class="pay-month-name">{{ $paiement->moisScolaire->nom_mois ?? 'N/A' }}</span>
                                                <span class="pay-month-date">
                                                    {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->translatedFormat('d M Y') : '—' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td data-label="Attendu" class="right">
                                            <div class="pay-amount">
                                                <span class="pay-amount-usd">{{ number_format($paiement->montant_attendu_usd, 0, ',', ' ') }} $</span>
                                                <span class="pay-amount-fc">{{ number_format($paiement->montant_attendu_usd * $tauxChange, 0, ',', ' ') }} FC</span>
                                            </div>
                                        </td>
                                        <td data-label="Payé" class="right">
                                            <div class="pay-amount">
                                                <span class="pay-amount-usd">{{ number_format($paiement->montant_paye_usd, 0, ',', ' ') }} $</span>
                                                <span class="pay-amount-fc">{{ number_format($paiement->montant_paye_usd * $tauxChange, 0, ',', ' ') }} FC</span>
                                            </div>
                                        </td>
                                        <td data-label="Reste" class="right">
                                            <div class="pay-amount">
                                                <span class="pay-amount-usd">{{ number_format($paiement->montant_restant_usd, 0, ',', ' ') }} $</span>
                                                <span class="pay-amount-fc">{{ number_format($paiement->montant_restant_usd * $tauxChange, 0, ',', ' ') }} FC</span>
                                            </div>
                                        </td>
                                        <td data-label="Statut" class="center">
                                            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td><strong>Total</strong></td>
                                    <td class="right">
                                        {{ number_format($salaireData['totalAttendu'], 0, ',', ' ') }} $
                                        <div class="pay-amount-fc">{{ number_format($salaireData['totalAttendu'] * $tauxChange, 0, ',', ' ') }} FC</div>
                                    </td>
                                    <td class="right">
                                        {{ number_format($salaireData['totalPerçu'], 0, ',', ' ') }} $
                                        <div class="pay-amount-fc">{{ number_format($salaireData['totalPerçu'] * $tauxChange, 0, ',', ' ') }} FC</div>
                                    </td>
                                    <td class="right">
                                        {{ number_format($salaireData['solde'], 0, ',', ' ') }} $
                                        <div class="pay-amount-fc">{{ number_format($salaireData['solde'] * $tauxChange, 0, ',', ' ') }} FC</div>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="empty">
                        <div class="empty-icon"><i class="fa-regular fa-inbox" aria-hidden="true"></i></div>
                        <p class="empty-title">Aucun paiement enregistré</p>
                        <p class="empty-text">Vos paiements de salaire apparaîtront ici.</p>
                    </div>
                @endif
            </div>

            {{-- ANNONCES --}}
            @if($annoncesVisibles->count())
                <div class="panel">
                    <div class="panel-header">
                        <div class="panel-title">
                            <i class="fa-regular fa-bullhorn" aria-hidden="true"></i>
                            <span>Dernières annonces</span>
                        </div>
                        <a href="{{ route('home') }}#annonces" class="panel-link">
                            Voir toutes <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div class="announcements-list">
                        @foreach($annoncesVisibles as $annonce)
                            <div class="announcement">
                                <div class="announcement-icon"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i></div>
                                <div class="announcement-body">
                                    <h4 class="announcement-title">{{ $annonce->titre }}</h4>
                                    <p class="announcement-text">{{ strip_tags(Str::limit($annonce->contenu, 140)) }}</p>
                                    <div class="announcement-meta">
                                        <span class="announcement-date">
                                            <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                            {{ $annonce->date_debut->translatedFormat('d M Y') }}
                                        </span>
                                        <span class="announcement-tag {{ $annonce->type === 'public' ? 'public' : 'private' }}">
                                            {{ $annonce->type === 'public' ? 'Public' : 'Privé' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('debtChart');
    if (!canvas) return;

    const projection = @json($projectionDette);
    const tauxChange = {{ $tauxChange ?? 2800 }};

    if (!projection || projection.length === 0) {
        const parent = canvas.parentElement;
        parent.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;font-size:0.9rem;text-align:center;padding:1rem;">'
            + '<i class="fa-solid fa-chart-line" style="font-size:2rem;margin-right:0.5rem;"></i>'
            + 'Aucune dette en cours</div>';
        return;
    }

    const labels     = projection.map(l => l.mois);
    const detteDebut = projection.map(l => l.dette_debut);
    const netPercu   = projection.map(l => l.net_a_percevoir);

    const isMobile = window.matchMedia('(max-width: 576px)').matches;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Dette restante (USD)',
                    data: detteDebut,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.08)',
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#ef4444',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: isMobile ? 3 : 5,
                    pointHoverRadius: isMobile ? 5 : 7,
                },
                {
                    label: 'Net à percevoir (USD)',
                    data: netPercu,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.08)',
                    borderWidth: 2.5,
                    tension: 0.35,
                    fill: true,
                    pointBackgroundColor: '#10b981',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: isMobile ? 3 : 5,
                    pointHoverRadius: isMobile ? 5 : 7,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        boxWidth: 8,
                        padding: isMobile ? 8 : 15,
                        font: { family: 'Inter', size: isMobile ? 10 : 12, weight: '600' },
                        color: '#334155',
                    },
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    padding: 12,
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    titleFont: { family: 'Inter', size: 13, weight: '700' },
                    bodyFont: { family: 'Inter', size: 12 },
                    callbacks: {
                        label: function (context) {
                            const value = context.parsed.y;
                            const fc = Math.round(value * tauxChange).toLocaleString('fr-FR');
                            return context.dataset.label + ' : ' + value.toLocaleString('fr-FR') + ' $ (≈ ' + fc + ' FC)';
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { family: 'Inter', size: isMobile ? 9 : 11, weight: '600' },
                        color: '#64748b',
                        maxRotation: isMobile ? 45 : 0,
                        autoSkip: true,
                        maxTicksLimit: isMobile ? 6 : 12,
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(148, 163, 184, 0.15)', drawBorder: false },
                    ticks: {
                        font: { family: 'Inter', size: isMobile ? 9 : 11 },
                        color: '#64748b',
                        callback: function (value) { return value.toLocaleString('fr-FR') + ' $'; },
                    },
                },
            },
        },
    });
});
</script>
@endpush