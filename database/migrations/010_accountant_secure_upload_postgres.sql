alter table accounting_documents add column if not exists checksum_sha256 char(64) null;
alter table accounting_documents add column if not exists uploaded_by_role varchar(30) not null default 'admin';
alter table accounting_documents add column if not exists file_status varchar(30) not null default 'available';
create index if not exists accounting_documents_uploader_idx on accounting_documents(uploaded_by,created_at);
