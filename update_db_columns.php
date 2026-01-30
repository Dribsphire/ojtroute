<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/services/StudentService.php';

use App\Services\StudentService;

try {
    $service = new StudentService();
    $db = $service->getDb();

    $tables = ['student_workplaces', 'workplace_change_requests'];

    $cols = [
        'supervisor_position' => 'VARCHAR(255) NULL AFTER company_head', // Adjust AFTER as needed later
        'head_trainee' => 'VARCHAR(255) NULL',
        'head_trainee_position' => 'VARCHAR(255) NULL',
        'head_trainee_contact' => 'VARCHAR(255) NULL',
        'head_trainee_email' => 'VARCHAR(255) NULL'
    ];

    // For workplace_change_requests, structure might be slightly different key names?
    // User inputs: supervisor_position, head_trainee, head_trainee_position, head_trainee_contact, head_trainee_email
    // Existing student_workplaces has: company_head (supervisor). 
    // workplace_change_requests has: supervisor_name.

    // Let's standardize the new columns.

    foreach ($tables as $table) {
        echo "Updating table: $table\n";
        foreach ($cols as $col => $def) {
            try {
                // Check if column exists
                $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
                $stmt->execute([$col]);
                if (!$stmt->fetch()) {
                    $sql = "ALTER TABLE `$table` ADD COLUMN `$col` $def";
                    $db->exec($sql);
                    echo " - Added column: $col\n";
                } else {
                    echo " - Column $col already exists.\n";
                }
            } catch (Exception $e) {
                echo " - Error adding $col: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "Database update completed.\n";

} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
}
