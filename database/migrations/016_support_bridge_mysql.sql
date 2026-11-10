alter table support_tickets add column tenant_external_id varchar(120) null;
alter table support_tickets add column requester_external_id varchar(120) null;
alter table support_tickets add column context_json text null;
alter table support_tickets add column bridge_key varchar(64) null;
alter table support_tickets add column first_responded_at datetime null;
create unique index support_bridge_key on support_tickets(system_id,bridge_key);
create table support_api_receipts (
  system_id bigint unsigned not null, event_id varchar(80) not null,
  payload_hash char(64) not null, response_json longtext not null,
  created_at datetime not null default current_timestamp,
  primary key(system_id,event_id)
) engine=InnoDB;
create table support_api_nonces (
  system_id bigint unsigned not null, nonce varchar(80) not null,
  created_at datetime not null default current_timestamp,
  primary key(system_id,nonce), index support_nonce_time(created_at)
) engine=InnoDB;
create table support_audit (
  id bigint unsigned auto_increment primary key, system_id bigint unsigned null,
  admin_user_id bigint unsigned null, action varchar(80) not null,
  entity_id varchar(120) null, created_at datetime not null default current_timestamp
) engine=InnoDB;
alter table knowledge_documents add column metadata_json text null;
create table knowledge_document_versions (
  id bigint unsigned auto_increment primary key, document_id bigint unsigned not null,
  version integer not null, snapshot_json longtext not null,
  created_at datetime not null default current_timestamp,
  unique key knowledge_version(document_id,version)
) engine=InnoDB;
create table support_ticket_attachments (
  id bigint unsigned auto_increment primary key, ticket_id bigint unsigned not null,
  mime varchar(40) not null, data mediumtext not null,
  created_at datetime not null default current_timestamp,
  foreign key(ticket_id) references support_tickets(id) on delete cascade
) engine=InnoDB;
