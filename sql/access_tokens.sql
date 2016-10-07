CREATE TABLE `access_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `platform` enum('twitter','facebook','instagram') NOT NULL,
  `type` enum('app','user') NOT NULL,
  `access_token` varchar(1000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=UTF8;