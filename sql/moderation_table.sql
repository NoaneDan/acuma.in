CREATE TABLE `moderation_table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `platform` enum('twitter','instagram') NOT NULL,
  `state` enum('pending','accepted','blacklisted') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=UTF8;
