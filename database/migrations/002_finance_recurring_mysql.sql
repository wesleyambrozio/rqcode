create table if not exists chart_of_accounts (
  id bigint unsigned auto_increment primary key,
  code varchar(20) not null unique,
  name varchar(120) not null,
  direction varchar(20) not null,
  group_name varchar(80) not null,
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp
) engine=InnoDB default charset=utf8mb4;

create table if not exists payment_methods (
  id bigint unsigned auto_increment primary key,
  name varchar(100) not null unique,
  method_type varchar(40) not null default 'other',
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp
) engine=InnoDB default charset=utf8mb4;

create table if not exists financial_recurring_rules (
  id bigint unsigned auto_increment primary key,
  description varchar(180) not null,
  account_id bigint unsigned not null,
  payment_method_id bigint unsigned null,
  direction varchar(20) not null,
  amount decimal(12,2) not null,
  frequency varchar(20) not null,
  start_date date not null,
  end_date date null,
  installments int not null default 12,
  generated_count int not null default 0,
  active tinyint(1) not null default 1,
  notes text null,
  created_at timestamp default current_timestamp,
  foreign key (account_id) references chart_of_accounts(id),
  foreign key (payment_method_id) references payment_methods(id)
) engine=InnoDB default charset=utf8mb4;

alter table financial_entries add column account_id bigint unsigned null after category;
alter table financial_entries add column payment_method_id bigint unsigned null after payment_method;
alter table financial_entries add column recurrence_id bigint unsigned null after payment_method_id;
alter table financial_entries add column installment_number int null after recurrence_id;
alter table financial_entries add column total_installments int null after installment_number;

insert ignore into chart_of_accounts (code, name, direction, group_name) values
('1.01', 'Mensalidades e assinaturas SaaS', 'receivable', 'Receitas operacionais'),
('1.02', 'Desenvolvimento de sistemas', 'receivable', 'Receitas operacionais'),
('1.03', 'Implantação e treinamento', 'receivable', 'Receitas de serviços'),
('1.04', 'Suporte e manutenção', 'receivable', 'Receitas de serviços'),
('1.05', 'Venda de produtos e impressão 3D', 'receivable', 'Outras receitas'),
('2.01', 'VPS, hospedagem e infraestrutura', 'payable', 'Tecnologia e infraestrutura'),
('2.02', 'Domínios, licenças e software', 'payable', 'Tecnologia e infraestrutura'),
('2.03', 'Contabilidade e serviços profissionais', 'payable', 'Administrativo'),
('2.04', 'Comissões sobre vendas', 'payable', 'Comercial'),
('2.05', 'Energia elétrica', 'payable', 'Ocupação e utilidades'),
('2.06', 'Internet e telefonia', 'payable', 'Ocupação e utilidades'),
('2.07', 'Insumos para impressão 3D', 'payable', 'Produção 3D'),
('2.08', 'Manutenção e peças de impressoras 3D', 'payable', 'Produção 3D'),
('2.09', 'Marketing e publicidade', 'payable', 'Comercial'),
('2.10', 'Impostos, tarifas e taxas', 'payable', 'Tributos e financeiro'),
('2.11', 'Materiais de escritório', 'payable', 'Administrativo');

insert ignore into payment_methods (name, method_type) values
('PIX', 'instant'),
('Boleto bancário', 'bank_slip'),
('Cartão de crédito', 'credit_card'),
('Cartão de débito', 'debit_card'),
('Transferência bancária', 'bank_transfer'),
('Débito automático', 'direct_debit'),
('Dinheiro', 'cash'),
('Asaas', 'gateway');
