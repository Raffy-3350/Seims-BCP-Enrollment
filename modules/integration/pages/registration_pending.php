<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Pending - BCP Enrollment System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        
        h1 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .status {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 1rem;
            border-radius: 5px;
            margin: 1.5rem 0;
        }
        
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 0.8rem 2rem;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
            margin-top: 1rem;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .footer {
            margin-top: 2rem;
            font-size: 0.9rem;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">⏳</div>
        <h1>Registration Pending</h1>
        
        <div class="status">
            <strong>Your registration is currently under review.</strong><br>
            Our admin team will process your application within 24-48 hours.
        </div>
        
        <p>
            You will receive an email notification once your registration is approved. 
            After approval, you will receive your login credentials to access the system.
        </p>
        
        <p>
            <strong>Registration ID:</strong> <span id="registrationId"></span>
        </p>
        
        <a href="../pages/login.php" class="btn">Go to Login</a>
        
        <div class="footer">
            BCP Enrollment System © 2026
        </div>
    </div>
    
    <script>
        // Get registration ID from URL parameter if available
        const urlParams = new URLSearchParams(window.location.search);
        const regId = urlParams.get('id');
        if (regId) {
            document.getElementById('registrationId').textContent = regId;
        }
    </script>
</body>
</html>
