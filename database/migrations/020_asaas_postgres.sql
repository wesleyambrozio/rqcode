create table if not exists asaas_charges (
 id bigserial primary key,
 entry_id bigint not null,
 environment varchar(20) not null,
 external_reference varchar(100) not null unique,
 customer_id varchar(100) not null,
 billing_type varchar(30) not null,
 amount decimal(12,2) not null,
 due_date date not null,
 payment_id varchar(100) null,
 invoice_url text null,
 gateway_status varchar(50) not null default 'CREATING',
 created_by bigint not null,
 created_at timestamp default current_timestamp,
 updated_at timestamp default current_timestamp,
 unique (entry_id, environment),
 unique (environment, payment_id)
);
create table if not exists asaas_events (
 environment varchar(20) not null,
 event_id varchar(190) not null,
 event_type varchar(100) not null,
 charge_id bigint not null,
 processed_at timestamp default current_timestamp,
 primary key (environment, event_id)
);
