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

<head>
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
                <!-- Morning Block -->
                <div class="attendance-block">
                    <div class="block-header">
                        <h3>Morning Block</h3>
                        <span class="status-badge not-started">Not Started</span>
                    </div>
                    <p class="time-range">Time Range: 6:00 AM - 12:00 PM</p>
                    <button class="time-in-btn" data-block="morning">
                        <i class="fas fa-clock"></i> Time In
                    </button>
                </div>

                <!-- Afternoon Block -->
                <div class="attendance-block">
                    <div class="block-header">
                        <h3>Afternoon Block</h3>
                        <span class="status-badge not-started">Not Started</span>
                    </div>
                    <p class="time-range">Time Range: 12:00 PM - 6:00 PM</p>
                    <button class="time-in-btn" data-block="afternoon">
                        <i class="fas fa-clock"></i> Time In
                    </button>
                </div>

                <!-- Overtime Block -->
                <div class="attendance-block">
                    <div class="block-header">
                        <h3>Overtime Block</h3>
                        <span class="status-badge not-started">Not Started</span>
                    </div>
                    <p class="time-range">Time Range: 6:00 PM - 10:00 PM</p>
                    <button class="time-in-btn" data-block="overtime">
                        <i class="fas fa-clock"></i> Time In
                    </button>
                </div>
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

        #cameraPreview,
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
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
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

            // Set canvas dimensions to match video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            // Draw current video frame to canvas
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Convert canvas to data URL and display in preview
            const imageDataUrl = canvas.toDataURL('image/png');
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

                        // Validate Distance
                        const dist = calculateDistance(lat, lng, workplaceLat, workplaceLng);
                        if (dist > 60) {
                            alert(`You are too far from your workplace (${Math.round(dist)}m).\nMaximum allowed distance is 60m.\nPlease move closer regarding the map location.`);
                            debugBtn.innerHTML = '<i class="fas fa-check"></i> Submit Time In';
                            debugBtn.disabled = false;
                            return;
                        }

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
                        alert(data.message);
                        closeCameraModal();
                        // Button state will be handled by real-time updater or we force it here?
                        // Force it here for immediate feedback
                        const btn = document.querySelector(`.time-in-btn[data-block="${block}"]`);
                        if (btn) {
                            btn.innerHTML = '<i class="fas fa-check-circle"></i> Time In Recorded';
                            btn.style.background = '#32cd32';
                            btn.disabled = true;
                            btn.classList.add('recorded');
                        }
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

                const blocks = {
                    'morning': { start: 6, end: 12 },
                    'afternoon': { start: 12, end: 18 },
                    'overtime': { start: 18, end: 22 }
                };

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
                            // Ongoing - check if block has ended
                            if (currentHour > range.end) {
                                // Block has ended - cannot time out anymore
                                btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Block Ended - Cannot Time Out';
                                btn.style.background = '#ff6b6b';
                                btn.disabled = true;
                                btn.style.cursor = 'default';
                                updateStatusBadge(btn, 'Block Ended', 'missing-timeout');
                            } else {
                                // Block still active - show Time Out button
                                btn.innerHTML = '<i class="fas fa-sign-out-alt"></i> Time Out';
                                btn.style.background = '#dc3545';
                                btn.disabled = false;
                                btn.style.cursor = 'pointer';
                                btn.setAttribute('data-action', 'time_out');
                                updateStatusBadge(btn, 'In Progress', 'in-progress');
                            }
                        }
                    } else {
                        // No record - Standard Time In Logic
                        btn.setAttribute('data-action', 'time_in');

                        // If manually disabled by eligibility check
                        if (document.querySelector('.alert-banner')) {
                            btn.disabled = true;
                            continue;
                        }

                        if (currentHour > range.end) {
                            btn.disabled = true;
                            btn.innerHTML = '<i class="fas fa-ban"></i> Ended';
                            btn.style.background = '#6c757d';
                            btn.style.cursor = 'not-allowed';
                        } else if (currentHour < range.start) {
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
                        // Check distance before opening camera
                        if (currentDistance > 60) {
                            alert(`You are too far from your workplace (${Math.round(currentDistance)}m).\nPlease move closer (within 60m) to Time In.`);
                            return;
                        }
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
                            alert(data.message);
                            // Update local state
                            if (!recordedBlocks[block]) recordedBlocks[block] = {};
                            recordedBlocks[block].status = 'completed';
                            recordedBlocks[block].time_out = new Date().toISOString();
                            updateBlockButtons();
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