INSERT INTO `wp_9_notifications` (`hash`, `title`, `trigger_slug`, `enabled`, `created_at`, `updated_at`) VALUES
('fixture-notify-hash', 'Fixture welcome email', 'zrz_user_registered', 1, '2024-01-01 00:00:00', '2024-01-01 00:00:00');

INSERT INTO `wp_9_notification_carriers` (`ID`, `notification_hash`, `slug`, `data`, `enabled`, `created_at`, `updated_at`) VALUES
(1, 'fixture-notify-hash', 'email', '{"subject":"Welcome {{user_name}}","body":"<p>Hello from legacy</p>","recipients":["related:prospect"]}', 1, '2024-01-01 00:00:00', '2024-01-01 00:00:00');

INSERT INTO `wp_9_notification_extras` (`ID`, `notification_hash`, `slug`, `data`, `created_at`, `updated_at`) VALUES
(1, 'fixture-notify-hash', 'zrz_conditionals', '{"rule":"AND","groups":[]}', '2024-01-01 00:00:00', '2024-01-01 00:00:00');
