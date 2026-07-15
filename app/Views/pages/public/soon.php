<?php $flash = $_SESSION['site_flash'] ?? null; unset($_SESSION['site_flash']); ?>

<header class="site-header">
  <div class="site-header-inner">
    <a class="site-brand" href="/" aria-label="RQCode">
      <img src="/assets/images/rqcode/logomarcaParaCamisaPreta.png" alt="RQCode Sistemas e Servicos">
    </a>
    <nav class="site-nav" aria-label="Navegacao principal">
      <a href="#solucoes">Solucoes</a>
      <a href="#servicos">Servicos</a>
      <a href="#contato">Contato</a>
      <a class="nav-login" href="/login">Entrar</a>
    </nav>
  </div>
</header>

<main>
  <section class="hero-section">
    <div class="hero-copy">
      <p class="eyebrow">Sistemas, automacao e solucoes digitais</p>
      <h1>Tecnologia sob medida para empresas que querem operar melhor.</h1>
      <p class="hero-text">
        A RQCode cria plataformas proprias e sistemas personalizados para transformar processos manuais em rotinas mais simples, rastreaveis e eficientes.
      </p>
      <div class="hero-actions">
        <a class="btn primary" href="#contato">Solicitar contato</a>
        <a class="btn ghost" href="#solucoes">Ver solucoes</a>
      </div>
    </div>
    <div class="hero-visual" aria-label="Marca RQCode">
      <img src="/assets/images/sistemas/imageSiteRqcode.png" alt="RQCode Sistemas e Servicos">
    </div>
  </section>

  <section class="metrics-strip" aria-label="Diferenciais">
    <article>
      <strong>30 dias</strong>
      <span>MVPs e primeiras versoes com entrega objetiva.</span>
    </article>
    <article>
      <strong>24h</strong>
      <span>Acompanhamento claro das etapas do projeto.</span>
    </article>
    <article>
      <strong>100%</strong>
      <span>Solucoes pensadas para a operacao real.</span>
    </article>
  </section>

  <section id="solucoes" class="section">
    <div class="section-heading">
      <p class="eyebrow">Produtos</p>
      <h2>Solucoes em desenvolvimento e evolucao</h2>
      <p>Mostramos apenas o essencial. Cada plataforma nasce para resolver uma operacao especifica, sem excesso de complexidade.</p>
    </div>

    <div class="solution-grid">
      <?php
      $solutions = [
        ['Zap Checklist', 'Checklists digitais e controle operacional.', 'zapchecklistPerfil.png'],
        ['Fleet Way', 'Gestao de frotas, rotinas e manutencoes.', 'fleewayPerfil.png'],
        ['Gestor ECV', 'Organizacao para empresas de vistoria.', 'gestorecvPerfil.png'],
        ['Alert Mind', 'Alertas, lembretes e vencimentos.', 'alertMindPerfil.png'],
        ['Venda Hoje', 'Acompanhamento comercial e resultados.'],
        ['Bet Sardinha', 'Operacoes digitais e automacoes esportivas.', 'betSardinhaPerfil.png'],
      ];
      foreach ($solutions as $item):
        $name = $item[0];
        $description = $item[1];
        $perfil = $item[2] ?? null;
      ?>
        <article class="solution-card">
          <?php if ($perfil): ?>
            <img class="solution-card-img" src="/assets/images/sistemas/<?= e($perfil) ?>" alt="<?= e($name) ?>" loading="lazy">
          <?php else: ?>
            <span><?= e(substr($name, 0, 2)) ?></span>
          <?php endif; ?>
          <h3><?= e($name) ?></h3>
          <p><?= e($description) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section id="servicos" class="section split-section">
    <div>
      <p class="eyebrow">Como ajudamos</p>
      <h2>Do diagnostico ao sistema funcionando.</h2>
    </div>
    <div class="service-list">
      <article>
        <h3>Sistemas sob medida</h3>
        <p>Projetos pensados para a rotina real da empresa, com telas, fluxos e relatorios que fazem sentido no dia a dia.</p>
      </article>
      <article>
        <h3>Automacao de processos</h3>
        <p>Reducao de retrabalho, integracoes entre ferramentas e rotinas que deixam de depender de planilhas soltas.</p>
      </article>
      <article>
        <h3>Centrais administrativas</h3>
        <p>Dashboards, financeiro, suporte, usuarios, permissoes e dados importantes reunidos em um so lugar.</p>
      </article>
    </div>
  </section>

  <section id="contato" class="contact-section">
    <div class="contact-copy">
      <p class="eyebrow">Contato</p>
      <h2>Vamos conversar sobre o seu proximo sistema?</h2>
      <p>Conte brevemente o que voce precisa. A RQCode retorna com o melhor caminho para tirar a ideia do papel.</p>
      <a class="whatsapp-link" href="https://wa.me/5531984340474" target="_blank" rel="noopener">
        <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3.2A12.7 12.7 0 0 0 5 22.3L3.7 29l6.9-1.8A12.7 12.7 0 1 0 16 3.2Zm0 23.1c-1.9 0-3.8-.5-5.4-1.5l-.4-.2-4.1 1.1 1.1-4-.3-.4A10.4 10.4 0 1 1 16 26.3Zm5.8-7.8c-.3-.2-1.9-1-2.2-1.1-.3-.1-.5-.2-.8.2-.2.3-.9 1.1-1.1 1.3-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-1-1.6-2.1-1.8-2.4-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.3.3-.5.1-.2.1-.4 0-.6-.1-.2-.8-1.9-1-2.5-.3-.6-.5-.5-.8-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.2 3.3 1.4 3.5c.2.2 2.4 3.7 5.8 5.2.8.4 1.5.6 2 .7.8.3 1.6.2 2.2.1.7-.1 1.9-.8 2.2-1.5.3-.7.3-1.4.2-1.5-.1-.2-.4-.3-.7-.5Z"/></svg>
        31 98434-0474
      </a>
    </div>

    <form class="contact-form" action="/contato" method="post">
      <?= csrf_field() ?>
      <?php if ($flash): ?>
        <div class="form-alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
      <?php endif; ?>
      <label>Nome <input type="text" name="name" required></label>
      <label>E-mail <input type="email" name="email" required></label>
      <label>Telefone/WhatsApp <input type="tel" name="phone"></label>
      <label>Mensagem <textarea name="message" required></textarea></label>
      <button class="btn primary" type="submit">Enviar por e-mail</button>
    </form>
  </section>
