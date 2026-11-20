/**
 * CALIBRAMED SYSTEM - JavaScript Controller
 * Chart.js, Live Multi-Search, SweetAlert2 Interactivity, & Modal Handlers
 */

$(document).ready(function () {
    initCharts();
    initFilterHandlers();
    updateLiveClock();
    setInterval(updateLiveClock, 1000);
});

// ---------------------------------------------------------------------
// 1. Live Timestamp Clock
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
// 2. Chart.js Data Visualizations
// ---------------------------------------------------------------------
let hospitalChartInstance = null;
let conditionChartInstance = null;

function initCharts() {
    // A. Grafik Pengerjaan Tiap Rumah Sakit (10 RS Mitra)
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
                        backgroundColor: "rgba(14, 165, 233, 0.75)",
                        borderColor: "#38bdf8",
                        borderWidth: 1,
                        borderRadius: 3
                    },
                    {
                        label: "Selesai & Layak",
                        data: window.hospitalChartData.completed,
                        backgroundColor: "rgba(34, 197, 94, 0.75)",
                        borderColor: "#4ade80",
                        borderWidth: 1,
                        borderRadius: 3
                    },
                    {
                        label: "Maintenance",
                        data: window.hospitalChartData.maintenance,
                        backgroundColor: "rgba(245, 158, 11, 0.75)",
                        borderColor: "#fbbf24",
                        borderWidth: 1,
                        borderRadius: 3
                    },
                    {
                        label: "Karantina / Rusak",
                        data: window.hospitalChartData.quarantine,
                        backgroundColor: "rgba(244, 63, 94, 0.75)",
                        borderColor: "#fb7185",
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
                            color: '#cbd5e1',
                            font: { family: 'Plus Jakarta Sans', size: 11, weight: '500' },
                            padding: 12,
                            usePointStyle: true,
                            boxWidth: 7
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#38bdf8',
                        bodyColor: '#f8fafc',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 10
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#94a3b8', font: { size: 10 } },
                        grid: { color: 'rgba(51, 65, 85, 0.3)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8', font: { size: 10 } },
                        grid: { color: 'rgba(51, 65, 85, 0.3)' }
                    }
                }
            }
        });
    }

    // B. Donut Chart Distribusi Kelaikan
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
                    backgroundColor: ["#22c55e", "#f59e0b", "#f43f5e"],
                    borderColor: "#1e293b",
                    borderWidth: 2,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#cbd5e1',
                            font: { family: 'Plus Jakarta Sans', size: 10.5 },
                            padding: 10,
                            usePointStyle: true,
                            boxWidth: 7
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
    $("#search-device").on("keyup input", applyFilters);
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

        // Multi-search target text: includes name, code, brand, room, hospital
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
    applyFilters();
}

// ---------------------------------------------------------------------
// 4. Modal Detail Alat & Inspeksi Metrologi
// ---------------------------------------------------------------------
function openDetailModal(deviceId) {
    Swal.fire({
        title: 'Memuat Data Alat...',
        allowOutsideClick: false,
        background: '#1e293b',
        color: '#f8fafc',
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
                            <div class="p-2 mb-2 rounded" style="background:#0f172a; border-left: 3px solid #0ea5e9;">
                                <div class="d-flex justify-content-between small">
                                    <strong class="text-light">${log.action_type}</strong>
                                    <span class="text-muted">${log.log_date}</span>
                                </div>
                                <div class="small text-secondary">${log.findings} &bull; ${log.action_taken}</div>
                                <div class="small text-info">Oleh: ${log.tech_name}</div>
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
                    background: '#1e293b',
                    color: '#f8fafc'
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Error Jaringan',
                text: 'Tidak dapat menghubungi server lokal.',
                background: '#1e293b',
                color: '#f8fafc'
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
        background: '#1e293b',
        color: '#f8fafc',
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
                    background: '#1e293b',
                    color: '#f8fafc',
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
                    background: '#1e293b',
                    color: '#f8fafc'
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Sistem',
                text: 'Gagal mengirim data inspeksi ke server.',
                background: '#1e293b',
                color: '#f8fafc'
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
        background: '#1e293b',
        color: '#f8fafc',
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
                    background: '#1e293b',
                    color: '#f8fafc',
                    confirmButtonColor: '#10b981'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menambah Alat',
                    text: res.message,
                    background: '#1e293b',
                    color: '#f8fafc'
                });
            }
        },
        error: function () {
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Sistem',
                text: 'Gagal memproses pendaftaran alat baru.',
                background: '#1e293b',
                color: '#f8fafc'
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
        background: '#1e293b',
        color: '#f8fafc',
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
                background: '#1e293b',
                color: '#f8fafc',
                timer: 1300,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        }
    }, 'json');
}

// ---------------------------------------------------------------------
// 8. Konfirmasi Logout dengan SweetAlert2
// ---------------------------------------------------------------------
function confirmLogout() {
    Swal.fire({
        title: 'Konfirmasi Keluar',
        text: 'Apakah Anda yakin ingin keluar dari CALIBRAMED SYSTEM?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#475569',
        confirmButtonText: '<i class="fa fa-sign-out-alt me-1"></i> Ya, Keluar',
        cancelButtonText: 'Batal',
        background: '#1e293b',
        color: '#f8fafc'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Mengeluarkan Sesi...',
                background: '#1e293b',
                color: '#f8fafc',
                timer: 900,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'logout.php';
            });
        }
    });
}
