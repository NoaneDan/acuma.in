CREATE TABLE `twitter_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `twitter_id` bigint(20) NOT NULL,
  `text` varchar(144) DEFAULT NULL,
  `hashtags` varchar(144) DEFAULT NULL,
  `image_url` mediumtext,
  `twitter_url` mediumtext,
  `language` varchar(3) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `twitter_id` (`twitter_id`),
  CONSTRAINT `post_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `twitter_user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=UTF8;

alter table twitter_post add slug varchar(160) not null;
alter table twitter_post change created_at created_at timestamp null default null;
