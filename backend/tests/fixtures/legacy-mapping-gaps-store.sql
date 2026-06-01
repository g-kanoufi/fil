DROP TABLE IF EXISTS `fil_posts`;
DROP TABLE IF EXISTS `fil_postmeta`;

CREATE TABLE `fil_posts` (
  `ID` bigint NOT NULL,
  `post_author` bigint NOT NULL,
  `post_date` datetime NOT NULL,
  `post_date_gmt` datetime NOT NULL,
  `post_content` longtext NOT NULL,
  `post_title` text NOT NULL,
  `post_excerpt` text NOT NULL,
  `post_status` varchar(20) NOT NULL,
  `comment_status` varchar(20) NOT NULL,
  `ping_status` varchar(20) NOT NULL,
  `post_password` varchar(255) NOT NULL,
  `post_name` varchar(200) NOT NULL,
  `to_ping` text NOT NULL,
  `pinged` text NOT NULL,
  `post_modified` datetime NOT NULL,
  `post_modified_gmt` datetime NOT NULL,
  `post_content_filtered` longtext NOT NULL,
  `post_parent` bigint NOT NULL,
  `guid` varchar(255) NOT NULL,
  `menu_order` int NOT NULL,
  `post_type` varchar(20) NOT NULL,
  `post_mime_type` varchar(100) NOT NULL,
  `comment_count` bigint NOT NULL
);

CREATE TABLE `fil_postmeta` (
  `meta_id` bigint NOT NULL,
  `post_id` bigint NOT NULL,
  `meta_key` varchar(255) NOT NULL,
  `meta_value` longtext NOT NULL
);

INSERT INTO `fil_posts` VALUES
(9001,1,'2024-01-01 00:00:00','2024-01-01 00:00:00','','Demo Store','','publish','closed','closed','','demo-store','','','2024-01-01 00:00:00','2024-01-01 00:00:00','',0,'',0,'store','',0);

INSERT INTO `fil_postmeta` VALUES
(1,9001,'store_status','open'),
(2,9001,'store_number','1042'),
(3,9001,'doctors_license_0_file','5001'),
(4,9001,'finished_photos_group_0_lead_photo_for_website','6001'),
(5,9001,'checklist_0_item','legacy-checklist-value'),
(6,9001,'nso_checklist_embed','https://example.test/checklist'),
(7,9001,'business_license_file_upload','7001'),
(8,9001,'square_access_token','secret-token'),
(9,9001,'history_table','legacy-grid'),
(10,9001,'private_notes','legacy-notes');
