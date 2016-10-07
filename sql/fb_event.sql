CREATE TABLE `fb_event` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp,
  `description` text,
  `name` varchar(1000) DEFAULT NULL,
  `event_id` bigint(20) NOT NULL,
  `location_id` int(11) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `attending_count` int(11) DEFAULT NULL,
  `interested_count` int(11) DEFAULT NULL,
  `maybe_count` int(11) DEFAULT NULL,
  `declined_count` int(11) DEFAULT NULL,
  `is_canceled` tinyint(1) DEFAULT NULL,
  `ticket_uri` mediumtext,
  `album_id` bigint,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_id` (`event_id`),
  CONSTRAINT `fb_event_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `fb_location` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=UTF8;

alter table fb_event add facebook_url varchar(200) not null;
alter table fb_event add slug varchar(1000) not null;
alter table fb_event change start_time start_time timestamp null default null;
alter table fb_event change end_time end_time timestamp null default null;
