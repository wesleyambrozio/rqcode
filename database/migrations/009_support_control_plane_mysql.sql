alter table support_tickets add column description text null;
alter table support_tickets add column category varchar(60) null;
alter table support_tickets add column assigned_admin_user_id bigint unsigned null;
alter table support_tickets add column first_response_due_at datetime null;
alter table support_tickets add column resolution_due_at datetime null;
alter table support_tickets add column updated_at datetime null;

create table knowledge_documents (
  id bigint unsigned auto_increment primary key,
  system_id bigint unsigned not null,
  title varchar(220) not null,
  slug varchar(180) not null,
  summary text null,
  content longtext not null,
  document_type varchar(40) not null default 'guide',
  audience varchar(40) not null default 'support',
  status varchar(30) not null default 'draft',
  language varchar(12) not null default 'pt-BR',
  tags varchar(500) null,
  source_path varchar(500) null,
  version integer not null default 1,
  token_estimate integer not null default 0,
  created_by bigint unsigned null,
  reviewed_at datetime null,
  created_at datetime default current_timestamp,
  updated_at datetime null,
  unique key knowledge_documents_system_slug_unique (system_id, slug),
  key knowledge_documents_retrieval_idx (system_id, status, audience, document_type),
  constraint knowledge_documents_system_fk foreign key (system_id) references saas_systems(id),
  constraint knowledge_documents_author_fk foreign key (created_by) references admin_users(id)
);

create table system_users (
  id bigint unsigned auto_increment primary key,
  system_id bigint unsigned not null,
  external_id varchar(120) null,
  tenant_external_id varchar(120) null,
  tenant_name varchar(180) null,
  name varchar(180) not null,
  email varchar(180) null,
  role varchar(80) null,
  status varchar(30) not null default 'active',
  last_seen_at datetime null,
  synced_at datetime null,
  created_at datetime default current_timestamp,
  updated_at datetime null,
  unique key system_users_external_unique (system_id, external_id),
  key system_users_lookup_idx (system_id, status, email),
  constraint system_users_system_fk foreign key (system_id) references saas_systems(id)
);

create table support_ticket_messages (
  id bigint unsigned auto_increment primary key,
  ticket_id bigint unsigned not null,
  author_type varchar(30) not null default 'admin',
  author_name varchar(180) null,
  body text not null,
  internal_note tinyint(1) not null default 0,
  created_at datetime default current_timestamp,
  key support_ticket_messages_ticket_idx (ticket_id, created_at),
  constraint support_ticket_messages_ticket_fk foreign key (ticket_id) references support_tickets(id)
);
