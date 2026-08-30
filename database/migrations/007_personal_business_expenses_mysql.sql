alter table fiscal_invoices add column recipient_document varchar(20) null after issuer_document;
alter table fiscal_invoices add column expense_origin varchar(30) not null default 'company' after recipient_document;
alter table financial_entries add column fiscal_invoice_id bigint unsigned null after recurrence_id;
alter table financial_entries add column expense_origin varchar(30) not null default 'company' after fiscal_invoice_id;
alter table financial_entries add constraint financial_entries_fiscal_invoice_fk foreign key (fiscal_invoice_id) references fiscal_invoices(id);
create index financial_entries_expense_origin_idx on financial_entries(expense_origin);
insert ignore into chart_of_accounts (code,name,direction,group_name) values
('2.12','Despesas operacionais pagas pelo titular','payable','Titular e reembolsos');
