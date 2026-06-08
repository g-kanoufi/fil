INSERT INTO `wp_9_posts` VALUES (202,1,'2024-01-01 00:00:00','2024-01-01 00:00:00','','PrimeIV Demo','','publish','closed','closed','','primeiv-demo','','','2024-01-02 00:00:00','2024-01-02 00:00:00','',0,'',0,'store','',0);

INSERT INTO `wp_9_ach_transfers` (`id`, `store_id`, `time`, `source`, `destination`, `transfer`, `status`, `errors`, `amount`, `royalty_name`, `royalty_id`, `royalties_ids`, `store_ids`, `history`, `dwolla_status`, `zz_fee_from`, `crm_fees_ids`, `addenda`, `description`) VALUES
(9001, 202, '2024-03-05 10:00:00', 'fs-src', 'fs-dst', 'transfer-abc', 1, '', 750.00, 'Unit royalty', 8001, '8001', '202', NULL, 'processed', NULL, NULL, 'Royalty', 'Weekly royalty ACH');
