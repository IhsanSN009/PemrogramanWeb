<?php
/**
 * CALIBRAMED SYSTEM - Backend Action Endpoint
 * Menangani Tambah Alat, Update Inspeksi Teknisi, dan Simulator Role
 */

require_once __DIR__ . '/config/database.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Sesi login tidak valid!']);
    exit();
}

$currentUser = getCurrentUser($pdo);
$action = $_REQUEST['action'] ?? '';

// ---------------------------------------------------------------------
// 1. Simulator Role Switcher (Khusus Owner)
// ---------------------------------------------------------------------
if ($action === 'switch_role' && $_SESSION['actual_role'] === 'owner') {
    $targetRole = clean($_POST['target_role'] ?? 'owner');
    $targetHosp = !empty($_POST['target_hospital_id']) ? (int)$_POST['target_hospital_id'] : null;

    if ($targetRole === 'owner') {
        unset($_SESSION['preview_role']);
        unset($_SESSION['preview_hospital_id']);
    } else {
        $_SESSION['preview_role'] = $targetRole;
        $_SESSION['preview_hospital_id'] = $targetHosp;
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => "Mode simulator diubah ke: $targetRole"]);
    exit();
}

// ---------------------------------------------------------------------
// 2. Tambah Alat Medis Baru (Hanya Teknisi & Owner)
// ---------------------------------------------------------------------
if ($action === 'add_device') {
    if (!isTeknisi() && !isOwner()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk menambah alat!']);
        exit();
    }

    $device_code       = clean($_POST['device_code'] ?? '');
    $hospital_id       = (int)($_POST['hospital_id'] ?? 0);
    $name              = clean($_POST['name'] ?? '');
    $brand_model       = clean($_POST['brand_model'] ?? '');
    $serial_number     = clean($_POST['serial_number'] ?? '');
    $room_location     = clean($_POST['room_location'] ?? '');
    $image_file        = clean($_POST['image_file'] ?? 'default_device.svg');
    $condition_state   = clean($_POST['condition_state'] ?? 'Layak Pakai');
    $physical_note     = clean($_POST['physical_note'] ?? 'Peralatan baru terdaftar.');
    $operation_status  = clean($_POST['operation_status'] ?? 'Siap Digunakan');
    $maintenance_type  = clean($_POST['maintenance_type'] ?? 'Preventif');
    $maintenance_cycle = clean($_POST['maintenance_cycle'] ?? 'Bulanan');
    $last_maintenance  = clean($_POST['last_maintenance'] ?? date('Y-m-d'));
    $next_maintenance  = clean($_POST['next_maintenance'] ?? date('Y-m-d', strtotime('+30 days')));
    $last_calibration  = clean($_POST['last_calibration'] ?? date('Y-m-d'));
    $next_calibration  = clean($_POST['next_calibration'] ?? date('Y-m-d', strtotime('+1 year')));
    $certificate_no    = clean($_POST['certificate_number'] ?? 'CERT/CAL/' . date('Y/m') . '-' . rand(1000, 9999));
    $error_deviation   = clean($_POST['error_deviation'] ?? '0.0% (Toleransi < ±5%)');
    $leakage_current   = clean($_POST['leakage_current'] ?? '30 µA (Max: 100 µA)');
    $pic_engineer      = clean($currentUser['full_title']);

    if (empty($device_code) || empty($name) || empty($hospital_id)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Kode Alat, Nama Alat, dan Rumah Sakit wajib diisi!']);
        exit();
    }

    // Cek duplikasi kode
    $chk = $pdo->prepare("SELECT id FROM devices WHERE device_code = ?");
    $chk->execute([$device_code]);
    if ($chk->fetch()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => "Kode alat '$device_code' sudah terdaftar! Gunakan kode lain."]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO devices 
            (device_code, hospital_id, name, brand_model, serial_number, room_location, image_file, condition_state, 
             physical_note, operation_status, maintenance_type, maintenance_cycle, last_maintenance, next_maintenance, 
             last_calibration, next_calibration, certificate_number, certificate_status, error_deviation, leakage_current, pic_engineer) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Valid', ?, ?, ?)");
        
        $stmt->execute([
            $device_code, $hospital_id, $name, $brand_model, $serial_number, $room_location, $image_file,
            $condition_state, $physical_note, $operation_status, $maintenance_type, $maintenance_cycle,
            $last_maintenance, $next_maintenance, $last_calibration, $next_calibration, $certificate_no,
            $error_deviation, $leakage_current, $pic_engineer
        ]);

        $newId = $pdo->lastInsertId();

        // Update counter rumah sakit
        $pdo->query("UPDATE hospitals SET target_devices = target_devices + 1, completed_devices = completed_devices + 1 WHERE id = $hospital_id");

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => "Alat medis $name ($device_code) berhasil didaftarkan!", 'id' => $newId]);
        exit();
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan ke database: ' . $e->getMessage()]);
        exit();
    }
}

