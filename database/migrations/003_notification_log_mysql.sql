create table if not exists notification_logs (
  id bigint unsigned auto_increment primary key,
  event_type varchar(50) not null,
  event_key varchar(190) not null,
  recipient varchar(190) not null,
  subject varchar(220) not null,
  status varchar(30) not null,
  error_message text null,
  sent_at datetime null,
  created_at timestamp default current_timestamp,
  updated_at timestamp null,
  unique key notification_event_recipient_unique (event_key, recipient),
  index notification_status_date_idx (status, sent_at)
) engine=InnoDB default charset=utf8mb4;
