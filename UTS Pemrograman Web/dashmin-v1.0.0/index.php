<?php
/**
 * CALIBRAMED SYSTEM - Master Enterprise Dashboard
 * Sistem Monitoring Kalibrasi & Pemeliharaan Alat Kesehatan Faskes
 */

require_once __DIR__ . '/config/auth_check.php';

$activeRole = getActiveRole();
$isOwnerUser = isOwner();
$isTeknisiUser = isTeknisi();
$isKlienUser = isKlien();

// Filter data jika role klien murni
$hospitalFilterSql = "";
$queryParams = [];
if ($isKlienUser && !empty($currentUser['hospital_id'])) {
    $hospitalFilterSql = " WHERE d.hospital_id = ? ";
    $queryParams[] = $currentUser['hospital_id'];
}

// 1. Ambil data 10 rumah sakit
$hospitalsStmt = $pdo->query("SELECT * FROM hospitals ORDER BY target_devices DESC");
$hospitals = $hospitalsStmt->fetchAll();

// 2. Ambil data alat medis
$devicesSql = "SELECT d.*, h.name AS hospital_name, h.city AS hospital_city, h.code AS hospital_code 
               FROM devices d 
               JOIN hospitals h ON d.hospital_id = h.id 
               $hospitalFilterSql 
               ORDER BY d.id ASC";
$devicesStmt = $pdo->prepare($devicesSql);
$devicesStmt->execute($queryParams);
$devices = $devicesStmt->fetchAll();

// 3. Kalkulasi statistik KPI
$totalDevices = count($devices);
$countLayak = 0;
$countBersyarat = 0;
$countRusak = 0;
$countMaintenance = 0;
$countQuarantine = 0;

foreach ($devices as $dev) {
    if ($dev['condition_state'] === 'Layak Pakai') $countLayak++;
    elseif ($dev['condition_state'] === 'Layak Bersyarat') $countBersyarat++;
    elseif ($dev['condition_state'] === 'Tidak Layak Pakai') $countRusak++;

    if ($dev['operation_status'] === 'Dalam Maintenance') $countMaintenance++;
    elseif (strpos($dev['operation_status'], 'Karantina') !== false) $countQuarantine++;
}

$pctLayak = $totalDevices > 0 ? round(($countLayak / $totalDevices) * 100, 1) : 0;
$pctMaint = $totalDevices > 0 ? round(($countMaintenance / $totalDevices) * 100, 1) : 0;
$pctRusak = $totalDevices > 0 ? round(($countRusak / $totalDevices) * 100, 1) : 0;

// 4. Data untuk Chart.js (10 RS Mitra)
$chartLabels = [];
$chartTargets = [];
$chartCompleted = [];
$chartMaintenance = [];
$chartQuarantine = [];

