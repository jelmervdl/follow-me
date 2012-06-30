CREATE TABLE `list_service_stored_items` (
  `service_id` varchar(255) NOT NULL DEFAULT '',
  `item_id` varchar(255) NOT NULL DEFAULT '',
  `item_timestamp` int(11) NOT NULL,
  `item_data` text NOT NULL,
  PRIMARY KEY (`item_id`),
  KEY `service_id` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;