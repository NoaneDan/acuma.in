CREATE TABLE `ig_location` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location_id` bigint(20) NOT NULL,
  `latitude` float NOT NULL,
  `longitude` float NOT NULL,
  `name` varchar(200) NOT NULL,
  `facebook_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_location` (`location_id`),
  CONSTRAINT `ig_location_ibfk_1` FOREIGN KEY (`facebook_id`) REFERENCES `fb_location` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=UTF8;

alter table ig_location add city_id int not null;