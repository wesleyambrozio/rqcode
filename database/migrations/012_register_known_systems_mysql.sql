insert ignore into saas_systems(name,slug,database_type,active) values
('Fleetway','fleetway','mariadb',1),
('Checklist','checklist','mariadb',1),
('Confeitaria','confeitaria','mariadb',1),
('EVC Insight','evc-insight','mariadb',1),
('Venda Hoje','Venda Hoje','mariadb',1);
insert ignore into support_queues(system_id,name,slug,category) select id,'Atendimento geral','geral','general' from saas_systems;
insert ignore into ai_chat_configs(system_id,provider,active) select id,'disabled',0 from saas_systems;
