<?php
/**
 * Script di Ripristino e Ottimizzazione Logo Mobile e Desktop (Versione Super-Robusta)
 * Applica le modifiche a app.blade.php e style.css su SiteGround
 * ed esegue la pulizia della cache di Laravel.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='it'>
<head>
    <meta charset='UTF-8'>
    <title>Applicatore Fix Logo</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #121212; color: #fff; padding: 40px; }
        .card { max-width: 600px; margin: 0 auto; background: #1e1e1e; padding: 30px; border-radius: 12px; border: 1px solid #FFD700; box-shadow: 0 0 20px rgba(255, 215, 0, 0.15); }
        h1 { color: #FFD700; margin-top: 0; }
        .success { color: #4ade80; font-weight: bold; }
        .error { color: #f87171; font-weight: bold; }
        pre { background: #000; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 13px; border: 1px solid #333; }
        .btn { display: inline-block; background: #FFD700; color: #121212; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 20px; }
        .btn:hover { background: #e5c100; }
    </style>
</head>
<body>
<div class='card'>
    <h1>🔄 Applicatore Fix Logo Capodanno Roma</h1>";

// Rileva se lo script si trova nella cartella public/ o nella root
$basePath = (basename(__DIR__) === 'public') ? dirname(__DIR__) : __DIR__;

// 1. Modifica resources/views/layouts/app.blade.php
$appLayoutPath = $basePath . '/resources/views/layouts/app.blade.php';
if (file_exists($appLayoutPath)) {
    $content = file_get_contents($appLayoutPath);
    
    // Rimpiazza il contenitore con lo sfondo bianco inline a priorità assoluta
    $content = str_replace(
        '<span class="brand-logo-container">',
        '<span class="brand-logo-container" style="background-color: #ffffff !important; display: flex; align-items: center; justify-content: center;">',
        $content
    );
    
    // Rimpiazza l'immagine originaria (o quella precedentemente modificata) con quella finale e robusta
    $imgTarget1 = '<img src="{{ asset(\'img/logo-rc-scuro.png\') }}" alt="{{ config(\'app.name\', \'Capodanno Roma\') }}" class="brand-logo-img" onerror="this.onerror=null; this.parentElement.style.display=\'none\';">';
    $imgTarget2 = '<img src="{{ asset(\'img/logo-rc-scuro.png\') }}" alt="{{ config(\'app.name\', \'Capodanno Roma\') }}" class="brand-logo-img" style="height: 45px; max-height: 45px; width: auto; max-width: 100%; object-fit: contain; display: block;" onerror="this.onerror=null; this.parentElement.style.display=\'none\';">';
    
    $imgReplacement = '<img src="{{ asset(\'img/logo-rc-scuro.png\') }}" alt="{{ config(\'app.name\', \'Capodanno Roma\') }}" class="brand-logo-img" style="height: 45px; max-height: 45px; width: auto; max-width: 100%; object-fit: contain; display: block;" onerror="this.onerror=null; this.parentElement.style.display=\'none\';">';
    
    $content = str_replace($imgTarget1, $imgReplacement, $content);
    $content = str_replace($imgTarget2, $imgReplacement, $content);
    
    file_put_contents($appLayoutPath, $content);
    echo "<p class='success'>✅ app.blade.php aggiornato con successo con gli stili inline ed il badge bianco!</p>";
} else {
    echo "<p class='error'>❌ File app.blade.php non trovato al percorso: $appLayoutPath</p>";
}

// 2. Modifica public/css/style.css tramite append di override (a prova di cache e differenze spazi)
$styleCssPath = $basePath . '/public/css/style.css';
if (file_exists($styleCssPath)) {
    $cssContent = file_get_contents($styleCssPath);
    
    $appendCss = "\n\n/* --- OVERRIDE LOGO FIX --- */\n" .
".brand-logo-container {\n" .
"    display: flex !important;\n" .
"    align-items: center !important;\n" .
"    justify-content: center !important;\n" .
"    background: #ffffff !important;\n" .
"    border-radius: 8px !important;\n" .
"    padding: 5px 12px !important;\n" .
"    height: 55px !important;\n" .
"    max-width: 180px !important;\n" .
"    transition: all var(--transition-smooth);\n" .
"}\n\n" .
".brand-logo-img {\n" .
"    height: 45px !important;\n" .
"    max-height: 45px !important;\n" .
"    width: auto !important;\n" .
"    max-width: 100% !important;\n" .
"    object-fit: contain !important;\n" .
"    display: block !important;\n" .
"    transition: all var(--transition-smooth);\n" .
"}\n\n" .
"@media (max-width: 768px) {\n" .
"    .brand-logo-container {\n" .
"        padding: 4px 8px !important;\n" .
"        border-radius: 6px !important;\n" .
"        height: 40px !important;\n" .
"        max-width: 130px !important;\n" .
"    }\n" .
"    .brand-logo-img {\n" .
"        height: 32px !important;\n" .
"        max-height: 32px !important;\n" .
"        width: auto !important;\n" .
"    }\n" .
"}";

    if (strpos($cssContent, '/* --- OVERRIDE LOGO FIX --- */') === false) {
        file_put_contents($styleCssPath, $cssContent . $appendCss);
        echo "<p class='success'>✅ style.css aggiornato con successo con gli stili di override del logo!</p>";
    } else {
        $parts = explode("/* --- OVERRIDE LOGO FIX --- */", $cssContent);
        file_put_contents($styleCssPath, rtrim($parts[0]) . $appendCss);
        echo "<p class='success'>✅ style.css rinfrescato con successo con gli stili di override del logo più recenti!</p>";
    }
} else {
    echo "<p class='error'>❌ File style.css non trovato al percorso: $styleCssPath</p>";
}

// 3. Esegui la pulizia della cache di Laravel
echo "<h3>🧹 Pulizia della Cache di Laravel:</h3>";
echo "<pre>";
$artisanPath = escapeshellarg($basePath . '/artisan');
$outputView = shell_exec("php $artisanPath view:clear 2>&1");
echo "View Cache: " . trim($outputView) . "\n";
$outputConfig = shell_exec("php $artisanPath config:clear 2>&1");
echo "Config Cache: " . trim($outputConfig) . "\n";
$outputCache = shell_exec("php $artisanPath cache:clear 2>&1");
echo "Application Cache: " . trim($outputCache) . "\n";
echo "</pre>";

echo "<p class='success'>🎉 Operazione conclusa! Ricarica la pagina sul cellulare (se necessario, svuota la cache del browser mobile o usa una scheda in incognito).</p>";
echo "<p style='color: #A0A0A0; font-size: 12px; margin-top: 30px;'>⚠️ <strong>Nota di sicurezza:</strong> Una volta verificato il corretto funzionamento, elimina questo file (<code>apply_logo_fix.php</code>) dal server.</p>";
echo "</div>
</body>
</html>";
