alter table user change password password char(60) not null;
alter table user change id id int not null auto_increment;