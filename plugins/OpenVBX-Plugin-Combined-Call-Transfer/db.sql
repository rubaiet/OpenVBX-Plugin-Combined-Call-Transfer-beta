CREATE TABLE IF NOT EXISTS `conference_calls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `call_sid` longtext COLLATE utf8_unicode_ci NOT NULL,
  `conf_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'sms body',
  `from` longtext COLLATE utf8_unicode_ci,
  `to` longtext COLLATE utf8_unicode_ci,
  `current_agent_name` varchar(255) COLLATE utf8_unicode_ci,
  `current_agent_email` varchar(255) COLLATE utf8_unicode_ci,
  `agent` longtext COLLATE utf8_unicode_ci,
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '0 = CLOSED, 2 = OPEN',
  `status` longtext COLLATE utf8_unicode_ci,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `recorded_calls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `call_sid` longtext COLLATE utf8_unicode_ci NOT NULL,
  `transcript` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'sms body',
  `from` longtext COLLATE utf8_unicode_ci,
  `to` longtext COLLATE utf8_unicode_ci,
  `duration` int(11) DEFAULT NULL,
  `recording_url` longtext COLLATE utf8_unicode_ci,
  `type` tinyint(4) NOT NULL DEFAULT '1' COMMENT '1 = INBOUND, 2 = OUTBOUND',
  `status` longtext COLLATE utf8_unicode_ci,
  `created` timestamp NULL DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;