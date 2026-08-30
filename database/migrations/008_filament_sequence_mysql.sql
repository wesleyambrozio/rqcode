create table if not exists inventory_sequences (
  entity varchar(50) primary key,
  current_value bigint unsigned not null default 0,
  updated_at timestamp default current_timestamp on update current_timestamp
) engine=InnoDB default charset=utf8mb4;
update filaments_3d set inventory_code=concat('TMP-',id);
set @filament_sequence=0;
update filaments_3d set inventory_code=concat('FIL-',lpad((@filament_sequence:=@filament_sequence+1),6,'0')) order by id;
insert into inventory_sequences(entity,current_value) values('filament',@filament_sequence)
on duplicate key update current_value=values(current_value);
