alter table fiscal_invoices add column if not exists recipient_document varchar(20) null;
alter table fiscal_invoices add column if not exists expense_origin varchar(30) not null default 'company';
alter table financial_entries add column if not exists fiscal_invoice_id bigint null references fiscal_invoices(id);
alter table financial_entries add column if not exists expense_origin varchar(30) not null default 'company';
create index if not exists financial_entries_expense_origin_idx on financial_entries(expense_origin);
insert into chart_of_accounts (code,name,direction,group_name) values
('2.12','Despesas operacionais pagas pelo titular','payable','Titular e reembolsos')
on conflict (code) do nothing;
