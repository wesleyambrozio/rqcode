alter table knowledge_documents engine=InnoDB;
alter table system_users engine=InnoDB;
alter table support_ticket_messages engine=InnoDB;
alter table knowledge_documents add constraint knowledge_documents_system_fk foreign key(system_id) references saas_systems(id);
alter table knowledge_documents add constraint knowledge_documents_author_fk foreign key(created_by) references admin_users(id);
alter table system_users add constraint system_users_system_fk foreign key(system_id) references saas_systems(id);
alter table support_ticket_messages add constraint support_ticket_messages_ticket_fk foreign key(ticket_id) references support_tickets(id);
