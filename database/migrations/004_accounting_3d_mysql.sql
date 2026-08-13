create table if not exists accounting_documents (
  id bigint unsigned auto_increment primary key,
  title varchar(180) not null,
  category varchar(60) not null,
  reference_month char(7) null,
  original_name varchar(255) not null,
  storage_name varchar(255) not null unique,
  mime_type varchar(120) not null,
  file_size bigint unsigned not null default 0,
  notes text null,
  uploaded_by bigint unsigned null,
  created_at timestamp default current_timestamp,
  index accounting_documents_reference_idx (reference_month, category)
) engine=InnoDB default charset=utf8mb4;

create table if not exists accounting_document_events (
  id bigint unsigned auto_increment primary key,
  document_id bigint unsigned not null,
  admin_user_id bigint unsigned not null,
  event_type varchar(30) not null,
  ip_address varchar(80) null,
  user_agent varchar(500) null,
  created_at timestamp default current_timestamp,
  foreign key (document_id) references accounting_documents(id),
  foreign key (admin_user_id) references admin_users(id),
  index accounting_events_document_idx (document_id, created_at)
) engine=InnoDB default charset=utf8mb4;

create table if not exists accounting_messages (
  id bigint unsigned auto_increment primary key,
  sender_user_id bigint unsigned not null,
  subject varchar(180) not null,
  message text not null,
  status varchar(30) not null default 'open',
  due_date date null,
  created_at timestamp default current_timestamp,
  foreign key (sender_user_id) references admin_users(id)
) engine=InnoDB default charset=utf8mb4;

create table if not exists suppliers_3d (
  id bigint unsigned auto_increment primary key,
  name varchar(160) not null,
  document_number varchar(30) null,
  contact_name varchar(120) null,
  email varchar(160) null,
  phone varchar(40) null,
  lead_time_days int not null default 0,
  notes text null,
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp
) engine=InnoDB default charset=utf8mb4;

create table if not exists filaments_3d (
  id bigint unsigned auto_increment primary key,
  supplier_id bigint unsigned null,
  name varchar(140) not null,
  material varchar(40) not null,
  brand varchar(80) null,
  color varchar(80) null,
  diameter_mm decimal(5,2) not null default 1.75,
  spool_net_weight_g decimal(10,2) not null,
  current_weight_g decimal(10,2) not null,
  purchase_price decimal(12,2) not null,
  batch_code varchar(80) null,
  purchase_date date null,
  minimum_stock_g decimal(10,2) not null default 0,
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp,
  foreign key (supplier_id) references suppliers_3d(id)
) engine=InnoDB default charset=utf8mb4;

create table if not exists products_3d (
  id bigint unsigned auto_increment primary key,
  sku varchar(80) not null unique,
  name varchar(180) not null,
  description text null,
  category varchar(80) null,
  license_type varchar(80) null,
  license_source varchar(255) null,
  license_notes text null,
  print_time_minutes int not null default 0,
  energy_cost decimal(12,2) not null default 0,
  labor_cost decimal(12,2) not null default 0,
  packaging_cost decimal(12,2) not null default 0,
  other_cost decimal(12,2) not null default 0,
  waste_percent decimal(7,2) not null default 0,
  sale_price decimal(12,2) not null default 0,
  stock_quantity decimal(10,2) not null default 0,
  minimum_stock decimal(10,2) not null default 0,
  image_path varchar(255) null,
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp
) engine=InnoDB default charset=utf8mb4;

create table if not exists product_filaments_3d (
  id bigint unsigned auto_increment primary key,
  product_id bigint unsigned not null,
  filament_id bigint unsigned not null,
  quantity_g decimal(10,2) not null,
  foreign key (product_id) references products_3d(id) on delete cascade,
  foreign key (filament_id) references filaments_3d(id),
  unique key product_filament_unique (product_id, filament_id)
) engine=InnoDB default charset=utf8mb4;

create table if not exists sales_channels_3d (
  id bigint unsigned auto_increment primary key,
  name varchar(140) not null,
  channel_type varchar(40) not null,
  contact_name varchar(120) null,
  fee_percent decimal(7,2) not null default 0,
  fixed_fee decimal(12,2) not null default 0,
  commission_percent decimal(7,2) not null default 0,
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp
) engine=InnoDB default charset=utf8mb4;

create table if not exists production_orders_3d (
  id bigint unsigned auto_increment primary key,
  product_id bigint unsigned not null,
  quantity decimal(10,2) not null default 1,
  status varchar(30) not null default 'planned',
  started_at datetime null,
  completed_at datetime null,
  unit_cost_snapshot decimal(12,2) not null default 0,
  total_cost decimal(12,2) not null default 0,
  notes text null,
  created_at timestamp default current_timestamp,
  foreign key (product_id) references products_3d(id)
) engine=InnoDB default charset=utf8mb4;

create table if not exists sales_3d (
  id bigint unsigned auto_increment primary key,
  product_id bigint unsigned not null,
  channel_id bigint unsigned null,
  quantity decimal(10,2) not null default 1,
  unit_price decimal(12,2) not null,
  gross_amount decimal(12,2) not null,
  fees_amount decimal(12,2) not null default 0,
  cost_amount decimal(12,2) not null default 0,
  net_profit decimal(12,2) not null default 0,
  sold_at date not null,
  external_order_id varchar(120) null,
  created_at timestamp default current_timestamp,
  foreign key (product_id) references products_3d(id),
  foreign key (channel_id) references sales_channels_3d(id)
) engine=InnoDB default charset=utf8mb4;

insert ignore into sales_channels_3d (name, channel_type, fee_percent) values
('Venda direta', 'direct', 0), ('Mercado Livre', 'marketplace', 16),
('Shopee', 'marketplace', 20), ('Amazon', 'marketplace', 15),
('Parceiro / ponto de venda', 'partner', 0);
