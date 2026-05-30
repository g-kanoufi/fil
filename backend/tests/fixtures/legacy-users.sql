INSERT INTO `wp_users` (`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_url`, `user_registered`, `user_activation_key`, `user_status`, `display_name`, `spam`, `deleted`) VALUES
(501,'jane-prospect','$P$Btest','jane-prospect','jane@example.com','','2024-01-01 00:00:00','',0,'Jane Prospect',0,0),
(502,'corp-admin','$P$Btest','corp-admin','admin@primeiv.test','','2024-01-01 00:00:00','',0,'Corp Admin',0,0);

INSERT INTO `wp_usermeta` (`umeta_id`, `user_id`, `meta_key`, `meta_value`) VALUES
(9001, 501, 'wp_9_capabilities', 'a:1:{s:8:"prospect";b:1;}'),
(9002, 502, 'wp_9_capabilities', 'a:1:{s:14:"franchiseadmin";b:1;}'),
(9003, 502, 'first_name', 'Corp'),
(9004, 502, 'last_name', 'Admin');
