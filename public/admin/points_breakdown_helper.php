<?php
/**
 * Get points breakdown from document submissions
 * Includes bonus points, accuracy/quality, professional presentation, and timeliness
 */
function getPointsBreakdown($pdo, $studentId)
{
    // Get sum of all point types
    $sql = "
        SELECT 
            COALESCE(SUM(points), 0) as bonus_points,
            COALESCE(SUM(accuracyQualityPoints), 0) as accuracy_quality_points,
            COALESCE(SUM(professionalPresentationPoints), 0) as professional_presentation_points
        FROM document_submissions
        WHERE student_id = :student_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':student_id' => $studentId]);
    $pointsResult = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate timeliness points (deductions for late submissions)
    $timelinessSql = "
        SELECT 
            ds.submitted_at,
            dt.deadline
        FROM document_submissions ds
        INNER JOIN document_types dt ON ds.document_type_id = dt.id
        WHERE ds.student_id = :student_id
        AND dt.deadline IS NOT NULL
        AND dt.deadline != '0000-00-00'
        AND ds.status = 'approved'
    ";

    $stmt = $pdo->prepare($timelinessSql);
    $stmt->execute([':student_id' => $studentId]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $timelinessPoints = 0;
    foreach ($submissions as $submission) {
        try {
            $deadline = new DateTime($submission['deadline']);
            $submittedAt = new DateTime($submission['submitted_at']);

            // Calculate days late (positive if late, negative if early)
            $interval = $deadline->diff($submittedAt);
            $daysLate = $interval->invert ? -$interval->days : $interval->days;

            // Apply timeliness rules
            if ($daysLate <= 0) {
                // On time or early - no deduction
                $timelinessPoints += 0;
            } elseif ($daysLate >= 1 && $daysLate <= 3) {
                // 1-3 days late: -5 pts
                $timelinessPoints -= 5;
            } elseif ($daysLate >= 4 && $daysLate <= 7) {
                // 4-7 days late: -10 pts
                $timelinessPoints -= 10;
            } else {
                // Beyond 7 days: -20 pts
                $timelinessPoints -= 20;
            }
        } catch (Exception $e) {
            // Skip invalid dates
            continue;
        }
    }

    // Calculate total points
    $totalPoints = floatval($pointsResult['bonus_points'])
        + floatval($pointsResult['accuracy_quality_points'])
        + floatval($pointsResult['professional_presentation_points'])
        + $timelinessPoints;

    return [
        'bonus_points' => floatval($pointsResult['bonus_points']),
        'accuracy_quality_points' => floatval($pointsResult['accuracy_quality_points']),
        'professional_presentation_points' => floatval($pointsResult['professional_presentation_points']),
        'timeliness_points' => $timelinessPoints,
        'total_points' => $totalPoints
    ];
}
