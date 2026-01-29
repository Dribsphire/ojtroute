<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="images/CHMSU.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Developers</title>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
        :root{
        --base-clr: #11121a;
        --line-clr: #42434a;
        --hover-clr: #222533;
        --text-clr: #e6e6ef;
        --accent-clr: #1ad21c;
        --secondary-text-clr: #b0b3c1;
        }
        *{
        margin: 0;
        padding: 0;
        }
        html{
        font-family: Poppins, 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.5rem;
        }
        body{
        background-color: var(--base-clr);
        color: var(--text-clr);
        }
        .developers-section {
            padding: 2em 0;
        }
        
        .developers-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 2em;
            margin-top: 2em;
        }
        
        .developer-card {
            text-align: center;
            max-width: 300px;
            flex: 1;
            min-width: 250px;
        }
        
        .profile-circle {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-clr), #0f9b0f);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1em;
            border: 3px solid var(--line-clr);
            box-shadow: 0 4px 15px rgba(26, 210, 28, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .profile-circle:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(26, 210, 28, 0.3);
        }
        
        .profile-icon {
            font-size: 3em;
            color: white;
        }
        
        .profile-icon img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--line-clr);
        }
        
        .developer-name {
            font-size: 1.4em;
            font-weight: 600;
            color: var(--text-clr);
            margin-bottom: 0.5em;
        }
        
        .developer-role {
            color: var(--accent-clr);
            font-weight: 500;
            margin-bottom: 1em;
            font-size: 0.95em;
        }
        
        .developer-description {
            color: var(--secondary-text-clr);
            line-height: 1.6;
            font-size: 0.9em;
        }
        
        .back-button {
            background-color: var(--accent-clr);
            color: var(--base-clr);
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 2em;
            text-decoration: none;
            display: inline-block;
        }
        
        .back-button:hover {
            background-color: #0f9b0f;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(26, 210, 28, 0.3);
        }
        
        @media (max-width: 768px) {
            main{
                padding: 2em;
            }
            h1{
                font-size: 1.8em;
                line-height: 1.3;
                margin-bottom: 1em;
                word-wrap: break-word;
                padding: 0 10px;
            }
            .developers-container {
                flex-direction: column;
                align-items: center;
            }
            
            .developer-card {
                max-width: 100%;
            }
            
            .back-button {
                padding: 10px 25px;
                font-size: 0.9em;
                margin: 2em auto;
                display: block;
                width: fit-content;
            }
        }
    </style>
</head>
<body style="text-align: center;">
    <main>
            <br>
            <h1>Meet Our CoughStone Team</h1><br>
            <p>Dedicated team of developers passionate about creating innovative solutions for our project.</p>
            
            <div class="developers-section">
                <div class="developers-container">
                    
                    <!-- Project Manager/Documentator -->
                    <div class="developer-card">
                        <div class="profile-circle">
                            <div class="profile-icon"><img src="../storage/images/pia.png"></div>
                        </div><br>
                        <h3 class="developer-name">Pia Juliana</h3>
                        <p class="developer-role">Project Manager</p>
                        <p class="developer-description">
                            Pia leads our project management efforts and oversees all documentation processes. 
                            She ensures that project milestones are met on time and maintains comprehensive 
                            documentation for development processes, user guides, and technical specifications.
                        </p>
                    </div>
                    
                    <!-- Documentator -->
                    <div class="developer-card">
                        <div class="profile-circle">
                            <div class="profile-icon"><img src="../storage/images/kyla.png "></div>
                        </div><br>
                        <h3 class="developer-name">Kyla</h3>
                        <p class="developer-role">Documentator</p>
                        <p class="developer-description">
                            Kyla specializes in creating clear and comprehensive documentation for our projects. 
                            She works closely with the team to produce user manuals, API documentation, 
                            and technical guides that makes it accessible to all users.
                        </p>
                    </div>

                    <!-- Frontend/Backend Developer -->
                    <div class="developer-card">
                        <div class="profile-circle">
                            <div class="profile-icon"><img src="../storage/images/manuel.png"></div>
                        </div><br>
                        <h3 class="developer-name">Manuel</h3>
                        <p class="developer-role">Developer</p>
                        <p class="developer-description">
                            Manuel is the developer 
                        </p>
                    </div>
                </div>
                <a href="login.php" class="back-button">Back</a>
            </div>
            
        
    </main>
</body>
</html>