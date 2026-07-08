<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Models\FinancialEntry;

final class FinanceController extends Controller
{
    public function index(): void
    {
        $entries = (new FinancialEntry())->all('due_date asc');
        $summary = [
            'receivable' => $this->sum("direction = 'receivable' and status = 'pending'"),
            'payable' => $this->sum("direction = 'payable' and status = 'pending'"),
            'paid' => $this->sum("status = 'paid'"),
            'overdue' => $this->sum("status = 'pending' and due_date < '" . date('Y-m-d') . "'"),
        ];
        $this->view('finance/index', compact('entries', 'summary') + ['title' => 'Financeiro']);
    }

    public function store(): void
    {
        (new FinancialEntry())->create([
            'description' => $this->input('description'),
            'category' => $this->input('category'),
            'direction' => $this->input('direction'),
            'amount' => $this->input('amount'),
            'due_date' => $this->input('due_date'),
            'status' => $this->input('status', 'pending'),
            'payment_method' => $this->input('payment_method'),
            'notes' => $this->input('notes'),
        ]);
        redirect('/financeiro');
    }

    public function settle(): void
    {
        $statement = Database::connection()->prepare("update financial_entries set status = 'paid', paid_at = current_date where id = :id");
        $statement->execute(['id' => $this->input('id')]);
        redirect('/financeiro');
    }

    private function sum(string $where): float
    {
        return (float) Database::connection()->query("select coalesce(sum(amount),0) from financial_entries where {$where}")->fetchColumn();
    }
}
