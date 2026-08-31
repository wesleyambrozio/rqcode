create table if not exists support_queues (
  id bigint unsigned auto_increment primary key,
  system_id bigint unsigned not null,
  name varchar(120) not null,
  slug varchar(120) not null,
  category varchar(60) null,
  default_priority varchar(20) not null default 'normal',
  first_response_hours int not null default 8,
  resolution_hours int not null default 48,
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp,
  unique key support_queues_system_slug_unique(system_id,slug),
  foreign key(system_id) references saas_systems(id)
) engine=InnoDB default charset=utf8mb4;
alter table support_queues engine=InnoDB;
alter table support_tickets add column queue_id bigint unsigned null after system_id;
alter table support_tickets add constraint support_tickets_queue_fk foreign key(queue_id) references support_queues(id);
create index support_tickets_queue_status_idx on support_tickets(queue_id,status,priority);
create table if not exists ai_chat_configs (
  id bigint unsigned auto_increment primary key,
  system_id bigint unsigned not null unique,
  provider varchar(40) not null default 'disabled',
  model varchar(100) null,
  credential_env_key varchar(100) null,
  temperature decimal(4,2) not null default 0.20,
  max_output_tokens int not null default 800,
  min_confidence decimal(4,2) not null default 0.70,
  rag_enabled tinyint(1) not null default 1,
  fallback_queue_id bigint unsigned null,
  system_prompt text null,
  active tinyint(1) not null default 0,
  updated_by bigint unsigned null,
  created_at timestamp default current_timestamp,
  updated_at timestamp null,
  foreign key(system_id) references saas_systems(id),
  foreign key(fallback_queue_id) references support_queues(id),
  foreign key(updated_by) references admin_users(id)
) engine=InnoDB default charset=utf8mb4;
insert ignore into support_queues(system_id,name,slug,category) select id,'Atendimento geral','geral','general' from saas_systems;
insert ignore into ai_chat_configs(system_id,provider,active) select id,'disabled',0 from saas_systems;
