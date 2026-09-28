<?php

declare(strict_types=1);

use App\Services\DatabaseInitializer;

require dirname(__DIR__) . '/includes/bootstrap.php';

$initializer = new DatabaseInitializer();

try {
    $report = $initializer->run();
} catch (Throwable $exception) {
    fwrite(STDERR, "ERROR:\n" . $exception->getMessage() . PHP_EOL);
    try {
        $report = $initializer->report();
        fwrite(STDERR, 'States: ' . (string) ($report['counts']['states'] ?? 0) . PHP_EOL);
        fwrite(STDERR, 'Categories: ' . (string) ($report['counts']['categories'] ?? 0) . PHP_EOL);
        fwrite(STDERR, 'Cities: ' . (string) ($report['counts']['cities'] ?? 0) . PHP_EOL);
    } catch (Throwable) {
    }
    exit(1);
}

$counts = $report['counts'];
fwrite(STDOUT, "Database connection: OK\n\n");
fwrite(STDOUT, "Tables:\n" . ($report['missing'] === [] ? "OK\n\n" : 'Missing ' . implode(', ', $report['missing']) . "\n\n"));
fwrite(STDOUT, "States:\n" . (string) ($counts['states'] ?? 0) . "\n\n");
fwrite(STDOUT, "Categories:\n" . (string) ($counts['categories'] ?? 0) . "\n\n");
fwrite(STDOUT, "Cities:\n" . (string) ($counts['cities'] ?? 0) . "\n\n");
fwrite(STDOUT, "Locations:\n" . (string) ($counts['locations'] ?? 0) . "\n\n");
fwrite(STDOUT, "Listings:\n" . (string) ($counts['listings'] ?? 0) . "\n\n");
fwrite(STDOUT, "Database initialization completed successfully.\n");
