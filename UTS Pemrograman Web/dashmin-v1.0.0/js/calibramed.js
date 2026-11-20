/**
 * CALIBRAMED SYSTEM - JavaScript Controller (PolluxUI Theme)
 * Chart.js Light Mode, Live Multi-Search, SweetAlert2 Light Theme, & Modal Handlers
 */

$(document).ready(function () {
    initCharts();
    initFilterHandlers();
    updateLiveClock();
    setInterval(updateLiveClock, 1000);
});

// ---------------------------------------------------------------------
// 1. Live Timestamp Clock (Format Indonesia)
// ---------------------------------------------------------------------
function updateLiveClock() {
    const now = new Date();
    const options = { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    };
    $('#live-timestamp').text(now.toLocaleDateString('id-ID', options) + ' WIB');
}

// ---------------------------------------------------------------------
// 2. Chart.js Data Visualizations (PolluxUI Palette)
// ---------------------------------------------------------------------
let hospitalChartInstance = null;
let conditionChartInstance = null;

function initCharts() {
    // A. Grafik Pengerjaan Tiap Rumah Sakit (10 RS Mitra) - Multi-colored Bar Chart
    const ctxHospital = document.getElementById("hospital-progress-chart");
    if (ctxHospital && window.hospitalChartData) {
        hospitalChartInstance = new Chart(ctxHospital.getContext("2d"), {
            type: "bar",
            data: {
                labels: window.hospitalChartData.labels,
                datasets: [
                    {
                        label: "Target Alat",
                        data: window.hospitalChartData.targets,
                        backgroundColor: "#6f42c1", // Pollux Purple
                        borderColor: "#593196",
                        borderWidth: 1,
                        borderRadius: 3
                    },
                    {
                        label: "Selesai & Layak",
                        data: window.hospitalChartData.completed,
                        backgroundColor: "#00c292", // Pollux Teal
                        borderColor: "#00a87e",
                        borderWidth: 1,
                        borderRadius: 3
                    },
                    {
                        label: "Maintenance",
                        data: window.hospitalChartData.maintenance,
                        backgroundColor: "#f1b44c", // Pollux Orange
                        borderColor: "#d99726",
                        borderWidth: 1,
                        borderRadius: 3
                    },
                    {
                        label: "Karantina / Rusak",
                        data: window.hospitalChartData.quarantine,
                        backgroundColor: "#f46a6a", // Pollux Coral Red
                        borderColor: "#d64747",
                        borderWidth: 1,
                        borderRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#495057',
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: '600' },
                            padding: 12,
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: {
                        backgroundColor: '#2a3042',
                        titleColor: '#00c292',
                        bodyColor: '#ffffff',
                        borderWidth: 0,
                        padding: 10
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#6c757d', font: { size: 10 } },
                        grid: { color: 'rgba(0, 0, 0, 0.04)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#6c757d', font: { size: 10 } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    }
                }
            }
        });
    }

    // B. Donut Chart Distribusi Kelaikan (PolluxUI Vibrant Slice Palette)
    const ctxCondition = document.getElementById("equipment-condition-chart");
    if (ctxCondition && window.conditionCounts) {
        conditionChartInstance = new Chart(ctxCondition.getContext("2d"), {
            type: "doughnut",
            data: {
                labels: ["Layak Pakai", "Layak Bersyarat", "Karantina / Rusak"],
                datasets: [{
                    data: [
                        window.conditionCounts.layak,
                        window.conditionCounts.bersyarat,
                        window.conditionCounts.rusak
                    ],
                    backgroundColor: [
                        "#00c292", // Vibrant Teal
                        "#f1b44c", // Amber Gold
                        "#f46a6a"  // Coral Red
                    ],
                    borderColor: "#ffffff",
                    borderWidth: 3,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#495057',
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: '500' },
                            padding: 10,
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    }
                }
            }
        });
    }
}

// ---------------------------------------------------------------------
// 3. Live Multi-Search & 2-Row Filter Grid Controller
// ---------------------------------------------------------------------
function initFilterHandlers() {
    $("#filter-hospital, #filter-condition, #filter-status, #filter-type, #filter-cycle").on("change", applyFilters);
    $("#search-device, #nav-search-device").on("keyup input", function () {
        // Sinkronisasi nilai jika diketik di search bar navbar atau search bar tabel
        const val = $(this).val();
        $("#search-device").val(val);
        $("#nav-search-device").val(val);
        applyFilters();
    });
}

