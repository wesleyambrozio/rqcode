create table if not exists fiscal_invoices (
  id bigint unsigned auto_increment primary key,
  access_key char(44) not null unique,
  model varchar(10) null,
  series varchar(10) null,
  invoice_number varchar(30) not null,
  issued_at datetime not null,
  supplier_id bigint unsigned null,
  issuer_name varchar(180) not null,
  issuer_document varchar(20) not null,
  total_products decimal(14,2) not null default 0,
  total_freight decimal(14,2) not null default 0,
  total_discount decimal(14,2) not null default 0,
  total_taxes decimal(14,2) not null default 0,
  total_invoice decimal(14,2) not null default 0,
  xml_storage_name varchar(255) not null unique,
  imported_by bigint unsigned not null,
  created_at timestamp default current_timestamp,
  foreign key (supplier_id) references suppliers_3d(id),
  foreign key (imported_by) references admin_users(id),
  index fiscal_invoices_issued_idx (issued_at),
  index fiscal_invoices_issuer_idx (issuer_document)
) engine=InnoDB default charset=utf8mb4;

create table if not exists fiscal_invoice_items (
  id bigint unsigned auto_increment primary key,
  fiscal_invoice_id bigint unsigned not null,
  item_number int not null,
  supplier_product_code varchar(80) null,
  ean varchar(30) null,
  description varchar(255) not null,
  ncm varchar(20) null,
  cfop varchar(10) null,
  unit varchar(10) null,
  quantity decimal(14,4) not null default 0,
  unit_price decimal(14,6) not null default 0,
  total_price decimal(14,2) not null default 0,
  is_filament tinyint(1) not null default 0,
  filament_id bigint unsigned null,
  created_at timestamp default current_timestamp,
  foreign key (fiscal_invoice_id) references fiscal_invoices(id) on delete cascade,
  foreign key (filament_id) references filaments_3d(id),
  unique key fiscal_invoice_item_unique (fiscal_invoice_id, item_number)
) engine=InnoDB default charset=utf8mb4;

alter table filaments_3d add column inventory_code varchar(30) null unique after id;
alter table filaments_3d add column fiscal_invoice_item_id bigint unsigned null after supplier_id;
alter table filaments_3d add column spool_quantity decimal(10,2) not null default 1 after spool_net_weight_g;
alter table filaments_3d add constraint filaments_3d_fiscal_item_fk foreign key (fiscal_invoice_item_id) references fiscal_invoice_items(id);
