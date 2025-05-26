<?php
// Rename duplicate migration files

$duplicateMigrations = [
    __DIR__ . '/database/migrations/2025_05_23_142120_create_productive_invoices_table.php',
    __DIR__ . '/database/migrations/2025_05_23_142130_create_productive_pages_table.php',
    __DIR__ . '/database/migrations/2025_05_23_142135_create_productive_discussions_table.php',
    __DIR__ . '/database/migrations/2025_05_24_002437_create_productive_attachments_table.php'
];

$renamed = 0;
foreach ($duplicateMigrations as $migrationFile) {
    if (file_exists($migrationFile)) {
        echo "Renaming: " . basename($migrationFile) . " to duplicate_" . basename($migrationFile) . "\n";
        rename($migrationFile, dirname($migrationFile) . "/duplicate_" . basename($migrationFile));
        $renamed++;
    } else {
        echo "Not found: " . basename($migrationFile) . "\n";
    }
}

echo "Renamed $renamed duplicate migration files.\n";
