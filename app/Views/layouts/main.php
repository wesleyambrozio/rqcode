<?php
$current = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$navItems = [
  '/dashboard' => ['Dashboard', '⌂'],
  '/vendedores' => ['Vendedores', '◎'],
  '/sistemas' => ['Sistemas e planos', '▦'],
  '/vendas' => ['Vendas', '↗'],
  '/financeiro' => ['Financeiro', '$'],
  '/fornecedores' => ['Fornecedores', 'F'],
  '/notas-fiscais' => ['Notas fiscais', 'NF'],
  '/suporte' => ['Suporte', '?'],
  '/integracoes' => ['Integrações', '◇'],
  '/relatorios' => ['Relatórios', '≡'],
  '/contabilidade' => ['Contabilidade', 'DOC'],
  '/impressao-3d' => ['Producao 3D', '3D'],
  '/configuracoes' => ['Configurações', '⚙'],
  '/usuarios-administrativos' => ['Usuarios administrativos', 'ADM'],
];
$isAccountant = (\App\Core\Auth::user()['role'] ?? '') === 'accountant';
if ($isAccountant) $navItems = ['/contabilidade' => ['Portal contabil', 'DOC']];
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title><?= e($title ?? config('app.name')) ?></title>
  <script>document.documentElement.dataset.theme=localStorage.getItem('rqcode-theme')||((matchMedia('(prefers-color-scheme: dark)').matches)?'dark':'light');</script>
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar" id="app-sidebar">
      <a class="brand" href="/dashboard" aria-label="RQCode Dashboard">
        <span class="brand-mark">RQ</span>
        <span><strong>RQCODE</strong><small>COMMAND CENTER</small></span>
      </a>

      <div class="admin-profile">
        <div class="admin-avatar">WA</div>
        <div><strong><?= e(\App\Core\Auth::user()['name'] ?? 'Usuario') ?></strong><span><?= $isAccountant ? 'Acesso contabilidade' : 'Central RQCODE' ?></span></div>
        <span class="status-dot" title="Online"></span>
      </div>

      <p class="nav-caption">GESTÃO CENTRAL</p>
      <nav class="nav" aria-label="Navegação principal">
        <?php foreach ($navItems as $path => [$label, $icon]): ?>
          <a class="<?= $current === $path ? 'active' : '' ?>" href="<?= $path ?>">
            <span class="nav-icon" aria-hidden="true"><?= $icon ?></span><span><?= $label ?></span>
          </a>
        <?php endforeach; ?>
      </nav>

      <div class="sidebar-footer">
        <span class="system-status"><i></i> Sistemas operacionais</span>
        <small>RQCODE · <?= date('Y') ?></small>
      </div>
    </aside>

    <main class="main">
      <header class="topbar">
        <div class="topbar-title">
          <button class="icon-button menu-toggle" type="button" data-menu-toggle aria-label="Abrir menu">☰</button>
          <div>
            <p class="eyebrow">CENTRAL ADMINISTRATIVA</p>
            <h1><?= e($title ?? 'Central') ?></h1>
            <p class="muted">Administração unificada dos produtos e operações RQCODE.</p>
          </div>
        </div>
        <div class="topbar-actions">
          <span class="live-pill"><i></i> Ambiente local</span>
          <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Alternar tema" title="Alternar modo claro/escuro">
            <span class="theme-sun">☀</span><span class="theme-moon">☾</span>
          </button>
          <form method="post" action="/logout">
            <?= csrf_field() ?>
            <button class="logout-button" type="submit">Sair</button>
          </form>
        </div>
      </header>
      <div class="content-frame"><?= $content ?></div>
    </main>
  </div>
  <div class="sidebar-scrim" data-menu-toggle></div>
  <script src="/assets/js/app.js"></script>
</body>
</html>
