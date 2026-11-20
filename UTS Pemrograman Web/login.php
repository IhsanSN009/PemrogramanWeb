<?php
require_once __DIR__ . '/config/database.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$alertScript = '';

// Proses Login POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        $alertScript = "Swal.fire({
            icon: 'warning',
            title: 'Data Belum Lengkap',
            text: 'Harap masukkan Email/Username dan Password.',
            background: '#1e293b',
            color: '#f8fafc',
            confirmButtonColor: '#0ea5e9'
        });";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']     = $user['id'];
            $_SESSION['username']    = $user['username'];
            $_SESSION['full_title']  = $user['full_title'];
            $_SESSION['role']        = $user['role'];
            $_SESSION['actual_role'] = $user['role'];
            $_SESSION['hospital_id'] = $user['hospital_id'];

            $welcomeName = addslashes($user['full_title']);
            $alertScript = "Swal.fire({
                icon: 'success',
                title: 'Autentikasi Berhasil',
                html: 'Selamat datang kembali,<br><strong>$welcomeName</strong>',
                background: '#1e293b',
                color: '#f8fafc',
                timer: 1600,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'index.php';
            });";
        } else {
            $alertScript = "Swal.fire({
                icon: 'error',
                title: 'Akses Ditolak',
                text: 'Email/Username atau Password tidak sesuai dengan database!',
                background: '#1e293b',
                color: '#f8fafc',
                confirmButtonColor: '#ef4444'
            });";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Login - CALIBRAMED SYSTEM</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome & Bootstrap -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/calibramed.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body {
            background: radial-gradient(circle at 10% 20%, rgba(14, 165, 233, 0.08) 0%, transparent 40%),
                        radial-gradient(circle at 90% 80%, rgba(16, 185, 129, 0.08) 0%, transparent 40%),
                        #0a0e1a !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            max-width: 480px;
            width: 100%;
            overflow: hidden;
        }
        .demo-btn {
            border: 1px solid #334155;
            background: #141d2d;
            color: #cbd5e1;
            transition: all 0.2s ease;
            text-align: left;
            font-size: 0.8rem;
            padding: 0.55rem 0.85rem;
            border-radius: 8px;
            width: 100%;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .demo-btn:hover {
            border-color: #0ea5e9;
            background: #1e2c45;
            color: #f8fafc;
        }
    </style>
</head>
<body>

    <div class="login-card p-4 p-md-5">
        <!-- Brand Header -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle mb-2" style="background: rgba(14, 165, 233, 0.15); border: 1px solid rgba(14, 165, 233, 0.3);">
                <i class="fa fa-heartbeat text-info fa-2x"></i>
            </div>
            <h4 class="fw-bold text-light mb-0">CALIBRA<span class="text-info">MED</span></h4>
            <div class="small text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 1.5px;">
                Biomedical Calibration & Hospital Maintenance System
            </div>
        </div>

        <!-- Form Login -->
        <form method="POST" action="login.php" id="loginForm">
            <div class="mb-3">
                <label class="form-label small text-secondary">Email atau Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-info"><i class="fa fa-user"></i></span>
                    <input type="text" name="identifier" id="identifier" class="form-control" placeholder="Contoh: owner@calibramed.com" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small text-secondary">Kata Sandi</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-info"><i class="fa fa-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password..." required>
                </div>
            </div>

            <button type="submit" class="btn btn-calibra-primary w-100 py-2 mb-3">
                <i class="fa fa-sign-in-alt me-1"></i> Masuk ke Sistem
            </button>
        </form>

        <!-- Quick 1-Click Demo Access Box -->
        <div class="p-3 rounded-3 mt-3" style="background: #141d2d; border: 1px dashed #334155;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small fw-bold text-info"><i class="fa fa-bolt me-1 text-warning"></i>Akses Cepat 1-Klik (Demo UTS):</span>
                <span class="badge bg-dark border border-secondary text-muted" style="font-size: 0.65rem;">Auto-Fill & Login</span>
            </div>
            
            <!-- Button Owner -->
            <button type="button" class="demo-btn" onclick="quickLogin('owner@calibramed.com', 'owner123')">
                <div>
                    <strong class="text-warning"><i class="fa fa-crown me-1"></i>1. OWNER / CEO</strong>
                    <div class="text-muted" style="font-size: 0.7rem;">Prof. Dr.Eng. Ir. Ihsan Setyo Nugroho</div>
                </div>
                <span class="badge bg-warning text-dark">Super Admin</span>
            </button>

            <!-- Button Teknisi -->
            <button type="button" class="demo-btn" onclick="quickLogin('teknisi@calibramed.com', 'teknisi123')">
                <div>
                    <strong class="text-info"><i class="fa fa-wrench me-1"></i>2. TEKNISI PT</strong>
                    <div class="text-muted" style="font-size: 0.7rem;">Eng. Dimas Prasetyo, S.Tr.TEM</div>
                </div>
                <span class="badge bg-info text-dark">Engineer</span>
            </button>

            <!-- Button Klien RS -->
            <button type="button" class="demo-btn" onclick="quickLogin('soetomo@hospital.com', 'klien123')">
                <div>
                    <strong class="text-success"><i class="fa fa-hospital me-1"></i>3. KLIEN RS</strong>
                    <div class="text-muted" style="font-size: 0.7rem;">dr. Hendra (RSUD Dr. Soetomo)</div>
                </div>
                <span class="badge bg-success">Hospital</span>
            </button>
        </div>

        <!-- Footer Links -->
        <div class="text-center mt-3 pt-2">
            <small class="text-muted">
                Belum memiliki akun? <a href="register.php" class="text-info text-decoration-none fw-medium">Registrasi Akun Baru</a>
            </small>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script>
        function quickLogin(user, pass) {
            $('#identifier').val(user);
            $('#password').val(pass);
            $('#loginForm').submit();
        }
    </script>
    <?php if ($alertScript): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?= $alertScript ?>
        });
    </script>
    <?php endif; ?>
</body>
</html>
