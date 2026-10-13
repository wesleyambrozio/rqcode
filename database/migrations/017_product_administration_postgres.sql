alter table plans alter column price drop not null;
alter table plans
  add external_id varchar(36) null,
  add slug varchar(120) null,
  add description text null,
  add features_json text null,
  add limits_json text null,
  add sort_order int not null default 0,
  add revision bigint not null default 1,
  add applied_revision bigint not null default 0,
  add sync_error varchar(250) null,
  add applied_at timestamp null,
  add unique(system_id,external_id),
  add unique(system_id,slug);
create table product_admin_state (
  system_id bigint primary key references saas_systems(id), enabled boolean not null default false,
  last_sync_at timestamp null, summary_json text null
);
create table product_accounts (
  id bigserial primary key, system_id bigint not null references saas_systems(id),
  external_id varchar(36) not null, name varchar(180) not null, slug varchar(180) not null,
  profile varchar(30) not null, plan_external_id varchar(36) null, active boolean not null,
  revision bigint not null default 1, observed_revision bigint not null default 0,
  observed_plan_external_id varchar(36) null, observed_active boolean not null,
  usage_json text null, sync_error varchar(250) null, last_seen_at timestamp not null,
  applied_at timestamp null, unique(system_id,external_id)
);
create table product_admin_audit (
  id bigserial primary key, system_id bigint not null references saas_systems(id), actor_id bigint null,
  entity_type varchar(30) not null, external_id varchar(36) not null, reason varchar(500) not null,
  before_json text null, after_json text not null, created_at timestamp not null default current_timestamp
);
create index admin_audit_product on product_admin_audit(system_id,id);
