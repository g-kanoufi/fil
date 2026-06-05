<?php

declare(strict_types=1);

use App\Services\Legacy\LegacyDumpTableSchema;
use App\Services\Legacy\LegacyNamedTableImporter;

it('imports rows from mysqldump INSERT without column list', function (): void {
    $dump = tempnam(sys_get_temp_dir(), 'fil-dump-');
    expect($dump)->not->toBeFalse();

    file_put_contents($dump, <<<'SQL'
CREATE TABLE `vnzokz0zw_users` (
  `ID` bigint unsigned NOT NULL,
  `user_login` varchar(60) NOT NULL,
  `user_pass` varchar(255) NOT NULL,
  `user_nicename` varchar(50) NOT NULL,
  `user_email` varchar(100) NOT NULL,
  `user_url` varchar(100) NOT NULL,
  `user_registered` datetime NOT NULL,
  `user_activation_key` varchar(255) NOT NULL,
  `user_status` int NOT NULL,
  `display_name` varchar(250) NOT NULL,
  `spam` tinyint NOT NULL,
  `deleted` tinyint NOT NULL
) ENGINE=InnoDB;
INSERT INTO `vnzokz0zw_users` VALUES (1,'staff','$P$x','staff','staff@example.com','','2020-01-01 00:00:00','',0,'Staff User',0,0);
SQL);

    $importer = new LegacyNamedTableImporter(new LegacyDumpTableSchema);
    $emails = [];

    $stats = $importer->import(
        $dump,
        'vnzokz0zw_users',
        function (array $row, bool $execute) use (&$emails): void {
            $emails[] = $row['user_email'] ?? null;
        },
        false,
    );

    @unlink($dump);

    expect($stats['matched'])->toBe(1)
        ->and($emails)->toBe(['staff@example.com']);
});
