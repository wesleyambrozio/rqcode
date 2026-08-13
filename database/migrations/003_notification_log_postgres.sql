create table if not exists notification_logs (
  id bigserial primary key,
  event_type varchar(50) not null,
  event_key varchar(190) not null,
  recipient varchar(190) not null,
  subject varchar(220) not null,
  status varchar(30) not null,
  error_message text null,
  sent_at timestamp null,
  created_at timestamp default current_timestamp,
  updated_at timestamp null,
  unique (event_key, recipient)
);
create index if not exists notification_status_date_idx on notification_logs (status, sent_at);
