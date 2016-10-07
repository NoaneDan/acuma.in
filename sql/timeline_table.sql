CREATE TABLE `timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` enum('twitter_post','twitter_tweet','ig_post','fb_event') NOT NULL,
  `source_id` int(11) NOT NULL,
  `source_timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `timestamp_index` (`source_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;

alter table timeline add source_user_id bigint not null;
alter table timeline add blocked enum('yes', 'no') not null default 'no';
alter table timeline add city_id int not null;
