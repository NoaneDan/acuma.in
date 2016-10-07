CREATE TABLE IF NOT EXISTS `city` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city` varchar(255) NOT NULL,
  `latitude` float(11) NOT NULL,
  `longitude` float(11) NOT NULL,
  `radius` int(3) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;

alter table city change radius radiusInMeters int not null;
INSERT INTO `city` (`id`, `city`, `latitude`, `longitude`, `radiusInMeters`) VALUES (1, 'Oradea', 47.0667, 21.9333, 15000);
