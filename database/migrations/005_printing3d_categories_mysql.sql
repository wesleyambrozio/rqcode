create table if not exists product_categories_3d (
  id bigint unsigned auto_increment primary key,
  name varchar(120) not null unique,
  description varchar(500) null,
  active tinyint(1) not null default 1,
  created_at timestamp default current_timestamp
) engine=InnoDB default charset=utf8mb4;

alter table products_3d add column category_id bigint unsigned null after category;
alter table products_3d add column technical_notes text null after description;
alter table products_3d add constraint products_3d_category_fk foreign key (category_id) references product_categories_3d(id);

insert ignore into product_categories_3d (name, description) values
('Decoracao', 'Objetos decorativos e colecionaveis'),
('Utilidades', 'Pecas funcionais para uso cotidiano'),
('Reposicao', 'Componentes e pecas de reposicao'),
('Personalizados', 'Produtos produzidos sob encomenda');
