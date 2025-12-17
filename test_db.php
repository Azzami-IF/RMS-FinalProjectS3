<?php
// Test database connection and schema
require_once 'config/database.php';
require_once 'config/env.php';

try {
    $config = require 'config/env.php';
    $db = (new Database($config))->getConnection();

    echo "✅ Database connection successful!\n";

    // Test if tables exist
    $tables = ['users', 'foods', 'food_categories', 'meal_types', 'schedules', 'notifications'];
    foreach ($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "❌ Table '$table' missing\n";
        }
    }

    // Test sample data
    $stmt = $db->query("SELECT COUNT(*) as user_count FROM users");
    $userCount = $stmt->fetch()['user_count'];
    echo "👥 Users in database: $userCount\n";

    $stmt = $db->query("SELECT COUNT(*) as food_count FROM foods");
    $foodCount = $stmt->fetch()['food_count'];
    echo "🍎 Foods in database: $foodCount\n";

    echo "\n🎉 Database setup completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>