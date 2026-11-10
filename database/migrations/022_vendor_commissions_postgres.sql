alter table vendors
  add code varchar(40) null unique,
  add is_default boolean not null default false,
  add commission_type varchar(20) not null default 'lifetime',
  add commission_installments int null;

create table vendor_assignments (
  id bigserial primary key,
  system_id bigint not null references saas_systems(id),
  tenant_external_id varchar(36) not null,
  vendor_id bigint not null references vendors(id),
  commission_percent numeric(8,2) not null,
  commission_type varchar(20) not null,
  installments_limit int null,
  source varchar(20) not null,
  starts_at timestamp not null default current_timestamp,
  ends_at timestamp null,
  assigned_by bigint null,
  reason varchar(500) null
);
create index vendor_assignments_account on vendor_assignments(system_id,tenant_external_id,ends_at);

create table commission_entries (
  id bigserial primary key,
  assignment_id bigint not null references vendor_assignments(id),
  vendor_id bigint not null references vendors(id),
  system_id bigint not null,
  tenant_external_id varchar(36) not null,
  billing_payment_id bigint not null,
  kind varchar(20) not null,
  installment_number int not null,
  base_amount numeric(12,2) not null,
  commission_percent numeric(8,2) not null,
  amount numeric(12,2) not null,
  financial_entry_id bigint null,
  created_at timestamp not null default current_timestamp,
  unique(billing_payment_id,kind)
);
create index commission_vendor on commission_entries(vendor_id,created_at);
