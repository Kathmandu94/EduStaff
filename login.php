<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: admin.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Google Sign-In Logic
    if (isset($_POST['google_credential'])) {
        $credential = $_POST['google_credential'];
        
        // NOTE FOR USER: 
        // To complete this backend securely, you need to install the Google API PHP Client
        // using Composer (`composer require google/apiclient`) and verify the $credential token.
        // Once verified, you would check if the Google Email exists in your database.
        
        // Example logic:
        /*
        $client = new Google_Client(['client_id' => 'YOUR_GOOGLE_CLIENT_ID']);
        $payload = $client->verifyIdToken($credential);
        if ($payload) {
            $email = $payload['email'];
            // SELECT * FROM users WHERE email = ?
            // Login user...
        }
        */
        
        $error = "Google UI integrated. To complete login, provide Client ID and implement PHP token verification.";
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
                    
                    header('Location: admin.php');
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
    
    <div style="margin: 2rem 0; display: flex; align-items: center; justify-content: center; gap: 1rem;">
        <hr style="flex-grow: 1; border: none; border-top: 1px solid var(--border-color);">
        <span style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500;">OR</span>
        <hr style="flex-grow: 1; border: none; border-top: 1px solid var(--border-color);">
    </div>

    <!-- Google Identity Services Front-End -->
    <div id="g_id_onload"
         data-client_id="YOUR_GOOGLE_CLIENT_ID" 
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
