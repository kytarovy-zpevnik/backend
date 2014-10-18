-- Adminer 4.1.0 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `bad_content`;
CREATE TABLE `bad_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `song_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B33A2F4EA76ED395` (`user_id`),
  KEY `IDX_B33A2F4EA0BDB2F3` (`song_id`),
  CONSTRAINT `FK_B33A2F4EA0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`),
  CONSTRAINT `FK_B33A2F4EA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `ban`;
CREATE TABLE `ban` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `legitimate` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_62FED0E5A76ED395` (`user_id`),
  CONSTRAINT `FK_62FED0E5A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `comment`;
CREATE TABLE `comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9474526CA76ED395` (`user_id`),
  CONSTRAINT `FK_9474526CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `editors_songbooks`;
CREATE TABLE `editors_songbooks` (
  `songbook_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`songbook_id`,`user_id`),
  KEY `IDX_28D28408E9EA4588` (`songbook_id`),
  KEY `IDX_28D28408A76ED395` (`user_id`),
  CONSTRAINT `FK_28D28408A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_28D28408E9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `editors_songs`;
CREATE TABLE `editors_songs` (
  `song_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`song_id`,`user_id`),
  KEY `IDX_D2FE1B6DA0BDB2F3` (`song_id`),
  KEY `IDX_D2FE1B6DA76ED395` (`user_id`),
  CONSTRAINT `FK_D2FE1B6DA0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_D2FE1B6DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `notification`;
CREATE TABLE `notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `read` tinyint(1) NOT NULL,
  `type` int(11) NOT NULL,
  `song` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BF5476CAA76ED395` (`user_id`),
  CONSTRAINT `FK_BF5476CAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `rating`;
CREATE TABLE `rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `rating` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D8892622A76ED395` (`user_id`),
  CONSTRAINT `FK_D8892622A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `recommendation`;
CREATE TABLE `recommendation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recommend_from_id` int(11) DEFAULT NULL,
  `recommend_to_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_433224D2CAD10515` (`recommend_from_id`),
  KEY `IDX_433224D280728146` (`recommend_to_id`),
  CONSTRAINT `FK_433224D280728146` FOREIGN KEY (`recommend_to_id`) REFERENCES `user` (`id`),
  CONSTRAINT `FK_433224D2CAD10515` FOREIGN KEY (`recommend_from_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `role`;
CREATE TABLE `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `role` (`id`, `slug`, `name`) VALUES
(1,	'admin',	'administrator'),
(2,	'registered',	'registrovany');

DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `expiration` datetime NOT NULL,
  `long_life` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D044D5D4A76ED395` (`user_id`),
  CONSTRAINT `FK_D044D5D4A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `session` (`id`, `user_id`, `token`, `created`, `expiration`, `long_life`) VALUES
(3,	2,	'QsKU0J5DjQeTSJ+0iFe+LiCuoWxCGmjjmn+BwEVqSoU=',	'2014-10-17 13:18:35',	'2014-10-17 13:38:41',	0),
(5,	1,	'rOeMtBHYRyajpydqrvkqU4ygrmZtWGJ4go1gPFrTkiw=',	'2014-10-17 13:38:04',	'2014-10-17 13:58:08',	0),
(6,	1,	'kSozbqqKMIE9KcnF4bHHY3IFd47W5ruuGj2eKoOqVcw=',	'2014-10-17 16:50:38',	'2014-10-17 17:12:41',	0),
(7,	1,	'PrANXvoNd3YYHQ0hKf3K+TMdTxmSiJjOqXsZnYvIqUE=',	'2014-10-17 16:53:04',	'2014-10-17 17:13:08',	0),
(8,	1,	'p6YbrYLglqIMoEROIwyB9lylIbpVB7amdxQwuLW7GWk=',	'2014-10-17 16:53:52',	'2014-10-17 17:13:55',	0);

DROP TABLE IF EXISTS `song`;
CREATE TABLE `song` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `lyrics` longtext COLLATE utf8_unicode_ci NOT NULL,
  `chords` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `album` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `author` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `original_author` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL,
  `public` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_33EDEEA17E3C61F9` (`owner_id`),
  CONSTRAINT `FK_33EDEEA17E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `song` (`id`, `owner_id`, `title`, `lyrics`, `chords`, `created`, `modified`, `album`, `author`, `original_author`, `year`, `archived`, `public`) VALUES
(1,	NULL,	'Foobar',	'',	'',	NULL,	NULL,	'to ta helpa',	'karel gott',	NULL,	1850,	0,	0),
(2,	1,	'supersong',	'',	'',	NULL,	NULL,	'nejlepší songy',	NULL,	NULL,	2005,	0,	0),
(3,	1,	'Highway to hell',	'',	'',	NULL,	NULL,	NULL,	'AC-DC',	NULL,	NULL,	0,	0),
(4,	NULL,	'Červená řeka',	'',	'',	NULL,	NULL,	NULL,	'Helenka',	NULL,	1950,	0,	0),
(5,	1,	'Hymna',	'',	'',	NULL,	NULL,	'České songy',	'Miloš Zeman',	'Josef Kajetán Tyl',	2014,	0,	0),
(6,	NULL,	'efqnfi',	'',	'',	NULL,	NULL,	NULL,	NULL,	NULL,	NULL,	0,	0);

DROP TABLE IF EXISTS `songbook`;
CREATE TABLE `songbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `archived` tinyint(1) NOT NULL,
  `public` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C94ECC4C7E3C61F9` (`owner_id`),
  CONSTRAINT `FK_C94ECC4C7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `songbook_comment`;
