<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Maintenance - <?= defined('APP_NAME') ? e(APP_NAME) : 'AutoSAV' ?></title>
  <style>
    body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: Arial, sans-serif; background: #f4f6f9; color: #1f2937; }
    .box { max-width: 620px; padding: 40px; text-align: center; background: #fff; border: 1px solid #d1d5db; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
    h1 { margin: 0 0 16px; font-size: 28px; }
    p { margin: 0; line-height: 1.6; color: #4b5563; }
  </style>
</head>
<body>
  <main class="box">
    <h1>Maintenance en cours</h1>
    <p><?= isset($message) ? e($message) : 'L application est temporairement indisponible. Veuillez reessayer ulterieurement.' ?></p>
  </main>
</body>
</html>
