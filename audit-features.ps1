# ═══════════════════════════════════════════════════════════════
# AUDIT DES FONCTIONNALITÉS — MyscoolApp
# Génère un rapport complet des routes par catégorie
# ═══════════════════════════════════════════════════════════════

Write-Host "`n=== AUDIT DES FONCTIONNALITÉS ===" -ForegroundColor Cyan

# 1. Export de toutes les routes en JSON
Write-Host "`n[1/4] Extraction des routes..." -ForegroundColor Yellow
php artisan route:list --json | Out-File -Encoding UTF8 -FilePath ".\routes-raw.json"

if (-not (Test-Path ".\routes-raw.json")) {
    Write-Host "  ❌ Échec de l'extraction" -ForegroundColor Red
    exit 1
}

$routes = Get-Content ".\routes-raw.json" -Raw | ConvertFrom-Json
Write-Host "  ✅ $($routes.Count) routes extraites" -ForegroundColor Green

# 2. Catégorisation
Write-Host "`n[2/4] Catégorisation..." -ForegroundColor Yellow

$categories = @{
    'PUBLIC'      = @()
    'EXTERNAL'    = @()
    'ADMIN'       = @()
    'MEMBER'      = @()
    'AUTH'        = @()
    'SYSTEM'      = @()
}

foreach ($route in $routes) {
    $uri    = $route.uri
    $name   = $route.name
    $method = ($route.method -replace '\|.*', '') # Garde seulement le 1er verbe

    # Détection du middleware
    $middleware = $route.middleware -join ','

    # Catégorisation par URI
    if ($uri -match '^_|^up$|^storage|^build|^sanctum|^livewire|^telescope|^horizon' -or $name -match '^(ignition|sanctum|livewire|telescope|horizon|filament|_debugbar)') {
        $categories['SYSTEM'] += $route
    }
    elseif ($uri -match '^admin' -or $name -match '^admin\.') {
        $categories['ADMIN'] += $route
    }
    elseif ($uri -match '^member' -or $name -match '^member\.') {
        $categories['MEMBER'] += $route
    }
    elseif ($uri -match '^external' -or $name -match '^external\.') {
        $categories['EXTERNAL'] += $route
    }
    elseif ($name -match '^(login|register|password|logout|verification|forgot)' -or $uri -match '^(login|register|password|logout)') {
        $categories['AUTH'] += $route
    }
    elseif ($uri -match '^public' -or $name -match '^public\.' -or $uri -eq '/' -or $uri -eq 'home' -or $name -match '^(home|annonces)') {
        $categories['PUBLIC'] += $route
    }
    else {
        # Par défaut : si pas de middleware auth → PUBLIC, sinon OTHER
        if ($middleware -match 'auth') {
            $categories['MEMBER'] += $route
        } else {
            $categories['PUBLIC'] += $route
        }
    }
}

# 3. Génération du rapport
Write-Host "`n[3/4] Génération du rapport..." -ForegroundColor Yellow

$reportPath = ".\AUDIT-FONCTIONNALITES.txt"
$report = @()

$report += "═══════════════════════════════════════════════════════════════"
$report += "  AUDIT COMPLET DES FONCTIONNALITÉS — MYSCOOLAPP"
$report += "  Généré le : $(Get-Date -Format 'dd/MM/yyyy à HH:mm')"
$report += "═══════════════════════════════════════════════════════════════"
$report += ""

# Ordre d'affichage
$order = @('PUBLIC', 'AUTH', 'EXTERNAL', 'MEMBER', 'ADMIN', 'SYSTEM')

foreach ($cat in $order) {
    $list = $categories[$cat]

    $report += ""
    $report += "═══════════════════════════════════════════════════════════════"
    $report += "  [$cat] — $($list.Count) routes"
    $report += "═══════════════════════════════════════════════════════════════"
    $report += ""

    if ($list.Count -eq 0) {
        $report += "  (aucune)"
        continue
    }

    # Tri par URI
    $sorted = $list | Sort-Object uri

    foreach ($route in $sorted) {
        $method = ($route.method -replace '\|.*', '')
        $uri    = $route.uri
        $name   = if ($route.name) { $route.name } else { '—' }
        $action = if ($route.action) { $route.action -replace 'App\\Http\\Controllers\\', '' } else { 'Closure' }
        $mw     = if ($route.middleware) { ($route.middleware | Where-Object { $_ -match 'auth|role|permission|throttle' }) -join ',' } else { '' }

        $report += "  $method $uri"
        $report += "    └─ Nom    : $name"
        $report += "    └─ Action : $action"
        if ($mw) {
            $report += "    └─ MW     : $mw"
        }
        $report += ""
    }
}

# Statistiques
$report += ""
$report += "═══════════════════════════════════════════════════════════════"
$report += "  STATISTIQUES"
$report += "═══════════════════════════════════════════════════════════════"
$report += ""
$report += "  Routes publiques       : $($categories['PUBLIC'].Count)"
$report += "  Routes d'authentification : $($categories['AUTH'].Count)"
$report += "  Espace abonné (external) : $($categories['EXTERNAL'].Count)"
$report += "  Espace membre            : $($categories['MEMBER'].Count)"
$report += "  Espace admin             : $($categories['ADMIN'].Count)"
$report += "  Système (framework)      : $($categories['SYSTEM'].Count)"
$report += "  ────────────────────────────────"
$report += "  TOTAL                    : $($routes.Count)"
$report += ""

$report | Out-File -Encoding UTF8 $reportPath

Write-Host "  ✅ Rapport généré : $reportPath" -ForegroundColor Green

# 4. Résumé console
Write-Host "`n[4/4] Résumé :" -ForegroundColor Yellow
Write-Host "  PUBLIC       : $($categories['PUBLIC'].Count) routes" -ForegroundColor Cyan
Write-Host "  AUTH         : $($categories['AUTH'].Count) routes" -ForegroundColor Cyan
Write-Host "  EXTERNAL     : $($categories['EXTERNAL'].Count) routes" -ForegroundColor Cyan
Write-Host "  MEMBER       : $($categories['MEMBER'].Count) routes" -ForegroundColor Cyan
Write-Host "  ADMIN        : $($categories['ADMIN'].Count) routes" -ForegroundColor Cyan
Write-Host "  SYSTEM       : $($categories['SYSTEM'].Count) routes" -ForegroundColor DarkGray

Write-Host "`n=== TERMINÉ ===" -ForegroundColor Cyan
Write-Host "`nOuvre le rapport : notepad .\AUDIT-FONCTIONNALITES.txt`n" -ForegroundColor Green
