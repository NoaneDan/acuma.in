CREATE TABLE `twitter_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `screenname` varchar(15) NOT NULL,
  `profile_img` mediumtext,
  `twitter_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `twitter_id` (`twitter_id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=UTF8;

alter table twitter_user add name varchar(20) not null;
