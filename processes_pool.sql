CREATE TABLE `processes_pool` (
  `label` varchar(40) PRIMARY KEY,
  `nb_launched` mediumint(6) unsigned NOT NULL,
  `pid_list` varchar(2048) default NULL,
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
