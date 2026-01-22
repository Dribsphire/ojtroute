<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Assignment - OJT Route</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --base-clr: #11121a;
            --line-clr: #42434a;
            --hover-clr: #222533;
            --text-clr: #e6e6ef;
            --accent-clr: #1ad21c;
            --secondary-text-clr: #b0b3c1;
            --warning-orange: #ff6b35;
            --warning-light: #fff4f0;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('images/homepage.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .assignment-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 700px;
            width: 100%;
            text-align: center;
        }
        
        .assignment-header {
            background: var(--accent-clr);
            color: white;
            padding: 3rem 2rem;
            position: relative;
        }
        
        .status-icon {
            font-size: 3rem;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        .assignment-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
        }
        
        .assignment-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0.5rem 0 0 0;
        }
        
        .assignment-body {
            padding: 2.5rem 2rem;
        }
        
        .info-card {
            background: var(--warning-light);
            border-left: 4px solid var(--warning-orange);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .info-card h5 {
            color: var(--warning-orange);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-card p {
            color: #6c757d;
            margin-bottom: 0.5rem;
            line-height: 1.6;
        }
        
        .steps-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .steps-container h5 {
            color: var(--base-clr);
            font-weight: 600;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .step-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }
        
        .step-number {
            background: var(--accent-clr);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .step-content {
            flex: 1;
        }
        
        .step-content h6 {
            color: var(--base-clr);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .step-content p {
            color: #6c757d;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .contact-info {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .contact-info h5 {
            color: var(--base-clr);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            color: #6c757d;
        }
        
        .contact-item i {
            color: var(--accent-clr);
            width: 20px;
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn-logout {
            background: var(--warning-orange);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: #e55a2b;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }
        
        .btn-refresh {
            background: var(--accent-clr);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .btn-refresh:hover {
            background: #15b01c;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 210, 28, 0.3);
        }
        
        .footer-note {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .pulse-animation {
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
        }
        
        @media (max-width: 576px) {
            .assignment-title {
                font-size: 1.4rem;
            }
            
            .assignment-subtitle {
                font-size: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-logout, .btn-refresh {
                width: 100%;
                max-width: 250px;
            }
        }
    </style>
</head>
<body>
    <div class="assignment-container">
        <div class="assignment-header">
            <div class="status-icon">
                <i class="bi bi-clipboard-x"></i>
            </div>
            <h1 class="assignment-title">No Section Assignment</h1>
            <p class="assignment-subtitle">Your instructor account hasn't been assigned to any sections yet</p>
        </div>
        
        <div class="assignment-body">
            
            <div class="contact-info">
                <h5>
                    <i class="bi bi-telephone"></i>
                    Contact Information
                </h5>
                <div class="contact-item">
                    <i class="bi bi-person-badge"></i>
                    <span>System Administrator</span>
                </div>
                <div class="contact-item">
                    <i class="bi bi-envelope"></i>
                    <span>admin@chmsu.edu.ph</span>
                </div>
                <div class="contact-item">
                    <i class="bi bi-telephone-fill"></i>
                    <span>(034) 433-8248</span>
                </div>
                <div class="contact-item">
                    <i class="bi bi-geo-alt"></i>
                    <span>CHMSU Main Campus, Talisay City</span>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="logout.php" class="btn-logout">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
                <a href="javascript:location.reload()" class="btn-refresh">
                    <i class="bi bi-arrow-clockwise"></i>
                    Refresh Status
                </a>
            </div>
            
            <div class="footer-note">
                <p><i class="bi bi-shield-check me-2"></i>CHMSU OJT Route System - Instructor Portal</p>
                <small>This is a security feature to ensure only assigned instructors can access section data.</small>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh functionality
        let refreshInterval;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Set up auto-refresh every 5 minutes
            refreshInterval = setInterval(function() {
                console.log('Checking for assignment updates...');
                // In production, this would make an AJAX call to check assignment status
                // For now, we'll just log it
            }, 300000); // 5 minutes
            
            // Add click effects to buttons
            const buttons = document.querySelectorAll('.btn-logout, .btn-refresh');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 100);
                });
            });
            
            // Log the access attempt
            console.log('Instructor access: No sections assigned - ' + new Date().toLocaleString());
        });
        
        // Clean up interval when page unloads
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
</body>
</html>