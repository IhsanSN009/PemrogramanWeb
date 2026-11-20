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
            background: '#ffffff',
            color: '#2a3042',
            confirmButtonColor: '#00c292'
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
                background: '#ffffff',
                color: '#2a3042',
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
                background: '#ffffff',
                color: '#2a3042',
                confirmButtonColor: '#f46a6a'
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Heebo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome & Bootstrap -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/calibramed.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        body {
            background-color: #f4f6f9 !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-card {
            background-color: #ffffff;
            border: 1px solid #e9ecf2;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            max-width: 480px;
            width: 100%;
            overflow: hidden;
        }
        .login-header-purple {
            background: linear-gradient(135deg, #6f42c1 0%, #1b3d6d 100%);
            padding: 1.5rem;
            color: #ffffff;
            text-align: center;
        }
        .demo-btn {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
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
            border-color: #00c292;
            background: #ffffff;
            color: #1e293b;
            box-shadow: 0 2px 6px rgba(0, 194, 146, 0.15);
        }
    </style>
</head>
<body>

    <div class="login-card">
        <!-- Dual Header Top Box (Purple to Navy) -->
        <div class="login-header-purple">
            <div class="d-inline-flex align-items-center justify-content-center p-2 rounded-circle mb-2" style="background: rgba(255, 255, 255, 0.2); width: 44px; height: 44px;">
                <i class="fa fa-heartbeat text-white fa-lg"></i>
            </div>
            <h4 class="fw-bold text-white mb-0">CALIBRAMED</h4>
            <div class="small text-white-50 text-uppercase" style="font-size: 0.68rem; letter-spacing: 1.5px;">
                Biomedical Calibration & Hospital Maintenance System
            </div>
        </div>

        <div class="p-4 p-md-4">
            <!-- Form Login -->
            <form method="POST" action="login.php" id="loginForm">
                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold">Email atau Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa fa-user"></i></span>
                        <input type="text" name="identifier" id="identifier" class="form-control" placeholder="Contoh: owner@calibramed.com" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small text-muted fw-bold">Kata Sandi</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="fa fa-lock"></i></span>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password..." required>
                    </div>
                </div>

                <button type="submit" class="btn btn-calibra-primary w-100 py-2 mb-3">
                    <i class="fa fa-sign-in-alt me-1"></i> Masuk ke Sistem
                </button>
            </form>

            <!-- Quick 1-Click Demo Access Box -->
            <div class="p-3 rounded-3 mt-2 border" style="background: #fbfcfe;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-bold text-dark"><i class="fa fa-bolt me-1 text-warning"></i>Akses Cepat 1-Klik (Demo UTS):</span>
                    <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">Auto-Fill & Login</span>
                </div>
                
                <!-- Button Owner -->
                <button type="button" class="demo-btn" onclick="quickLogin('owner@calibramed.com', 'owner123')">
                    <div>
                        <strong class="text-dark"><i class="fa fa-crown me-1 text-warning"></i>1. OWNER / CEO</strong>
                        <div class="text-muted" style="font-size: 0.7rem;">Prof. Dr.Eng. Ir. Ihsan Setyo Nugroho</div>
                    </div>
                    <span class="badge" style="background:#fff8e8; color:#b78103; border:1px solid #ffe7b3;">Super Admin</span>
                </button>

                <!-- Button Teknisi -->
                <button type="button" class="demo-btn" onclick="quickLogin('teknisi@calibramed.com', 'teknisi123')">
                    <div>
                        <strong class="text-dark"><i class="fa fa-wrench me-1 text-info"></i>2. TEKNISI PT</strong>
                        <div class="text-muted" style="font-size: 0.7rem;">Eng. Dimas Prasetyo, S.Tr.TEM</div>
                    </div>
                    <span class="badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd;">Engineer</span>
                </button>

                <!-- Button Klien RS -->
                <button type="button" class="demo-btn" onclick="quickLogin('soetomo@hospital.com', 'klien123')">
                    <div>
                        <strong class="text-dark"><i class="fa fa-hospital me-1 text-success"></i>3. KLIEN RS</strong>
                        <div class="text-muted" style="font-size: 0.7rem;">dr. Hendra (RSUD Dr. Soetomo)</div>
                    </div>
                    <span class="badge" style="background:#e8f9f2; color:#008764; border:1px solid #c1edd8;">Hospital</span>
                </button>
            </div>

            <!-- Footer Links -->
            <div class="text-center mt-3 pt-1">
                <small class="text-muted">
                    Belum memiliki akun? <a href="register.php" class="text-success text-decoration-none fw-bold">Registrasi Akun Baru</a>
                </small>
            </div>
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
