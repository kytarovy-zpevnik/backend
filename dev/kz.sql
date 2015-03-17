-- phpMyAdmin SQL Dump
-- version 3.5.8.2
-- http://www.phpmyadmin.net
--
-- Počítač: wm77.wedos.net:3306
-- Vygenerováno: Úte 24. úno 2015, 21:46
-- Verze serveru: 5.6.17
-- Verze PHP: 5.4.23

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databáze: `d89361_kz`
--

-- --------------------------------------------------------

--
-- Struktura tabulky `bad_content`
--

CREATE TABLE IF NOT EXISTS `bad_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `song_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_B33A2F4EA76ED395` (`user_id`),
  KEY `IDX_B33A2F4EA0BDB2F3` (`song_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `ban`
--

CREATE TABLE IF NOT EXISTS `ban` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `legitimate` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_62FED0E5A76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `notification`
--

CREATE TABLE IF NOT EXISTS `notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `song_id` int(11) DEFAULT NULL,
  `songbook_id` int(11) DEFAULT NULL,
  `created` datetime NOT NULL,
  `read` tinyint(1) NOT NULL,
  `text` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BF5476CAA76ED395` (`user_id`),
  KEY `IDX_BF5476CAA0BDB2F3` (`song_id`),
  KEY `IDX_BF5476CAE9EA4588` (`songbook_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `password_reset`
--

CREATE TABLE IF NOT EXISTS `password_reset` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `created_on` datetime NOT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_B1017252A76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `recommendation`
--

CREATE TABLE IF NOT EXISTS `recommendation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recommend_from_id` int(11) DEFAULT NULL,
  `recommend_to_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_433224D2CAD10515` (`recommend_from_id`),
  KEY `IDX_433224D280728146` (`recommend_to_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `role`
--

CREATE TABLE IF NOT EXISTS `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Vypisuji data pro tabulku `role`
--

INSERT INTO `role` (`id`, `slug`, `name`) VALUES
(1, 'admin', 'Administrátor');

-- --------------------------------------------------------

--
-- Struktura tabulky `session`
--

CREATE TABLE IF NOT EXISTS `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  `expiration` datetime NOT NULL,
  `long_life` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D044D5D4A76ED395` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3 ;

--
-- Vypisuji data pro tabulku `session`
--

INSERT INTO `session` (`id`, `user_id`, `token`, `created`, `expiration`, `long_life`) VALUES
(1, 1, 'zHbxgDn1AEso2YFbod/qqm/z3vjg8i+kIVPyurgBHWg=', '2015-01-29 19:41:19', '2015-01-29 20:01:24', 0),
(2, 1, '9SybI+Oy4GHjyEXVyi+dKvIUUF1ueykVVHJP41bPjHo=', '2015-02-24 16:59:15', '2015-02-24 17:20:09', 0);

-- --------------------------------------------------------

--
-- Struktura tabulky `song`
--

CREATE TABLE IF NOT EXISTS `song` (
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
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_33EDEEA17E3C61F9` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `songbook`
--

CREATE TABLE IF NOT EXISTS `songbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `archived` tinyint(1) NOT NULL,
  `public` tinyint(1) NOT NULL,
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C94ECC4C7E3C61F9` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `songbook_comment`
--

CREATE TABLE IF NOT EXISTS `songbook_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `songbook_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `comment` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_44E470FAE9EA4588` (`songbook_id`),
  KEY `IDX_44E470FAA76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `songbook_rating`
--

CREATE TABLE IF NOT EXISTS `songbook_rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `songbook_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `rating` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_28BD47C7E9EA4588` (`songbook_id`),
  KEY `IDX_28BD47C7A76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `songbook_recommendation`
--

CREATE TABLE IF NOT EXISTS `songbook_recommendation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `songbook_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_721C5D60E9EA4588` (`songbook_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `songbook_sharing`
--

CREATE TABLE IF NOT EXISTS `songbook_sharing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `songbook_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `editable` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_7E11CEFEE9EA4588` (`songbook_id`),
  KEY `IDX_7E11CEFEA76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `songbook_tag`
--

CREATE TABLE IF NOT EXISTS `songbook_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `songbook_id` int(11) DEFAULT NULL,
  `tag` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_8BD73E47E9EA4588` (`songbook_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `song_comment`
--

CREATE TABLE IF NOT EXISTS `song_comment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `comment` longtext COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_991F4343A0BDB2F3` (`song_id`),
  KEY `IDX_991F4343A76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `song_rating`
--

CREATE TABLE IF NOT EXISTS `song_rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `rating` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DEF237A8A0BDB2F3` (`song_id`),
  KEY `IDX_DEF237A8A76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `song_recommendation`
--

CREATE TABLE IF NOT EXISTS `song_recommendation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `comment` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_AEC52218A0BDB2F3` (`song_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `song_sharing`
--

CREATE TABLE IF NOT EXISTS `song_sharing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `editable` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_A3EAFD47A0BDB2F3` (`song_id`),
  KEY `IDX_A3EAFD47A76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `song_songbook`
--

CREATE TABLE IF NOT EXISTS `song_songbook` (
  `song_id` int(11) NOT NULL,
  `songbook_id` int(11) NOT NULL,
  PRIMARY KEY (`song_id`,`songbook_id`),
  KEY `IDX_62929A04A0BDB2F3` (`song_id`),
  KEY `IDX_62929A04E9EA4588` (`songbook_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabulky `song_tag`
--

CREATE TABLE IF NOT EXISTS `song_tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `song_id` int(11) DEFAULT NULL,
  `tag` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_4C49C104A0BDB2F3` (`song_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Struktura tabulky `user`
--

CREATE TABLE IF NOT EXISTS `user` (
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
  KEY `IDX_8D93D649D60322AC` (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Vypisuji data pro tabulku `user`
--

INSERT INTO `user` (`id`, `role_id`, `username`, `first_name`, `last_name`, `email`, `password_hash`, `last_login`) VALUES
(1, 1, 'markatom', NULL, NULL, 'markatom@fit.cvut.cz', '$2y$12$aMniZuWCm5GgN6kehQA6Qu6fU5qWVKvVx9HMnRNx9.RjK2OSxWk1i', '2015-02-24 16:59:15');

-- --------------------------------------------------------

--
-- Struktura tabulky `wish`
--

CREATE TABLE IF NOT EXISTS `wish` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `interpret` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `note` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_D7D174C9A76ED395` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

--
-- Vypisuji data pro tabulku `wish`
--

INSERT INTO `wish` (`id`, `user_id`, `name`, `interpret`, `note`, `created`, `modified`) VALUES
(1, 1, 'rwar', 'tttt', NULL, '2015-02-24 16:59:59', '2015-02-24 16:59:59');

--
-- Omezení pro exportované tabulky
--

--
-- Omezení pro tabulku `bad_content`
--
ALTER TABLE `bad_content`
  ADD CONSTRAINT `FK_B33A2F4EA0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`),
  ADD CONSTRAINT `FK_B33A2F4EA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Omezení pro tabulku `ban`
--
ALTER TABLE `ban`
  ADD CONSTRAINT `FK_62FED0E5A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Omezení pro tabulku `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `FK_BF5476CAA0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`),
  ADD CONSTRAINT `FK_BF5476CAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_BF5476CAE9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`);

--
-- Omezení pro tabulku `password_reset`
--
ALTER TABLE `password_reset`
  ADD CONSTRAINT `FK_B1017252A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Omezení pro tabulku `recommendation`
--
ALTER TABLE `recommendation`
  ADD CONSTRAINT `FK_433224D280728146` FOREIGN KEY (`recommend_to_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_433224D2CAD10515` FOREIGN KEY (`recommend_from_id`) REFERENCES `user` (`id`);

--
-- Omezení pro tabulku `session`
--
ALTER TABLE `session`
  ADD CONSTRAINT `FK_D044D5D4A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Omezení pro tabulku `song`
--
ALTER TABLE `song`
  ADD CONSTRAINT `FK_33EDEEA17E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`);

--
-- Omezení pro tabulku `songbook`
--
ALTER TABLE `songbook`
  ADD CONSTRAINT `FK_C94ECC4C7E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`);

--
-- Omezení pro tabulku `songbook_comment`
--
ALTER TABLE `songbook_comment`
  ADD CONSTRAINT `FK_44E470FAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_44E470FAE9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`);

--
-- Omezení pro tabulku `songbook_rating`
--
ALTER TABLE `songbook_rating`
  ADD CONSTRAINT `FK_28BD47C7A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_28BD47C7E9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`);

--
-- Omezení pro tabulku `songbook_recommendation`
--
ALTER TABLE `songbook_recommendation`
  ADD CONSTRAINT `FK_721C5D60E9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`);

--
-- Omezení pro tabulku `songbook_sharing`
--
ALTER TABLE `songbook_sharing`
  ADD CONSTRAINT `FK_7E11CEFEA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_7E11CEFEE9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`);

--
-- Omezení pro tabulku `songbook_tag`
--
ALTER TABLE `songbook_tag`
  ADD CONSTRAINT `FK_8BD73E47E9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`);

--
-- Omezení pro tabulku `song_comment`
--
ALTER TABLE `song_comment`
  ADD CONSTRAINT `FK_991F4343A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`),
  ADD CONSTRAINT `FK_991F4343A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Omezení pro tabulku `song_rating`
--
ALTER TABLE `song_rating`
  ADD CONSTRAINT `FK_DEF237A8A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`),
  ADD CONSTRAINT `FK_DEF237A8A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Omezení pro tabulku `song_recommendation`
--
ALTER TABLE `song_recommendation`
  ADD CONSTRAINT `FK_AEC52218A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`);

--
-- Omezení pro tabulku `song_sharing`
--
ALTER TABLE `song_sharing`
  ADD CONSTRAINT `FK_A3EAFD47A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`),
  ADD CONSTRAINT `FK_A3EAFD47A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Omezení pro tabulku `song_songbook`
--
ALTER TABLE `song_songbook`
  ADD CONSTRAINT `FK_62929A04A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_62929A04E9EA4588` FOREIGN KEY (`songbook_id`) REFERENCES `songbook` (`id`) ON DELETE CASCADE;

--
-- Omezení pro tabulku `song_tag`
--
ALTER TABLE `song_tag`
  ADD CONSTRAINT `FK_4C49C104A0BDB2F3` FOREIGN KEY (`song_id`) REFERENCES `song` (`id`);

--
-- Omezení pro tabulku `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `FK_8D93D649D60322AC` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`);

--
-- Omezení pro tabulku `wish`
--
ALTER TABLE `wish`
  ADD CONSTRAINT `FK_D7D174C9A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
