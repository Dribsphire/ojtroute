<?php
// Quick database check script
// Place this in: c:\xampp\htdocs\ojtlast\check_hours.php

require_once __DIR__ . '/app/config/database.php';

$db = Database::getInstance()->getConnection();

// Get student ID from URL parameter
$studentId = $_GET['student_id'] ?? 1;

echo "<h2>Hours Check for Student ID: $studentId</h2>";

// Check ojt_summaries
echo "<h3>OJT Summaries Table:</h3>";
$stmt = $db->prepare("SELECT * FROM ojt_summaries WHERE student_id = ?");
$stmt->execute([$studentId]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($summary);
echo "</pre>";

// Check attendance records
echo "<h3>Attendance Records (Completed):</h3>";
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_records,
        SUM(hours) as total_hours
    FROM attendance_records 
    WHERE student_id = ? AND status = 'completed'
");
$stmt->execute([$studentId]);
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($attendance);
echo "</pre>";

// Check students table
echo "<h3>Students Table:</h3>";
$stmt = $db->prepare("
    SELECT s.id, s.user_id, u.full_name, u.school_id 
    FROM students s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($student);
echo "</pre>";

echo "<hr>";
echo "<p>To check another student, add ?student_id=X to the URL</p>";