// ---------------------------------------------------------------------
// 3. Update Status & Catat Laporan Teknisi (Technician Workbench)
// ---------------------------------------------------------------------
if ($action === 'update_inspection') {
    if (!isTeknisi() && !isOwner()) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk memperbarui status alat!']);
        exit();
    }

    $device_id        = (int)($_POST['device_id'] ?? 0);
    $operation_status = clean($_POST['operation_status'] ?? 'Siap Digunakan');
    $condition_state  = clean($_POST['condition_state'] ?? 'Layak Pakai');
    $physical_note    = clean($_POST['physical_note'] ?? '');
    $error_deviation  = clean($_POST['error_deviation'] ?? '');
    $leakage_current  = clean($_POST['leakage_current'] ?? '');
    $action_taken     = clean($_POST['action_taken'] ?? 'Inspeksi & Kalibrasi Berkala');
    $tech_notes       = clean($_POST['tech_notes'] ?? '');

    if ($device_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'ID Alat tidak valid!']);
        exit();
    }

    try {
        // Update device
        $upd = $pdo->prepare("UPDATE devices SET 
            operation_status = ?, 
            condition_state = ?, 
            physical_note = CASE WHEN ? != '' THEN ? ELSE physical_note END,
            error_deviation = CASE WHEN ? != '' THEN ? ELSE error_deviation END,
            leakage_current = CASE WHEN ? != '' THEN ? ELSE leakage_current END,
            last_maintenance = CURRENT_DATE,
            updated_at = NOW()
            WHERE id = ?");
        
        $upd->execute([
            $operation_status, $condition_state, 
            $physical_note, $physical_note,
            $error_deviation, $error_deviation,
            $leakage_current, $leakage_current,
            $device_id
        ]);

        // Simpan log riwayat
        $log = $pdo->prepare("INSERT INTO maintenance_logs 
            (device_id, user_id, log_date, action_type, findings, action_taken, result_condition, result_status, notes) 
            VALUES (?, ?, NOW(), 'Inspeksi & Pembaruan Status', ?, ?, ?, ?, ?)");
        $log->execute([
            $device_id, 
            $currentUser['id'], 
            ($physical_note ?: 'Pemeriksaan rutin kelayakan alat.'),
            $action_taken, 
            $condition_state, 
            $operation_status, 
            $tech_notes
        ]);

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Status dan riwayat kalibrasi berhasil diperbarui di sistem!']);
        exit();
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui: ' . $e->getMessage()]);
        exit();
    }
}

// ---------------------------------------------------------------------
// 4. Fetch Single Device Data (Untuk Modal Detail)
// ---------------------------------------------------------------------
if ($action === 'get_device') {
    $device_id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT d.*, h.name AS hospital_name, h.city AS hospital_city, h.type_class AS hospital_type 
                           FROM devices d 
                           JOIN hospitals h ON d.hospital_id = h.id 
                           WHERE d.id = ?");
    $stmt->execute([$device_id]);
    $dev = $stmt->fetch();

    if ($dev) {
        // Ambil riwayat log terakhir
        $logStmt = $pdo->prepare("SELECT l.*, u.full_title AS tech_name 
                                  FROM maintenance_logs l 
                                  JOIN users u ON l.user_id = u.id 
                                  WHERE l.device_id = ? 
                                  ORDER BY l.log_date DESC LIMIT 3");
        $logStmt->execute([$device_id]);
        $dev['logs'] = $logStmt->fetchAll();

        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'data' => $dev]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Alat tidak ditemukan!']);
    }
    exit();
}

header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Action tidak dikenali!']);
exit();
