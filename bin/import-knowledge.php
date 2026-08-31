<?php

declare(strict_types=1);

use App\Core\Database;

require dirname(__DIR__) . '/vendor/autoload.php';

$db = Database::connection();
$roots = ['checklist' => dirname(__DIR__) . '/projetos/checklist/kb', 'fleetway' => dirname(__DIR__) . '/projetos/fleetway/kb'];
$systemQuery = $db->prepare('select id from saas_systems where slug = :slug limit 1');
$find = $db->prepare('select id, version, content from knowledge_documents where system_id = :system_id and slug = :slug limit 1');
$insert = $db->prepare('insert into knowledge_documents (system_id, title, slug, summary, content, document_type, audience, status, language, tags, source_path, version, token_estimate, reviewed_at) values (:system_id, :title, :slug, :summary, :content, :document_type, :audience, :status, :language, :tags, :source_path, 1, :token_estimate, current_timestamp)');
$update = $db->prepare('update knowledge_documents set title=:title, summary=:summary, content=:content, document_type=:document_type, audience=:audience, status=:status, language=:language, tags=:tags, source_path=:source_path, token_estimate=:token_estimate, version=version+1, reviewed_at=current_timestamp, updated_at=current_timestamp where id=:id');

foreach ($roots as $systemSlug => $root) {
    $systemQuery->execute(['slug' => $systemSlug]);
    $systemId = (int) $systemQuery->fetchColumn();
    if (!$systemId || !is_dir($root)) continue;
    foreach (glob($root . '/*.md') ?: [] as $file) {
        $content = trim((string) file_get_contents($file));
        preg_match('/^#\s+(.+)$/m', $content, $titleMatch);
        preg_match('/^Público:\s*(.+?)\s*$/mi', $content, $audienceMatch);
        preg_match('/^Tipo:\s*(.+?)\s*$/mi', $content, $typeMatch);
        preg_match('/^Tags:\s*(.+?)\s*$/mi', $content, $tagsMatch);
        $paragraphs = preg_split('/\R\s*\R/', $content) ?: [];
        $summary = '';
        foreach ($paragraphs as $paragraph) {
            if (!str_starts_with(trim($paragraph), '#') && !preg_match('/^(Público|Tipo|Tags):/iu', trim($paragraph))) { $summary = trim($paragraph); break; }
        }
        $slug = preg_replace('/^\d+-/', '', pathinfo($file, PATHINFO_FILENAME));
        $data = [
            'system_id' => $systemId, 'title' => trim($titleMatch[1] ?? $slug), 'slug' => $slug,
            'summary' => mb_substr($summary, 0, 900), 'content' => $content,
            'document_type' => trim($typeMatch[1] ?? 'guide'), 'audience' => trim(explode(',', $audienceMatch[1] ?? 'support')[0]),
            'status' => 'published', 'language' => 'pt-BR', 'tags' => trim($tagsMatch[1] ?? ''),
            'source_path' => str_replace('\\', '/', substr($file, strlen(dirname(__DIR__)) + 1)),
            'token_estimate' => (int) ceil(mb_strlen($content) / 4),
        ];
        $find->execute(['system_id' => $systemId, 'slug' => $slug]);
        $existing = $find->fetch();
        if ($existing && hash_equals((string) $existing['content'], $content)) { echo "Sem alteração: {$systemSlug}/{$slug}\n"; }
        elseif ($existing) {
            $updateData = $data;
            unset($updateData['system_id'], $updateData['slug']);
            $update->execute($updateData + ['id' => $existing['id']]);
            echo "Atualizado: {$systemSlug}/{$slug}\n";
        }
        else { $insert->execute($data); echo "Importado: {$systemSlug}/{$slug}\n"; }
    }
}
