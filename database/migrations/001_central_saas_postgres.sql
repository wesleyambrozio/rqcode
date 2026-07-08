create table admin_users (
  id bigserial primary key,
  name varchar(120) not null,
  email varchar(160) not null unique,
  password_hash varchar(255) not null,
  role varchar(40) not null default 'admin',
  active boolean not null default true,
  created_at timestamp default current_timestamp,
  updated_at timestamp null
);

create table vendors (
  id bigserial primary key,
  name varchar(140) not null,
  email varchar(160) null,
  phone varchar(40) null,
  commission_default_percent numeric(8,2) not null default 0,
  active boolean not null default true,
  created_at timestamp default current_timestamp
);

create table saas_systems (
  id bigserial primary key,
  name varchar(140) not null,
  slug varchar(80) not null unique,
  base_url varchar(255) null,
  database_type varchar(40) not null default 'supabase',
  active boolean not null default true,
  created_at timestamp default current_timestamp
);

create table plans (
  id bigserial primary key,
  system_id bigint not null references saas_systems(id),
  name varchar(120) not null,
  billing_cycle varchar(30) not null,
  price numeric(12,2) not null default 0,
  active boolean not null default true,
  created_at timestamp default current_timestamp
);

create table sales (
  id bigserial primary key,
  vendor_id bigint not null references vendors(id),
  system_id bigint not null references saas_systems(id),
  plan_id bigint null references plans(id),
  customer_name varchar(180) not null,
  customer_email varchar(180) null,
  amount numeric(12,2) not null,
  commission_percent numeric(8,2) not null default 0,
  commission_amount numeric(12,2) not null default 0,
  recurring boolean not null default false,
  status varchar(30) not null default 'pending',
  sold_at date not null,
  created_at timestamp default current_timestamp
);

create table financial_entries (
  id bigserial primary key,
  description varchar(180) not null,
  category varchar(80) null,
  direction varchar(20) not null,
  amount numeric(12,2) not null,
  due_date date not null,
  paid_at date null,
  status varchar(30) not null default 'pending',
  payment_method varchar(80) null,
  notes text null,
  created_at timestamp default current_timestamp
);

create index financial_entries_due_status_idx on financial_entries (due_date, status);

create table support_tickets (
  id bigserial primary key,
  system_id bigint not null references saas_systems(id),
  external_id varchar(120) null,
  customer_name varchar(180) null,
  customer_email varchar(180) null,
  subject varchar(220) not null,
  priority varchar(30) not null default 'normal',
  status varchar(40) not null default 'open',
  opened_at timestamp not null,
  closed_at timestamp null,
  created_at timestamp default current_timestamp
);

create table integrations (
  id bigserial primary key,
  system_id bigint null references saas_systems(id),
  name varchar(140) not null,
  type varchar(40) not null,
  endpoint_url varchar(500) null,
  status varchar(30) not null default 'pending',
  last_sync_at timestamp null,
  last_error text null,
  created_at timestamp default current_timestamp
);

create table metric_snapshots (
  id bigserial primary key,
  system_id bigint not null references saas_systems(id),
  snapshot_date date not null,
  accounts_total integer not null default 0,
  new_accounts integer not null default 0,
  active_users integer not null default 0,
  online_users integer not null default 0,
  pending_payments integer not null default 0,
  paid_payments integer not null default 0,
  created_at timestamp default current_timestamp,
  unique (system_id, snapshot_date)
);

create table audit_logs (
  id bigserial primary key,
  admin_user_id bigint null,
  action varchar(120) not null,
  entity varchar(80) null,
  entity_id varchar(80) null,
  ip_address varchar(80) null,
  created_at timestamp default current_timestamp
);
