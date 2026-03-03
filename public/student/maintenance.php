<?php
session_start();
require_once 'student_nav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - OJT TrainTrack</title>
    <link rel="icon" type="image/png" href="../images/CHMSU.png">
    <link rel="stylesheet" href="../css/student_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <main>
        <div class="maintenance-container">
            <div class="maintenance-header">
                <i class="fas fa-tools"></i>
                <h1>System Maintenance</h1><br>
                
                <p class="subtitle">Ayambott</p>
            </div>

        
        </div>
    </main>

    <style>
        .maintenance-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .maintenance-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .maintenance-header i {
            font-size: 4rem;
            color: var(--accent-clr);
            margin-bottom: 1rem;
        }

        .maintenance-header h1 {
            color: var(--text-clr);
            margin-bottom: 0.5rem;
            font-size: 2.5rem;
        }

        .subtitle {
            color: var(--secondary-text-clr);
            font-size: 1.1rem;
        }

        .maintenance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .maintenance-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--line-clr);
        }

        .maintenance-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        .card-icon {
            width: 60px;
            height: 60px;
            background: var(--accent-clr);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .card-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .card-content h3 {
            color: var(--text-clr);
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .card-content p {
            color: var(--secondary-text-clr);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .btn-primary {
            background: var(--accent-clr);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
        }

        .quick-actions {
            margin-bottom: 3rem;
        }

        .quick-actions h2 {
            color: var(--text-clr);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            background: var(--card-bg);
            border: 2px solid var(--line-clr);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            border-color: var(--accent-clr);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .action-btn i {
            font-size: 1.5rem;
            color: var(--accent-clr);
        }

        .action-btn span {
            color: var(--text-clr);
            font-weight: 600;
        }

        .system-status {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid var(--line-clr);
        }

        .system-status h2 {
            color: var(--text-clr);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-item i {
            font-size: 1.5rem;
            color: var(--accent-clr);
        }

        .status-info h4 {
            color: var(--text-clr);
            margin-bottom: 0.25rem;
        }

        .status-online {
            color: #28a745;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .maintenance-container {
                padding: 1rem;
            }

            .maintenance-header h1 {
                font-size: 2rem;
            }

            .maintenance-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .action-buttons {
                grid-template-columns: repeat(2, 1fr);
            }

            .status-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .maintenance-card {
                padding: 1.5rem;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
