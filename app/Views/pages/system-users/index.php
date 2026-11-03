<?php
$sortLink = static function (string $key) use ($systemFilter, $sort, $direction): string {
    $next = $sort === $key ? ($direction === 'asc' ? 'desc' : 'asc') : ($key === 'name' ? 'asc' : 'desc');
    return '/usuarios-sistemas?' . http_build_query(['system_id' => $systemFilter, 'sort' => $key, 'direction' => $next]);
};
$ariaSort = static fn (string $key): string => $sort !== $key ? 'none' : ($direction === 'asc' ? 'ascending' : 'descending');
$sortMark = static fn (string $key): string => $sort !== $key ? '↕' : ($direction === 'asc' ? '↑' : '↓');
?>
<section class="card">
    <div class="card-heading"><div><span class="section-kicker">DIRETÓRIO CENTRAL</span><h2>Usuários dos produtos</h2></div><span class="badge"><?= count($users) ?> vínculo(s) de usuário</span></div>
    <form method="get" action="/usuarios-sistemas" class="filter-row">
        <label>Plataforma <select name="system_id"><option value="0">Todas as plataformas</option><?php foreach ($systems as $system): ?><option value="<?= e($system['id']) ?>" <?= $systemFilter === (int) $system['id'] ? 'selected' : '' ?>><?= e($system['name']) ?></option><?php endforeach; ?></select></label>
        <label>Ordenar por <select name="sort"><?php foreach ($sorts as $key => $label): ?><option value="<?= e($key) ?>" <?= $sort === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <label>Direção <select name="direction"><option value="asc" <?= $direction === 'asc' ? 'selected' : '' ?>>Crescente</option><option value="desc" <?= $direction === 'desc' ? 'selected' : '' ?>>Decrescente</option></select></label>
        <button>Aplicar</button>
    </form>
    <p class="muted">Todos os vínculos sincronizados, incluindo contas inativas. Um usuário pode pertencer a mais de uma conta. Último acesso corresponde ao último login registrado.</p>
    <p class="muted">Última sincronização deste diretório: <?= e($lastSync ?: 'Ainda não realizada') ?>.</p>
</section>
<section class="card users-list"><div class="table-wrap"><table><thead><tr>
    <th>Plataforma</th><th>Conta / empresa</th>
    <th aria-sort="<?= $ariaSort('name') ?>"><a href="<?= e($sortLink('name')) ?>" aria-label="Ordenar por nome">Usuário <span aria-hidden="true"><?= $sortMark('name') ?></span></a></th>
    <th aria-sort="<?= $ariaSort('registered_at') ?>"><a href="<?= e($sortLink('registered_at')) ?>" aria-label="Ordenar por data de inclusão">Inclusão <span aria-hidden="true"><?= $sortMark('registered_at') ?></span></a></th>
    <th aria-sort="<?= $ariaSort('last_seen_at') ?>"><a href="<?= e($sortLink('last_seen_at')) ?>" aria-label="Ordenar por último acesso">Último acesso <span aria-hidden="true"><?= $sortMark('last_seen_at') ?></span></a></th>
    <th>Indicações</th><th>Papel</th><th>Status</th>
</tr></thead><tbody>
<?php foreach ($users as $user): ?>
    <tr><td><?= e($user['system_name']) ?></td><td><?= e($user['tenant_name'] ?: '—') ?></td><td><strong><?= e($user['name']) ?></strong><br><span class="muted"><?= e($user['email']) ?></span></td><td><?= e($user['registered_at'] ?: $user['created_at']) ?></td><td><?= e($user['last_seen_at'] ?: 'Ainda não registrado') ?></td><td><?= (int) ($user['referral_count'] ?? 0) ?></td><td><?= e($user['role'] ?: '—') ?></td><td><form method="post" action="/usuarios-sistemas/atualizar" class="status-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($user['id']) ?>"><select name="status"><?php foreach (['active','invited','suspended','inactive'] as $value): ?><option <?= $user['status'] === $value ? 'selected' : '' ?>><?= $value ?></option><?php endforeach; ?></select><button>Alterar</button></form></td></tr>
<?php endforeach; ?>
<?php if (!$users): ?><tr><td colspan="8" class="empty-state">Nenhum usuário encontrado para esta plataforma.</td></tr><?php endif; ?>
</tbody></table></div></section>
