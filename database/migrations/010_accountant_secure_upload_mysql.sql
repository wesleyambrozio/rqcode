alter table accounting_documents add column checksum_sha256 char(64) null after file_size;
alter table accounting_documents add column uploaded_by_role varchar(30) not null default 'admin' after uploaded_by;
alter table accounting_documents add column file_status varchar(30) not null default 'available' after uploaded_by_role;
create index accounting_documents_uploader_idx on accounting_documents(uploaded_by,created_at);
