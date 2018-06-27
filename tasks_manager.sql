CREATE TABLE `tasks_manager` (
  `cluster_label` varchar(40),
  `calcul_label` varchar(40),
  `status` enum('waiting', 'running', 'failed', 'success') default 'waiting',
  `updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`cluster_label`, `calcul_label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