CREATE TABLE `songbook_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `songbook_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_44E470FAE9EA4588` (`songbook_id`),
  CONSTRAINT `FK_44E470FAE9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `songbook_rating`;
CREATE TABLE `songbook_rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `songbook_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `rating` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_28BD47C7E9EA4588` (`songbook_id`),
  CONSTRAINT `FK_28BD47C7E9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `songbook_recommendation`;
CREATE TABLE `songbook_recommendation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `songbook_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_721C5D60E9EA4588` (`songbook_id`),
  CONSTRAINT `FK_721C5D60E9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `song_comment`;
CREATE TABLE `song_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_991F4343A0BDB2F3` (`song_id`),
  CONSTRAINT `FK_991F4343A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `song_rating`;
CREATE TABLE `song_rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `rating` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DEF237A8A0BDB2F3` (`song_id`),
  CONSTRAINT `FK_DEF237A8A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `song_recommendation`;
CREATE TABLE `song_recommendation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AEC52218A0BDB2F3` (`song_id`),
  CONSTRAINT `FK_AEC52218A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `tag`;
CREATE TABLE `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tag` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `public` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_389B783A0BDB2F3` (`song_id`),
  KEY `IDX_389B783A76ED395` (`user_id`),
  CONSTRAINT `FK_389B783A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`),
  CONSTRAINT `FK_389B783A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) DEFAULT NULL,
  `username` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `first_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8D93D649F85E0677` (`username`),
  UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`),
  KEY `IDX_8D93D649D60322AC` (`role_id`),
  CONSTRAINT `FK_8D93D649D60322AC` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `user` (`id`, `role_id`, `username`, `first_name`, `last_name`, `email`, `password_hash`, `last_login`) VALUES
(1,	1,	'mantljir',	NULL,	NULL,	'mantljir@fit.cvut.cz',	'$2y$12$rXbQZpIITI77Q8tj7tpKC.u5/6XwRGlwMgbzh8nlA8FmykFO9UdQW',	'2014-10-17 16:53:52'),
(2,	2,	'kamilmaslo',	NULL,	NULL,	'sfdwdf@sgwg.sg',	'$2y$12$OqVULlLFLoefyw4MWnAw7.G350iH/.x3NSdvsaJlTijkmxa.AEG.m',	'2014-10-17 13:19:28');

DROP TABLE IF EXISTS `viewers_songbooks`;
CREATE TABLE `viewers_songbooks` (
  `songbook_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`songbook_id`,`user_id`),
  KEY `IDX_6698EEB1E9EA4588` (`songbook_id`),
  KEY `IDX_6698EEB1A76ED395` (`user_id`),
  CONSTRAINT `FK_6698EEB1A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_6698EEB1E9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `viewers_songs`;
CREATE TABLE `viewers_songs` (
  `song_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`song_id`,`user_id`),
  KEY `IDX_319F73CDA0BDB2F3` (`song_id`),
  KEY `IDX_319F73CDA76ED395` (`user_id`),
  CONSTRAINT `FK_319F73CDA0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_319F73CDA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `wish`;
CREATE TABLE `wish` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `wish` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D7D174C9A76ED395` (`user_id`),
  CONSTRAINT `FK_D7D174C9A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- 2014-10-17 15:09:05