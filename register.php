<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        $error = 'Password is required';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Generate a random username
        $username = generateUsername();
        $result = registerUser($username, $password);

        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            // Auto-login after registration
            $loginResult = loginUser($username, $password);
            if (isset($loginResult['success'])) {
                header('Location: index.php');
                exit;
            } else {
                $error = 'Registration successful but login failed. Please try logging in.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Grey Shot</title>
    <link rel="stylesheet" href="assets/css/style.css">
   
</head>
<body>
    <header>
        <h1>Grey Shot</h1>
        <p class="tagline">Share one truth, discover many.</p>
    </header>

    <main>
        <div class="auth-container">
            <h2>Create Your Account</h2>
            
            <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <p class="info-text">
                To maintain anonymity, we'll generate a unique username for you.
                You'll see it after registration.
            </p>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="button">Create Account</button>
            </form>

            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Grey Shot. Share your truth, find your light.</p>
    </footer>
</body>
</html> 