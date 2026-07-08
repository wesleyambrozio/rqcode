<?php $current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); ?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? config('app.name')) ?></title>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar">
      <div class="brand"><?= e(config('app.name')) ?></div>
      <nav class="nav">
        <?php foreach ([
          '/' => 'Dashboard',
          '/vendedores' => 'Vendedores',
          '/sistemas' => 'Sistemas e planos',
          '/vendas' => 'Vendas',
          '/financeiro' => 'Financeiro',
          '/suporte' => 'Suporte',
          '/integracoes' => 'Integrações',
          '/relatorios' => 'Relatórios',
          '/configuracoes' => 'Configurações',
        ] as $path => $label): ?>
          <a class="<?= $current === $path ? 'active' : '' ?>" href="<?= $path ?>"><?= $label ?></a>
        <?php endforeach; ?>
      </nav>
    </aside>
    <main class="main">
      <header class="topbar">
        <div>
          <h1><?= e($title ?? 'Central') ?></h1>
          <p class="muted">Administração unificada dos seus SaaS.</p>
        </div>
        <form method="post" action="/logout">
          <?= csrf_field() ?>
          <button type="submit">Sair</button>
        </form>
      </header>
      <?= $content ?>
    </main>
  </div>
  <script src="/assets/js/app.js"></script>
</body>
</html>
