create table if not exists inventory_sequences (entity varchar(50) primary key,current_value bigint not null default 0,updated_at timestamp default current_timestamp);
update filaments_3d set inventory_code='TMP-'||id;
with numbered as (select id,row_number() over(order by id) sequence from filaments_3d) update filaments_3d f set inventory_code='FIL-'||lpad(numbered.sequence::text,6,'0') from numbered where numbered.id=f.id;
insert into inventory_sequences(entity,current_value) values('filament',(select count(*) from filaments_3d)) on conflict(entity) do update set current_value=excluded.current_value,updated_at=current_timestamp;
