<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Guide - OJT Route</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="icon" type="image/png" href="../../public/images/CHMSU.png">
    <link rel="stylesheet" href="../css/student_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<style>
    main {
        padding: 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    h1 {
        text-align: center;
        margin-bottom: 30px;
        color: #cac8c8ff;
    }

    p {
        color: #cac8c8ff !important;
    }

    .userguide-container {
        display: flex;
        margin-bottom: 40px;
        gap: 3rem;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }



    .userguide-container img {
        width: 100%;
        max-width: 300px;
        height: auto;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(116, 110, 110, 0.1);
        transition: transform 0.3s ease;
    }

    .userguide-container img:hover {
        transform: scale(1.02);
    }

    .description {
        flex: 1;
        text-align: center;
        /* Centralize the description */
        padding: 0 20px;
    }

    .description p {
        font-size: 1.1rem;
        line-height: 1.6;
        color: #555;
    }

    /* Page Breaker Style */
    .page-breaker {
        border: none;
        border-top: 3px dotted #d8d4d4;
        width: 100%;
        margin: 40px 0;
    }

    /* Responsive Design */
    @media (max-width: 900px) {
        .userguide-container {
            flex-direction: column !important;
            /* Stack vertically on smaller screens */
            gap: 1.5rem;
            text-align: center;
        }

        .userguide-container img {
            max-width: 80%;
            /* Smaller images on mobile */
        }
    }
</style>

<body>
    <?php
    include 'student_nav.php';
    ?>
    <main>
        <h1>User Guide</h1>

        <div class="userguide-container">
            <img src="../../userguide-mobile/guide1.png" alt="User Guide 1">
            <div class="description">
                <p>When you first open the attendance page, a red alert message will appear instructing you to set your
                    workplace location. You can do this by accessing your profile through the profile button icon.
                </p>
            </div>
        </div>

        <hr class="page-breaker">

        <div class="userguide-container">
            <img src="../../userguide-mobile/guide2.png" alt="User Guide 2">
            <div class="description">
                <p>Once you are on the profile page, you will see the Edit Profile and Set Workplace buttons. Click Set
                    Workplace, and a modal will open for setting up your workplace location.
                </p>
            </div>
        </div>

        <hr class="page-breaker">

        <div class="userguide-container">
            <img src="../../userguide-mobile/guide2.1.png" alt="User Guide 2.1">
            <div class="description">
                <p>In the Set Workplace modal, you can locate your workplace by navigating the map or by searching in
                    the search field if the workplace is available.
                    <label style="font-weight:600;color: #1bc229ff !important;">(Please note that the map uses a Leaflet
                        mapping
                        API,
                        so not all workplaces may appear in the search bar, unlike Google Maps).</label> It is
                    recommended to
                    use the Current
                    Location
                    button if you are already at your workplace, as this will automatically set the correct location
                    without needing to manually navigate the map.
                </p><br>
                <p>
                    Once you pin your workplace, this location will be used as your attendance reference. The circular
                    border indicates the valid area for attendance checking—you will not be able to time in from the
                    attendance page unless your location is within the green radius. After pinning the location, fill in
                    the remaining fields, such as the workplace name, address, your position as an intern, and the name
                    of your supervisor.
                </p><br>
                <p style="font-weight:600;color: #1bc229ff !important;">
                    Note: Setting up your workplace is a one-time process. Any future changes to your workplace location
                    or information will require approval from your instructor.
                </p>
            </div>
        </div>

        <hr class="page-breaker">

        <div class="userguide-container">
            <img src="../../userguide-mobile/guide3.png" alt="User Guide 3">
            <img src="../../userguide-mobile/guide4.png" alt="User Guide 4">
            <div class="description">
                <p>Once your workplace information is set, you still will not be able to time in on the attendance page.
                    You must first submit the required documents uploaded by your instructor, which can be accessed
                    through the folder (Documents) icon. After submitting all the required documents, you will need to
                    wait for approval from your instructor before you can take attendance.
                </p>
            </div>
        </div>

        <hr class="page-breaker">

        <div class="userguide-container">
            <img src="../../userguide-mobile/guide5.png" alt="User Guide 5">
            <div class="description">
                <p>Once the submitted documents have been reviewed and approved by the instructor, students will then be
                    able to time in and time out in the attendance page.
                </p>
            </div>
        </div>

        <hr class="page-breaker">

        <div class="userguide-container">
            <img src="../../userguide-mobile/guide6.png" alt="User Guide 6">
            <img src="../../userguide-mobile/guide7.png" alt="User Guide 7">
            <div class="description">
                <p style="font-size:15px;">To time in on the attendance page, you must allow location access on your
                    device. This will capture
                    your current location and display your distance from your workplace. The red pin represents your
                    workplace location, while the blue pin represents your device location. If your device location is
                    within the green border, it indicates excellent GPS accuracy. If it is within the orange border, it
                    indicates lower GPS accuracy, but you can still time in within both the green and orange borders.
                    Make sure your location is within the border before timing in, as attendance will not be recorded if
                    you are outside the workplace boundary.</p>

                <p style="font-size:15px;">Once you are within the workplace border, you can time in. This process
                    requires granting access to
                    your device’s camera to securely verify that you are at your workplace and that the attendance is
                    being recorded by you. The captured GPS location and photo will then be sent to your instructor for
                    monitoring purposes.</p>

                <label style="font-weight:600;color: #1bc229ff !important;">Note: Granting GPS and camera
                    access for attendance is a one-time permission and does not mean the
                    app will track you continuously.</label>
                </p>
            </div>
        </div>

        <hr class="page-breaker">

        <div class="userguide-container">
            <img src="../../userguide-mobile/guide8.png" alt="User Guide 8">
            <div class="description">
                <p>After you time in, the system will start tracking your working hours until you time out. When you
                    click Time Out, a confirmation will appear, and your record will be automatically saved to your DTR
                    on the calendar page. It will display the total number of hours worked based on your time in and
                    time out.</p><br>

                <p>Timing out does not require photo verification, but you must still be within the workplace location
                    boundary. For this reason, it is important to monitor your time in and time out carefully.</p><br>

                <label style="font-weight:600;color: #1bc229ff !important;">Note: Changing the time on your device will
                    not allow you to time in or time out earlier or later.
                    The system follows Philippine Standard Time UTC+08:00, so altering your device time will not affect
                    attendance
                    records.
                </label>
            </div>
        </div>

        <hr class="page-breaker">

        <div class="userguide-container">
            <img src="../../userguide-mobile/guide9.png" alt="User Guide 9">
            <div class="description">
                <p>After you have completed all your time-in, time-out, and excuse records, you can export your DTR
                    (Daily Time Record) at any time from the calendar page by clicking the Export button. You can select
                    the month and year based on your work period, and the system will automatically generate a report
                    showing your working hours for the selected month.
                </p>
            </div>
        </div>
        <footer style="text-align: center;">
            <p>&copy; 2025 OJT ROUTE. All rights reserved.</p>
        </footer>

    </main>

</body>

</html>