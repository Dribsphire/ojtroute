<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden - OJT Route</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --chmsu-green: #0ea539;
            --chmsu-green-light: #34d399;
            --chmsu-green-dark: #11121a;
            --forbidden-red: #1ad21c;
            --forbidden-red-light: #f8d7da;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--chmsu-green-dark);
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
        
        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .error-header {
            background: var(--forbidden-red);
            color: white;
            padding: 3rem 2rem;
            position: relative;
        }
        
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            margin: 0;
            line-height: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 1rem 0 0.5rem 0;
        }
        
        .error-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .error-body {
            padding: 2.5rem 2rem;
        }
        
        .error-icon {
            font-size: 4rem;
            color: var(--forbidden-red);
            margin-bottom: 1.5rem;
        }
        
        .error-message {
            color: #6c757d;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .error-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .error-details h5 {
            color: var(--forbidden-red);
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .error-details ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .error-details li {
            margin-bottom: 0.5rem;
            color: #6c757d;
        }
        
        .btn-home {
            background: var(--chmsu-green);
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
        
        .btn-home:hover {
            background: var(--chmsu-green-dark);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(14, 165, 57, 0.3);
        }
        
        .btn-login {
            background: var(--forbidden-red);
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
            margin-left: 1rem;
        }
        
        .btn-login:hover {
            background: #c82333;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
        }
        
        .action-buttons {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .lock-animation {
            animation: lockPulse 2s ease-in-out infinite;
        }
        
        @keyframes lockPulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
        }
        
        .footer-info {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        @media (max-width: 576px) {
            .error-code {
                font-size: 4rem;
            }
            
            .error-title {
                font-size: 1.2rem;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-login {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-code">403</div>
            <h1 class="error-title">Access Forbidden</h1>
            <p class="error-subtitle">You don't have permission to access this page</p>
        </div>
        
        <div class="error-body">
            <!--<div class="error-icon lock-animation">
                <i class="bi bi-shield-exclamation"></i>
            </div>-->
            
            <div class="error-message">
                <strong>Oops!</strong> You've reached a restricted area. This page requires special permissions to access.
            </div>
            
            <div class="action-buttons">
                <a href="login.php" class="btn-home">
                    <i class="bi bi-house-door"></i>
                    Back to Login
                </a>
                <a href="javascript:history.back()" class="btn-login">
                    <i class="bi bi-arrow-left"></i>
                    Go Back
                </a>
            </div>
            
            <div class="footer-info">
                <p><i class="bi bi-shield-check me-2"></i>CHMSU OJT Route System - Protected Area</p>
                <small>If you believe this is an error, please contact your system administrator</small>
            </div>
        </div>
    </div>
    
    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Log the access attempt (in production, this would send to server)
            console.log('403 Forbidden: Access denied to ' + window.location.href);
            
            // Add click sound effect to buttons (optional)
            const buttons = document.querySelectorAll('.btn-home, .btn-login');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // Add a subtle click effect
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 100);
                });
            });
        });
    </script>
</body>
</html>