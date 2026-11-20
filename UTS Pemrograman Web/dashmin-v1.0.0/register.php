<?php
require_once __DIR__ . '/config/database.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

// Ambil daftar 10 rumah sakit untuk pilihan klien
$hospitals = $pdo->query("SELECT id, name, city FROM hospitals ORDER BY name ASC")->fetchAll();

$alertScript = '';

// Proses Registrasi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email        = clean($_POST['email'] ?? '');
    $first_name   = clean($_POST['first_name'] ?? '');
    $last_name    = clean($_POST['last_name'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';
    $role         = clean($_POST['role'] ?? 'klien');
    $hospital_id  = !empty($_POST['hospital_id']) ? (int)$_POST['hospital_id'] : null;

    // Otomatis membentuk username dari nama depan dan nama belakang
    $base_user = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $first_name) . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $last_name));
    if (empty($base_user) || $base_user === '.') {
        $base_user = 'user.' . rand(100, 999);
    }
    $username = $base_user;

    // Validasi
    if (empty($email) || empty($first_name) || empty($last_name) || empty($password)) {
        $alertScript = "Swal.fire({
            icon: 'warning',
            title: 'Formulir Belum Lengkap',
            text: 'Harap lengkapi semua kolom yang wajib diisi!',
            background: '#1e293b',
            color: '#f8fafc',
            confirmButtonColor: '#0ea5e9'
        });";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $alertScript = "Swal.fire({
            icon: 'error',
            title: 'Format Email Tidak Valid',
            text: 'Masukkan alamat email yang valid!',
            background: '#1e293b',
            color: '#f8fafc'
        });";
    } elseif ($password !== $confirm_pass) {
        $alertScript = "Swal.fire({
            icon: 'error',
            title: 'Password Tidak Cocok',
            text: 'Konfirmasi password tidak sama dengan password awal!',
            background: '#1e293b',
            color: '#f8fafc'
        });";
    } elseif (strlen($password) < 6) {
        $alertScript = "Swal.fire({
            icon: 'warning',
            title: 'Password Kurang Kuat',
            text: 'Password minimal terdiri dari 6 karakter.',
            background: '#1e293b',
            color: '#f8fafc'
        });";
    } else {
        // Cek apakah email sudah terdaftar
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $alertScript = "Swal.fire({
                icon: 'error',
                title: 'Email Sudah Terdaftar',
                text: 'Email ini sudah digunakan oleh akun lain, silakan gunakan email lain atau login.',
                background: '#1e293b',
                color: '#f8fafc'
            });";
        } else {
            // Pastikan username unik
            $chkUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $chkUser->execute([$username]);
            if ($chkUser->fetch()) {
                $username = $username . rand(10, 99);
            }

            // Bentuk nama lengkap
            $fullTitle = trim($first_name . ' ' . $last_name);
            if ($role === 'teknisi') {
                $fullTitle = 'Eng. ' . $fullTitle . ', S.Tr.TEM';
            }

            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            $insertStmt = $pdo->prepare("INSERT INTO users (email, username, first_name, last_name, full_title, password_hash, role, hospital_id) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->execute([
                $email,
                $username,
                $first_name,
                $last_name,
                $fullTitle,
                $passwordHash,
                $role,
                ($role === 'klien' ? $hospital_id : null)
            ]);

            $alertScript = "Swal.fire({
                icon: 'success',
                title: 'Registrasi Berhasil!',
                html: 'Akun Anda berhasil dibuat dengan username: <strong>$username</strong>.<br>Silakan login.',
                background: '#1e293b',
                color: '#f8fafc',
                confirmButtonColor: '#10b981'
            }).then(() => {
                window.location.href = 'login.php';
            });";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Registrasi Akun - CALIBRAMED SYSTEM</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
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
        .register-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            max-width: 540px;
            width: 100%;
        }
    </style>
</head>
<body>

    <div class="register-card p-4 p-md-5">
        <!-- Brand Header -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center p-3 rounded-circle mb-2" style="background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3);">
                <i class="fa fa-user-plus text-success fa-2x"></i>
            </div>
            <h4 class="fw-bold text-light mb-0">Registrasi Akun Baru</h4>
            <div class="small text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 1px;">
                CALIBRAMED Medical Equipment Management System
            </div>
        </div>

        <!-- Form Registrasi -->
        <form method="POST" action="register.php" id="registerForm">
            <!-- Email -->
            <div class="mb-3">
                <label class="form-label small text-secondary">Alamat Email <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-info"><i class="fa fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="nama@faskes.com" required>
                </div>
            </div>

            <!-- Nama Awal & Nama Akhir (Otomatis Membentuk Username) -->
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label small text-secondary">Nama Awal <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" id="first_name" class="form-control" placeholder="Contoh: Budi" required oninput="previewUsername()">
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-secondary">Nama Akhir <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Contoh: Santoso" required oninput="previewUsername()">
                </div>
                <div class="col-12 mt-1">
                    <small class="text-muted" style="font-size: 0.75rem;">
                        <i class="fa fa-info-circle me-1 text-info"></i>Username otomatis dibentuk: <strong id="username_preview" class="text-info">-</strong>
                    </small>
                </div>
            </div>

            <!-- Pilihan Peran (Role) -->
            <div class="mb-3">
                <label class="form-label small text-secondary">Pilihan Peran Pengguna <span class="text-danger">*</span></label>
                <select name="role" id="role_select" class="form-select" onchange="toggleHospitalField()" required>
                    <option value="klien">🏥 Klien Rumah Sakit / Faskes (Customer View)</option>
                    <option value="teknisi">🔧 Teknisi PT (Biomedical Calibration Engineer)</option>
                </select>
            </div>

            <!-- Pilih RS (Hanya Muncul Jika Peran = Klien) -->
            <div class="mb-3" id="hospital_field">
                <label class="form-label small text-secondary">Pilih Fasilitas Kesehatan Mitra</label>
                <select name="hospital_id" class="form-select">
                    <option value="">-- Pilih Rumah Sakit Anda --</option>
                    <?php foreach ($hospitals as $h): ?>
                        <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?> (<?= htmlspecialchars($h['city']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Password & Konfirmasi -->
            <div class="row g-2 mb-4">
                <div class="col-md-6">
                    <label class="form-label small text-secondary">Kata Sandi <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Min 6 karakter" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small text-secondary">Konfirmasi Sandi <span class="text-danger">*</span></label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi sandi" required>
                </div>
            </div>

            <button type="submit" class="btn btn-calibra-success w-100 py-2 mb-3">
                <i class="fa fa-user-check me-1"></i> Daftar Sekarang
            </button>
        </form>

        <div class="text-center mt-2">
            <small class="text-muted">
                Sudah memiliki akun? <a href="login.php" class="text-info text-decoration-none fw-medium">Masuk di sini</a>
            </small>
        </div>
    </div>

    <!-- Script Preview Username & Toggle Field -->
    <script>
        function previewUsername() {
            const first = document.getElementById('first_name').value.trim().toLowerCase().replace(/[^a-z0-9]/g, '');
            const last = document.getElementById('last_name').value.trim().toLowerCase().replace(/[^a-z0-9]/g, '');
            const preview = document.getElementById('username_preview');
            if (first || last) {
                preview.textContent = (first ? first : 'user') + '.' + (last ? last : 'name');
            } else {
                preview.textContent = '-';
            }
        }

        function toggleHospitalField() {
            const role = document.getElementById('role_select').value;
            const hospField = document.getElementById('hospital_field');
            if (role === 'klien') {
                hospField.style.display = 'block';
            } else {
                hospField.style.display = 'none';
            }
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
