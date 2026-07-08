create table admin_users (
  id bigint unsigned auto_increment primary key,
  name varchar(120) not null,
  email varchar(160) not null unique,
  password_hash varchar(255) not null,
  role varchar(40) not null default 'admin',
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp,
  updated_at timestamp null
) engine=InnoDB default charset=utf8mb4;

create table vendors (
  id bigint unsigned auto_increment primary key,
  name varchar(140) not null,
  email varchar(160) null,
  phone varchar(40) null,
  commission_default_percent decimal(8,2) not null default 0,
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp
) engine=InnoDB default charset=utf8mb4;

create table saas_systems (
  id bigint unsigned auto_increment primary key,
  name varchar(140) not null,
  slug varchar(80) not null unique,
  base_url varchar(255) null,
  database_type varchar(40) not null default 'mariadb',
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp
) engine=InnoDB default charset=utf8mb4;

create table plans (
  id bigint unsigned auto_increment primary key,
  system_id bigint unsigned not null,
  name varchar(120) not null,
  billing_cycle varchar(30) not null,
  price decimal(12,2) not null default 0,
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp,
  foreign key (system_id) references saas_systems(id)
) engine=InnoDB default charset=utf8mb4;

create table sales (
  id bigint unsigned auto_increment primary key,
  vendor_id bigint unsigned not null,
  system_id bigint unsigned not null,
  plan_id bigint unsigned null,
  customer_name varchar(180) not null,
  customer_email varchar(180) null,
  amount decimal(12,2) not null,
  commission_percent decimal(8,2) not null default 0,
  commission_amount decimal(12,2) not null default 0,
  recurring tinyint(1) not null default 0,
  status varchar(30) not null default 'pending',
  sold_at date not null,
  created_at timestamp default current_timestamp,
  foreign key (vendor_id) references vendors(id),
  foreign key (system_id) references saas_systems(id),
  foreign key (plan_id) references plans(id)
) engine=InnoDB default charset=utf8mb4;

create table financial_entries (
  id bigint unsigned auto_increment primary key,
  description varchar(180) not null,
  category varchar(80) null,
  direction varchar(20) not null,
  amount decimal(12,2) not null,
  due_date date not null,
  paid_at date null,
  status varchar(30) not null default 'pending',
  payment_method varchar(80) null,
  notes text null,
  created_at timestamp default current_timestamp,
  index financial_entries_due_status_idx (due_date, status)
) engine=InnoDB default charset=utf8mb4;

create table support_tickets (
  id bigint unsigned auto_increment primary key,
  system_id bigint unsigned not null,
  external_id varchar(120) null,
  customer_name varchar(180) null,
  customer_email varchar(180) null,
  subject varchar(220) not null,
  priority varchar(30) not null default 'normal',
  status varchar(40) not null default 'open',
  opened_at datetime not null,
  closed_at datetime null,
  created_at timestamp default current_timestamp,
  foreign key (system_id) references saas_systems(id)
) engine=InnoDB default charset=utf8mb4;

create table integrations (
  id bigint unsigned auto_increment primary key,
  system_id bigint unsigned null,
  name varchar(140) not null,
  type varchar(40) not null,
  endpoint_url varchar(500) null,
  status varchar(30) not null default 'pending',
  last_sync_at datetime null,
  last_error text null,
  created_at timestamp default current_timestamp,
  foreign key (system_id) references saas_systems(id)
) engine=InnoDB default charset=utf8mb4;

create table metric_snapshots (
  id bigint unsigned auto_increment primary key,
  system_id bigint unsigned not null,
  snapshot_date date not null,
  accounts_total int not null default 0,
  new_accounts int not null default 0,
  active_users int not null default 0,
  online_users int not null default 0,
  pending_payments int not null default 0,
  paid_payments int not null default 0,
  created_at timestamp default current_timestamp,
  unique key metric_snapshots_system_date_unique (system_id, snapshot_date),
  foreign key (system_id) references saas_systems(id)
) engine=InnoDB default charset=utf8mb4;

create table audit_logs (
  id bigint unsigned auto_increment primary key,
  admin_user_id bigint unsigned null,
  action varchar(120) not null,
  entity varchar(80) null,
  entity_id varchar(80) null,
  ip_address varchar(80) null,
  created_at timestamp default current_timestamp
) engine=InnoDB default charset=utf8mb4;
