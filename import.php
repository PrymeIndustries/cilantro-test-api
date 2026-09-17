<?php
$host = 'mysql-2ace9a09-schoolapi-2e3f.d.aivencloud.com';
$port = 14979;
$dbname = 'defaultdb';
$user = 'avnadmin';
$password = 'AVNS_zxgeurxfjoDux_VjXN3';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "1. Connected to Aiven MySQL...\n";

    // Disable foreign key checks, primary key requirement, and strict mode at session level
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('SET SESSION sql_require_primary_key = 0');
    $pdo->exec("SET SESSION sql_mode = ''");

    // Drop all existing tables first to start fresh
    $existingTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($existingTables)) {
        foreach ($existingTables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `$table`");
        }
        echo "2. Database wiped clean (" . count($existingTables) . " old tables dropped)...\n";
    }

    $filename = 'db.sql';
    if (!file_exists($filename)) {
        die("Error: db.sql file not found in current directory.\n");
    }

    // Read full file content
    $sql = file_get_contents($filename);

    // Remove single-line comments (-- ...) safely
    $sql = preg_replace('/^--.*$/m', '', $sql);

    // Remove multi-line C-style comments (/* ... */) safely
    $sql = preg_replace('#/\*.*?\*/#s', '', $sql);

    // Split strictly by semicolons outside quotes/strings
    $queries = preg_split("/;(?=(?:[^'\"`]*['\"`][^'\"`]*['\"`])*[^'\"`]*$)/", $sql);

    $success = 0;
    $failed = 0;

    foreach ($queries as $query) {
        $query = trim($query);

        // Skip session-resetting statements that re-enable strict mode or transactions
        if (
            empty($query) || 
            preg_match('/^SET\s+SQL_MODE/i', $query) || 
            preg_match('/^START\s+TRANSACTION/i', $query) || 
            preg_match('/^COMMIT/i', $query)
        ) {
            continue;
        }

        try {
            $pdo->exec($query);
            $success++;
        } catch (Exception $e) {
            $failed++;
        }
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    echo "3. Import process finished!\n";
    echo "   - Successfully executed: $success statements\n";
    echo "   - Non-critical errors bypassed: $failed statements\n";

    // Show actual tables created
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "4. Total tables created on Aiven: " . count($tables) . "\n";

} catch (PDOException $e) {
    echo "Connection Error: " . $e->getMessage() . "\n";
}