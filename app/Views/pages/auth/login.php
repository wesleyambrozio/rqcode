<section class="login-box">
  <img class="login-brand" src="/assets/images/rqcode/logo-principal-claro.png" alt="RQCode">
  <p class="muted">Acesse a central administrativa.</p>
  <?php if (!empty($_SESSION['flash'])): ?>
    <div class="alert"><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
  <?php endif; ?>
  <form method="post" action="/login" class="grid">
    <?= csrf_field() ?>
    <label>E-mail <input type="email" name="email" required autofocus></label>
    <label>Senha <input type="password" name="password" required></label>
    <button type="submit">Entrar</button>
  </form>
</section>
