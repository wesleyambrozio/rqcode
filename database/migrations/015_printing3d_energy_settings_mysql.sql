create table if not exists printing_3d_settings (
  id tinyint unsigned primary key,
  energy_rate_kwh decimal(12,5) not null default 0.90329,
  updated_at timestamp default current_timestamp on update current_timestamp
);

insert into printing_3d_settings(id, energy_rate_kwh) values(1, 0.90329)
on duplicate key update id=values(id);
