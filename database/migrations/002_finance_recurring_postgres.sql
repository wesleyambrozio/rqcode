create table if not exists chart_of_accounts (
  id bigserial primary key, code varchar(20) not null unique, name varchar(120) not null,
  direction varchar(20) not null, group_name varchar(80) not null, active boolean not null default true,
  created_at timestamp default current_timestamp
);
create table if not exists payment_methods (
  id bigserial primary key, name varchar(100) not null unique, method_type varchar(40) not null default 'other',
  active boolean not null default true, created_at timestamp default current_timestamp
);
create table if not exists financial_recurring_rules (
  id bigserial primary key, description varchar(180) not null, account_id bigint not null references chart_of_accounts(id),
  payment_method_id bigint null references payment_methods(id), direction varchar(20) not null, amount numeric(12,2) not null,
  frequency varchar(20) not null, start_date date not null, end_date date null, installments int not null default 12,
  generated_count int not null default 0, active boolean not null default true, notes text null,
  created_at timestamp default current_timestamp
);
alter table financial_entries add column if not exists account_id bigint null;
alter table financial_entries add column if not exists payment_method_id bigint null;
alter table financial_entries add column if not exists recurrence_id bigint null;
alter table financial_entries add column if not exists installment_number int null;
alter table financial_entries add column if not exists total_installments int null;

insert into chart_of_accounts (code, name, direction, group_name) values
('1.01','Mensalidades e assinaturas SaaS','receivable','Receitas operacionais'),('1.02','Desenvolvimento de sistemas','receivable','Receitas operacionais'),
('1.03','Implantação e treinamento','receivable','Receitas de serviços'),('1.04','Suporte e manutenção','receivable','Receitas de serviços'),
('1.05','Venda de produtos e impressão 3D','receivable','Outras receitas'),('2.01','VPS, hospedagem e infraestrutura','payable','Tecnologia e infraestrutura'),
('2.02','Domínios, licenças e software','payable','Tecnologia e infraestrutura'),('2.03','Contabilidade e serviços profissionais','payable','Administrativo'),
('2.04','Comissões sobre vendas','payable','Comercial'),('2.05','Energia elétrica','payable','Ocupação e utilidades'),
('2.06','Internet e telefonia','payable','Ocupação e utilidades'),('2.07','Insumos para impressão 3D','payable','Produção 3D'),
('2.08','Manutenção e peças de impressoras 3D','payable','Produção 3D'),('2.09','Marketing e publicidade','payable','Comercial'),
('2.10','Impostos, tarifas e taxas','payable','Tributos e financeiro'),('2.11','Materiais de escritório','payable','Administrativo')
on conflict (code) do nothing;
insert into payment_methods (name, method_type) values
('PIX','instant'),('Boleto bancário','bank_slip'),('Cartão de crédito','credit_card'),('Cartão de débito','debit_card'),
('Transferência bancária','bank_transfer'),('Débito automático','direct_debit'),('Dinheiro','cash'),('Asaas','gateway')
on conflict (name) do nothing;
