alter table support_tickets add column description text null;
alter table support_tickets add column category varchar(60) null;
alter table support_tickets add column assigned_admin_user_id bigint null;
alter table support_tickets add column first_response_due_at timestamp null;
alter table support_tickets add column resolution_due_at timestamp null;
alter table support_tickets add column updated_at timestamp null;

create table knowledge_documents (
  id bigserial primary key,
  system_id bigint not null references saas_systems(id),
  title varchar(220) not null,
  slug varchar(180) not null,
  summary text null,
  content text not null,
  document_type varchar(40) not null default 'guide',
  audience varchar(40) not null default 'support',
  status varchar(30) not null default 'draft',
  language varchar(12) not null default 'pt-BR',
  tags varchar(500) null,
  source_path varchar(500) null,
  version integer not null default 1,
  token_estimate integer not null default 0,
  created_by bigint null references admin_users(id),
  reviewed_at timestamp null,
  created_at timestamp default current_timestamp,
  updated_at timestamp null,
  unique (system_id, slug)
);
create index knowledge_documents_retrieval_idx on knowledge_documents (system_id, status, audience, document_type);

create table system_users (
  id bigserial primary key,
  system_id bigint not null references saas_systems(id),
  external_id varchar(120) null,
  tenant_external_id varchar(120) null,
  tenant_name varchar(180) null,
  name varchar(180) not null,
  email varchar(180) null,
  role varchar(80) null,
  status varchar(30) not null default 'active',
  last_seen_at timestamp null,
  synced_at timestamp null,
  created_at timestamp default current_timestamp,
  updated_at timestamp null,
  unique (system_id, external_id)
);
create index system_users_lookup_idx on system_users (system_id, status, email);

create table support_ticket_messages (
  id bigserial primary key,
  ticket_id bigint not null references support_tickets(id),
  author_type varchar(30) not null default 'admin',
  author_name varchar(180) null,
  body text not null,
  internal_note boolean not null default false,
  created_at timestamp default current_timestamp
);
create index support_ticket_messages_ticket_idx on support_ticket_messages (ticket_id, created_at);
