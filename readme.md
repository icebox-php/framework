## About Icebox

This repository contains the core code of the Icebox framework.

# How to run testsuite

$ vendor/bin/phpunit ./tests/

# Crud generator

php icebox generate crud box

## Test all column type

php icebox g crud post title:string content:text published:boolean publish_date:date create_time:datetime decimal_col:decimal float_col:float int_col:integer time_col:time

-- db schema
CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8_unicode_ci,
  `published` tinyint(1) DEFAULT NULL,
  `publish_date` date DEFAULT NULL,
  `create_time` datetime DEFAULT NULL,
  `decimal_col` decimal(10,0) DEFAULT NULL,
  `float_col` decimal(10,0) DEFAULT NULL,
  `int_col` int(11) DEFAULT NULL,
  `time_col` time DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

