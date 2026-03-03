<?php
session_start();

// Initialize Services
require_once '../../app/services/StudentService.php';
$studentService = new \App\Services\StudentService();

$userId = $_SESSION['user_id'] ?? 0;
$student = $studentService->getStudentProfile($userId);

// Fetch Real Data (using profile data if available, else defaults)
$defaultLat = 10.64318297908722;
$defaultLng = 122.93941207740018;

$student_data = [
    'workplace_latitude' => (!empty($student['latitude']) && $student['latitude'] != 0) ? $student['latitude'] : $defaultLat,
    'workplace_longitude' => (!empty($student['longitude']) && $student['longitude'] != 0) ? $student['longitude'] : $defaultLng,
    'workplace_name' => $student['workplace'] ?? 'No Workplace Assigned'
];

$dbId = $studentService->getStudentDbId($userId);
$todayAttendance = $studentService->getTodayAttendance($dbId);

// Check Eligibility
$eligibility = $studentService->checkAttendanceEligibility($dbId);
$isAllowed = $eligibility['allowed'];
$blockMessage = $isAllowed ? '' : $eligibility['message'];

// Get student schedule
$schedule = $studentService->getStudentSchedule($dbId);
$hasSchedule = !empty($schedule);
$scheduleStart = $hasSchedule ? $schedule['schedule_start_time'] : null;
$scheduleEnd = $hasSchedule ? $schedule['schedule_end_time'] : null;

// Calculate overtime end (schedule_end + 4 hours)
$overtimeEnd = null;
if ($hasSchedule) {
    $otEndDt = new DateTime($scheduleEnd);
    $otEndDt->modify('+4 hours');
    $overtimeEnd = $otEndDt->format('H:i:s');
}

// Handle POST Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Clean buffer
    while (ob_get_level())
        ob_end_clean();
    header('Content-Type: application/json');

    if (!$isAllowed && $_POST['action'] === 'time_in') {
        echo json_encode(['success' => false, 'message' => $blockMessage]);
        exit;
    }

    $block = $_POST['block'] ?? '';

    if ($_POST['action'] === 'time_in') {
        $lat = $_POST['latitude'] ?? 0;
        $lng = $_POST['longitude'] ?? 0;
        $photo = $_POST['photo'] ?? '';
        $result = $studentService->recordAttendance($dbId, $block, $lat, $lng, $photo);
        echo json_encode($result);
        exit;
    } elseif ($_POST['action'] === 'time_out') {
        $result = $studentService->recordTimeOut($dbId, $block);
        echo json_encode($result);
        exit;
    }
}

