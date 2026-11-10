alter table vendors
  add code varchar(40) null,
  add is_default boolean not null default false,
  add commission_type varchar(20) not null default 'lifetime',
  add commission_installments int null,
  add unique key vendors_code(code);

create table vendor_assignments (
  id bigint unsigned auto_increment primary key,
  system_id bigint unsigned not null,
  tenant_external_id varchar(36) not null,
  vendor_id bigint unsigned not null,
  commission_percent decimal(8,2) not null,
  commission_type varchar(20) not null,
  installments_limit int null,
  source varchar(20) not null,
  starts_at datetime not null default current_timestamp,
  ends_at datetime null,
  assigned_by bigint unsigned null,
  reason varchar(500) null,
  key vendor_assignments_account(system_id,tenant_external_id,ends_at),
  foreign key (system_id) references saas_systems(id),
  foreign key (vendor_id) references vendors(id)
) engine=InnoDB default charset=utf8mb4;

create table commission_entries (
  id bigint unsigned auto_increment primary key,
  assignment_id bigint unsigned not null,
  vendor_id bigint unsigned not null,
  system_id bigint unsigned not null,
  tenant_external_id varchar(36) not null,
  billing_payment_id bigint unsigned not null,
  kind varchar(20) not null,
  installment_number int not null,
  base_amount decimal(12,2) not null,
  commission_percent decimal(8,2) not null,
  amount decimal(12,2) not null,
  financial_entry_id bigint unsigned null,
  created_at datetime not null default current_timestamp,
  unique key commission_payment_kind(billing_payment_id,kind),
  key commission_vendor(vendor_id,created_at),
  foreign key (assignment_id) references vendor_assignments(id),
  foreign key (vendor_id) references vendors(id)
) engine=InnoDB default charset=utf8mb4
