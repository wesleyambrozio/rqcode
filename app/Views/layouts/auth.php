<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? config('app.name')) ?></title>
  <link rel="icon" type="image/png" href="/assets/images/rqcode/favicon-rqcode.png">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="login-page">
  <?= $content ?>
</body>
</html>
