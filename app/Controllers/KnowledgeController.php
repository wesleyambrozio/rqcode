<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\KnowledgeDocument;

final class KnowledgeController extends Controller
{
    public function index(): void
    {
        $db = Database::connection();
        $systemId = (int) $this->input('system_id', 0);
        $status = trim((string) $this->input('status', ''));
        $where = [];
        $params = [];
        if ($systemId > 0) { $where[] = 'd.system_id = :system_id'; $params['system_id'] = $systemId; }
        if ($status !== '') { $where[] = 'd.status = :status'; $params['status'] = $status; }
        $sql = 'select d.*, s.name system_name from knowledge_documents d join saas_systems s on s.id = d.system_id';
        if ($where) $sql .= ' where ' . implode(' and ', $where);
        $sql .= ' order by d.updated_at desc, d.created_at desc';
        $statement = $db->prepare($sql);
        $statement->execute($params);
        $documents = $statement->fetchAll();
        $systems = $db->query('select id, name from saas_systems where active = 1 order by name')->fetchAll();
        $this->view('knowledge/index', compact('documents', 'systems', 'systemId', 'status') + ['title' => 'Base de conhecimento']);
    }

    public function store(): void
    {
        $title = trim((string) $this->input('title'));
        $content = trim((string) $this->input('content'));
        if ($title === '' || $content === '') redirect('/conhecimento');
        $slug = trim((string) $this->input('slug')) ?: $this->slug($title);
        (new KnowledgeDocument())->create([
            'system_id' => (int) $this->input('system_id'), 'title' => $title, 'slug' => $slug,
            'summary' => trim((string) $this->input('summary')), 'content' => $content,
            'document_type' => $this->input('document_type', 'guide'), 'audience' => $this->input('audience', 'support'),
            'status' => $this->input('status', 'draft'), 'language' => $this->input('language', 'pt-BR'),
            'tags' => trim((string) $this->input('tags')), 'source_path' => trim((string) $this->input('source_path')),
            'token_estimate' => (int) ceil(mb_strlen($content) / 4), 'created_by' => Auth::user()['id'] ?? null,
            'reviewed_at' => $this->input('status') === 'published' ? date('Y-m-d H:i:s') : null,
        ]);
        redirect('/conhecimento');
    }

    public function update(): void
    {
        $db = Database::connection();
        $statement = $db->prepare('update knowledge_documents set status = :status, version = version + 1, reviewed_at = :reviewed_at, updated_at = current_timestamp where id = :id');
        $status = (string) $this->input('status', 'draft');
        $statement->execute(['status' => $status, 'reviewed_at' => $status === 'published' ? date('Y-m-d H:i:s') : null, 'id' => (int) $this->input('id')]);
        redirect('/conhecimento');
    }

    private function slug(string $value): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        return trim(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii) ?? ''), '-');
    }
}
