<?php
/**
 * Automatic Missing Timeout Detection Script
 * 
 * This script runs periodically to detect students who timed in but did not time out
 * before the block period ended. It automatically flags these records as "Missing Time-Out".
 * 
 * Schedule: Run every hour or at specific times after each block ends:
 * - After 12:00 PM (for morning block)
 * - After 6:00 PM (for afternoon block)
 * - After 10:00 PM (for overtime block)
 * 
 * Usage:
 * - Via cron: php detect_missing_timeouts.php
 * - Via browser: http://localhost/ojtlast/app/cron/detect_missing_timeouts.php
 */

require_once __DIR__ . '/../../vendor/autoload.php';

class MissingTimeoutDetector
{
    private $db;

    public function __construct()
    {
        $this->connect();
    }

    private function connect()
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'ojt_db';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASS') ?: '';

        try {
            $this->db = new PDO(
                "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            // Set timezone to Asia/Manila
            $this->db->exec("SET time_zone = '+08:00'");
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed.");
        }
    }

    /**
     * Detect and flag missing timeouts
     * 
     * Logic:
     * - Morning block (6:00 AM - 12:00 PM): Check after 12:00 PM
     * - Afternoon block (12:00 PM - 6:00 PM): Check after 6:00 PM
     * - Overtime block (6:00 PM - 10:00 PM): Check after 10:00 PM
     */
    public function detectMissingTimeouts()
    {
        $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $currentDate = $currentDateTime->format('Y-m-d');
        $currentTime = $currentDateTime->format('H:i:s');

        $flaggedCount = 0;

        // Define block end times
        $blocks = [
            'morning' => '12:00:00',
            'afternoon' => '18:00:00',
            'overtime' => '22:00:00'
        ];

        foreach ($blocks as $blockType => $endTime) {
            // Only check blocks that have already ended
            if ($currentTime < $endTime) {
                continue;
            }

            // Find records that:
            // 1. Are from today or earlier
            // 2. Have time_in but no time_out
            // 3. Block period has ended
            // 4. Not already flagged as missing timeout
            // 5. No pending/approved/rejected request status

            $sql = "
                SELECT 
                    ar.id,
                    ar.student_id,
                    ar.attendance_date,
                    ar.block_type,
                    ar.time_in,
                    CONCAT(ar.attendance_date, ' ', ?) as block_end_datetime
                FROM attendance_records ar
                WHERE ar.block_type = ?
                AND ar.time_out IS NULL
                AND ar.missing_timeout_flagged_at IS NULL
                AND ar.request_status IS NULL
                AND (
                    ar.attendance_date < ?
                    OR (ar.attendance_date = ? AND ? >= ?)
                )
            ";

            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $endTime,           // Block end time
                    $blockType,         // Block type
                    $currentDate,       // For past dates
                    $currentDate,       // For today
                    $currentTime,       // Current time
                    $endTime            // Block end time
                ]);

                $records = $stmt->fetchAll();

                foreach ($records as $record) {
                    $this->flagMissingTimeout($record['id']);
                    $flaggedCount++;

                    $this->log(
                        "Flagged missing timeout: Record ID {$record['id']}, " .
                        "Student ID {$record['student_id']}, " .
                        "Date {$record['attendance_date']}, " .
                        "Block {$record['block_type']}"
                    );
                }

            } catch (PDOException $e) {
                $this->log("Error detecting missing timeouts for {$blockType}: " . $e->getMessage(), 'ERROR');
            }
        }

        return $flaggedCount;
    }

    /**
     * Flag a record as missing timeout
     */
    private function flagMissingTimeout($recordId)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE attendance_records 
                SET missing_timeout_flagged_at = NOW(),
                    status = 'pending_exception'
                WHERE id = ?
            ");
            $stmt->execute([$recordId]);

            return true;
        } catch (PDOException $e) {
            $this->log("Error flagging record {$recordId}: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Log messages
     */
    private function log($message, $level = 'INFO')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}\n";

        // Log to file
        $logFile = __DIR__ . '/../../storage/logs/missing_timeout_detector.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents($logFile, $logMessage, FILE_APPEND);

        // Also output to console if running from CLI
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
}

// Run the detector
try {
    $detector = new MissingTimeoutDetector();
    $flaggedCount = $detector->detectMissingTimeouts();

    $message = "Missing timeout detection completed. Flagged {$flaggedCount} record(s).";
    error_log($message);

    if (php_sapi_name() === 'cli') {
        echo $message . "\n";
    } else {
        // If accessed via browser, return JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'flagged_count' => $flaggedCount,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

} catch (Exception $e) {
    $errorMessage = "Error running missing timeout detector: " . $e->getMessage();
    error_log($errorMessage);

    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
    } else {
        echo $errorMessage . "\n";
    }
}
