<!DOCTYPE html>
<html lang="fr" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">

    <title>{{ $emailSubject ?? 'Message' }}</title>

    <style>
        /* ============================================================
           RESET EMAIL-SAFE
           ============================================================ */
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            color: #1e293b;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Preheader invisible (aperçu Gmail/Outlook) */
        .preheader {
            display: none !important;
            visibility: hidden;
            opacity: 0;
            color: transparent;
            height: 0;
            width: 0;
            max-height: 0;
            max-width: 0;
            overflow: hidden;
            mso-hide: all;
            font-size: 1px;
            line-height: 1px;
        }

        /* ============================================================
           STRUCTURE
           ============================================================ */
        .email-wrapper {
            background-color: #f8fafc;
            padding: 30px 16px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        /* ============================================================
           EN-TÊTE
           ============================================================ */
        .email-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 28px 30px;
            color: #ffffff;
            text-align: center;
        }

        .email-header .logo {
            display: inline-block;
            margin-bottom: 10px;
        }

        .email-header .logo img {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            object-fit: cover;
            display: inline-block;
            vertical-align: middle;
        }

        .email-header .logo-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.35rem;
            color: #ffffff;
            line-height: 1;
            vertical-align: middle;
        }

        .email-header h1 {
            margin: 0;
            font-size: 1.35rem;
            font-weight: 700;
            letter-spacing: -0.3px;
            line-height: 1.3;
        }

        /* ============================================================
           SUJET
           ============================================================ */
        .email-subject {
            text-align: center;
            padding: 24px 30px 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            line-height: 1.4;
            letter-spacing: -0.2px;
        }

        /* ============================================================
           CORPS
           ============================================================ */
        .email-body {
            padding: 20px 30px 30px;
            line-height: 1.7;
            font-size: 0.95rem;
            color: #475569;
            word-wrap: break-word;
        }

        /* Contenu texte (sauts de ligne respectés) */
        .email-body p {
            margin: 0 0 1rem;
            line-height: 1.7;
        }
        .email-body p:last-child { margin-bottom: 0; }

        /* ============================================================
           FOOTER
           ============================================================ */
        .email-footer {
            background: #f9fafb;
            border-top: 1px solid #f1f5f9;
            padding: 20px 30px;
            text-align: center;
            font-size: 0.78rem;
            color: #94a3b8;
            line-height: 1.6;
        }

        .email-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .email-footer .unsubscribe {
            margin-top: 8px;
            font-size: 0.72rem;
            color: #cbd5e1;
        }

        /* ============================================================
           LIENS
           ============================================================ */
        a {
            color: #667eea;
            text-decoration: none;
        }

        /* ============================================================
           RESPONSIVE — MOBILE
           ============================================================ */
        @media only screen and (max-width: 600px) {
            .email-wrapper { padding: 16px 8px; }
            .email-container { border-radius: 12px; }
            .email-header { padding: 22px 20px; }
            .email-header h1 { font-size: 1.15rem; }
            .email-subject { padding: 20px 20px 0; font-size: 1.05rem; }
            .email-body { padding: 18px 20px 24px; font-size: 0.9rem; }
            .email-footer { padding: 16px 20px; font-size: 0.72rem; }
        }

        /* ============================================================
           DARK MODE
           ============================================================ */
        @media (prefers-color-scheme: dark) {
            body, .email-wrapper { background-color: #0f172a !important; }
            .email-container { background-color: #1e293b !important; }
            .email-subject { color: #f1f5f9 !important; }
            .email-body { color: #cbd5e1 !important; }
            .email-footer {
                background-color: #0f172a !important;
                border-top-color: #334155 !important;
                color: #94a3b8 !important;
            }
            a { color: #a5b4fc !important; }
        }
    </style>
</head>
<body>

    @php
        // ✅ Variables passées par le Mailable — plus de requête DB ici
        $displayName = $siteName ?? config('app.name', 'École');
        $displayLogo = $siteLogo ?? null;

        // Initiale UTF-8 safe
        $initial = '';
        if (is_string($displayName) && $displayName !== '') {
            $initial = mb_strtoupper(mb_substr(trim($displayName), 0, 1));
        }
    @endphp

    {{-- ============ PREHEADER (invisible, aperçu Gmail) ============ --}}
    <div class="preheader">
        {{ $emailSubject ?? 'Nouveau message' }} — {{ \Illuminate\Support\Str::limit(strip_tags($content), 100) }}
    </div>

    {{-- ============ EMAIL ============ --}}
    <div class="email-wrapper" role="article" aria-label="{{ $emailSubject ?? 'Message' }}">
        <div class="email-container">

            {{-- En-tête --}}
            <div class="email-header">
                <div class="logo">
                    @if($displayLogo)
                        <img src="{{ asset('storage/' . $displayLogo) }}"
                             alt="{{ $displayName }}"
                             width="48" height="48">
                    @else
                        <div class="logo-placeholder" aria-hidden="true">
                            {{ $initial ?: '📧' }}
                        </div>
                    @endif
                </div>
                <h1>{{ $displayName }}</h1>
            </div>

            {{-- Sujet (optionnel — déjà dans l'en-tête du mail) --}}
            @if(!empty($emailSubject))
                <p class="email-subject">{{ $emailSubject }}</p>
            @endif

            {{-- Corps du message --}}
            <div class="email-body">
                {!! nl2br(e($content)) !!}
            </div>

            {{-- Footer --}}
            <div class="email-footer">
                &copy; {{ now()->year }} <strong>{{ $displayName }}</strong>. Tous droits réservés.

                @if(!empty($unsubscribeUrl))
                    <div class="unsubscribe">
                        <a href="{{ $unsubscribeUrl }}">Se désinscrire de nos communications</a>
                    </div>
                @endif
            </div>

        </div>
    </div>
</body>
</html>