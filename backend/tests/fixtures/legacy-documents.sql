INSERT INTO `wp_9_posts` VALUES (202,1,'2024-01-01 00:00:00','2024-01-01 00:00:00','','PrimeIV Demo','','publish','closed','closed','','primeiv-demo','','','2024-01-02 00:00:00','2024-01-02 00:00:00','',0,'',0,'store','',0);
INSERT INTO `wp_9_posts` VALUES (303,1,'2024-01-01 00:00:00','2024-01-01 00:00:00','','Demo Location','','publish','closed','closed','','demo-location','','','2024-01-02 00:00:00','2024-01-02 00:00:00','',0,'',0,'franchise_location','',0);
INSERT INTO `wp_9_posts` VALUES (5001,1,'2024-01-01 00:00:00','2024-01-01 00:00:00','','Doctors License Scan','','inherit','open','closed','','doctors-license-scan','','','2024-01-01 00:00:00','2024-01-01 00:00:00','',202,'',0,'attachment','application/pdf',0);
INSERT INTO `wp_9_posts` VALUES (5002,1,'2024-01-01 00:00:00','2024-01-01 00:00:00','','LOI Document','','inherit','open','closed','','loi-document','','','2024-01-01 00:00:00','2024-01-01 00:00:00','',303,'',0,'attachment','application/pdf',0);

INSERT INTO `wp_9_postmeta` (`meta_id`, `post_id`, `meta_key`, `meta_value`) VALUES
(20001, 5001, '_wp_attached_file', '2024/01/doctors-license.pdf'),
(20002, 202, 'doctors_license_0_file', '5001'),
(20003, 202, 'dwolla_customer', 'cust-legacy-abc'),
(20004, 202, 'dwolla_funding_source', 'fs-legacy-xyz'),
(20005, 5002, '_wp_attached_file', '2024/01/loi-document.pdf'),
(20006, 303, 'pre-lease_loi_documents_0_file', '5002'),
(20007, 303, 'store_no', '202');

INSERT INTO `wp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) VALUES
(30001, 502, 'medical_certification_0_file', '5001');
