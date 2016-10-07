CREATE TABLE `ig_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(30) NOT NULL,
  `profile_picture` mediumtext,
  `instagram_id` bigint(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instagram_id` (`instagram_id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;

CREATE TABLE `ig_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `instagram_id` bigint(20) NOT NULL,
  `text` varchar(1000) DEFAULT NULL,
  `tags` varchar(1000) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `media_url` mediumtext,
  `instagram_url` mediumtext NOT NULL,
  `location_id` bigint(20) NOT NULL,
  `user_id` int not null,
  PRIMARY KEY (`id`),
  UNIQUE KEY `instagram_id` (`instagram_id`),
  CONSTRAINT `post_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `ig_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;

alter table ig_post add slug varchar(1031) not null;
alter table ig_post change created_at created_at timestamp null default null;