foreach ($hospitals as $h) {
    $chartLabels[] = $h['name'];
    $chartTargets[] = (int)$h['target_devices'];
    $chartCompleted[] = (int)$h['completed_devices'];
    $chartMaintenance[] = (int)$h['maintenance_devices'];
    $chartQuarantine[] = (int)$h['quarantine_devices'];
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>CALIBRAMED - Sistem Manajemen Kalibrasi & Pemeliharaan Alat Kesehatan</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    
    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/calibramed.css" rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="container-xxl position-relative d-flex p-0">
        <!-- Sidebar Start -->
        <div class="sidebar pb-3">
            <nav class="navbar navbar-dark">
                <!-- Brand Logo -->
                <a href="index.php" class="navbar-brand mx-4 mb-3 d-flex align-items-center">
                    <div class="kpi-icon-wrap kpi-cyan me-2" style="width: 38px; height: 38px; font-size: 1.25rem;">
                        <i class="fa fa-heartbeat"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 text-info fw-bold">CALIBRA<span class="text-light">MED</span></h4>
                        <div class="text-muted text-uppercase" style="font-size: 0.62rem; letter-spacing: 1px;">Biomedical System</div>
                    </div>
                </a>

                <!-- User Profile Box -->
                <div class="sidebar-user-box">
                    <div class="d-flex align-items-center">
                        <div class="position-relative">
                            <img class="rounded-circle border border-2 border-info" src="img/<?= htmlspecialchars($currentUser['avatar']) ?>" alt="User Avatar" style="width: 44px; height: 44px; object-fit: cover;">
                            <div class="bg-success rounded-circle border border-2 border-dark position-absolute end-0 bottom-0 p-1"></div>
                        </div>
                        <div class="ms-3 overflow-hidden">
                            <h6 class="mb-0 text-light text-truncate" style="font-size: 0.85rem;" title="<?= htmlspecialchars($currentUser['full_title']) ?>">
                                <?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>
                            </h6>
                            <?php if ($currentUser['role'] === 'owner'): ?>
                                <span class="badge bg-warning text-dark fw-bold" style="font-size: 0.65rem;">👑 OWNER & CEO</span>
                            <?php elseif ($currentUser['role'] === 'teknisi'): ?>
                                <span class="badge bg-info text-dark fw-bold" style="font-size: 0.65rem;">🔧 TEKNISI PT</span>
                            <?php else: ?>
                                <span class="badge bg-success fw-bold" style="font-size: 0.65rem;">🏥 KLIEN RS</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Soft Red Logout Button Directly Below Profile -->
                    <button type="button" class="btn-sidebar-logout" onclick="confirmLogout()">
                        <i class="fa fa-sign-out-alt"></i> Logout Akun
                    </button>
                </div>

                <!-- Navigation Links with Fixed 2-Line Alignment -->
                <div class="navbar-nav w-100">
                    <a href="index.php" class="nav-item nav-link active">
                        <i class="fa fa-tachometer-alt"></i>
                        <span>Dashboard Utama</span>
                    </a>
                    <a href="#section-charts" class="nav-item nav-link">
                        <i class="fa fa-chart-bar"></i>
                        <span>Grafik Progres 10 RS</span>
                    </a>
                    <a href="#section-schedules" class="nav-item nav-link">
                        <i class="fa fa-calendar-alt"></i>
                        <span>Jadwal Pemeliharaan</span>
                    </a>
                    <a href="#section-devices" class="nav-item nav-link">
                        <i class="fa fa-stethoscope"></i>
                        <span>Monitoring Alat Medis</span>
                    </a>
                    <a href="#section-hospitals" class="nav-item nav-link">
                        <i class="fa fa-hospital"></i>
                        <span>Data 10 Faskes Klien</span>
                    </a>
                    <?php if ($isTeknisiUser || $isOwnerUser): ?>
                    <a href="#section-workbench" class="nav-item nav-link">
                        <i class="fa fa-tools"></i>
                        <span>Ruang Kerja Teknisi</span>
                    </a>
                    <?php endif; ?>
                    <a href="#section-legal" class="nav-item nav-link">
                        <i class="fa fa-award"></i>
                        <span>Legalitas & Sertifikasi</span>
                    </a>
                </div>

                <!-- Legal Compliance Badge -->
                <div class="mt-4 px-3">
                    <div class="p-2 rounded text-center" style="background: rgba(14, 165, 233, 0.06); border: 1px dashed rgba(14, 165, 233, 0.25);">
                        <i class="fa fa-shield-alt text-info me-1"></i>
                        <span class="text-light fw-bold" style="font-size: 0.72rem;">ISO/IEC 17025 Accredited</span>
                        <div class="text-muted" style="font-size: 0.65rem;">Permenkes RI No. 54/2015</div>
                    </div>
                </div>
            </nav>
        </div>
        <!-- Sidebar End -->

        <!-- Content Area Start -->
        <div class="content">
            <!-- Navbar Start -->
            <nav class="navbar navbar-expand sticky-top px-4 py-2">
                <a href="index.php" class="navbar-brand d-flex d-lg-none me-3">
                    <h4 class="text-info mb-0"><i class="fa fa-heartbeat"></i></h4>
                </a>
                <a href="#" class="sidebar-toggler flex-shrink-0 me-3">
                    <i class="fa fa-bars"></i>
                </a>

                <!-- Live Timestamp -->
                <div class="d-none d-md-flex align-items-center text-muted small">
                    <i class="fa fa-clock text-info me-2"></i>
                    <span id="live-timestamp" class="text-light fw-medium">Memuat waktu...</span>
                </div>

                <div class="navbar-nav align-items-center ms-auto">
                    <!-- Simulator Role Switcher (Khusus Owner) -->
                    <?php if ($currentUser['role'] === 'owner'): ?>
                    <div class="nav-item dropdown me-2">
                        <button class="btn btn-sm btn-outline-warning dropdown-toggle" data-bs-toggle="dropdown" title="Simulator Mode Pratinjau">
                            <i class="fa fa-eye me-1"></i> Mode: <strong><?= strtoupper($activeRole) ?></strong>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end border-0 rounded-3 m-0 shadow">
                            <div class="px-3 py-1 small text-muted border-bottom border-secondary">
                                Pratinjau Sudut Pandang Pengguna:
                            </div>
                            <a href="javascript:void(0)" class="dropdown-item py-2" onclick="switchOwnerRole('owner', null)">
                                <i class="fa fa-crown text-warning me-2"></i>Mode Owner (Akses Penuh)
                            </a>
                            <a href="javascript:void(0)" class="dropdown-item py-2" onclick="switchOwnerRole('teknisi', null)">
                                <i class="fa fa-wrench text-info me-2"></i>Mode Teknisi PT (Engineer)
                            </a>
                            <a href="javascript:void(0)" class="dropdown-item py-2" onclick="switchOwnerRole('klien', 1)">
                                <i class="fa fa-hospital text-success me-2"></i>Mode Klien (RSUD Dr. Soetomo)
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Action Button: Tambah Alat Baru (Owner / Teknisi) -->
                    <?php if ($isTeknisiUser || $isOwnerUser): ?>
                    <button class="btn btn-sm btn-calibra-primary me-3 d-none d-sm-inline-flex align-items-center" data-bs-toggle="modal" data-bs-target="#newDeviceModal">
                        <i class="fa fa-plus-circle me-1"></i> Tambah Alat Baru
                    </button>
                    <?php endif; ?>

                    <!-- Urgent Alerts Dropdown -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fa fa-bell text-warning"></i>
                            <span class="badge rounded-pill bg-danger" style="position: relative; top: -8px; left: -6px; font-size: 0.65rem;">
                                <?= $countQuarantine ?>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end border-0 rounded-3 m-0 p-2 shadow" style="width: 310px;">
                            <div class="p-2 border-bottom border-secondary mb-2">
                                <h6 class="mb-0 text-light fw-bold"><i class="fa fa-exclamation-triangle text-danger me-2"></i>Peringatan Alat Kritis</h6>
                                <small class="text-muted"><?= $countQuarantine ?> alat memerlukan tindakan mendesak</small>
                            </div>
                            <div class="p-2 small text-secondary">
                                Terdapat <strong><?= $countQuarantine ?> unit</strong> dalam status Karantina / Rusak dan <?= $countMaintenance ?> unit dalam antrean maintenance.
                            </div>
                            <div class="text-center pt-2">
                                <a href="#section-devices" class="small text-info text-decoration-none">Buka Tabel Monitoring</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="nav-item dropdown ms-3">
                        <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                            <img class="rounded-circle me-lg-2" src="img/<?= htmlspecialchars($currentUser['avatar']) ?>" alt="" style="width: 36px; height: 36px; object-fit: cover;">
                            <span class="d-none d-lg-inline-flex text-light fw-medium">
                                <?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end border-0 rounded-3 m-0 shadow">
                            <div class="px-3 py-2 border-bottom border-secondary">
                                <div class="fw-bold text-light small"><?= htmlspecialchars($currentUser['full_title']) ?></div>
                                <small class="text-info"><?= htmlspecialchars($currentUser['email']) ?></small>
                            </div>
                            <a href="#section-devices" class="dropdown-item py-2"><i class="fa fa-stethoscope me-2 text-info"></i>Monitoring Alat</a>
                            <a href="#section-legal" class="dropdown-item py-2"><i class="fa fa-shield-alt me-2 text-success"></i>Sertifikat & Mutu</a>
                            <hr class="dropdown-divider">
                            <a href="javascript:void(0)" class="dropdown-item text-danger py-2" onclick="confirmLogout()">
                                <i class="fa fa-sign-out-alt me-2"></i>Keluar Sistem
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
            <!-- Navbar End -->

            <!-- Header Welcome Banner Start -->
            <div class="container-fluid pt-4 px-4">
                <div class="calibra-card rounded-3 p-4" style="background: linear-gradient(135deg, #132238 0%, #1e293b 100%) !important; border-left: 5px solid #0ea5e9 !important;">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-info text-dark fw-bold px-2 py-1">CALIBRAMED ENTERPRISE v2.5</span>
                                <?php if ($currentUser['role'] === 'owner'): ?>
                                    <span class="badge bg-warning text-dark fw-bold px-2 py-1">👑 OWNER EXECUTIVE VIEW</span>
                                <?php elseif ($currentUser['role'] === 'teknisi'): ?>
                                    <span class="badge bg-primary text-light px-2 py-1">🔧 BIOMEDICAL ENGINEER VIEW</span>
                                <?php else: ?>
                                    <span class="badge bg-success text-light px-2 py-1">🏥 CUSTOMER VIEW (<?= htmlspecialchars($currentUser['hospital_name'] ?? 'Rumah Sakit') ?>)</span>
                                <?php endif; ?>
                            </div>
                            <h4 class="fw-bold text-light mb-1">
                                Selamat Bertugas, <?= htmlspecialchars($currentUser['full_title']) ?>
                            </h4>
                            <p class="text-secondary mb-0" style="font-size: 0.88rem;">
                                Sistem monitoring kepatuhan kalibrasi dan pemeliharaan alat kesehatan terintegrasi berbasis standar Kemenkes RI No. 54 Tahun 2015 & ISO/IEC 17025.
                            </p>
                        </div>
                        <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                            <?php if ($isTeknisiUser || $isOwnerUser): ?>
                            <button class="btn btn-calibra-primary me-2" data-bs-toggle="modal" data-bs-target="#newDeviceModal">
                                <i class="fa fa-plus-circle me-1"></i> Tambah Alat
                            </button>
                            <?php endif; ?>
                            <a href="#section-devices" class="btn btn-outline-info">
                                <i class="fa fa-search me-1"></i> Cari Alat Medis
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Header Welcome Banner End -->

            <!-- KPI Metric Cards Start -->
            <div class="container-fluid pt-4 px-4">
                <div class="row g-3">
                    <!-- Total Inventaris -->
                    <div class="col-sm-6 col-xl">
                        <div class="kpi-card kpi-total h-100">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small text-uppercase fw-bold" style="font-size: 0.72rem;">Total Alat Terdaftar</span>
                                    <h3 class="mb-0 fw-bold text-light mt-1"><?= $totalDevices ?> <span class="fs-6 text-muted fw-normal">Unit</span></h3>
                                    <div class="small text-info mt-1"><i class="fa fa-check me-1"></i>Tercatat di Database</div>
                                </div>
                                <div class="kpi-icon-wrap kpi-cyan">
                                    <i class="fa fa-laptop-medical"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Layak Pakai (Angka Hijau Lebih Terang & Kontras Tinggi Sesuai Permintaan) -->
                    <div class="col-sm-6 col-xl">
                        <div class="kpi-card kpi-fit h-100">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small text-uppercase fw-bold" style="font-size: 0.72rem;">Kondisi Layak Pakai</span>
                                    <h3 class="mb-0 fw-bold stat-number-fit mt-1"><?= $countLayak ?> <span class="fs-6 text-muted fw-normal">Unit</span></h3>
                                    <div class="small stat-number-fit mt-1">
                                        <i class="fa fa-shield-alt me-1"></i><strong><?= $pctLayak ?>%</strong> Siap Operasional
                                    </div>
                                </div>
                                <div class="kpi-icon-wrap kpi-emerald">
                                    <i class="fa fa-check-double"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dalam Maintenance -->
                    <div class="col-sm-6 col-xl">
                        <div class="kpi-card kpi-maint h-100">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small text-uppercase fw-bold" style="font-size: 0.72rem;">Dalam Maintenance</span>
                                    <h3 class="mb-0 fw-bold text-warning mt-1"><?= $countMaintenance ?> <span class="fs-6 text-muted fw-normal">Unit</span></h3>
                                    <div class="small text-warning mt-1"><i class="fa fa-tools me-1"></i><strong><?= $pctMaint ?>%</strong> Perbaikan</div>
                                </div>
                                <div class="kpi-icon-wrap kpi-amber">
                                    <i class="fa fa-wrench"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tidak Layak / Karantina -->
                    <div class="col-sm-6 col-xl">
                        <div class="kpi-card kpi-unfit h-100">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small text-uppercase fw-bold" style="font-size: 0.72rem;">Karantina / Rusak</span>
                                    <h3 class="mb-0 fw-bold text-danger mt-1"><?= $countRusak ?> <span class="fs-6 text-muted fw-normal">Unit</span></h3>
                                    <div class="small text-danger mt-1"><i class="fa fa-ban me-1"></i><strong><?= $pctRusak ?>%</strong> Kritis</div>
                                </div>
                                <div class="kpi-icon-wrap kpi-rose">
                                    <i class="fa fa-exclamation-triangle"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Klien Mitra 10 RS -->
                    <div class="col-sm-6 col-xl">
                        <div class="kpi-card kpi-clients h-100">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-secondary small text-uppercase fw-bold" style="font-size: 0.72rem;">Mitra Faskes</span>
                                    <h3 class="mb-0 fw-bold text-light mt-1"><?= count($hospitals) ?> <span class="fs-6 text-muted fw-normal">RS/Klinik</span></h3>
                                    <div class="small text-info mt-1"><i class="fa fa-handshake me-1"></i>Terakreditasi KARS</div>
                                </div>
                                <div class="kpi-icon-wrap kpi-indigo">
                                    <i class="fa fa-hospital-user"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- KPI Metric Cards End -->

            <!-- Charts Section Start (Grafik 10 Rumah Sakit) -->
            <div id="section-charts" class="container-fluid pt-4 px-4">
                <div class="row g-4">
                    <!-- Grafik Pengerjaan Tiap Rumah Sakit -->
                    <div class="col-lg-8">
                        <div class="calibra-card p-4 h-100">
                            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 border-bottom border-secondary pb-3">
                                <div>
                                    <h6 class="mb-0 text-light fw-bold">
                                        <i class="fa fa-chart-bar text-info me-2"></i>Grafik Pengerjaan & Progres Pemeliharaan 10 Rumah Sakit Mitra
                                    </h6>
                                    <small class="text-muted">Perbandingan beban alat ditangani, selesai layak, masa maintenance, dan karantina</small>
                                </div>
                                <span class="badge bg-dark border border-secondary text-info">Live Database</span>
                            </div>
                            <div style="position: relative; height: 320px; width: 100%;">
                                <canvas id="hospital-progress-chart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Distribusi Kelaikan -->
                    <div class="col-lg-4">
                        <div class="calibra-card p-4 h-100 d-flex flex-column justify-content-between">
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary pb-3">
                                <div>
                                    <h6 class="mb-0 text-light fw-bold">
                                        <i class="fa fa-chart-pie text-success me-2"></i>Rasio Kelaikan Alat
                                    </h6>
                                    <small class="text-muted">Proporsi Kelaikan Operasional</small>
                                </div>
                                <span class="badge bg-success"><?= $pctLayak ?>% Layak</span>
                            </div>
                            <div style="position: relative; height: 230px; width: 100%;">
                                <canvas id="equipment-condition-chart"></canvas>
                            </div>
                            <div class="mt-3 p-3 rounded" style="background: rgba(15, 23, 42, 0.7); border: 1px solid #334155;">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Tingkat Kepatuhan SLA:</span>
                                    <span class="text-success fw-bold">96.8% Terpenuhi</span>
                                </div>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 96.8%"></div>
                                </div>
                                <small class="text-muted d-block text-center" style="font-size: 0.72rem;">
                                    Standar Pelayanan Uji Kalibrasi Permenkes No. 54 Tahun 2015
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Charts Section End -->

            <!-- Schedule & Periodic Maintenance Section Start -->
            <div id="section-schedules" class="container-fluid pt-4 px-4">
                <div class="calibra-card p-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 border-bottom border-secondary pb-3">
                        <div>
                            <h6 class="mb-0 text-light fw-bold">
                                <i class="fa fa-calendar-alt text-warning me-2"></i>Matriks Jadwal Pemeliharaan & Kalibrasi Berkala
                            </h6>
                            <small class="text-muted">
                                Pengelompokan siklus: <strong>Harian</strong>, <strong>Mingguan</strong>, <strong>Bulanan</strong>, dan <strong>Tahunan (Kalibrasi Metrologis)</strong>
                            </small>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <span class="badge bg-info text-dark me-1"><i class="fa fa-check-circle me-1"></i>Preventif</span>
                            <span class="badge bg-purple text-light me-1" style="background:#a855f7;"><i class="fa fa-layer-group me-1"></i>Kolektif</span>
                            <span class="badge bg-danger text-light"><i class="fa fa-wrench me-1"></i>Korektif</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Harian -->
                        <div class="col-md-3">
                            <div class="p-3 rounded h-100" style="background: #141d2d; border: 1px solid #334155;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary text-light">Harian</span>
                                    <span class="text-info fw-bold small">Checklist Rutin</span>
                                </div>
                                <div class="fw-bold text-light small mb-1">Inspeksi Fungsi & Kebersihan</div>
                                <p class="text-muted mb-2" style="font-size: 0.73rem;">
                                    Power-on self-test, cek integritas kabel elektroda, baterai cadangan pada Defibrillator & Ventilator.
                                </p>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: 86%"></div>
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Capaian: 86% Terverifikasi</small>
                            </div>
                        </div>

                        <!-- Mingguan -->
                        <div class="col-md-3">
                            <div class="p-3 rounded h-100" style="background: #141d2d; border: 1px solid #334155;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-warning text-dark">Mingguan</span>
                                    <span class="text-warning fw-bold small">Filter & Akurasi</span>
                                </div>
                                <div class="fw-bold text-light small mb-1">Pembersihan Filter & Uji Nol</div>
                                <p class="text-muted mb-2" style="font-size: 0.73rem;">
                                    Flushing saluran dental unit, pembersihan filter HEPA ventilator, kalibrasi internal nol monitor pasien.
                                </p>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: 80%"></div>
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Capaian: 80% Terverifikasi</small>
                            </div>
                        </div>

                        <!-- Bulanan -->
                        <div class="col-md-3">
                            <div class="p-3 rounded h-100" style="background: #141d2d; border: 1px solid #334155;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-success text-light">Bulanan</span>
                                    <span class="text-success fw-bold small">Safety IEC 62353</span>
                                </div>
                                <div class="fw-bold text-light small mb-1">Uji Keselamatan Listrik</div>
                                <p class="text-muted mb-2" style="font-size: 0.73rem;">
                                    Pengukuran resistansi pembumian & arus bocor casis/pasien, validasi akurasi laju infus pump.
                                </p>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: 88%"></div>
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Capaian: 88% Terverifikasi</small>
                            </div>
                        </div>

                        <!-- Tahunan -->
                        <div class="col-md-3">
                            <div class="p-3 rounded h-100" style="background: #141d2d; border: 1px solid #334155;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-danger text-light">Tahunan</span>
                                    <span class="text-danger fw-bold small">Legalitas Kemenkes</span>
                                </div>
                                <div class="fw-bold text-light small mb-1">Kalibrasi Legal Metrologi</div>
                                <p class="text-muted mb-2" style="font-size: 0.73rem;">
                                    Kalibrasi komprehensif tertelusur BPFK/KAN, penerbitan Sertifikat Kalibrasi Resmi & Stiker Laik Pakai.
                                </p>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-danger" style="width: 90%"></div>
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Capaian: 90% Terverifikasi</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Schedule & Periodic Maintenance Section End -->

            <!-- Main Device Monitoring Table Section Start (#section-devices) -->
            <div id="section-devices" class="container-fluid pt-4 px-4">
                <div class="calibra-card p-4">
                    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                        <div>
                            <h6 class="mb-0 text-light fw-bold">
                                <i class="fa fa-stethoscope text-info me-2"></i>Monitoring Inventaris & Kelaikan Alat Medis
                            </h6>
                            <small class="text-muted">
                                Data inventaris real-time meliputi penempatan ruangan, kondisi fisik, kelaikan, status operasional, dan siklus perawatan.
                            </small>
                        </div>
                        <div class="mt-2 mt-md-0 d-flex align-items-center gap-2">
                            <span id="device-count-badge" class="badge bg-info text-dark px-3 py-2 fw-bold">
                                <?= $totalDevices ?> Unit Ditampilkan
                            </span>
                            <button class="btn btn-sm btn-outline-secondary text-light" onclick="resetFilters()">
                                <i class="fa fa-sync-alt me-1"></i>Reset Filter
                            </button>
                        </div>
                    </div>

                    <!-- Filter Control Bar: Grid 2 Baris x 3 Kolom Sesuai Permintaan Spesifikasi -->
                    <div class="filter-bar-grid">
                        <!-- Baris 1: Rumah Sakit | Kondisi Kelayakan | Status Operasional -->
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-hospital me-1 text-info"></i>Rumah Sakit / Mitra</label>
                                <select id="filter-hospital" class="form-select form-select-sm">
                                    <option value="">Semua Rumah Sakit (10 Faskes)</option>
                                    <?php foreach ($hospitals as $h): ?>
                                        <option value="<?= htmlspecialchars($h['name']) ?>"><?= htmlspecialchars($h['name']) ?> (<?= htmlspecialchars($h['city']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-heartbeat me-1 text-info"></i>Kondisi Kelayakan</label>
                                <select id="filter-condition" class="form-select form-select-sm">
                                    <option value="">Semua Kondisi Kelayakan</option>
                                    <option value="Layak Pakai">Layak Pakai</option>
                                    <option value="Layak Bersyarat">Layak Bersyarat</option>
                                    <option value="Tidak Layak Pakai">Tidak Layak Pakai</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-tasks me-1 text-info"></i>Status Operasional</label>
                                <select id="filter-status" class="form-select form-select-sm">
                                    <option value="">Semua Status Operasional</option>
                                    <option value="Siap Digunakan">Siap Digunakan</option>
                                    <option value="Dalam Maintenance">Dalam Maintenance</option>
                                    <option value="Karantina">Karantina / Rusak</option>
                                </select>
                            </div>
                        </div>

                        <!-- Baris 2: Tipe Pemeliharaan | Siklus | Pencarian Cepat (Live Multi-Search) -->
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-wrench me-1 text-info"></i>Tipe Pemeliharaan</label>
                                <select id="filter-type" class="form-select form-select-sm">
                                    <option value="">Semua Tipe Pemeliharaan</option>
                                    <option value="Preventif">Preventif (Pencegahan)</option>
                                    <option value="Korektif">Korektif (Perbaikan)</option>
                                    <option value="Kolektif">Kolektif (Borongan Ruangan)</option>
                                    <option value="Kalibrasi Legal">Kalibrasi Legal Kemenkes</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-clock me-1 text-info"></i>Siklus Perawatan</label>
                                <select id="filter-cycle" class="form-select form-select-sm">
                                    <option value="">Semua Siklus</option>
                                    <option value="Harian">Harian</option>
                                    <option value="Mingguan">Mingguan</option>
                                    <option value="Bulanan">Bulanan</option>
                                    <option value="Tahunan">Tahunan</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-search me-1 text-info"></i>Pencarian Cepat Cerdas</label>
                                <input type="text" id="search-device" class="form-control form-control-sm" placeholder="Cari nama alat, kode, merk, ruangan, RS...">
                            </div>
                        </div>
                    </div>

                    <!-- Responsive Table Container -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="min-width: 220px;">Alat Medis & Kode Registrasi</th>
                                    <th scope="col" style="min-width: 150px;">Faskes / Klien</th>
                                    <th scope="col" style="min-width: 140px;">Ruangan / Unit</th>
                                    <th scope="col" style="min-width: 150px;">Kondisi & Kelayakan</th>
                                    <th scope="col" style="min-width: 130px;">Status Operasional</th>
                                    <!-- Kolom Gabungan Tipe Maintenance & Jadwal Perawatan Sesuai Permintaan -->
                                    <th scope="col" style="min-width: 170px;">Siklus & Jadwal Pemeliharaan</th>
                                    <th scope="col" style="min-width: 140px;">Sertifikat & Kalibrasi</th>
                                    <th scope="col" style="min-width: 90px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="device-table-body">
                                <?php foreach ($devices as $d): ?>
                                    <?php
                                        // Badge class kelayakan
                                        $condBadge = "badge-layak";
                                        $condIcon = "fa-check-circle";
                                        if ($d['condition_state'] === 'Tidak Layak Pakai') {
                                            $condBadge = "badge-rusak";
                                            $condIcon = "fa-times-circle";
                                        } elseif ($d['condition_state'] === 'Layak Bersyarat') {
                                            $condBadge = "badge-bersyarat";
                                            $condIcon = "fa-exclamation-circle";
                                        }

                                        // Badge class status operasional
                                        $statBadge = "badge-ready";
                                        if ($d['operation_status'] === 'Dalam Maintenance') {
                                            $statBadge = "badge-maint";
                                        } elseif (strpos($d['operation_status'], 'Karantina') !== false) {
                                            $statBadge = "badge-quarantine";
                                        }

                                        // Tipe badge
                                        $typeBadge = "badge bg-dark border border-secondary text-info";
                                        if ($d['maintenance_type'] === 'Kolektif') $typeBadge = "badge bg-purple text-light";
                                        if ($d['maintenance_type'] === 'Korektif') $typeBadge = "badge bg-danger text-light";

                                        // Target pencarian multi-text
                                        $searchData = strtolower($d['name'] . ' ' . $d['device_code'] . ' ' . $d['brand_model'] . ' ' . $d['room_location'] . ' ' . $d['hospital_name']);
                                    ?>
                                    <tr class="device-row" 
                                        data-hospital="<?= htmlspecialchars($d['hospital_name']) ?>"
                                        data-condition="<?= htmlspecialchars($d['condition_state']) ?>"
                                        data-status="<?= htmlspecialchars($d['operation_status']) ?>"
                                        data-type="<?= htmlspecialchars($d['maintenance_type']) ?>"
                                        data-cycle="<?= htmlspecialchars($d['maintenance_cycle']) ?>"
                                        data-search="<?= htmlspecialchars($searchData) ?>">
                                        
                                        <!-- Kolom 1: Thumbnail Gambar Alat + Nama & Kode Registrasi -->
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="device-thumb-wrap me-2" title="<?= htmlspecialchars($d['name']) ?>">
                                                    <img src="img/devices/<?= htmlspecialchars($d['image_file']) ?>" alt="<?= htmlspecialchars($d['name']) ?>">
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-light"><?= htmlspecialchars($d['name']) ?></div>
                                                    <small class="text-muted">
                                                        <span class="text-info fw-bold"><?= htmlspecialchars($d['device_code']) ?></span> &bull; <?= htmlspecialchars($d['brand_model']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Kolom 2: Faskes Klien -->
                                        <td>
                                            <div class="text-light fw-medium"><?= htmlspecialchars($d['hospital_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($d['hospital_city']) ?></small>
                                        </td>

                                        <!-- Kolom 3: Ruangan -->
                                        <td>
                                            <span class="badge bg-dark border border-secondary text-info px-2 py-1">
                                                <i class="fa fa-map-marker-alt me-1"></i><?= htmlspecialchars($d['room_location']) ?>
                                            </span>
                                        </td>

                                        <!-- Kolom 4: Kondisi & Kelayakan -->
                                        <td>
                                            <span class="badge-status <?= $condBadge ?>">
                                                <i class="fa <?= $condIcon ?>"></i> <?= htmlspecialchars($d['condition_state']) ?>
                                            </span>
                                            <div class="small text-muted mt-1 text-truncate" style="max-width: 170px;" title="<?= htmlspecialchars($d['physical_note']) ?>">
                                                <?= htmlspecialchars($d['physical_note']) ?>
                                            </div>
                                        </td>

                                        <!-- Kolom 5: Status Operasional -->
                                        <td>
                                            <span class="badge-status <?= $statBadge ?>">
                                                <?= htmlspecialchars($d['operation_status']) ?>
                                            </span>
                                        </td>

                                        <!-- Kolom 6: Siklus & Jadwal Pemeliharaan (KOLOM GABUNGAN RAPI) -->
                                        <td>
                                            <div class="d-flex align-items-center gap-1 mb-1">
                                                <span class="badge bg-secondary text-light px-2 py-0" style="font-size: 0.72rem;">
                                                    <i class="fa fa-clock me-1 text-info"></i><?= htmlspecialchars($d['maintenance_cycle']) ?>
                                                </span>
                                                <span class="<?= $typeBadge ?> px-2 py-0" style="font-size: 0.72rem;">
                                                    <?= htmlspecialchars($d['maintenance_type']) ?>
                                                </span>
                                            </div>
                                            <div class="small text-muted" style="font-size: 0.73rem;">
                                                Tempo: <strong class="text-light"><?= formatIndo($d['next_maintenance']) ?></strong>
                                            </div>
                                        </td>

                                        <!-- Kolom 7: Sertifikat & Metrologi -->
                                        <td>
                                            <div class="small text-info fw-bold"><?= htmlspecialchars($d['certificate_number']) ?></div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">
                                                Due: <span class="<?= $d['certificate_status'] === 'Expired' ? 'text-danger fw-bold' : 'text-success' ?>">
                                                    <?= formatIndo($d['next_calibration']) ?>
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Kolom 8: Aksi -->
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" onclick="openDetailModal(<?= $d['id'] ?>)" title="Lihat Spesifikasi & Metrologi">
                                                <i class="fa fa-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <!-- Baris Jika Filter Kosong -->
                                <tr id="no-device-row" class="d-none">
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fa fa-info-circle me-2"></i>Tidak ada alat yang cocok dengan filter atau kata kunci pencarian.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- Main Device Monitoring Table Section End -->

            <!-- Hospitals & Clinics 10 Clients Overview Start (#section-hospitals) -->
            <div id="section-hospitals" class="container-fluid pt-4 px-4">
                <div class="calibra-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary pb-3">
                        <div>
                            <h6 class="mb-0 text-light fw-bold">
                                <i class="fa fa-hospital text-info me-2"></i>Daftar Profil 10 Fasilitas Kesehatan Mitra CALIBRAMED
                            </h6>
                            <small class="text-muted">Ringkasan akreditasi, kelas rumah sakit, PIC faskes, serta progres kuota alat</small>
                        </div>
                        <span class="badge bg-primary">10 Mitra Aktif</span>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($hospitals as $h): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="p-3 rounded h-100" style="background: #141d2d; border: 1px solid #334155;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="text-light fw-bold mb-0"><?= htmlspecialchars($h['name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($h['type_class']) ?> &bull; <?= htmlspecialchars($h['city']) ?></small>
                                    </div>
                                    <span class="badge bg-dark border border-info text-info small"><?= htmlspecialchars($h['contract_tier']) ?></span>
                                </div>
                                <div class="small text-secondary mb-1">
                                    Akreditasi: <strong class="text-light"><?= htmlspecialchars($h['accreditation']) ?></strong>
                                </div>
                                <div class="small text-secondary mb-1">
                                    Target Alat: <strong class="text-light"><?= $h['target_devices'] ?> Unit</strong> &bull; Selesai: <strong class="text-success"><?= $h['completed_devices'] ?></strong>
                                </div>
                                <?php 
                                    $hProgress = $h['target_devices'] > 0 ? round(($h['completed_devices'] / $h['target_devices']) * 100, 1) : 0;
                                ?>
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-info" style="width: <?= $hProgress ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center small text-muted">
                                    <span><i class="fa fa-user-tie me-1 text-info"></i><?= htmlspecialchars($h['pic_name']) ?></span>
                                    <span><?= htmlspecialchars($h['pic_phone']) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <!-- Hospitals Overview End -->

            <!-- Ruang Kerja & Laporan Teknisi (#section-workbench) -->
            <?php if ($isTeknisiUser || $isOwnerUser): ?>
            <div id="section-workbench" class="container-fluid pt-4 px-4">
                <div class="calibra-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary pb-3">
                        <div>
                            <h6 class="mb-0 text-light fw-bold">
                                <i class="fa fa-tools text-warning me-2"></i>Ruang Kerja Khusus Teknisi & Pembaruan Status Kalibrasi
                            </h6>
                            <small class="text-muted">Akses operasional input hasil pengujian metrologi, keselamatan listrik, dan registrasi alat</small>
                        </div>
                        <button class="btn btn-sm btn-calibra-primary" data-bs-toggle="modal" data-bs-target="#newDeviceModal">
                            <i class="fa fa-plus me-1"></i> Form Input Registrasi Alat Baru
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="p-3 rounded" style="background: #141d2d; border: 1px dashed #38bdf8;">
                                <h6 class="text-info mb-2"><i class="fa fa-clipboard-check me-2"></i>Standar Operasional Prosedur Pengujian Teknisi (SOP)</h6>
                                <ol class="small text-secondary ps-3 mb-0">
                                    <li class="mb-1"><strong>Inspeksi Fisik & Fungsi:</strong> Memeriksa keutuhan casis, tombol, display, dan grounding kabel daya.</li>
                                    <li class="mb-1"><strong>Uji Keselamatan Listrik (IEC 62353):</strong> Mengukur arus bocor casis (&lt; 100 µA) dan resistansi pembumian (&lt; 0.2 Ω).</li>
                                    <li class="mb-1"><strong>Uji Metrologi:</strong> Menggunakan simulator kalibrasi standar tertelusur BPFK, deviasi harus dalam ambang batas toleransi.</li>
                                    <li><strong>Pemberian Label Kemenkes:</strong> Stiker Hijau (Laik Pakai), Kuning (Laik Bersyarat), atau Merah (Karantina Tidak Laik).</li>
                                </ol>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="p-3 rounded h-100 d-flex flex-column justify-content-center text-center" style="background: #141d2d; border: 1px solid #334155;">
                                <div class="text-light fw-bold mb-1">Butuh Memperbarui Status Alat Tertentu?</div>
                                <p class="small text-muted mb-3">Klik tombol <strong>"Detail"</strong> pada baris alat di tabel monitoring untuk membuka form kerja teknisi alat tersebut secara instan.</p>
                                <a href="#section-devices" class="btn btn-sm btn-outline-info w-75 mx-auto">
                                    <i class="fa fa-arrow-up me-1"></i> Pilih Alat di Tabel Monitoring
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Legalitas & Sertifikasi Standard (#section-legal) -->
            <div id="section-legal" class="container-fluid pt-4 px-4">
                <div class="calibra-card p-4">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom border-secondary pb-3">
                        <div>
                            <h6 class="mb-0 text-light fw-bold">
                                <i class="fa fa-award text-success me-2"></i>Legalitas, Akreditasi ISO/IEC 17025 & Kepatuhan Regulasi Kemenkes
                            </h6>
                            <small class="text-muted">Landasan hukum dan jaminan mutu pengujian kalibrasi alat kesehatan</small>
                        </div>
                        <span class="badge bg-success">Legal & Terakreditasi Penuh</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded h-100" style="background: #141d2d; border: 1px solid #334155;">
                                <div class="text-success fw-bold mb-1"><i class="fa fa-gavel me-1"></i>Permenkes RI No. 54 Tahun 2015</div>
                                <p class="small text-muted mb-0">
                                    Menetapkan kewajiban setiap alat kesehatan yang dipergunakan di fasilitas pelayanan kesehatan untuk dilakukan pengujian dan/atau kalibrasi secara berkala minimal 1 (satu) kali dalam setahun guna menjamin keselamatan pasien.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded h-100" style="background: #141d2d; border: 1px solid #334155;">
                                <div class="text-info fw-bold mb-1"><i class="fa fa-certificate me-1"></i>Standar ISO/IEC 17025:2017</div>
                                <p class="small text-muted mb-0">
                                    Kompetensi umum laboratorium pengujian dan kalibrasi. CALIBRAMED menerapkan sistem manajemen mutu bersertifikat, menjamin ketertelusuran pengukuran (*traceability*) ke Standar Metrologi Nasional dan Internasional.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded h-100" style="background: #141d2d; border: 1px solid #334155;">
                                <div class="text-warning fw-bold mb-1"><i class="fa fa-user-shield me-1"></i>Otoritas & Penanggung Jawab Teknis</div>
                                <p class="small text-muted mb-0">
                                    Pengujian disupervisi langsung oleh <strong>Prof. Dr.Eng. Ir. Ihsan Setyo Nugroho, S.Tr.T., M.Eng., IPU., ASEAN Eng., APEC Eng., CCE.</strong> selaku Lead Certified Clinical Engineer terlisensi.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Legalitas End -->

            <!-- Footer Start with Safe Padding-Bottom -->
            <div class="container-fluid pt-4 px-4">
                <div class="footer-bar rounded-top">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-6 text-center text-md-start mb-2 mb-md-0">
                            <span class="text-light fw-bold">CALIBRAMED SYSTEM</span> &copy; 2026. Hak Cipta Dilindungi.
                            <div class="small text-muted">Aplikasi Pemrograman Web - UTS Pemrograman Web Elektromedik</div>
                        </div>
                        <div class="col-12 col-md-6 text-center text-md-end text-muted small">
                            <span>Sistem Terintegrasi Berbasis Standar Kemenkes RI No. 54/2015 & ISO/IEC 17025</span>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Footer End -->
        </div>
        <!-- Content Area End -->

        <!-- Compact Scroll-to-Top Button (36px, Tidak Menutupi Teks) -->
        <a href="#" class="back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- =====================================================================
         MODAL 1: DETAIL ALAT & WORKBENCH TEKNISI (INSPEKSI & METROLOGI)
         ===================================================================== -->
    <div class="modal fade" id="deviceDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title text-light fw-bold">
                            <i class="fa fa-microchip text-info me-2"></i>Spesifikasi Alat & Lembar Kerja Teknisi
                        </h6>
                        <small class="text-muted">Kode Registrasi: <span id="modal-dev-id" class="text-info fw-bold"></span></small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <!-- Identitas & Thumbnail -->
                        <div class="col-md-6">
                            <div class="p-3 rounded h-100" style="background: #141d2d; border: 1px solid #334155;">
                                <div class="d-flex align-items-center mb-3 border-bottom border-secondary pb-2">
                                    <div class="device-thumb-wrap me-2" style="width: 50px; height: 50px;">
                                        <img id="modal-dev-img" src="img/devices/default_device.svg" alt="Device">
                                    </div>
                                    <div>
                                        <h6 id="modal-dev-name" class="text-light fw-bold mb-0"></h6>
                                        <small id="modal-dev-serial" class="text-muted"></small>
                                    </div>
                                </div>
                                <table class="table table-borderless table-sm small mb-0">
                                    <tr>
                                        <td class="text-muted" style="width: 45%;">Rumah Sakit:</td>
                                        <td id="modal-dev-hospital" class="text-light fw-medium"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Ruangan Unit:</td>
                                        <td><span id="modal-dev-room" class="badge bg-secondary text-info"></span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Kondisi Fisik:</td>
                                        <td id="modal-dev-physical" class="text-warning"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Kelayakan:</td>
                                        <td><span id="modal-dev-condition" class="badge bg-success"></span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Status Operasi:</td>
                                        <td><span id="modal-dev-status" class="badge bg-info text-dark"></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Data Metrologi & Legalitas -->
                        <div class="col-md-6">
                            <div class="p-3 rounded h-100" style="background: #141d2d; border: 1px solid #334155;">
                                <h6 class="text-success border-bottom border-secondary pb-2 mb-3">
                                    <i class="fa fa-balance-scale me-1"></i>Metrologi & Keselamatan Listrik
                                </h6>
                                <table class="table table-borderless table-sm small mb-0">
                                    <tr>
                                        <td class="text-muted" style="width: 45%;">No. Sertifikat:</td>
                                        <td id="modal-dev-cert" class="text-info fw-bold"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Jatuh Tempo:</td>
                                        <td id="modal-dev-calib-due" class="text-light"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Deviasi/Error Uji:</td>
                                        <td id="modal-dev-error" class="text-light fw-bold"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Arus Bocor (Leakage):</td>
                                        <td id="modal-dev-leakage" class="text-light"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Siklus & Tipe:</td>
                                        <td id="modal-dev-cycle" class="text-light"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Teknisi PIC:</td>
                                        <td id="modal-dev-pic" class="text-light fw-medium"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Form Kerja Teknisi (Hanya Muncul jika Teknisi atau Owner) -->
                        <?php if ($isTeknisiUser || $isOwnerUser): ?>
                        <div class="col-12 mt-2">
                            <div class="p-3 rounded" style="background: #182235; border: 1px dashed #38bdf8;">
                                <h6 class="text-light mb-2"><i class="fa fa-wrench text-info me-1"></i>Formulir Kerja Teknisi: Pembaruan Hasil Uji & Status</h6>
                                <form id="inspectionForm">
                                    <input type="hidden" name="device_id" id="inspect-device-id">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <label class="small text-muted">Status Operasional:</label>
                                            <select name="operation_status" id="inspect-status" class="form-select form-select-sm">
                                                <option value="Siap Digunakan">Siap Digunakan</option>
                                                <option value="Dalam Maintenance">Dalam Maintenance</option>
                                                <option value="Karantina / Rusak">Karantina / Rusak</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small text-muted">Kondisi Kelaikan:</label>
                                            <select name="condition_state" id="inspect-condition" class="form-select form-select-sm">
                                                <option value="Layak Pakai">Layak Pakai</option>
                                                <option value="Layak Bersyarat">Layak Bersyarat</option>
                                                <option value="Tidak Layak Pakai">Tidak Layak Pakai</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small text-muted">Deviasi/Error Uji Metrologi:</label>
                                            <input type="text" name="error_deviation" id="inspect-error" class="form-control form-control-sm" placeholder="Contoh: +0.5% (Toleransi < ±5%)">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small text-muted">Arus Bocor (Leakage Current):</label>
                                            <input type="text" name="leakage_current" id="inspect-leakage" class="form-control form-control-sm" placeholder="Contoh: 35 µA">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small text-muted">Tindakan Kalibrasi Dilakukan:</label>
                                            <input type="text" name="action_taken" class="form-control form-control-sm" placeholder="Contoh: Kalibrasi ulang & uji fungsi">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="small text-muted">Catatan Temuan Fisik:</label>
                                            <input type="text" name="physical_note" id="inspect-physical" class="form-control form-control-sm" placeholder="Catatan kondisi fisik...">
                                        </div>
                                    </div>
                                    <div class="text-end mt-2">
                                        <button type="button" class="btn btn-sm btn-calibra-primary" onclick="submitInspection()">
                                            <i class="fa fa-save me-1"></i> Simpan Hasil Uji ke Database
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Riwayat Log Inspeksi -->
                        <div class="col-12 mt-2">
                            <h6 class="small text-muted mb-2"><i class="fa fa-history me-1"></i>Riwayat Pemeriksaan Terakhir:</h6>
                            <div id="modal-logs-list"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         MODAL 2: FORM INPUT REGISTRASI ALAT MEDIS BARU (TEKNISI & OWNER)
         ===================================================================== -->
    <?php if ($isTeknisiUser || $isOwnerUser): ?>
    <div class="modal fade" id="newDeviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title text-light fw-bold">
                            <i class="fa fa-plus-circle text-info me-2"></i>Registrasi Inventaris Alat Medis Baru
                        </h6>
                        <small class="text-muted">Masukkan rincian spesifikasi peralatan untuk dipantau dalam sistem</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="addDeviceForm">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="small text-muted">Kode Registrasi Alat <span class="text-danger">*</span></label>
                                <input type="text" name="device_code" class="form-control form-control-sm" placeholder="Contoh: CLM-MON-099" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted">Nama Alat Medis <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control form-control-sm" placeholder="Contoh: Patient Monitor" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted">Merk & Tipe/Model</label>
                                <input type="text" name="brand_model" class="form-control form-control-sm" placeholder="Contoh: Mindray ePM 12M" required>
                            </div>

                            <div class="col-md-4">
                                <label class="small text-muted">Nomor Seri (Serial Number)</label>
                                <input type="text" name="serial_number" class="form-control form-control-sm" placeholder="Contoh: SN-882910" required>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted">Rumah Sakit / Faskes Mitra <span class="text-danger">*</span></label>
                                <select name="hospital_id" class="form-select form-select-sm" required>
                                    <option value="">-- Pilih Rumah Sakit --</option>
                                    <?php foreach ($hospitals as $h): ?>
                                        <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['name']) ?> (<?= htmlspecialchars($h['city']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted">Ruangan Penempatan</label>
                                <input type="text" name="room_location" class="form-control form-control-sm" placeholder="Contoh: Ruang ICU Lantai 2" required>
                            </div>

                            <div class="col-md-4">
                                <label class="small text-muted">Pilih Ikon / Gambar Thumbnail</label>
                                <select name="image_file" class="form-select form-select-sm">
                                    <option value="defibrillator.svg">Defibrillator</option>
                                    <option value="ventilator.svg">Mechanical Ventilator</option>
                                    <option value="infusion_pump.svg">Infusion Pump</option>
                                    <option value="ecg_machine.svg">ECG 12-Lead</option>
                                    <option value="mobile_xray.svg">Mobile X-Ray</option>
                                    <option value="patient_monitor.svg">Patient Monitor</option>
                                    <option value="dental_unit.svg">Dental Unit</option>
                                    <option value="incubator.svg">Infant Incubator</option>
                                    <option value="syringe_pump.svg">Syringe Pump</option>
                                    <option value="autoclave.svg">Autoclave Sterilizer</option>
                                    <option value="usg_doppler.svg">USG Doppler</option>
                                    <option value="suction_pump.svg">Suction Pump</option>
                                    <option value="default_device.svg" selected>Default Device</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted">Kondisi Kelayakan Awal</label>
                                <select name="condition_state" class="form-select form-select-sm">
                                    <option value="Layak Pakai">Layak Pakai</option>
                                    <option value="Layak Bersyarat">Layak Bersyarat</option>
                                    <option value="Tidak Layak Pakai">Tidak Layak Pakai</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="small text-muted">Status Operasional Awal</label>
                                <select name="operation_status" class="form-select form-select-sm">
                                    <option value="Siap Digunakan">Siap Digunakan</option>
                                    <option value="Dalam Maintenance">Dalam Maintenance</option>
                                    <option value="Karantina / Rusak">Karantina / Rusak</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="small text-muted">Tipe Pemeliharaan</label>
                                <select name="maintenance_type" class="form-select form-select-sm">
                                    <option value="Preventif">Preventif</option>
                                    <option value="Kolektif">Kolektif</option>
                                    <option value="Korektif">Korektif</option>
                                    <option value="Kalibrasi Legal">Kalibrasi Legal</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">Siklus Perawatan</label>
                                <select name="maintenance_cycle" class="form-select form-select-sm">
                                    <option value="Harian">Harian</option>
                                    <option value="Mingguan">Mingguan</option>
                                    <option value="Bulanan" selected>Bulanan</option>
                                    <option value="Tahunan">Tahunan</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">Tgl Terakhir Kalibrasi</label>
                                <input type="date" name="last_calibration" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">Tgl Jatuh Tempo Kalibrasi</label>
                                <input type="date" name="next_calibration" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('+1 year')) ?>">
                            </div>

                            <div class="col-12">
                                <label class="small text-muted">Catatan Fisik / Riwayat Awal Alat</label>
                                <input type="text" name="physical_note" class="form-control form-control-sm" placeholder="Contoh: Alat baru diinstalasi, sensor lengkap, kondisi normal">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-calibra-primary btn-sm" onclick="submitNewDevice()">
                            <i class="fa fa-save me-1"></i> Daftarkan Alat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Passing PHP Data to JavaScript for Chart.js -->
    <script>
        window.hospitalChartData = {
            labels: <?= json_encode($chartLabels) ?>,
            targets: <?= json_encode($chartTargets) ?>,
            completed: <?= json_encode($chartCompleted) ?>,
            maintenance: <?= json_encode($chartMaintenance) ?>,
            quarantine: <?= json_encode($chartQuarantine) ?>
        };

        window.conditionCounts = {
            layak: <?= $countLayak ?>,
            bersyarat: <?= $countBersyarat ?>,
            rusak: <?= $countRusak ?>
        };
    </script>

    <!-- Core JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/chart/chart.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Logic -->
    <script src="js/main.js"></script>

    <!-- CalibraMed Dynamic Controller -->
    <script src="js/calibramed.js"></script>
</body>

</html>
