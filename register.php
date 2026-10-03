<?php
require_once 'config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'admin'; // Hardcoded to admin per project requirements
    
    // 1. Check for empty fields
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all required fields.';
    } 
    // 2. Email Validation: Must contain @ and a valid domain
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address containing an @ symbol.';
    } 
    elseif (!preg_match('/@(gmail\.com|hotmail\.com|yahoo\.com|outlook\.com|edu\.[a-z]{2,3}|[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})$/i', $email)) {
        $error = 'Email must belong to an official domain (e.g., gmail.com, hotmail.com) and have a valid extension (.com, etc.).';
    } 
    // 3. Password Validation: 1 Uppercase, 1 Number, 1 Symbol, Min 8 chars
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $error = 'Password must be at least 8 characters and include at least one uppercase letter, one number, and one symbol.';
    } 
    // 4. Confirm Password matching
    elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } 
    else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = 'Username or Email is already registered.';
        } else {
            // Hash password and register new user
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $insert_stmt->bind_param("ssss", $username, $email, $password_hash, $role);
            
            if ($insert_stmt->execute()) {
                $success = 'Registration successful! The new user can now sign in.';
            } else {
                $error = 'Error registering user. Please try again.';
            }
            $insert_stmt->close();
        }
        
        $stmt->close();
    }
}

include 'header.php';
?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 80vh; padding: 2rem 0;">
    <div class="card" style="width: 100%; max-width: 450px; padding: 2.5rem; animation: fadeIn 0.5s ease;">
        <div class="text-center mb-2">
            <div style="background: rgba(79, 70, 229, 0.1); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem auto;">
                <i class="fa-solid fa-user-shield" style="font-size: 2.5rem; color: var(--primary-color);"></i>
            </div>
            <h2 style="color: var(--text-main); font-weight: 700;">Create Account</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem;">Register a new admin or accountant</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
                <div style="margin-top: 1rem;">
                    <a href="login.php" class="btn btn-primary btn-block text-center">Go to Login</a>
                </div>
            </div>
        <?php else: ?>

        <!-- Google Registration Button -->
        <div id="g_id_onload"
             data-client_id="518752792189-15frprgg9bf9afe1f7vm3dhtk15bo7tk.apps.googleusercontent.com"
             data-context="signup"
             data-ux_mode="popup"
             data-callback="handleGoogleSignUp"
             data-auto_prompt="false">
        </div>
        <div class="g_id_signin"
             data-type="standard"
             data-shape="rectangular"
             data-theme="outline"
             data-text="signup_with"
             data-size="large"
             data-logo_alignment="center"
             style="display: flex; justify-content: center; margin-bottom: 1.5rem;">
        </div>

        <div style="display: flex; align-items: center; justify-content: center; gap: 1rem; margin-bottom: 1.5rem;">
            <hr style="flex-grow: 1; border: none; border-top: 1px solid var(--border-color);">
            <span style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">OR COMPLETE DETAILS</span>
            <hr style="flex-grow: 1; border: none; border-top: 1px solid var(--border-color);">
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-envelope" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="email" id="email" name="email" class="form-control" style="padding-left: 2.75rem;" placeholder="e.g. name@gmail.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="text" id="username" name="username" class="form-control" style="padding-left: 2.75rem;" placeholder="Choose a username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>
            </div>


            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="password" id="password" name="password" class="form-control" style="padding-left: 2.75rem;" placeholder="1 Uppercase, 1 Number, 1 Symbol" required>
                </div>
                <small style="color: var(--text-muted); font-size: 0.75rem; display: block; margin-top: 0.25rem;">Min 8 chars, 1 uppercase, 1 number, 1 symbol (!@#$&*).</small>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" for="confirm_password">Confirm Password</label>
                <div style="position: relative;">
                    <i class="fa-solid fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" style="padding-left: 2.75rem;" placeholder="Confirm your password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.875rem;">
                Complete Registration <i class="fa-solid fa-user-plus"></i>
            </button>
        </form>
        
        <?php if (!isLoggedIn()): ?>
        <div style="margin-top: 1.5rem; text-align: center; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
            <span style="color: var(--text-muted); font-size: 0.875rem;">Already have an account?</span> 
            <br>
            <a href="login.php" style="font-weight: 600; display: inline-block; margin-top: 0.5rem;">Sign in to your account</a>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
    function parseJwt (token) {
        try {
            var base64Url = token.split('.')[1];
            var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            var jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));
            return JSON.parse(jsonPayload);
        } catch(e) {
            return null;
        }
    }

    function handleGoogleSignUp(response) {
        const data = parseJwt(response.credential);
        if(data && data.email) {
            // Fill in the email field
            const emailInput = document.getElementById('email');
            emailInput.value = data.email;
            
            // Generate a suggested username from the email handle
            const usernameInput = document.getElementById('username');
            if(!usernameInput.value) {
                usernameInput.value = data.email.split('@')[0] + Math.floor(Math.random() * 100);
            }
            
            // Visually indicate that the email was captured successfully
            emailInput.style.backgroundColor = '#ecfdf5';
            emailInput.style.borderColor = '#10b981';
            
            alert('Google authentication successful! Please complete your username and strong password below to finish registration.');
        }
    }
</script>

<?php include 'footer.php'; ?>
