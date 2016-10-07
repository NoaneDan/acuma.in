CREATE TABLE `fb_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` bigint(20) NOT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_location` (`name`,`latitude`,`longitude`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;

alter table fb_location drop index unique_location;
alter table fb_location add unique unique_location (location_id);
alter table fb_location add city_id int not null;
alter table fb_location add blocked enum('yes', 'no') not null default 'yes';
