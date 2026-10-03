<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$info = '';
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $info = 'Your session has expired due to inactivity. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Google Sign-In Logic
    if (isset($_POST['google_credential'])) {
        $credential = $_POST['google_credential'];
        $google_client_id = '518752792189-15frprgg9bf9afe1f7vm3dhtk15bo7tk.apps.googleusercontent.com';
        
        $email = null;
        
        // Verify token with Google's API endpoint
        $verify_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
        $response = @file_get_contents($verify_url);
        
        if ($response !== false) {
            $payload = json_decode($response, true);
            if (!empty($payload['email']) && isset($payload['aud']) && $payload['aud'] === $google_client_id) {
                $email = $payload['email'];
            }
        } else {
            // Fallback decode JWT payload if direct HTTP request is blocked
            $parts = explode('.', $credential);
            if (count($parts) === 3) {
                $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                if (!empty($payload['email'])) {
                    $email = $payload['email'];
                }
            }
        }
        
        if ($email) {
            // Check if user exists in database
            $stmt = $conn->prepare("SELECT id, username, role FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['login_success'] = true;
                
                header('Location: index.php');
                exit;
            } else {
                $error = "No EduStaff account found for " . htmlspecialchars($email) . ". Please register first below!";
            }
            $stmt->close();
        } else {
            $error = "Google Sign-In failed or token is invalid.";
        }
    } 
    // 2. Standard Username/Password Logic
    else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $stmt = $conn->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($user = $result->fetch_assoc()) {
                if (password_verify($password, $user['password_hash'])) {
                    // Login success
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['login_success'] = true;
                    
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Invalid credentials.';
                }
            } else {
                $error = 'Invalid credentials.';
            }
            $stmt->close();
        }
    }
}

include 'header.php';
?>

<div class="auth-card">
    <div class="text-center mb-2">
        <i class="fa-solid fa-graduation-cap" style="font-size: 3rem; color: var(--primary-color);"></i>
        <h2 style="margin-top: 1rem; color: var(--text-main);">EduStaff</h2>
        <p style="color: var(--text-muted);">Sign in to your account</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($info): ?>
        <div class="alert alert-info" style="margin-bottom: 1.5rem; background-color: #e0f2fe; color: #0284c7; padding: 1rem; border-radius: 8px; border: 1px solid #bae6fd;">
            <i class="fa-solid fa-clock-rotate-left"></i> <?php echo htmlspecialchars($info); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label" for="username">Username</label>
            <div style="position: relative;">
                <i class="fa-solid fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="text" id="username" name="username" class="form-control" style="padding-left: 2.75rem;" placeholder="Enter username" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div style="position: relative;">
                <i class="fa-solid fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                <input type="password" id="password" name="password" class="form-control" style="padding-left: 2.75rem;" placeholder="Enter password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
            Sign In <i class="fa-solid fa-arrow-right"></i>
        </button>
    </form>
    
    <div style="text-align: center; margin-top: 1.5rem;">
        <span style="color: var(--text-muted);">Don't have an account?</span>
        <a href="register.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none; margin-left: 0.5rem;">Register here</a>
    </div>
    
    <div style="margin: 2rem 0; display: flex; align-items: center; justify-content: center; gap: 1rem;">
        <hr style="flex-grow: 1; border: none; border-top: 1px solid var(--border-color);">
        <span style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">OR</span>
        <hr style="flex-grow: 1; border: none; border-top: 1px solid var(--border-color);">
    </div>

    <!-- Google Identity Services Front-End -->
    <div id="g_id_onload"
         data-client_id="518752792189-15frprgg9bf9afe1f7vm3dhtk15bo7tk.apps.googleusercontent.com" 
         data-context="signin"
         data-ux_mode="popup"
         data-callback="handleGoogleLogin"
         data-auto_prompt="false">
    </div>

    <div class="g_id_signin"
         data-type="standard"
         data-shape="rectangular"
         data-theme="outline"
         data-text="signin_with"
         data-size="large"
         data-logo_alignment="center"
         style="display: flex; justify-content: center;">
    </div>
    
    <form id="google-login-form" method="POST" style="display: none;">
        <input type="hidden" name="google_credential" id="google_credential">
    </form>
    
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        function handleGoogleLogin(response) {
            // Put credential token into hidden input and submit
            document.getElementById('google_credential').value = response.credential;
            document.getElementById('google-login-form').submit();
        }
    </script>
</div>

<?php include 'footer.php'; ?>