</main>

<a class="whatsapp-float" href="https://wa.me/5531984340474" target="_blank" rel="noopener" aria-label="Chamar no WhatsApp">
  <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3.2A12.7 12.7 0 0 0 5 22.3L3.7 29l6.9-1.8A12.7 12.7 0 1 0 16 3.2Zm0 23.1c-1.9 0-3.8-.5-5.4-1.5l-.4-.2-4.1 1.1 1.1-4-.3-.4A10.4 10.4 0 1 1 16 26.3Zm5.8-7.8c-.3-.2-1.9-1-2.2-1.1-.3-.1-.5-.2-.8.2-.2.3-.9 1.1-1.1 1.3-.2.2-.4.2-.7.1-.3-.2-1.4-.5-2.6-1.6-1-1-1.6-2.1-1.8-2.4-.2-.3 0-.5.1-.7l.5-.6c.2-.2.2-.3.3-.5.1-.2.1-.4 0-.6-.1-.2-.8-1.9-1-2.5-.3-.6-.5-.5-.8-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9s1.2 3.3 1.4 3.5c.2.2 2.4 3.7 5.8 5.2.8.4 1.5.6 2 .7.8.3 1.6.2 2.2.1.7-.1 1.9-.8 2.2-1.5.3-.7.3-1.4.2-1.5-.1-.2-.4-.3-.7-.5Z"/></svg>
</a>

<footer class="site-footer">
  <span>RQCode Sistemas e Servicos</span>
  <span>2026</span>
</footer>
