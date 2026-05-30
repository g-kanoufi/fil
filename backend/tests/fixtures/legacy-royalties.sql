INSERT INTO `wp_9_posts` VALUES (202,1,'2024-01-01 00:00:00','2024-01-01 00:00:00','','PrimeIV Demo','','publish','closed','closed','','primeiv-demo','','','2024-01-02 00:00:00','2024-01-02 00:00:00','',0,'',0,'store','',0);

INSERT INTO `wp_9_weekly_store_revenue` (`id`, `store_id`, `frequency`, `time`, `week_start`, `week_end`, `week_revenue`, `orders`, `royalties`) VALUES
(7001, 202, 'weekly', '2024-03-01 00:00:00', '2024-02-26 00:00:00', '2024-03-03 23:59:59', 12500.00, 95, 750.00);

INSERT INTO `wp_9_weekly_store_royalties_detail` (`id`, `store_id`, `frequency`, `trigger_day`, `weekly_store_revenue_id`, `time`, `royalty_type`, `royalty_name`, `week_revenue`, `royalty_rate`, `royalty`, `ach_source`, `ach_destination`, `status`, `transfer_id`, `funding_source`) VALUES
(8001, 202, 'weekly', 'monday', 7001, '2024-03-01 00:00:00', 'unit', 'Unit royalty', 12500.00, 0.06, 750.00, 'src-1', 'dst-1', 0, NULL, 'fs-1');