function applyFilters() {
    const selectedHospital = ($("#filter-hospital").val() || "").toLowerCase();
    const selectedCond     = ($("#filter-condition").val() || "").toLowerCase();
    const selectedStatus   = ($("#filter-status").val() || "").toLowerCase();
    const selectedType     = ($("#filter-type").val() || "").toLowerCase();
    const selectedCycle    = ($("#filter-cycle").val() || "").toLowerCase();
    const query            = ($("#search-device").val() || "").toLowerCase().trim();

    let visibleCount = 0;

    $("#device-table-body tr.device-row").each(function () {
        const row = $(this);
        const hosp  = (row.data("hospital") || "").toLowerCase();
        const cond  = (row.data("condition") || "").toLowerCase();
        const stat  = (row.data("status") || "").toLowerCase();
        const type  = (row.data("type") || "").toLowerCase();
        const cycle = (row.data("cycle") || "").toLowerCase();

        // Multi-search target text
        const searchText = (row.data("search") || "").toLowerCase();

        const matchHosp  = !selectedHospital || hosp === selectedHospital;
        const matchCond  = !selectedCond || cond === selectedCond;
        const matchStat  = !selectedStatus || stat.includes(selectedStatus);
        const matchType  = !selectedType || type.includes(selectedType);
        const matchCycle = !selectedCycle || cycle === selectedCycle;
        const matchQuery = !query || searchText.includes(query);

        if (matchHosp && matchCond && matchStat && matchType && matchCycle && matchQuery) {
            row.show();
            visibleCount++;
        } else {
            row.hide();
        }
    });

    // Update Counter Badge
    $("#device-count-badge").text(visibleCount + " Unit Ditampilkan");

    // Tampilkan baris "Tidak Ditemukan" jika kosong
    if (visibleCount === 0) {
        $("#no-device-row").removeClass("d-none");
    } else {
        $("#no-device-row").addClass("d-none");
    }
}

function resetFilters() {
    $("#filter-hospital").val("");
    $("#filter-condition").val("");
    $("#filter-status").val("");
    $("#filter-type").val("");
    $("#filter-cycle").val("");
    $("#search-device").val("");
    $("#nav-search-device").val("");
    applyFilters();
}

// ---------------------------------------------------------------------
// 4. Modal Detail Alat & Inspeksi Metrologi (SweetAlert2 Light Mode)
// ---------------------------------------------------------------------
function openDetailModal(deviceId) {
    Swal.fire({
        title: 'Memuat Data Alat...',
        allowOutsideClick: false,
        background: '#ffffff',
        color: '#2a3042',
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        url: 'device_action.php?action=get_device&id=' + deviceId,
        type: 'GET',
        dataType: 'json',
        success: function (res) {
            Swal.close();
            if (res.status === 'success') {
                const dev = res.data;

                $('#modal-dev-id').text(dev.device_code);
                $('#modal-dev-name').text(dev.name + ' (' + dev.brand_model + ')');
                $('#modal-dev-serial').text(dev.serial_number);
                $('#modal-dev-hospital').text(dev.hospital_name + ' (' + dev.hospital_city + ')');
                $('#modal-dev-room').text(dev.room_location);
                $('#modal-dev-physical').text(dev.physical_note);
                $('#modal-dev-condition').text(dev.condition_state);
                $('#modal-dev-status').text(dev.operation_status);
                $('#modal-dev-cert').text(dev.certificate_number);
                $('#modal-dev-calib-due').text(dev.next_calibration);
                $('#modal-dev-error').text(dev.error_deviation);
                $('#modal-dev-leakage').text(dev.leakage_current);
                $('#modal-dev-cycle').text(dev.maintenance_cycle + ' (' + dev.maintenance_type + ')');
                $('#modal-dev-pic').text(dev.pic_engineer);
                $('#modal-dev-img').attr('src', 'img/devices/' + dev.image_file);

                // Set form input untuk workbench teknisi
                $('#inspect-device-id').val(dev.id);
                $('#inspect-status').val(dev.operation_status);
                $('#inspect-condition').val(dev.condition_state);
                $('#inspect-physical').val(dev.physical_note);
                $('#inspect-error').val(dev.error_deviation);
                $('#inspect-leakage').val(dev.leakage_current);

                // Tampilkan log riwayat
                const logsList = $('#modal-logs-list');
                logsList.empty();
                if (dev.logs && dev.logs.length > 0) {
                    dev.logs.forEach(log => {
                        logsList.append(`
                            <div class="p-2 mb-2 rounded" style="background:#f8f9fa; border-left: 3px solid #00c292;">
                                <div class="d-flex justify-content-between small">
                                    <strong class="text-dark">${log.action_type}</strong>
                                    <span class="text-muted">${log.log_date}</span>
                                </div>
                                <div class="small text-secondary">${log.findings} &bull; ${log.action_taken}</div>
                                <div class="small text-success">Oleh: ${log.tech_name}</div>
                            </div>
                        `);
                    });
                } else {
                    logsList.append('<div class="small text-muted">Belum ada riwayat perbaikan tercatat.</div>');
                }

                const modal = new bootstrap.Modal(document.getElementById('deviceDetailModal'));
                modal.show();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: res.message,
                    background: '#ffffff',
                    color: '#2a3042'
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Error Jaringan',
                text: 'Tidak dapat menghubungi server lokal.',
                background: '#ffffff',
                color: '#2a3042'
            });
        }
    });
}