// Prepare Data for Frontend - include full record details
$recordedBlocks = [];
foreach ($todayAttendance as $record) {
    $recordedBlocks[$record['block_type']] = [
        'status' => $record['status'],
        'time_in' => $record['time_in'],
        'time_out' => $record['time_out'],
        'missing_timeout_flagged' => !empty($record['missing_timeout_flagged_at']),
        'request_status' => $record['request_status'] ?? null
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - OJT TrainTrack</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="../css/student_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <!-- CRITICAL FIX: Add hours validation utilities -->
    <script src="../js/hours-validation.js"></script>
</head>

<body>
    <?php require_once 'student_nav.php'; ?>
    <main>
        <div class="attendance-container">
            <h1>Attendance</h1><br>

            <?php if (!$isAllowed): ?>
                <div class="alert-banner">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo htmlspecialchars($blockMessage); ?></span>
                </div>
                <style>
                    .alert-banner {
                        background-color: rgba(220, 53, 69, 0.9);
                        border-left: 5px solid #ff4d4d;
                        color: white;
                        padding: 1.5rem;
                        margin-bottom: 2rem;
                        border-radius: 8px;
                        display: flex;
                        align-items: center;
                        gap: 1rem;
                        font-weight: 500;
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
                    }

                    .time-in-btn {
                        pointer-events: none !important;
                        opacity: 0.5 !important;
                        background: #555 !important;
                        cursor: not-allowed !important;
                    }
                </style>
            <?php endif; ?>
            <div class="map-container">
                <div id="attendanceMap"></div>
            </div>

            <div class="attendance-blocks">
                <?php if (!$hasSchedule): ?>
                    <div class="attendance-block" style="grid-column: 1/-1; text-align: center; padding: 2rem;">
                        <i class="fas fa-calendar-alt" style="font-size: 2.5rem; color: #ffa500; margin-bottom: 1rem;"></i>
                        <h3 style="color: #ffa500; margin-bottom: 0.5rem;">Working Schedule Not Set</h3>
                        <p style="color: var(--secondary-text-clr); margin-bottom: 1rem;">You must set your working schedule
                            in your profile before you can record attendance.</p>
                        <a href="student_profile.php" class="btn btn-primary"
                            style="display: inline-flex; text-decoration: none;">
                            <i class="fas fa-user-cog"></i> Go to Profile
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Regular Hours Block -->
                    <div class="attendance-block">
                        <div class="block-header">
                            <h3>Regular Hours</h3>
                            <span class="status-badge not-started">Not Started</span>
                        </div>
                        <p class="time-range">Time Range: <?php echo date('g:i A', strtotime($scheduleStart)); ?> -
                            <?php echo date('g:i A', strtotime($scheduleEnd)); ?>
                        </p>
                        <button class="time-in-btn" data-block="regular">
                            <i class="fas fa-clock"></i> Time In
                        </button>
                    </div>

                    <!-- Overtime Block -->
                    <div class="attendance-block">
                        <div class="block-header">
                            <h3>Overtime</h3>
                            <span class="status-badge not-started">Not Started</span>
                        </div>
                        <p class="time-range">Time Range: <?php echo date('g:i A', strtotime($scheduleEnd)); ?> -
                            <?php echo date('g:i A', strtotime($overtimeEnd)); ?>
                        </p>
                        <button class="time-in-btn" data-block="overtime">
                            <i class="fas fa-clock"></i> Time In
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Camera Modal -->
    <div id="cameraModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Time In - <span id="modalBlockTitle">Morning</span></h3>
                <button class="close-btn" onclick="closeCameraModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="camera-container">
                    <video id="cameraPreview" autoplay playsinline></video>
                    <canvas id="photoCanvas" style="display: none;"></canvas>
                    <div id="photoPreview" class="photo-preview"></div>
                </div>
                <div class="camera-controls">
                    <button id="captureBtn" class="btn btn-primary">
                        <i class="fas fa-camera"></i> Capture
                    </button>
                    <button id="retakeBtn" class="btn btn-secondary" style="display: none;">
                        <i class="fas fa-redo"></i> Retake
                    </button>
                    <button id="submitTimeIn" class="btn btn-success" style="display: none;">
                        <i class="fas fa-check"></i> Submit Time In
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: #2a2b3a;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--line-clr);
        }

        .modal-header h3 {
            margin: 0;
            color: #fff;
            font-size: 1.25rem;
        }

        .close-btn {
            background: none;
            border: none;
            color: #b8b8b8;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.25rem;
        }

        .close-btn:hover {
            color: #fff;
        }

        /* Camera Styles */
        .camera-container {
            width: 100%;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            overflow: hidden;
            background: #1e1e2d;
            aspect-ratio: 4/3;
            position: relative;
        }

        #cameraPreview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
            /* un-mirror front camera preview */
        }

        #photoCanvas {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .photo-preview {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            display: none;
        }

        .camera-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--accent-clr);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #3a3b4a;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #4a4b5a;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #32cd32;
            color: white;
        }

        .btn-success:hover {
            background: #28a745;
            transform: translateY(-2px);
        }

        .map-container {
            background: #2a2b3a;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 2rem;
            height: 18rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        #attendanceMap {
            width: 100%;
            height: 100%;
        }

        .attendance-blocks {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(25rem, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .attendance-block {
            background: #2a2b3a;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .attendance-block:hover {
            transform: translateY(-5px);
        }

        .block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .block-header h3 {
            color: #fff;
            margin: 0;
            font-size: 1rem;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-badge.not-started {
            background-color: #ffd70033;
            color: #ffd700;
        }

        .status-badge.in-progress {
            background-color: #1e90ff33;
            color: #1e90ff;
        }

        .status-badge.completed {
            background-color: #32cd3233;
            color: #32cd32;
        }

        .status-badge.missing-timeout {
            background-color: #ff6b6b33;
            color: #ff6b6b;
        }

        .status-badge.pending-request {
            background-color: #ffa50033;
            color: #ffa500;
        }

        .status-badge.rejected {
            background-color: #dc354533;
            color: #dc3545;
        }

        .time-range {
            color: #b8b8b8;
            margin: 0.5rem 0 1.5rem;
            font-size: 0.95rem;
        }

        .time-in-btn {
            width: 100%;
            padding: 0.75rem;
            background: var(--accent-clr);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .time-in-btn:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        .time-in-btn:active {
            transform: translateY(0);
        }

        .time-in-btn i {
            font-size: 1rem;
        }

        /* Responsive Styles for Mobile */
        @media (max-width: 800px) {
            .attendance-container {
                padding: 0;
            }

            .attendance-container h1 {
                font-size: 1.5rem;
                text-align: center;
                margin-bottom: 0.5rem;
            }

            .alert-banner {
                padding: 1rem;
                margin-bottom: 1rem;
                font-size: 0.9rem;
                flex-direction: column;
                text-align: center;
                gap: 0.5rem;
            }

            .alert-banner i {
                font-size: 1.5rem;
            }

            .map-container {
                height: 12rem;
                margin-bottom: 1rem;
                border-radius: 8px;
            }

            .attendance-blocks {
                grid-template-columns: 1fr;
                gap: 1rem;
                margin-bottom: 80px;
                /* Extra margin to prevent overlap with fixed bottom nav */
                padding-bottom: 1rem;
            }

            .attendance-block {
                padding: 1rem;
                border-radius: 8px;
                transform: none !important;
                /* Disable transform that creates stacking context */
            }

            .attendance-block:hover {
                transform: none !important;
                /* Disable hover transform on mobile */
            }

            .time-in-btn:hover {
                transform: none !important;
                /* Disable hover transform on mobile */
            }

            .block-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
                margin-bottom: 0.75rem;
            }

            .block-header h3 {
                font-size: 0.95rem;
            }

            .status-badge {
                font-size: 0.75rem;
                padding: 0.2rem 0.6rem;
            }

            .time-range {
                font-size: 0.85rem;
                margin: 0.25rem 0 1rem;
            }

            .time-in-btn {
                padding: 0.65rem;
                font-size: 0.9rem;
            }

            .time-in-btn i {
                font-size: 0.9rem;
            }
        }

        /* Extra small devices */
        @media (max-width: 480px) {
            .attendance-container h1 {
                font-size: 1.25rem;
            }

            .alert-banner {
                padding: 0.75rem;
                font-size: 0.85rem;
                border-left-width: 3px;
            }

            .map-container {
                height: 15rem;
            }

            .attendance-block {
                padding: 0.85rem;
            }

            .block-header h3 {
                font-size: 0.9rem;
            }

            .status-badge {
                font-size: 0.7rem;
            }

            .time-range {
                font-size: 0.8rem;
            }

            .time-in-btn {
                padding: 0.6rem;
                font-size: 0.85rem;
            }

            /* Camera Modal Responsive */
            .modal-content {
                width: 95%;
                padding: 1rem;
                max-height: 85vh;
            }

            .modal-header {
                margin-bottom: 1rem;
                padding-bottom: 0.75rem;
            }

            .modal-header h3 {
                font-size: 1.1rem;
            }

            .camera-container {
                margin-bottom: 1rem;
            }

            .camera-controls {
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-top: 1rem;
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }
        }
    </style>

    <script>
        // Global variables for camera stream
        let cameraStream = null;
        let currentBlock = '';
        let capturedPhotoData = null;
        let currentDistance = Infinity;

        const workplaceLat = <?php echo json_encode(floatval($student_data['workplace_latitude'])); ?>;
        const workplaceLng = <?php echo json_encode(floatval($student_data['workplace_longitude'])); ?>;

        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371e3; // metres
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c;
        }

        // Camera Modal Functions
        function openCameraModal(block) {
            currentBlock = block;
            document.getElementById('modalBlockTitle').textContent = block.charAt(0).toUpperCase() + block.slice(1);
            document.getElementById('cameraModal').style.display = 'flex';
            startCamera();
        }

        function closeCameraModal() {
            stopCamera();
            document.getElementById('cameraModal').style.display = 'none';
            resetCameraUI();
        }

        function startCamera() {
            const video = document.getElementById('cameraPreview');

            // Stop any existing stream
            if (cameraStream) {
                stopCamera();
            }

            // Request camera access
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
                .then(function (stream) {
                    cameraStream = stream;
                    video.srcObject = stream;
                    video.play();
                })
                .catch(function (err) {
                    console.error("Error accessing camera: ", err);
                    alert('Could not access the camera. Please make sure you have granted camera permissions.');
                });
        }

        function stopCamera() {
            if (cameraStream) {
                const tracks = cameraStream.getTracks();
                tracks.forEach(track => track.stop());
                cameraStream = null;
            }

            const video = document.getElementById('cameraPreview');
            if (video.srcObject) {
                video.srcObject = null;
            }
        }

        function capturePhoto() {
            const video = document.getElementById('cameraPreview');
            const canvas = document.getElementById('photoCanvas');
            const photoPreview = document.getElementById('photoPreview');

            // Resize to max 640px width for faster upload while maintaining aspect ratio
            const maxWidth = 640;
            const scale = video.videoWidth > maxWidth ? maxWidth / video.videoWidth : 1;
            canvas.width = video.videoWidth * scale;
            canvas.height = video.videoHeight * scale;

            // Draw current video frame to canvas (resized), flipping horizontally to un-mirror
            const context = canvas.getContext('2d');
            context.save();
            context.translate(canvas.width, 0);
            context.scale(-1, 1);
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            context.restore();

            // Convert canvas to JPEG with 60% quality for much smaller file size
            const imageDataUrl = canvas.toDataURL('image/jpeg', 0.6);
            capturedPhotoData = imageDataUrl; // Store for submission
            photoPreview.style.backgroundImage = `url(${imageDataUrl})`;
            photoPreview.style.display = 'block';

            // Show/hide appropriate buttons
            document.getElementById('captureBtn').style.display = 'none';
            document.getElementById('retakeBtn').style.display = 'inline-flex';
            document.getElementById('submitTimeIn').style.display = 'inline-flex';

            // Stop the camera stream
            stopCamera();
        }

        function retakePhoto() {
            const photoPreview = document.getElementById('photoPreview');
            photoPreview.style.display = 'none';

            // Show/hide appropriate buttons
            document.getElementById('captureBtn').style.display = 'inline-flex';
            document.getElementById('retakeBtn').style.display = 'none';
            document.getElementById('submitTimeIn').style.display = 'none';

            // Restart the camera
            startCamera();
        }

        function resetCameraUI() {
            // Reset all UI elements
            document.getElementById('photoPreview').style.display = 'none';
            document.getElementById('captureBtn').style.display = 'inline-flex';
            document.getElementById('retakeBtn').style.display = 'none';
            document.getElementById('submitTimeIn').style.display = 'none';
        }

        function submitTimeIn() {
            if (!capturedPhotoData) {
                alert("Please capture a photo first.");
                return;
            }

            const block = currentBlock;
            const debugBtn = document.getElementById('submitTimeIn');
            debugBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting location...';
            debugBtn.disabled = true;

            // Get Geolocation
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        debugBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Validating location...';



                        debugBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                        sendTimeInRequest(block, lat, lng, capturedPhotoData);
                    },
                    function (err) {
                        let errorMsg = "Error getting location: ";

                        switch (err.code) {
                            case err.PERMISSION_DENIED:
                                errorMsg += "Location permission denied. Please enable location access in your browser settings.";
                                break;
                            case err.POSITION_UNAVAILABLE:
                                errorMsg += "Location information is unavailable. Please check your GPS settings.";
                                break;
                            case err.TIMEOUT:
                                errorMsg += "Location request timed out. Please ensure GPS is enabled and try again.";
                                break;
                            default:
                                errorMsg += "An unknown error occurred. Please try again.";
                                break;
                        }

                        alert(errorMsg);
                        debugBtn.innerHTML = '<i class="fas fa-check"></i> Submit Time In';
                        debugBtn.disabled = false;
                    },
                    { enableHighAccuracy: true, timeout: 30000, maximumAge: 5000 }
                );
            } else {
                alert("Geolocation is not supported by your browser.");
                debugBtn.innerHTML = '<i class="fas fa-check"></i> Submit Time In';
                debugBtn.disabled = false;
            }
        }

        function sendTimeInRequest(block, lat, lng, photoData) {
            const formData = new FormData();
            formData.append('action', 'time_in');
            formData.append('block', block);
            formData.append('latitude', lat);
            formData.append('longitude', lng);
            formData.append('photo', photoData);

            fetch('attendance.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('submitTimeIn').innerHTML = '<i class="fas fa-check"></i> Submit Time In';
                    document.getElementById('submitTimeIn').disabled = false;

                    if (data.success) {
                        if (data.late_minutes && data.late_minutes > 0) {
                            const hrs = Math.floor(data.late_minutes / 60);
                            const mins = data.late_minutes % 60;
                            const lateStr = hrs > 0 ? `${hrs} hour${hrs > 1 ? 's' : ''} and ${mins} minute${mins !== 1 ? 's' : ''}` : `${mins} minute${mins !== 1 ? 's' : ''}`;
                            alert(`⚠️ You're late ${lateStr}!\n\nYour scheduled start time has already passed. Please try to arrive on time.`);
                        }
                        alert(data.message);
                        closeCameraModal();
                        // Refresh the page to show updated status
                        window.location.reload();
                    } else {
                        alert("Submission Failed: " + data.message);
                    }
                })
                .catch(e => {
                    console.error(e);
                    document.getElementById('submitTimeIn').innerHTML = '<i class="fas fa-check"></i> Submit Time In';
                    document.getElementById('submitTimeIn').disabled = false;
                    alert("Network error occurred.");
                });
        }

        // Event Listeners for camera controls
        document.addEventListener('DOMContentLoaded', function () {
            // Camera modal controls
            document.getElementById('captureBtn').addEventListener('click', capturePhoto);
            document.getElementById('retakeBtn').addEventListener('click', retakePhoto);
            document.getElementById('submitTimeIn').addEventListener('click', submitTimeIn);

            // Close modal when clicking outside
            window.addEventListener('click', function (event) {
                if (event.target === document.getElementById('cameraModal')) {
                    closeCameraModal();
                }
            });

            // Initialize the map
            const defaultLat = <?php echo json_encode(floatval($student_data['workplace_latitude'])); ?>;
            const defaultLng = <?php echo json_encode(floatval($student_data['workplace_longitude'])); ?>;
            const workplaceName = <?php echo json_encode($student_data['workplace_name']); ?>;

            // Initialize map
            // Initialize map with Philippines constraint
            const map = L.map('attendanceMap', {
                minZoom: 8,
                maxBounds: [
                    [4.0, 116.0], // South West
                    [22.0, 128.0] // North East
                ],
                maxBoundsViscosity: 1.0,
                zoomSnap: 0.01 // Allow fractional zoom
            }).setView([defaultLat, defaultLng], 15);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Custom Icons using Leaflet Color Markers
            const redIcon = new L.Icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            const blueIcon = new L.Icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-blue.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            });

            // Add marker for workplace (Red)
            const workplaceMarker = L.marker([defaultLat, defaultLng], {
                title: workplaceName,
                icon: redIcon
            }).addTo(map)
                .bindPopup(`<b>${workplaceName}</b><br>Your workplace location`);

            // Add circle to show accuracy zones
            // 40m - Ideal Zone (Green)
            L.circle([defaultLat, defaultLng], {
                color: '#1ad21c',
                fillColor: '#1ad21c33',
                fillOpacity: 0.1,
                radius: 40
            }).addTo(map);

            // 60m - Allowed Limit (Yellow/Orange)
            L.circle([defaultLat, defaultLng], {
                color: '#ffa500',
                fillColor: '#ffa50033',
                fillOpacity: 0.1,
                radius: 60
            }).addTo(map);

            // Student Location Tracking
            let studentMarker = null;
            let connectionLine = null;
            let firstFix = true;

            if (navigator.geolocation) {
                navigator.geolocation.watchPosition(
                    function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;

                        // Calculate Distance using Global function
                        const dist = calculateDistance(lat, lng, defaultLat, defaultLng);
                        currentDistance = dist; // Update global variable

                        let statusColor = 'red';
                        let statusText = 'Too Far (>60m)';

                        if (dist <= 40) {
                            statusColor = 'green';
                            statusText = 'Excellent (Inside 40m)';
                        } else if (dist <= 60) {
                            statusColor = 'orange';
                            statusText = 'Allowed (Near 60m)';
                        }

                        // Update or Create Student Marker
                        const popupContent = `
                            <b>You</b><br>
                            Distance: ${Math.round(dist)}m<br>
                            Status: <span style="color:${statusColor}; font-weight:bold;">${statusText}</span><br>
                            Accuracy: ${Math.round(accuracy)}m
                        `;

                        if (studentMarker) {
                            studentMarker.setLatLng([lat, lng]);
                            studentMarker.setPopupContent(popupContent);
                            // Do not force open popup repeatedly, only if it was already open or first fix?
                            // Actually, keeping the popup up to date is enough. 
                        } else {
                            studentMarker = L.marker([lat, lng], { icon: blueIcon }).addTo(map);
                            studentMarker.bindPopup(popupContent).openPopup();
                        }

                        // Update or Create Connection Line
                        if (connectionLine) {
                            connectionLine.setLatLngs([[lat, lng], [defaultLat, defaultLng]]);
                            connectionLine.setStyle({ color: statusColor });
                        } else {
                            connectionLine = L.polyline([[lat, lng], [defaultLat, defaultLng]], {
                                color: statusColor,
                                dashArray: '10, 10',
                                weight: 2,
                                opacity: 0.6
                            }).addTo(map);
                        }

                        // Only fit bounds on the first fix
                        if (firstFix) {
                            const bounds = L.latLngBounds([
                                [lat, lng],
                                [defaultLat, defaultLng]
                            ]);
                            map.fitBounds(bounds, { padding: [50, 50] });
                            firstFix = false;
                        }
                    },
                    function (err) {
                        console.warn('Geolocation error on map: ' + err.message + ' (Code: ' + err.code + ')');
                        // Don't show alert for watchPosition errors as they can be frequent
                    },
                    {
                        enableHighAccuracy: true,
                        maximumAge: 10000,
                        timeout: 30000
                    }
                );
            }

            // Add click event to time-in buttons
            document.querySelectorAll('.time-in-btn').forEach(button => {
                button.addEventListener('click', function () {
                    if (this.disabled) return;

                    const block = this.getAttribute('data-block');
                    openCameraModal(block);
                });
            });

            // Recorded Blocks data from server
            const recordedBlocks = <?php echo json_encode($recordedBlocks); ?>;

            // Real-time Block Status Updater (UTC+8)
            function updateBlockButtons() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', { timeZone: 'Asia/Manila', hour12: false });
                const [h, m, s] = timeString.split(':').map(Number);
                const currentHour = h + (m / 60);

                <?php if ($hasSchedule): ?>
                    const scheduleStartParts = '<?php echo $scheduleStart; ?>'.split(':').map(Number);
                    const scheduleEndParts = '<?php echo $scheduleEnd; ?>'.split(':').map(Number);
                    const scheduleStartHour = scheduleStartParts[0] + (scheduleStartParts[1] / 60);
                    const scheduleEndHour = scheduleEndParts[0] + (scheduleEndParts[1] / 60);
                    const overtimeEndHour = scheduleEndHour + 4;
                    const earlyStartHour = scheduleStartHour - 0.5; // 30 min before schedule start
                    
                    // Detect if this is a cross-day shift (end time < start time)
                    const isCrossDayShift = scheduleEndHour < scheduleStartHour;
                    
                    const blocks = {
                        'regular': { 
                            start: earlyStartHour, 
                            end: scheduleEndHour,
                            isCrossDay: isCrossDayShift
                        },
                        'overtime': { 
                            start: scheduleEndHour, 
                            end: overtimeEndHour,
                            isCrossDay: isCrossDayShift
                        }
                    };
                <?php else: ?>
                    const blocks = {};
                <?php endif; ?>

                for (const [block, range] of Object.entries(blocks)) {
                    const btn = document.querySelector(`.time-in-btn[data-block="${block}"]`);
                    if (!btn) continue;

                    // Check if we have a record for this block
                    const record = recordedBlocks[block];

                    if (record) {
                        // Check if flagged as missing timeout
                        if (record.missing_timeout_flagged && !record.time_out) {
                            // Missing timeout - needs approval request
                            if (record.request_status === 'pending') {
                                btn.innerHTML = '<i class="fas fa-clock"></i> Request Pending';
                                btn.style.background = '#ffa500';
                                btn.disabled = true;
                                btn.style.cursor = 'default';
                                updateStatusBadge(btn, 'Request Pending', 'pending-request');
                            } else if (record.request_status === 'approved') {
                                btn.innerHTML = '<i class="fas fa-check-double"></i> Approved';
                                btn.style.background = '#32cd32';
                                btn.disabled = true;
                                btn.style.cursor = 'default';
                                updateStatusBadge(btn, 'Completed', 'completed');
                            } else if (record.request_status === 'rejected') {
                                btn.innerHTML = '<i class="fas fa-times-circle"></i> Request Rejected';
                                btn.style.background = '#dc3545';
                                btn.disabled = true;
                                btn.style.cursor = 'default';
                                updateStatusBadge(btn, 'Rejected', 'rejected');
                            } else {
                                // Flagged but no request submitted yet
                                btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Missing Time-Out';
                                btn.style.background = '#ff6b6b';
                                btn.disabled = true;
                                btn.style.cursor = 'default';
                                updateStatusBadge(btn, 'Missing Time-Out', 'missing-timeout');
                            }
                        } else if (record.status === 'completed' || record.time_out) {
                            // Completed - show completed status
                            btn.innerHTML = '<i class="fas fa-check-double"></i> Completed';
                            btn.style.background = '#32cd32';
                            btn.disabled = true;
                            btn.style.cursor = 'default';
                            updateStatusBadge(btn, 'Completed', 'completed');
                        } else {
                            // Ongoing - show Time Out button
                            btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Time Out';
                            btn.style.background = '#dc3545';
                            btn.disabled = false;
                            btn.style.cursor = 'pointer';
                            btn.setAttribute('data-action', 'time_out');
                            updateStatusBadge(btn, 'In Progress', 'in-progress');
                        }
                    } else {
                        // No record - Standard Time In Logic
                        btn.setAttribute('data-action', 'time_in');
                        
                        // Reset status badge to "Not Started" when no record exists for today
                        updateStatusBadge(btn, 'Not Started', 'not-started');

                        // If manually disabled by eligibility check
                        if (document.querySelector('.alert-banner')) {
                            btn.disabled = true;
                            continue;
                        }

                        // Handle cross-day shift logic
                        let isWithinTimeRange = false;
                        let isTimeEnded = false;
                        
                        if (range.isCrossDay) {
                            // For cross-day shifts (e.g., 21:00 to 06:00)
                            if (block === 'regular') {
                                // Regular block: from early start to midnight, then from midnight to end time
                                if (currentHour >= range.start || currentHour < range.end) {
                                    isWithinTimeRange = true;
                                }
                                // Block ends only when we pass the end time AND we're in the "ended window" (after end time but before start time on the same day cycle)
                                // For cross-day, this means after 6:00 AM but before 8:30 PM
                                if (currentHour >= range.end && currentHour < range.start && currentHour > 12) {
                                    isTimeEnded = true;
                                }
                            } else if (block === 'overtime') {
                                // Overtime block: from schedule end to midnight, then from midnight to overtime end
                                // For cross-day shifts, overtime starts at 6:00 AM (schedule end) and goes to 10:00 AM
                                if (currentHour >= range.start && currentHour < range.end) {
                                    isWithinTimeRange = true;
                                }
                                // Overtime ends when we pass the overtime end time (after 10:00 AM)
                                if (currentHour >= range.end) {
                                    isTimeEnded = true;
                                }
                            }
                        } else {
                            // Regular same-day logic
                            if (currentHour > range.end) {
                                isTimeEnded = true;
                            } else if (currentHour >= range.start) {
                                isWithinTimeRange = true;
                            }
                        }

                        if (isTimeEnded) {
                            btn.disabled = true;
                            btn.innerHTML = '<i class="fas fa-ban"></i> Ended';
                            btn.style.background = '#6c757d';
                            btn.style.cursor = 'not-allowed';
                        } else if (!isWithinTimeRange) {
                            btn.disabled = true;
                            btn.innerHTML = '<i class="fas fa-clock"></i> Not Started';
                            btn.style.background = '#6c757d';
                            btn.style.cursor = 'not-allowed';
                        } else {
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-clock"></i> Time In';
                            btn.style.background = 'var(--accent-clr)';
                            btn.style.cursor = 'pointer';
                        }
                    }
                }
            }

            function updateStatusBadge(btn, text, className) {
                const blockElement = btn.closest('.attendance-block');
                const statusBadge = blockElement.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.textContent = text;
                    statusBadge.className = `status-badge ${className}`;
                }
            }

            // Update click listeners to handle both time-in and time-out
            document.querySelectorAll('.time-in-btn').forEach(button => {
                const newBtn = button.cloneNode(true);
                button.parentNode.replaceChild(newBtn, button);

                newBtn.addEventListener('click', function () {
                    if (this.disabled) return;

                    const block = this.getAttribute('data-block');
                    const action = this.getAttribute('data-action') || 'time_in';

                    if (action === 'time_out') {
                        submitTimeOut(block);
                    } else {
                        openCameraModal(block);
                    }
                });
            });

            function submitTimeOut(block) {
                if (!confirm("Are you sure you want to Time Out?")) return;

                const formData = new FormData();
                formData.append('action', 'time_out');
                formData.append('block', block);

                fetch('attendance.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            // Format block name for display
                            const blockNames = {
                                'regular': 'Regular Hours',
                                'overtime': 'Overtime',
                                'morning': 'Morning Block',
                                'afternoon': 'Afternoon Block'
                            };
                            const blockName = blockNames[block] || block;

                            // Show hours worked if available
                            let message = data.message;
                            if (data.hours_worked !== undefined) {
                                message += `\n\n📊 ${blockName} Summary:\nHours Worked: ${data.hours_worked} hours`;
                            }

                            alert(message);
                            // Refresh page to show updated status
                            window.location.reload();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(e => {
                        console.error(e);
                        alert('Network error occurred.');
                    });
            }

            // Run immediately and every second
            updateBlockButtons();
            setInterval(updateBlockButtons, 1000);
        });
    </script>
</body>