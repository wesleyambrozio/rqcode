alter table plans
  modify price decimal(12,2) null default 0,
  add external_id varchar(36) null,
  add slug varchar(120) null,
  add description text null,
  add features_json text null,
  add limits_json text null,
  add sort_order int not null default 0,
  add revision bigint not null default 1,
  add applied_revision bigint not null default 0,
  add sync_error varchar(250) null,
  add applied_at datetime null,
  add unique key plans_product_external(system_id,external_id),
  add unique key plans_product_slug(system_id,slug);

create table product_admin_state (
  system_id bigint unsigned primary key,
  enabled boolean not null default false,
  last_sync_at datetime null,
  summary_json text null,
  foreign key (system_id) references saas_systems(id)
) engine=InnoDB default charset=utf8mb4;

create table product_accounts (
  id bigint unsigned auto_increment primary key,
  system_id bigint unsigned not null,
  external_id varchar(36) not null,
  name varchar(180) not null,
  slug varchar(180) not null,
  profile varchar(30) not null,
  plan_external_id varchar(36) null,
  active boolean not null,
  revision bigint not null default 1,
  observed_revision bigint not null default 0,
  observed_plan_external_id varchar(36) null,
  observed_active boolean not null,
  usage_json text null,
  sync_error varchar(250) null,
  last_seen_at datetime not null,
  applied_at datetime null,
  unique key accounts_product_external(system_id,external_id),
  foreign key (system_id) references saas_systems(id)
) engine=InnoDB default charset=utf8mb4;

create table product_admin_audit (
  id bigint unsigned auto_increment primary key,
  system_id bigint unsigned not null,
  actor_id bigint unsigned null,
  entity_type varchar(30) not null,
  external_id varchar(36) not null,
  reason varchar(500) not null,
  before_json text null,
  after_json text not null,
  created_at datetime not null default current_timestamp,
  index admin_audit_product(system_id,id),
  foreign key (system_id) references saas_systems(id)
) engine=InnoDB default charset=utf8mb4;
