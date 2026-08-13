create table if not exists product_categories_3d (
  id bigserial primary key,
  name varchar(120) not null unique,
  description varchar(500),
  active boolean not null default true,
  created_at timestamp default current_timestamp
);

alter table products_3d add column if not exists category_id bigint references product_categories_3d(id);
alter table products_3d add column if not exists technical_notes text;

insert into product_categories_3d (name, description) values
('Decoracao', 'Objetos decorativos e colecionaveis'),
('Utilidades', 'Pecas funcionais para uso cotidiano'),
('Reposicao', 'Componentes e pecas de reposicao'),
('Personalizados', 'Produtos produzidos sob encomenda')
on conflict (name) do nothing;
