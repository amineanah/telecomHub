<?php
/**
 * Database Setup Page
 * Access via: http://localhost/setup.php (if in XAMPP htdocs)
 * Or manually run: php setup.php
 */

// Database credentials
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'telecomhub';

// Get schema
$schema_file = __DIR__ . '/schema.sql';
if (!file_exists($schema_file)) {
    die("Error: schema.sql not found at " . $schema_file);
}

$sql = file_get_contents($schema_file);

// Try to connect
try {
    // Connect without database first to create it
    $pdo = new PDO("mysql:host={$DB_HOST}", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Execute schema
    $pdo->exec($sql);
    
    echo "<h2 style='color: green;'>✓ Database setup successful!</h2>";
    echo "<p>The 'telecomhub' database has been created with all tables.</p>";
    echo "<p>Your newsletter subscribers table is ready to accept emails.</p>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>✗ Error setting up database:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p>Make sure MySQL is running and your credentials in setup.php are correct.</p>";
}
?>