// ---------------------------------------------------------------------
// 5. Submit Update Hasil Inspeksi Teknisi
// ---------------------------------------------------------------------
function submitInspection() {
    const form = document.getElementById('inspectionForm');
    const formData = new FormData(form);

    Swal.fire({
        title: 'Menyimpan Pembaruan...',
        allowOutsideClick: false,
        background: '#ffffff',
        color: '#2a3042',
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        url: 'device_action.php?action=update_inspection',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: res.message,
                    background: '#ffffff',
                    color: '#2a3042',
                    timer: 1600,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Memperbarui',
                    text: res.message,
                    background: '#ffffff',
                    color: '#2a3042'
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Sistem',
                text: 'Gagal mengirim data inspeksi ke server.',
                background: '#ffffff',
                color: '#2a3042'
            });
        }
    });
}

// ---------------------------------------------------------------------
// 6. Submit Tambah Alat Medis Baru
// ---------------------------------------------------------------------
function submitNewDevice() {
    const form = document.getElementById('addDeviceForm');
    const formData = new FormData(form);

    Swal.fire({
        title: 'Mendaftarkan Alat...',
        allowOutsideClick: false,
        background: '#ffffff',
        color: '#2a3042',
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        url: 'device_action.php?action=add_device',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Pendaftaran Sukses!',
                    text: res.message,
                    background: '#ffffff',
                    color: '#2a3042',
                    confirmButtonColor: '#00c292'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menambah Alat',
                    text: res.message,
                    background: '#ffffff',
                    color: '#2a3042'
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Sistem',
                text: 'Gagal memproses pendaftaran alat baru.',
                background: '#ffffff',
                color: '#2a3042'
            });
        }
    });
}

// ---------------------------------------------------------------------
// 7. Role Switcher Simulator (Khusus Owner)
// ---------------------------------------------------------------------
function switchOwnerRole(role, hospitalId) {
    Swal.fire({
        title: 'Mengubah Mode Tampilan...',
        allowOutsideClick: false,
        background: '#ffffff',
        color: '#2a3042',
        didOpen: () => { Swal.showLoading(); }
    });

    $.post('device_action.php', {
        action: 'switch_role',
        target_role: role,
        target_hospital_id: hospitalId
    }, function (res) {
        if (res.status === 'success') {
            Swal.fire({
                icon: 'info',
                title: 'Mode Berhasil Diubah',
                text: res.message,
                background: '#ffffff',
                color: '#2a3042',
                timer: 1300,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        }
    }, 'json');
}

// ---------------------------------------------------------------------
// 8. Konfirmasi Logout dengan SweetAlert2 (Light Mode)
// ---------------------------------------------------------------------
function confirmLogout() {
    Swal.fire({
        title: 'Konfirmasi Keluar',
        text: 'Apakah Anda yakin ingin keluar dari CALIBRAMED SYSTEM?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f46a6a',
        cancelButtonColor: '#74788d',
        confirmButtonText: '<i class="fa fa-sign-out-alt me-1"></i> Ya, Keluar',
        cancelButtonText: 'Batal',
        background: '#ffffff',
        color: '#2a3042'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Mengeluarkan Sesi...',
                background: '#ffffff',
                color: '#2a3042',
                timer: 900,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'logout.php';
            });
        }
    });
}
