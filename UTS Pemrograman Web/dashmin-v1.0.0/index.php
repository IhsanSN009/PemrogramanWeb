<?php
/**
 * CALIBRAMED SYSTEM - Master Enterprise Dashboard
 * Theme: PolluxUI Corporate Medical Palette (Dual-Tone Purple & Navy, Teal Sub-bar, White Cards)
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Heebo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
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
        <!-- =====================================================================
             SIDEBAR: PURPLE LOGO BOX + DARK SLATE NAVIGATION (POLLUXUI)
             ===================================================================== -->
        <div class="sidebar pb-3">
            <nav class="navbar navbar-dark p-0">
                <!-- 1. Purple Brand Header Box (Persis Logo PolluxUI) -->
                <div class="sidebar-brand-box w-100">
                    <div class="brand-badge-circle">
                        <i class="fa fa-heartbeat"></i>
                    </div>
                    <div>
                        <h5 class="mb-0">CALIBRAMED</h5>
                        <small style="color: rgba(255,255,255,0.75); font-size: 0.65rem; letter-spacing: 0.5px;">BIOMEDICAL SYSTEM</small>
                    </div>
                </div>

                <!-- 2. Profile Card di Bawah Brand -->
                <div class="sidebar-user-box w-100">
                    <div class="d-flex align-items-center">
                        <div class="position-relative">
                            <img class="rounded-circle border border-2 border-white" src="img/<?= htmlspecialchars($currentUser['avatar']) ?>" alt="User Avatar" style="width: 42px; height: 42px; object-fit: cover;">
                            <div class="bg-success rounded-circle border border-2 border-dark position-absolute end-0 bottom-0 p-1"></div>
                        </div>
                        <div class="ms-3 overflow-hidden">
                            <h6 class="mb-0 text-white text-truncate" style="font-size: 0.82rem;" title="<?= htmlspecialchars($currentUser['full_title']) ?>">
                                <?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>
                            </h6>
                            <?php if ($currentUser['role'] === 'owner'): ?>
                                <span class="badge bg-warning text-dark fw-bold" style="font-size: 0.62rem;">👑 OWNER & CEO</span>
                            <?php elseif ($currentUser['role'] === 'teknisi'): ?>
                                <span class="badge bg-info text-dark fw-bold" style="font-size: 0.62rem;">🔧 TEKNISI PT</span>
                            <?php else: ?>
                                <span class="badge bg-success fw-bold" style="font-size: 0.62rem;">🏥 KLIEN RS</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tombol Logout Soft Red -->
                    <button type="button" class="btn-sidebar-logout" onclick="confirmLogout()">
                        <i class="fa fa-sign-out-alt"></i> Logout Akun
                    </button>
                </div>

                <!-- 3. Sidebar Menu Nav (Rapi, Font Pas, Indikator + dan NEW) -->
                <div class="navbar-nav w-100 mt-2">
                    <a href="index.php" class="nav-item nav-link active">
                        <i class="fa fa-tachometer-alt"></i>
                        <span>Dashboard Utama</span>
                        <span class="badge-new-pill">new</span>
                    </a>
                    <a href="#section-charts" class="nav-item nav-link">
                        <i class="fa fa-chart-line"></i>
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

                <!-- ISO Accreditation Footnote -->
                <div class="mt-4 px-3 w-100">
                    <div class="p-2 rounded text-center" style="background: rgba(255, 255, 255, 0.04); border: 1px dashed rgba(255, 255, 255, 0.15);">
                        <i class="fa fa-shield-alt text-success me-1"></i>
                        <span class="text-white fw-bold" style="font-size: 0.7rem;">ISO/IEC 17025 Accredited</span>
                        <div class="text-muted" style="font-size: 0.62rem;">Permenkes RI No. 54/2015</div>
                    </div>
                </div>
            </nav>
        </div>
        <!-- Sidebar End -->

        <!-- =====================================================================
             CONTENT AREA: DUAL TOP BARS + CLEAN WHITE CARDS (POLLUXUI)
             ===================================================================== -->
        <div class="content">
            <!-- 1. Top Navbar: Royal Navy Band (Persis Top Row PolluxUI) -->
            <div class="navbar-top-royal d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <button class="sidebar-toggler me-3">
                        <i class="fa fa-bars text-white"></i>
                    </button>
                    <!-- User Greeting Banner Top (Gaya Eugenia Mullins) -->
                    <div class="d-none d-md-flex align-items-center text-white small">
                        <img class="rounded-circle me-2 border border-light" src="img/<?= htmlspecialchars($currentUser['avatar']) ?>" alt="" style="width: 28px; height: 28px; object-fit: cover;">
                        <div>
                            <strong class="text-white"><?= htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?></strong>
                            <span class="text-white-50 ms-1" style="font-size: 0.75rem;">Status: Terautentikasi Aktif</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <!-- Simulator Role Switcher (Khusus Owner) -->
                    <?php if ($currentUser['role'] === 'owner'): ?>
                    <div class="dropdown me-1">
                        <button class="btn btn-sm text-white border-0 dropdown-toggle py-1 px-2" style="background: rgba(255,255,255,0.15); font-size: 0.75rem;" data-bs-toggle="dropdown">
                            <i class="fa fa-eye me-1 text-warning"></i> Role: <strong><?= strtoupper($activeRole) ?></strong>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 py-2">
                            <a href="javascript:void(0)" class="dropdown-item py-1 small" onclick="switchOwnerRole('owner', null)">
                                <i class="fa fa-crown text-warning me-2"></i>Mode Owner (Akses Penuh)
                            </a>
                            <a href="javascript:void(0)" class="dropdown-item py-1 small" onclick="switchOwnerRole('teknisi', null)">
                                <i class="fa fa-wrench text-info me-2"></i>Mode Teknisi PT (Engineer)
                            </a>
                            <a href="javascript:void(0)" class="dropdown-item py-1 small" onclick="switchOwnerRole('klien', 1)">
                                <i class="fa fa-hospital text-success me-2"></i>Mode Klien (RSUD Dr. Soetomo)
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Action: Tambah Alat Baru (Owner / Teknisi) -->
                    <?php if ($isTeknisiUser || $isOwnerUser): ?>
                    <button class="btn btn-sm btn-light py-1 px-2 me-1" style="font-size: 0.75rem; font-weight: 600; color: #183863;" data-bs-toggle="modal" data-bs-target="#newDeviceModal">
                        <i class="fa fa-plus-circle me-1 text-success"></i> Tambah Alat
                    </button>
                    <?php endif; ?>

                    <!-- Date Badge Top Right (Gaya Today : Mar 23) -->
                    <div class="d-none d-sm-inline-flex align-items-center py-1 px-2 rounded" style="background: rgba(255, 255, 255, 0.12); font-size: 0.75rem; color: #ffffff;">
                        <i class="fa fa-calendar-alt me-1 text-info"></i>
                        <span id="live-timestamp">Memuat tanggal...</span>
                    </div>

                    <!-- Urgent Notification Bell -->
                    <div class="dropdown ms-1">
                        <a href="#" class="text-white text-decoration-none position-relative p-2" data-bs-toggle="dropdown">
                            <i class="fa fa-bell"></i>
                            <?php if ($countQuarantine > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                <?= $countQuarantine ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="width: 280px;">
                            <div class="p-2 border-bottom mb-1">
                                <h6 class="mb-0 text-dark small fw-bold"><i class="fa fa-exclamation-triangle text-danger me-1"></i>Peringatan Alat Kritis</h6>
                            </div>
                            <div class="p-2 small text-muted">
                                <strong><?= $countQuarantine ?> unit</strong> Karantina/Rusak dan <strong><?= $countMaintenance ?> unit</strong> dalam pemeliharaan.
                            </div>
                            <div class="text-center pt-1 border-top">
                                <a href="#section-devices" class="small text-success text-decoration-none fw-bold">Buka Tabel Monitoring</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Sub-Navbar: Turquoise Teal Strip (Persis Bar Hijau PolluxUI) -->
            <div class="sub-navbar-teal d-flex flex-wrap align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <!-- Quick action icons (calendar, folder, document) -->
                    <div class="d-none d-md-flex align-items-center">
                        <a href="#section-schedules" class="quick-icon-btn" title="Jadwal Pemeliharaan"><i class="fa fa-calendar-alt"></i></a>
                        <a href="#section-devices" class="quick-icon-btn" title="Inventaris Alat"><i class="fa fa-stethoscope"></i></a>
                        <a href="#section-hospitals" class="quick-icon-btn" title="10 RS Mitra"><i class="fa fa-hospital"></i></a>
                        <a href="#section-legal" class="quick-icon-btn" title="Dokumen Legalitas"><i class="fa fa-file-contract"></i></a>
                    </div>
                    <!-- Page Title & Breadcrumb -->
                    <div>
                        <div class="page-title">
                            <span>Dashboard</span>
                            <span class="d-none d-sm-inline opacity-75 fw-normal" style="font-size: 0.85rem;">Home &gt; Main Dashboard</span>
                        </div>
                    </div>
                </div>

                <!-- Right Pill Search Bar (Persis Search Bar Putih PolluxUI) -->
                <div>
                    <input type="text" id="nav-search-device" class="search-pill-input" placeholder="Search...">
                </div>
            </div>

            <!-- =====================================================================
                 3. STATUS STATISTICS: 4 CLEAN WHITE BOXES (POLLUXUI METRICS)
                 ===================================================================== -->
            <div class="container-fluid pt-4 px-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">Status statistics</h6>
                    <small class="text-muted">Pemutakhiran Sistem Real-Time</small>
                </div>

                <div class="row g-3">
                    <!-- Box 1: Transactions / Total Alat (Wave Amber) -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-box-pollux">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label">Total Alat Medis</div>
                                    <div class="stat-value"><?= $totalDevices ?> <span class="fs-6 text-muted fw-normal">Unit</span></div>
                                </div>
                                <span class="stat-badge-percent bg-light text-success">+100% OK</span>
                            </div>
                            <!-- Mini Wave Accent Amber -->
                            <div class="mini-wave-accent mini-wave-amber">
                                <div class="bg-warning rounded-pill" style="width: 20%; height: 40%;"></div>
                                <div class="bg-warning rounded-pill" style="width: 20%; height: 70%;"></div>
                                <div class="bg-warning rounded-pill" style="width: 20%; height: 55%;"></div>
                                <div class="bg-warning rounded-pill" style="width: 20%; height: 90%;"></div>
                                <div class="bg-warning rounded-pill" style="width: 20%; height: 75%;"></div>
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 0.72rem;">10 Rumah Sakit & Faskes Mitra</small>
                        </div>
                    </div>

                    <!-- Box 2: Layak Pakai (Wave Teal / Hijau Cerah) -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-box-pollux">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label text-success">Layak Pakai</div>
                                    <div class="stat-value text-success"><?= $countLayak ?> <span class="fs-6 text-muted fw-normal">Unit</span></div>
                                </div>
                                <span class="stat-badge-percent" style="background:#e8f9f2; color:#008764;"><?= $pctLayak ?>%</span>
                            </div>
                            <!-- Mini Wave Accent Teal -->
                            <div class="mini-wave-accent mini-wave-teal">
                                <div class="bg-success rounded-pill" style="width: 20%; height: 50%;"></div>
                                <div class="bg-success rounded-pill" style="width: 20%; height: 85%;"></div>
                                <div class="bg-success rounded-pill" style="width: 20%; height: 70%;"></div>
                                <div class="bg-success rounded-pill" style="width: 20%; height: 95%;"></div>
                                <div class="bg-success rounded-pill" style="width: 20%; height: 80%;"></div>
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 0.72rem;">Lolos Sertifikasi Uji Kelayakan</small>
                        </div>
                    </div>

                    <!-- Box 3: Dalam Maintenance (Wave Blue) -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-box-pollux">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label text-warning">Dalam Maintenance</div>
                                    <div class="stat-value text-dark"><?= $countMaintenance ?> <span class="fs-6 text-muted fw-normal">Unit</span></div>
                                </div>
                                <span class="stat-badge-percent" style="background:#fff8e8; color:#b78103;"><?= $pctMaint ?>%</span>
                            </div>
                            <!-- Mini Wave Accent Blue -->
                            <div class="mini-wave-accent mini-wave-blue">
                                <div class="bg-info rounded-pill" style="width: 20%; height: 35%;"></div>
                                <div class="bg-info rounded-pill" style="width: 20%; height: 60%;"></div>
                                <div class="bg-info rounded-pill" style="width: 20%; height: 45%;"></div>
                                <div class="bg-info rounded-pill" style="width: 20%; height: 80%;"></div>
                                <div class="bg-info rounded-pill" style="width: 20%; height: 50%;"></div>
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 0.72rem;">Proses Perbaikan & Kalibrasi Ulang</small>
                        </div>
                    </div>

                    <!-- Box 4: Karantina / Rusak (Wave Coral Red) -->
                    <div class="col-sm-6 col-xl-3">
                        <div class="stat-box-pollux">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="stat-label text-danger">Karantina / Rusak</div>
                                    <div class="stat-value text-danger"><?= $countRusak ?> <span class="fs-6 text-muted fw-normal">Unit</span></div>
                                </div>
                                <span class="stat-badge-percent" style="background:#fde8e8; color:#d32f2f;"><?= $pctRusak ?>%</span>
                            </div>
                            <!-- Mini Wave Accent Coral Red -->
                            <div class="mini-wave-accent mini-wave-coral">
                                <div class="bg-danger rounded-pill" style="width: 20%; height: 65%;"></div>
                                <div class="bg-danger rounded-pill" style="width: 20%; height: 40%;"></div>
                                <div class="bg-danger rounded-pill" style="width: 20%; height: 90%;"></div>
                                <div class="bg-danger rounded-pill" style="width: 20%; height: 55%;"></div>
                                <div class="bg-danger rounded-pill" style="width: 20%; height: 30%;"></div>
                            </div>
                            <small class="text-muted d-block mt-2" style="font-size: 0.72rem;">Tindak Lanjut & Pergantian Sparepart</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =====================================================================
                 4. INCOME & WORKLOAD STATISTICS: CHARTS ROW (POLLUXUI STYLE)
                 ===================================================================== -->
            <div id="section-charts" class="container-fluid pt-4 px-4">
                <div class="row g-4">
                    <!-- Grafik Pengerjaan 10 Rumah Sakit -->
                    <div class="col-lg-8">
                        <div class="calibra-card p-4 h-100">
                            <div class="card-header-clean">
                                <div>
                                    <h6>Grafik Pengerjaan & Progres Pemeliharaan 10 Rumah Sakit Mitra</h6>
                                    <small class="text-muted">Perbandingan beban alat ditangani, selesai layak, masa maintenance, dan karantina</small>
                                </div>
                                <div class="d-flex align-items-center gap-2 small">
                                    <span class="d-flex align-items-center"><span class="badge rounded-circle p-1 me-1" style="background:#6f42c1;"> </span> Target</span>
                                    <span class="d-flex align-items-center"><span class="badge rounded-circle p-1 me-1" style="background:#00c292;"> </span> Selesai</span>
                                    <span class="d-flex align-items-center"><span class="badge rounded-circle p-1 me-1" style="background:#f1b44c;"> </span> Maint</span>
                                </div>
                            </div>
                            <div style="position: relative; height: 310px; width: 100%;">
                                <canvas id="hospital-progress-chart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Rasio Kelaikan & Callout Card -->
                    <div class="col-lg-4">
                        <div class="calibra-card p-4 h-100 d-flex flex-column justify-content-between">
                            <div class="card-header-clean">
                                <div>
                                    <h6>Rasio Kelaikan Alat Medis</h6>
                                    <small class="text-muted">Persentase Kesiapan Operasional</small>
                                </div>
                                <span class="badge" style="background:#e8f9f2; color:#008764; font-weight:700;"><?= $pctLayak ?>% Layak</span>
                            </div>
                            <div style="position: relative; height: 210px; width: 100%;">
                                <canvas id="equipment-condition-chart"></canvas>
                            </div>

                            <!-- Warm Callout Banner (Newsletter style PolluxUI) -->
                            <div class="pollux-callout-card mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong style="font-size: 0.85rem;"><i class="fa fa-certificate me-1"></i>SLA Kepatuhan Mutu</strong>
                                    <span class="badge bg-white text-dark fw-bold">96.8%</span>
                                </div>
                                <p class="mb-0 small" style="font-size: 0.75rem; color: rgba(255,255,255,0.95);">
                                    Seluruh proses pengujian terakreditasi ISO/IEC 17025 dan tertelusur BPFK/BSN.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =====================================================================
                 5. JADWAL PEMELIHARAAN BERKALA (HARIAN, MINGGUAN, BULANAN, TAHUNAN)
                 ===================================================================== -->
            <div id="section-schedules" class="container-fluid pt-4 px-4">
                <div class="calibra-card p-4">
                    <div class="card-header-clean">
                        <div>
                            <h6>Matriks Jadwal Pemeliharaan & Kalibrasi Berkala</h6>
                            <small class="text-muted">Siklus inspeksi: <strong>Harian</strong>, <strong>Mingguan</strong>, <strong>Bulanan</strong>, dan <strong>Tahunan (Kalibrasi Metrologis)</strong></small>
                        </div>
                        <div class="d-flex gap-1">
                            <span class="badge" style="background:#e0f2fe; color:#0369a1;">Preventif</span>
                            <span class="badge" style="background:#ede9fe; color:#6d28d9;">Kolektif</span>
                            <span class="badge" style="background:#fee2e2; color:#991b1b;">Korektif</span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="p-3 rounded h-100 border" style="background:#fcfdfe;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-primary">Harian</span>
                                    <small class="text-success fw-bold">86% Capaian</small>
                                </div>
                                <div class="fw-bold text-dark small mb-1">Inspeksi Fungsi & Kebersihan</div>
                                <p class="text-muted mb-2" style="font-size: 0.74rem;">
                                    Power-on self test, cek kabel elektroda, baterai cadangan defibrillator & ventilator.
                                </p>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-primary" style="width: 86%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="p-3 rounded h-100 border" style="background:#fcfdfe;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-warning text-dark">Mingguan</span>
                                    <small class="text-warning fw-bold">80% Capaian</small>
                                </div>
                                <div class="fw-bold text-dark small mb-1">Filter & Kalibrasi Nol</div>
                                <p class="text-muted mb-2" style="font-size: 0.74rem;">
                                    Flushing dental unit, penggantian filter HEPA ventilator, kalibrasi internal monitor.
                                </p>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-warning" style="width: 80%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="p-3 rounded h-100 border" style="background:#fcfdfe;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-success">Bulanan</span>
                                    <small class="text-success fw-bold">88% Capaian</small>
                                </div>
                                <div class="fw-bold text-dark small mb-1">Safety Test IEC 62353</div>
                                <p class="text-muted mb-2" style="font-size: 0.74rem;">
                                    Pengukuran arus bocor grounding & casis, validasi presisi tetes infusion pump.
                                </p>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: 88%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="p-3 rounded h-100 border" style="background:#fcfdfe;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge" style="background:#6f42c1; color:#fff;">Tahunan</span>
                                    <small class="fw-bold" style="color:#6f42c1;">90% Capaian</small>
                                </div>
                                <div class="fw-bold text-dark small mb-1">Kalibrasi Legal Kemenkes</div>
                                <p class="text-muted mb-2" style="font-size: 0.74rem;">
                                    Sertifikasi metrologis resmi tertelusur BPFK/KAN, penerbitan stiker laik pakai.
                                </p>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" style="width: 90%; background:#6f42c1;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =====================================================================
                 6. MONITORING ALAT MEDIS: TABEL DENGAN FILTER GRID 2x3 & THUMBNAIL
                 ===================================================================== -->
            <div id="section-devices" class="container-fluid pt-4 px-4">
                <div class="calibra-card p-4">
                    <div class="card-header-clean">
                        <div>
                            <h6>Monitoring Inventaris & Kelaikan Alat Medis</h6>
                            <small class="text-muted">Data inventaris real-time meliputi penempatan ruangan, kondisi fisik, kelaikan, status operasional, dan siklus perawatan.</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span id="device-count-badge" class="badge bg-light text-dark border px-3 py-2 fw-bold" style="font-size: 0.8rem;">
                                <?= $totalDevices ?> Unit Ditampilkan
                            </span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">
                                <i class="fa fa-sync-alt me-1"></i>Reset
                            </button>
                        </div>
                    </div>

                    <!-- Filter Control Bar: Grid 2 Baris x 3 Kolom -->
                    <div class="filter-bar-grid">
                        <div class="row g-2 mb-2">
                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-hospital me-1 text-primary"></i>Rumah Sakit / Mitra</label>
                                <select id="filter-hospital" class="form-select form-select-sm">
                                    <option value="">Semua Rumah Sakit (10 Faskes)</option>
                                    <?php foreach ($hospitals as $h): ?>
                                        <option value="<?= htmlspecialchars($h['name']) ?>"><?= htmlspecialchars($h['name']) ?> (<?= htmlspecialchars($h['city']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-heartbeat me-1 text-primary"></i>Kondisi Kelayakan</label>
                                <select id="filter-condition" class="form-select form-select-sm">
                                    <option value="">Semua Kondisi Kelayakan</option>
                                    <option value="Layak Pakai">Layak Pakai</option>
                                    <option value="Layak Bersyarat">Layak Bersyarat</option>
                                    <option value="Tidak Layak Pakai">Tidak Layak Pakai</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-tasks me-1 text-primary"></i>Status Operasional</label>
                                <select id="filter-status" class="form-select form-select-sm">
                                    <option value="">Semua Status Operasional</option>
                                    <option value="Siap Digunakan">Siap Digunakan</option>
                                    <option value="Dalam Maintenance">Dalam Maintenance</option>
                                    <option value="Karantina">Karantina / Rusak</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-wrench me-1 text-primary"></i>Tipe Pemeliharaan</label>
                                <select id="filter-type" class="form-select form-select-sm">
                                    <option value="">Semua Tipe Pemeliharaan</option>
                                    <option value="Preventif">Preventif (Pencegahan)</option>
                                    <option value="Korektif">Korektif (Perbaikan)</option>
                                    <option value="Kolektif">Kolektif (Borongan Ruangan)</option>
                                    <option value="Kalibrasi Legal">Kalibrasi Legal Kemenkes</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-clock me-1 text-primary"></i>Siklus Perawatan</label>
                                <select id="filter-cycle" class="form-select form-select-sm">
                                    <option value="">Semua Siklus</option>
                                    <option value="Harian">Harian</option>
                                    <option value="Mingguan">Mingguan</option>
                                    <option value="Bulanan">Bulanan</option>
                                    <option value="Tahunan">Tahunan</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="small text-muted mb-1"><i class="fa fa-search me-1 text-primary"></i>Pencarian Cepat Cerdas</label>
                                <input type="text" id="search-device" class="form-control form-control-sm" placeholder="Cari nama alat, kode, merk, ruangan, RS...">
                            </div>
                        </div>
                    </div>

                    <!-- Responsive Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col" style="min-width: 220px;">Alat Medis & Kode Registrasi</th>
                                    <th scope="col" style="min-width: 150px;">Faskes / Klien</th>
                                    <th scope="col" style="min-width: 140px;">Ruangan / Unit</th>
                                    <th scope="col" style="min-width: 150px;">Kondisi & Kelayakan</th>
                                    <th scope="col" style="min-width: 130px;">Status Operasional</th>
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
                                        $typeBadge = "badge bg-light text-dark border";
                                        if ($d['maintenance_type'] === 'Kolektif') $typeBadge = "badge" . ' style="background:#ede9fe; color:#6d28d9;"';
                                        if ($d['maintenance_type'] === 'Korektif') $typeBadge = "badge" . ' style="background:#fee2e2; color:#991b1b;"';

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
                                        
                                        <!-- Kolom 1: Thumbnail + Nama & Kode -->
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="device-thumb-wrap me-2" title="<?= htmlspecialchars($d['name']) ?>">
                                                    <img src="img/devices/<?= htmlspecialchars($d['image_file']) ?>" alt="<?= htmlspecialchars($d['name']) ?>">
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($d['name']) ?></div>
                                                    <small class="text-muted">
                                                        <span class="text-primary fw-bold"><?= htmlspecialchars($d['device_code']) ?></span> &bull; <?= htmlspecialchars($d['brand_model']) ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Kolom 2: Faskes Klien -->
                                        <td>
                                            <div class="text-dark fw-medium"><?= htmlspecialchars($d['hospital_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($d['hospital_city']) ?></small>
                                        </td>

                                        <!-- Kolom 3: Ruangan -->
                                        <td>
                                            <span class="badge bg-light text-dark border px-2 py-1">
                                                <i class="fa fa-map-marker-alt me-1 text-danger"></i><?= htmlspecialchars($d['room_location']) ?>
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
                                                <span class="badge bg-light text-dark border px-2 py-0" style="font-size: 0.72rem;">
                                                    <i class="fa fa-clock me-1 text-primary"></i><?= htmlspecialchars($d['maintenance_cycle']) ?>
                                                </span>
                                                <span class="<?= $typeBadge ?> px-2 py-0" style="font-size: 0.72rem;">
                                                    <?= htmlspecialchars($d['maintenance_type']) ?>
                                                </span>
                                            </div>
                                            <div class="small text-muted" style="font-size: 0.73rem;">
                                                Tempo: <strong class="text-dark"><?= formatIndo($d['next_maintenance']) ?></strong>
                                            </div>
                                        </td>

                                        <!-- Kolom 7: Sertifikat & Metrologi -->
                                        <td>
                                            <div class="small text-primary fw-bold"><?= htmlspecialchars($d['certificate_number']) ?></div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">
                                                Due: <span class="<?= $d['certificate_status'] === 'Expired' ? 'text-danger fw-bold' : 'text-success' ?>">
                                                    <?= formatIndo($d['next_calibration']) ?>
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Kolom 8: Aksi -->
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="openDetailModal(<?= $d['id'] ?>)" title="Lihat Spesifikasi & Metrologi">
                                                <i class="fa fa-eye"></i> Detail
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

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

            <!-- =====================================================================
                 7. DAFTAR 10 MITRA RUMAH SAKIT (#section-hospitals)
                 ===================================================================== -->
            <div id="section-hospitals" class="container-fluid pt-4 px-4">
                <div class="calibra-card p-4">
                    <div class="card-header-clean">
                        <div>
                            <h6>Daftar Profil 10 Fasilitas Kesehatan Mitra CALIBRAMED</h6>
                            <small class="text-muted">Ringkasan akreditasi, kelas rumah sakit, PIC faskes, serta progres kuota alat</small>
                        </div>
                        <span class="badge bg-success">10 Mitra Aktif</span>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($hospitals as $h): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="p-3 rounded h-100 border" style="background:#ffffff;">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="text-dark fw-bold mb-0" style="font-size: 0.88rem;"><?= htmlspecialchars($h['name']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($h['type_class']) ?> &bull; <?= htmlspecialchars($h['city']) ?></small>
                                    </div>
                                    <span class="badge bg-light text-primary border small"><?= htmlspecialchars($h['contract_tier']) ?></span>
                                </div>
                                <div class="small text-muted mb-1">
                                    Akreditasi: <strong class="text-dark"><?= htmlspecialchars($h['accreditation']) ?></strong>
                                </div>
                                <div class="small text-muted mb-1">
                                    Target: <strong class="text-dark"><?= $h['target_devices'] ?> Unit</strong> &bull; Selesai: <strong class="text-success"><?= $h['completed_devices'] ?></strong>
                                </div>
                                <?php 
                                    $hProgress = $h['target_devices'] > 0 ? round(($h['completed_devices'] / $h['target_devices']) * 100, 1) : 0;
                                ?>
                                <div class="progress mb-2" style="height: 5px;">
                                    <div class="progress-bar bg-primary" style="width: <?= $hProgress ?>%"></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center small text-muted" style="font-size: 0.72rem;">
                                    <span><i class="fa fa-user-tie me-1 text-primary"></i><?= htmlspecialchars($h['pic_name']) ?></span>
                                    <span><?= htmlspecialchars($h['pic_phone']) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- =====================================================================
                 8. RUANG KERJA TEKNISI (#section-workbench)
                 ===================================================================== -->
            <?php if ($isTeknisiUser || $isOwnerUser): ?>
            <div id="section-workbench" class="container-fluid pt-4 px-4">
                <div class="calibra-card p-4">
                    <div class="card-header-clean">
                        <div>
                            <h6>Ruang Kerja Khusus Teknisi & Pembaruan Status Kalibrasi</h6>
                            <small class="text-muted">Akses operasional input hasil pengujian metrologi, keselamatan listrik, dan registrasi alat</small>
                        </div>
                        <button class="btn btn-sm btn-calibra-primary" data-bs-toggle="modal" data-bs-target="#newDeviceModal">
                            <i class="fa fa-plus me-1"></i> Form Input Registrasi Alat Baru
                        </button>
                    </div>

                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="p-3 rounded border" style="background: #f8fafc; border-left: 4px solid var(--brand-teal) !important;">
                                <h6 class="text-dark mb-2" style="font-size: 0.88rem;"><i class="fa fa-clipboard-check text-success me-2"></i>Standar Operasional Prosedur Pengujian Teknisi (SOP)</h6>
                                <ol class="small text-muted ps-3 mb-0" style="font-size: 0.78rem;">
                                    <li class="mb-1"><strong>Inspeksi Fisik & Fungsi:</strong> Memeriksa keutuhan casis, tombol, display, dan grounding kabel daya.</li>
                                    <li class="mb-1"><strong>Uji Keselamatan Listrik (IEC 62353):</strong> Mengukur arus bocor casis (&lt; 100 µA) dan resistansi pembumian (&lt; 0.2 Ω).</li>
                                    <li class="mb-1"><strong>Uji Metrologi:</strong> Menggunakan simulator kalibrasi standar tertelusur BPFK, deviasi harus dalam ambang batas toleransi.</li>
                                    <li><strong>Pemberian Label Kemenkes:</strong> Stiker Hijau (Laik Pakai), Kuning (Laik Bersyarat), atau Merah (Karantina Tidak Laik).</li>
                                </ol>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="p-3 rounded h-100 d-flex flex-column justify-content-center text-center border" style="background:#ffffff;">
                                <div class="text-dark fw-bold mb-1">Butuh Memperbarui Status Alat Tertentu?</div>
                                <p class="small text-muted mb-3" style="font-size: 0.78rem;">Klik tombol <strong>"Detail"</strong> pada baris alat di tabel monitoring untuk membuka form kerja teknisi alat tersebut secara instan.</p>
                                <a href="#section-devices" class="btn btn-sm btn-outline-primary w-75 mx-auto">
                                    <i class="fa fa-arrow-up me-1"></i> Pilih Alat di Tabel Monitoring
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- =====================================================================
                 9. LEGALITAS & SERTIFIKASI (#section-legal)
                 ===================================================================== -->
            <div id="section-legal" class="container-fluid pt-4 px-4">
                <div class="calibra-card p-4">
                    <div class="card-header-clean">
                        <div>
                            <h6>Legalitas, Akreditasi ISO/IEC 17025 & Kepatuhan Regulasi Kemenkes</h6>
                            <small class="text-muted">Landasan hukum dan jaminan mutu pengujian kalibrasi alat kesehatan</small>
                        </div>
                        <span class="badge" style="background:#e8f9f2; color:#008764;">Legal & Terakreditasi Penuh</span>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded h-100 border" style="background:#ffffff;">
                                <div class="text-success fw-bold mb-1 small"><i class="fa fa-gavel me-1"></i>Permenkes RI No. 54 Tahun 2015</div>
                                <p class="small text-muted mb-0" style="font-size: 0.76rem;">
                                    Menetapkan kewajiban setiap alat kesehatan yang dipergunakan di fasilitas pelayanan kesehatan untuk dilakukan pengujian dan/atau kalibrasi secara berkala minimal 1 (satu) kali dalam setahun guna menjamin keselamatan pasien.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded h-100 border" style="background:#ffffff;">
                                <div class="text-primary fw-bold mb-1 small"><i class="fa fa-certificate me-1"></i>Standar ISO/IEC 17025:2017</div>
                                <p class="small text-muted mb-0" style="font-size: 0.76rem;">
                                    Kompetensi umum laboratorium pengujian dan kalibrasi. CALIBRAMED menerapkan sistem manajemen mutu bersertifikat, menjamin ketertelusuran pengukuran (*traceability*) ke Standar Metrologi Nasional dan Internasional.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 rounded h-100 border" style="background:#ffffff;">
                                <div class="text-warning fw-bold mb-1 small"><i class="fa fa-user-shield me-1"></i>Otoritas & Penanggung Jawab Teknis</div>
                                <p class="small text-muted mb-0" style="font-size: 0.76rem;">
                                    Pengujian disupervisi langsung oleh <strong>Prof. Dr.Eng. Ir. Ihsan Setyo Nugroho, S.Tr.T., M.Eng., IPU., ASEAN Eng., APEC Eng., CCE.</strong> selaku Lead Certified Clinical Engineer terlisensi.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =====================================================================
                 10. FOOTER DENGAN PADDING-BOTTOM AMAN
                 ===================================================================== -->
            <div class="container-fluid pt-4 px-4">
                <div class="footer-bar rounded-top">
                    <div class="row align-items-center">
                        <div class="col-12 col-md-6 text-center text-md-start mb-2 mb-md-0">
                            <span class="text-dark fw-bold">CALIBRAMED SYSTEM</span> &copy; 2026. Hak Cipta Dilindungi.
                            <div class="small text-muted">Aplikasi Pemrograman Web - UTS Pemrograman Web Elektromedik</div>
                        </div>
                        <div class="col-12 col-md-6 text-center text-md-end text-muted small">
                            <span>Sistem Terintegrasi Berbasis Standar Kemenkes RI No. 54/2015 & ISO/IEC 17025</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll-to-Top Button (36px, Tidak Menutupi Teks) -->
        <a href="#" class="back-to-top"><i class="bi bi-arrow-up"></i></a>
    </div>

    <!-- =====================================================================
         MODAL DETAIL ALAT & WORKBENCH TEKNISI (LIGHT THEME)
         ===================================================================== -->
    <div class="modal fade" id="deviceDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title text-dark fw-bold">
                            <i class="fa fa-microchip text-primary me-2"></i>Spesifikasi Alat & Lembar Kerja Teknisi
                        </h6>
                        <small class="text-muted">Kode Registrasi: <span id="modal-dev-id" class="text-primary fw-bold"></span></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded h-100 border" style="background:#fcfdfe;">
                                <div class="d-flex align-items-center mb-3 border-bottom pb-2">
                                    <div class="device-thumb-wrap me-2" style="width: 50px; height: 50px;">
                                        <img id="modal-dev-img" src="img/devices/default_device.svg" alt="Device">
                                    </div>
                                    <div>
                                        <h6 id="modal-dev-name" class="text-dark fw-bold mb-0"></h6>
                                        <small id="modal-dev-serial" class="text-muted"></small>
                                    </div>
                                </div>
                                <table class="table table-borderless table-sm small mb-0">
                                    <tr>
                                        <td class="text-muted" style="width: 45%;">Rumah Sakit:</td>
                                        <td id="modal-dev-hospital" class="text-dark fw-medium"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Ruangan Unit:</td>
                                        <td><span id="modal-dev-room" class="badge bg-light text-dark border"></span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Kondisi Fisik:</td>
                                        <td id="modal-dev-physical" class="text-warning fw-medium"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Kelayakan:</td>
                                        <td><span id="modal-dev-condition" class="badge bg-success"></span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Status Operasi:</td>
                                        <td><span id="modal-dev-status" class="badge bg-primary"></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded h-100 border" style="background:#fcfdfe;">
                                <h6 class="text-success border-bottom pb-2 mb-3" style="font-size:0.88rem;">
                                    <i class="fa fa-balance-scale me-1"></i>Metrologi & Keselamatan Listrik
                                </h6>
                                <table class="table table-borderless table-sm small mb-0">
                                    <tr>
                                        <td class="text-muted" style="width: 45%;">No. Sertifikat:</td>
                                        <td id="modal-dev-cert" class="text-primary fw-bold"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Jatuh Tempo:</td>
                                        <td id="modal-dev-calib-due" class="text-dark"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Deviasi/Error Uji:</td>
                                        <td id="modal-dev-error" class="text-dark fw-bold"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Arus Bocor (Leakage):</td>
                                        <td id="modal-dev-leakage" class="text-dark"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Siklus & Tipe:</td>
                                        <td id="modal-dev-cycle" class="text-dark"></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Teknisi PIC:</td>
                                        <td id="modal-dev-pic" class="text-dark fw-medium"></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <?php if ($isTeknisiUser || $isOwnerUser): ?>
                        <div class="col-12 mt-2">
                            <div class="p-3 rounded border" style="background: #f8fafc; border-top: 3px solid var(--brand-teal) !important;">
                                <h6 class="text-dark mb-2" style="font-size: 0.88rem;"><i class="fa fa-wrench text-success me-1"></i>Formulir Kerja Teknisi: Pembaruan Hasil Uji & Status</h6>
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
         MODAL REGISTRASI ALAT MEDIS BARU (LIGHT THEME)
         ===================================================================== -->
    <?php if ($isTeknisiUser || $isOwnerUser): ?>
    <div class="modal fade" id="newDeviceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header">
                    <div>
                        <h6 class="modal-title text-dark fw-bold">
                            <i class="fa fa-plus-circle text-success me-2"></i>Registrasi Inventaris Alat Medis Baru
                        </h6>
                        <small class="text-muted">Masukkan rincian spesifikasi peralatan untuk dipantau dalam sistem</small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

    <!-- Passing Data to JavaScript for Chart.js (PolluxUI Palette) -->
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
