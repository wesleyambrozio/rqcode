create table if not exists billing_customers (
 id bigserial primary key,
 system_id bigint not null,
 tenant_external_id varchar(36) not null,
 environment varchar(20) not null,
 external_reference varchar(100) not null unique,
 document_hash varchar(64) not null,
 document_mask varchar(24) not null,
 customer_id varchar(100) null,
 created_at timestamp default current_timestamp,
 unique (system_id,tenant_external_id,environment)
);
create table if not exists billing_subscriptions (
 id bigserial primary key,
 system_id bigint not null,
 tenant_external_id varchar(36) not null,
 environment varchar(20) not null,
 plan_external_id varchar(36) not null,
 plan_name varchar(120) not null,
 amount decimal(12,2) not null,
 billing_type varchar(30) not null,
 external_reference varchar(100) not null unique,
 subscription_id varchar(100) null,
 subscription_attempted boolean not null default false,
 customer_id varchar(100) null,
 gateway_status varchar(30) not null default 'CREATING',
 cancel_requested boolean not null default false,
 cancelled_at timestamp null,
 service_until date null,
 first_due_date date not null,
 accepted_by varchar(36) not null,
 terms_version varchar(40) not null,
 accepted_at timestamp default current_timestamp,
 created_at timestamp default current_timestamp,
 updated_at timestamp default current_timestamp,
 unique (environment,subscription_id)
);
create table if not exists billing_payments (
 id bigserial primary key,
 subscription_local_id bigint not null,
 environment varchar(20) not null,
 payment_id varchar(100) not null,
 billing_type varchar(30) not null default 'BOLETO',
 status varchar(50) not null,
 amount decimal(12,2) not null,
 due_date date not null,
 paid_at date null,
 invoice_url text null,
 entry_id bigint null,
 updated_at timestamp default current_timestamp,
 unique (environment,payment_id)
);
create table if not exists billing_events (
 environment varchar(20) not null,
 event_id varchar(190) not null,
 event_type varchar(100) not null,
 subscription_local_id bigint not null,
 processed_at timestamp default current_timestamp,
 primary key (environment,event_id)
);

create index if not exists billing_subscriptions_tenant on billing_subscriptions(system_id,tenant_external_id,environment,id);
