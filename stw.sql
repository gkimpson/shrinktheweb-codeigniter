SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for stw_acc_info
-- ----------------------------
DROP TABLE IF EXISTS `stw_acc_info`;
CREATE TABLE `stw_acc_info` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `key_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `account_level` tinyint(1) NOT NULL DEFAULT '0',
  `inside_pages` tinyint(1) NOT NULL DEFAULT '0',
  `custom_size` tinyint(1) NOT NULL DEFAULT '0',
  `full_length` tinyint(1) NOT NULL DEFAULT '0',
  `refresh_ondemand` tinyint(1) NOT NULL DEFAULT '0',
  `custom_delay` tinyint(1) NOT NULL DEFAULT '0',
  `custom_quality` tinyint(1) NOT NULL DEFAULT '0',
  `custom_resolution` tinyint(1) NOT NULL DEFAULT '0',
  `timestamp` varchar(12) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of stw_acc_info
-- ----------------------------

-- ----------------------------
-- Table structure for stw_requests
-- ----------------------------
DROP TABLE IF EXISTS `stw_requests`;
CREATE TABLE `stw_requests` (
  `siteid` int(10) NOT NULL AUTO_INCREMENT,
  `domain` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `hash` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `timestamp` varchar(12) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `capturedon` varchar(12) COLLATE utf8_unicode_ci NOT NULL,
  `quality` smallint(3) NOT NULL DEFAULT '90',
  `full` tinyint(1) NOT NULL DEFAULT '0',
  `xmax` smallint(4) NOT NULL DEFAULT '200',
  `ymax` smallint(4) NOT NULL DEFAULT '150',
  `nrx` smallint(4) NOT NULL DEFAULT '1024',
  `nry` smallint(4) NOT NULL DEFAULT '768',
  `invalid` tinyint(1) NOT NULL,
  `stwerrcode` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `error` tinyint(1) NOT NULL DEFAULT '0',
  `errcode` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `referrer` tinyint(1) NOT NULL,
  PRIMARY KEY (`siteid`),
  UNIQUE KEY `hash_idx` (`hash`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- ----------------------------
-- Records of stw_requests
-- ----------------------------
