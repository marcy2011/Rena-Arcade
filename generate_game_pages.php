<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_login.php');
    exit;
}

$games_file = 'games.json';
$games = [];
if (file_exists($games_file)) {
    $games = json_decode(file_get_contents($games_file), true) ?: [];
}

$generated_pages = [];
foreach ($games as $game) {
    $generated_pages[] = [
        'name' => $game['name_it'],
        'filename' => $game['filename'],
        'url' => $game['page_url']
    ];
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <title>Pagine Generate - Rena Arcades</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Open Sans', sans-serif; background: #0a0a0a; color: white; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { text-align: center; margin-bottom: 30px; }
        .success-message { background: rgba(46, 204, 113, 0.2); border: 1px solid rgba(46, 204, 113, 0.5); border-radius: 8px; padding: 15px; margin-bottom: 30px; text-align: center; }
        .pages-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .page-card { background: rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 20px; border: 1px solid rgba(255, 255, 255, 0.1); }
        .page-name { font-weight: 500; margin-bottom: 10px; font-size: 18px; }
        .page-info { color: rgba(255, 255, 255, 0.7); margin-bottom: 15px; font-size: 14px; }
        .btn { display: inline-block; background: #4a90e2; color: white; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 14px; transition: background 0.3s; }
        .btn:hover { background: #3a7bc8; }
        .actions { text-align: center; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Pagine Giochi Generate</h1>
        
        <div class="success-message">
            <p>Sono disponibili <?= count($generated_pages) ?> pagine giochi.</p>
            <p><strong>Nota:</strong> Le pagine vengono generate automaticamente quando aggiungi un nuovo gioco.</p>
        </div>
        
        <div class="pages-grid">
            <?php foreach ($generated_pages as $page): ?>
                <div class="page-card">
                    <div class="page-name"><?= htmlspecialchars($page['name']) ?></div>
                    <div class="page-info">File: <?= htmlspecialchars($page['filename']) ?></div>
                    <a href="<?= htmlspecialchars($page['filename']) ?>" class="btn" target="_blank">Apri Pagina</a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="actions">
            <a href="admin.php" class="btn">Torna alla Admin</a>
            <a href="giochi.html" class="btn" style="background: #27ae60;">Vedi Pagina Giochi</a>
        </div>
    </div>
</body>
</html>
