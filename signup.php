<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Create Account | Task Management System</title>
	<!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="css/auth.css">
</head>
<body class="auth-body">
      
      <div class="auth-container">
            <!-- Left Side: Branding -->
            <div class="auth-left">
                <div class="auth-left-content">
                    <h2>Start managing your tasks in minutes.</h2>
                    <p>Create an account and get instant access to powerful task management tools and team collaboration features.</p>
                    
                    <div class="auth-feature-list">
                        <div class="auth-feature-item">
                            <div class="auth-feature-icon">
                                <i class="fa fa-rocket"></i>
                            </div>
                            <div class="auth-feature-text">
                                <h4>Easy Setup</h4>
                                <p>Register in seconds and start collaborating with your team instantly</p>
                            </div>
                        </div>
                        
                        <div class="auth-feature-item">
                            <div class="auth-feature-icon">
                                <i class="fa fa-shield"></i>
                            </div>
                            <div class="auth-feature-text">
                                <h4>Role-Based Access</h4>
                                <p>Choose your role and get appropriate permissions for your workflow</p>
                            </div>
                        </div>
                        
                        <div class="auth-feature-item">
                            <div class="auth-feature-icon">
                                <i class="fa fa-desktop"></i>
                            </div>
                            <div class="auth-feature-text">
                                <h4>Real-time Monitoring</h4>
                                <p>Track your progress and performance with live updates 24/7</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side: Signup Form -->
            <div class="auth-right">
                <div class="auth-logos">
                    <img src="img/logo.png" alt="Logo 1" class="auth-logo-img">
                    <img src="img/logo2.png" alt="Logo 2" class="auth-logo-img">
                </div>
                <h3 class="auth-title">Create Account</h3>
                <p class="auth-subtitle">Join the Task Management System</p>

                <?php if (isset($_GET['error'])) { ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($_GET['error']) ?>
                    </div>
                <?php } ?>

                <div class="auth-info-box">
                    Enter your details to create an <strong>Employee</strong> account. A secure password will be emailed to you.
                </div>

                <form method="POST" action="app/signup.php">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" placeholder="John Doe" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="text" class="form-control" name="user_name" placeholder="you@example.com" required>
                    </div>
                    
                    <button type="submit" class="btn-primary">Register & Get Password</button>
                </form>

                <div class="auth-footer">
                    Already have an account? <a href="login.php" class="auth-link">Log In</a>
                </div>
            </div>
      </div>
</body>
</html>
