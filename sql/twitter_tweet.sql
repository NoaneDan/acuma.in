 CREATE TABLE `twitter_tweet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `twitter_id` bigint(20) NOT NULL,
  `text` varchar(144) DEFAULT NULL,
  `hashtags` varchar(144) DEFAULT NULL,
  `twitter_url` mediumtext,
  `language` varchar(3) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `twitter_id` (`twitter_id`),
  CONSTRAINT `unique_post` FOREIGN KEY (`user_id`) REFERENCES `twitter_user` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=482 DEFAULT CHARSET=UTF8;

alter table twitter_tweet add slug varchar(160) not null;
alter table twitter_tweet change created_at created_at timestamp null default null;
