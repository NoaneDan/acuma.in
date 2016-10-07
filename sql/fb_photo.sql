CREATE TABLE `fb_photo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `photo_url` mediumtext NOT NULL,
  `event_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fb_photo_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `fb_event` (`id`),
  CONSTRAINT `fb_photo_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `fb_location` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;
