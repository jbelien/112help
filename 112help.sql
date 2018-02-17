-- Adminer 4.2.4 MySQL dump

SET NAMES utf8;
SET time_zone = '+00:00';

CREATE TABLE `help` (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `datetime` datetime NOT NULL,
  `position` point NOT NULL,
  `address` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address_type` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `address_time` datetime DEFAULT NULL,
  `accuracy` double NOT NULL,
  `battery` int(11) DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `ip_forwarded` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `whois` text COLLATE utf8_unicode_ci,
  `name` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `urgence` int(11) DEFAULT NULL,
  `infos` text COLLATE utf8_unicode_ci,
  `social` text COLLATE utf8_unicode_ci,
  `indanger` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci PAGE_CHECKSUM=1;


CREATE TABLE `help_invalid` (
  `id` bigint(11) NOT NULL,
  `datetime` datetime NOT NULL,
  `position` point NOT NULL,
  `address` varchar(250) COLLATE utf8_unicode_ci DEFAULT NULL,
  `address_type` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `address_time` datetime DEFAULT NULL,
  `accuracy` double NOT NULL,
  `battery` int(11) DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `ip_forwarded` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `whois` text COLLATE utf8_unicode_ci,
  `name` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `phone` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `urgence` int(11) DEFAULT NULL,
  `infos` text COLLATE utf8_unicode_ci,
  `social` text COLLATE utf8_unicode_ci,
  `indanger` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci PAGE_CHECKSUM=1;


CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=Aria DEFAULT CHARSET=utf8 PAGE_CHECKSUM=1;


-- 2018-02-17 16:45:47
