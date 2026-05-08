<?php
require_once 'config/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Use Prepared Statements to prevent SQL Injection
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Invalid username or password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Payroll</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #4cc9f0;
            --bg: #f8f9fe;
        }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #4361ee 0%, #3f37c9 100%);
            height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            margin: 0;
        }
        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }
        .login-card { 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            transition: all 0.2s;
        }
        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.15);
            border-color: var(--primary);
            background-color: #fff;
        }
        .btn-primary { 
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border: none;
            border-radius: 12px;
            padding: 14px; 
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(67, 97, 238, 0.4);
        }
        .brand-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 24px;
            box-shadow: 0 10px 20px -5px rgba(67, 97, 238, 0.3);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="text-center mb-5">
                <div class="brand-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">Payroll</h3>
                <p class="text-muted small">Sign in to manage your workforce</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger border-0 rounded-3 py-2 small d-flex align-items-center mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="far fa-user"></i></span>
                        <input type="text" name="username" class="form-control border-start-0" required placeholder="admin">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="far fa-lock"></i></span>
                        <input type="password" name="password" class="form-control border-start-0" required placeholder="••••••••">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">Sign In</button>
            </form>
            
            <div class="text-center mt-4 border-top pt-4">
                <div class="p-2 bg-light rounded-3 d-inline-block px-3">
                    <small class="text-muted" style="font-size: 0.75rem;">
                        <i class="fas fa-info-circle me-1"></i> Default: <strong>admin</strong> / <strong>password</strong>
                    </small>
                </div>
            </div>
        </div>
        <p class="text-center text-white-50 mt-4 small">&copy; <?= date('Y') ?> Payroll Management System.</p>
    </div>
</body>
</html>
