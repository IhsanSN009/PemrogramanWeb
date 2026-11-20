/**
 * CALIBRAMED SYSTEM - Biomedical Calibration & Maintenance Management
 * Interactive Logic, Charts, Dynamic Filtering & Modal Inspector
 */

// Dataset Inventaris Alat Medis Lengkap
const calibraDevices = [
    {
        id: "CLM-DFB-014",
        name: "Defibrillator & Pacer",
        brand: "Mindray BeneHeart D3",
        serial: "SN-DFB-99201",
        hospital: "RSUD Dr. Soetomo",
        room: "Ruang IGD Resusitasi",
        condition: "Layak Pakai",
        physical: "Normal & Terkalibrasi",
        status: "Siap Digunakan",
        maintType: "Preventif",
        cycle: "Bulanan",
        lastMaint: "15 Okt 2026",
        nextMaint: "15 Nov 2026",
        lastCalib: "12 Apr 2026",
        nextCalib: "12 Apr 2027",
        calibStatus: "Valid",
        errorDev: "+0.8% (Toleransi < ±5%)",
        leakageCurrent: "42 µA (Max: 100 µA)",
        certNo: "CERT/CAL/2026/04-0992",
        pic: "Eng. Raditya A."
    },
    {
        id: "CLM-VNT-008",
        name: "Mechanical Ventilator",
        brand: "Hamilton C1",
        serial: "SN-VNT-44102",
        hospital: "RSUD Dr. Soetomo",
        room: "Ruang ICU Lantai 3",
        condition: "Layak Pakai",
        physical: "Normal & Terkalibrasi",
        status: "Siap Digunakan",
        maintType: "Kolektif",
        cycle: "Mingguan",
        lastMaint: "14 Nov 2026",
        nextMaint: "21 Nov 2026",
        lastCalib: "08 Jan 2026",
        nextCalib: "08 Jan 2027",
        calibStatus: "Valid",
        errorDev: "-1.2% (Toleransi < ±3%)",
        leakageCurrent: "55 µA (Max: 100 µA)",
        certNo: "CERT/CAL/2026/01-0412",
        pic: "Eng. Dimas P."
    },
    {
        id: "CLM-INF-102",
        name: "Infusion Pump",
        brand: "Terumo TE-112",
        serial: "SN-INF-77812",
        hospital: "RS Siloam Hospitals",
        room: "Rawat Inap Melati 4",
        condition: "Layak Bersyarat",
        physical: "Daya Baterai Cadangan Melemah",
        status: "Dalam Maintenance",
        maintType: "Korektif",
        cycle: "Bulanan",
        lastMaint: "02 Nov 2026",
        nextMaint: "02 Des 2026",
        lastCalib: "18 Mei 2026",
        nextCalib: "18 Mei 2027",
        calibStatus: "Valid",
        errorDev: "+2.1% (Toleransi < ±5%)",
        leakageCurrent: "35 µA (Max: 100 µA)",
        certNo: "CERT/CAL/2026/05-1802",
        pic: "Eng. Farhan H."
    },
    {
        id: "CLM-ECG-031",
        name: "ECG 12-Lead Diagnostic",
        brand: "Nihon Kohden Cardiofax M",
        serial: "SN-ECG-33910",
        hospital: "RS Siloam Hospitals",
        room: "Poli Jantung & Vaskular",
        condition: "Layak Pakai",
        physical: "Normal & Terkalibrasi",
        status: "Siap Digunakan",
        maintType: "Preventif",
        cycle: "Harian",
        lastMaint: "19 Nov 2026",
        nextMaint: "20 Nov 2026",
        lastCalib: "04 Mar 2026",
        nextCalib: "04 Mar 2027",
        calibStatus: "Valid",
        errorDev: "+0.3% (Toleransi < ±2%)",
        leakageCurrent: "28 µA (Max: 50 µA)",
        certNo: "CERT/CAL/2026/03-0391",
        pic: "Eng. Ihsan S."
    },
    {
        id: "CLM-XRY-002",
        name: "Mobile Digital X-Ray",
        brand: "Siemens Polymobil Plus",
        serial: "SN-XRY-11209",
        hospital: "RS Premier Surabaya",
        room: "Ruang Radiologi & Imaging",
        condition: "Tidak Layak Pakai",
        physical: "Collimator Error / Kv Output Dropped",
        status: "Karantina / Rusak",
        maintType: "Korektif",
        cycle: "Tahunan",
        lastMaint: "10 Nov 2026",
        nextMaint: "Segera / On-going",
        lastCalib: "14 Nov 2025",
        nextCalib: "14 Nov 2026 (Expired)",
        calibStatus: "Expired",
        errorDev: "-14.5% (Toleransi < ±5%)",
        leakageCurrent: "180 µA (Max: 100 µA)",
        certNo: "UNFIT/QUARANTINE-2026/09",
        pic: "Eng. Suryo T."
    },
    {
        id: "CLM-MON-088",
        name: "Bedside Patient Monitor",
        brand: "Philips IntelliVue MX450",
        serial: "SN-MON-66504",
        hospital: "RSUD Dr. Soetomo",
        room: "Kamar Bedah Sentral (OK-04)",
        condition: "Layak Pakai",
        physical: "Normal & Terkalibrasi",
        status: "Siap Digunakan",
        maintType: "Kolektif",
        cycle: "Mingguan",
        lastMaint: "16 Nov 2026",
        nextMaint: "23 Nov 2026",
        lastCalib: "20 Feb 2026",
        nextCalib: "20 Feb 2027",
        calibStatus: "Valid",
        errorDev: "+0.5% (Toleransi < ±2%)",
        leakageCurrent: "31 µA (Max: 100 µA)",
        certNo: "CERT/CAL/2026/02-0665",
        pic: "Eng. Raditya A."
    },
    {
        id: "CLM-DNT-004",
        name: "Dental Unit Smart Chair",
        brand: "Gnatus S400 Lux",
        serial: "SN-DNT-55219",
        hospital: "RS Unair Hospital",
        room: "Poli Gigi & Mulut",
        condition: "Layak Bersyarat",
        physical: "Solenoid Valve Air Bocor Ringan",
        status: "Dalam Maintenance",
        maintType: "Preventif",
        cycle: "Mingguan",
        lastMaint: "17 Nov 2026",
        nextMaint: "24 Nov 2026",
        lastCalib: "10 Jun 2026",
        nextCalib: "10 Jun 2027",
        calibStatus: "Valid",
        errorDev: "+1.0% (Toleransi < ±5%)",
        leakageCurrent: "49 µA (Max: 100 µA)",
        certNo: "CERT/CAL/2026/06-0552",
        pic: "Eng. Dimas P."
    },
    {
        id: "CLM-INC-011",
        name: "Infant Incubator",
        brand: "Draeger Isolette 8000",
        serial: "SN-INC-99321",
        hospital: "RS Mitra Keluarga",
        room: "Ruang Perinatologi / NICU",
        condition: "Layak Pakai",
        physical: "Normal & Terkalibrasi",
        status: "Siap Digunakan",
        maintType: "Kalibrasi Legal",
        cycle: "Bulanan",
        lastMaint: "05 Nov 2026",
        nextMaint: "05 Des 2026",
        lastCalib: "22 Agt 2026",
        nextCalib: "22 Agt 2027",
        calibStatus: "Valid",
        errorDev: "+0.2°C (Toleransi < ±0.5°C)",
        leakageCurrent: "38 µA (Max: 100 µA)",
        certNo: "CERT/CAL/2026/08-0993",
        pic: "Eng. Farhan H."
    },
    {
        id: "CLM-SYR-045",
        name: "Syringe Pump Precision",
        brand: "B. Braun Perfusor Space",
        serial: "SN-SYR-12340",
        hospital: "RS Premier Surabaya",
        room: "Ruang ICU Lantai 2",
        condition: "Layak Pakai",
        physical: "Normal & Terkalibrasi",
        status: "Siap Digunakan",
        maintType: "Preventif",
        cycle: "Harian",
        lastMaint: "19 Nov 2026",
        nextMaint: "20 Nov 2026",
        lastCalib: "11 Sep 2026",
        nextCalib: "11 Sep 2027",
        calibStatus: "Valid",
        errorDev: "-0.9% (Toleransi < ±3%)",
        leakageCurrent: "25 µA (Max: 100 µA)",
        certNo: "CERT/CAL/2026/09-0123",
        pic: "Eng. Ihsan S."
    },
    {
        id: "CLM-ATC-003",
        name: "Autoclave Sterilizer High-Pressure",
        brand: "Tuttnauer 3870EL",
        serial: "SN-ATC-88123",
        hospital: "Klinik Parahita Diagnostic",
        room: "Laboratorium CSSD & Sterilisasi",
        condition: "Tidak Layak Pakai",
        physical: "Sensor Tekanan & Suhu Deviasi Ekstrem",
        status: "Karantina / Rusak",
        maintType: "Korektif",
        cycle: "Bulanan",
        lastMaint: "01 Nov 2026",
        nextMaint: "Sedang Dipesankan Sensor",
        lastCalib: "05 Nov 2025",
        nextCalib: "05 Nov 2026 (Expired)",
        calibStatus: "Expired",
        errorDev: "+6.8°C (Toleransi < ±1.0°C)",
        leakageCurrent: "120 µA (Max: 100 µA)",
        certNo: "UNFIT/QUARANTINE-2026/11",
        pic: "Eng. Suryo T."
    }
];

// Document Ready
$(document).ready(function () {
    initCharts();
    renderDeviceTable(calibraDevices);
    initFilterHandlers();
    updateLiveClock();
    setInterval(updateLiveClock, 1000);
});

// Realtime Clock Header
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
    $('#live-timestamp').text(now.toLocaleDateString('id-ID', options));
}

// Inisialisasi Chart.js
let hospitalChartInstance = null;
let conditionChartInstance = null;
let cycleChartInstance = null;

function initCharts() {
    // 1. Grafik Pengerjaan Tiap Rumah Sakit (Multi-Hospital Progress Bar Chart)
    const ctxHospital = document.getElementById("hospital-progress-chart");
    if (ctxHospital) {
        hospitalChartInstance = new Chart(ctxHospital.getContext("2d"), {
            type: "bar",
            data: {
                labels: [
                    "RSUD Dr. Soetomo", 
                    "RS Siloam", 
                    "RS Premier", 
                    "RS Unair", 
                    "RS Mitra Keluarga"
                ],
                datasets: [
                    {
                        label: "Total Alat Ditangani",
                        data: [420, 280, 210, 190, 148],
                        backgroundColor: "rgba(14, 165, 233, 0.85)",
                        borderColor: "#38bdf8",
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: "Sudah Ditangani & Layak",
                        data: [360, 245, 180, 165, 135],
                        backgroundColor: "rgba(16, 185, 129, 0.85)",
                        borderColor: "#34d399",
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: "Dalam Masa Maintenance",
                        data: [42, 25, 20, 15, 8],
                        backgroundColor: "rgba(245, 158, 11, 0.85)",
                        borderColor: "#fbbf24",
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: "Tidak Layak / Karantina",
                        data: [18, 10, 10, 10, 5],
                        backgroundColor: "rgba(244, 63, 94, 0.85)",
                        borderColor: "#fb7185",
                        borderWidth: 1,
                        borderRadius: 4
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
                            font: { family: 'Heebo', size: 12, weight: '500' },
                            padding: 15,
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#38bdf8',
                        bodyColor: '#f8fafc',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#94a3b8', font: { family: 'Heebo' } },
                        grid: { color: 'rgba(51, 65, 85, 0.4)' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8', font: { family: 'Heebo' } },
                        grid: { color: 'rgba(51, 65, 85, 0.4)' }
                    }
                }
            }
        });
    }

    // 2. Grafik Distribusi Kondisi & Status Alat (Doughnut)
    const ctxCondition = document.getElementById("equipment-condition-chart");
    if (ctxCondition) {
        conditionChartInstance = new Chart(ctxCondition.getContext("2d"), {
            type: "doughnut",
            data: {
                labels: [
                    "Layak Pakai & Siap Operasional", 
                    "Preventif Terjadwal", 
                    "Dalam Maintenance / Perbaikan", 
                    "Tidak Layak / Karantina"
                ],
                datasets: [{
                    data: [1085, 85, 52, 26],
                    backgroundColor: [
                        "#10b981", 
                        "#0ea5e9", 
                        "#f59e0b", 
                        "#f43f5e"
                    ],
                    borderColor: "#1e293b",
                    borderWidth: 3,
                    hoverOffset: 6
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
                            color: '#cbd5e1',
                            font: { family: 'Heebo', size: 11 },
                            padding: 12,
                            usePointStyle: true,
                            boxWidth: 8
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
                }
            }
        });
    }

    // 3. Grafik Kepatuhan Siklus Maintenance (Harian, Mingguan, Bulanan, Tahunan)
    const ctxCycle = document.getElementById("maintenance-cycle-chart");
    if (ctxCycle) {
        cycleChartInstance = new Chart(ctxCycle.getContext("2d"), {
            type: "bar",
            data: {
                labels: ["Harian", "Mingguan", "Bulanan", "Tahunan (Kalibrasi)"],
                datasets: [
                    {
                        label: "Target Unit",
                        data: [45, 120, 210, 350],
                        backgroundColor: "rgba(99, 102, 241, 0.4)",
                        borderColor: "#818cf8",
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: "Realisasi Selesai",
                        data: [38, 95, 182, 310],
                        backgroundColor: "rgba(16, 185, 129, 0.8)",
                        borderColor: "#34d399",
                        borderWidth: 1,
                        borderRadius: 4
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#cbd5e1',
                            font: { family: 'Heebo', size: 11 },
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(51, 65, 85, 0.4)' }
                    },
                    y: {
                        ticks: { color: '#94a3b8' },
                        grid: { color: 'rgba(51, 65, 85, 0.4)' }
                    }
                }
            }
        });
    }
}

// Render Tabel Inventaris & Monitoring
function renderDeviceTable(devices) {
    const tbody = $("#device-table-body");
    tbody.empty();

    if (devices.length === 0) {
        tbody.append(`
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                    <i class="fa fa-info-circle me-2"></i>Tidak ditemukan alat dengan filter yang dipilih.
                </td>
            </tr>
        `);
        $("#device-count-badge").text("0 Unit Ditampilkan");
        return;
    }

    devices.forEach((dev) => {
        // Kondisi Badge
        let condBadgeClass = "badge-layak";
        let condIcon = "fa-check-circle";
        if (dev.condition === "Tidak Layak Pakai") {
            condBadgeClass = "badge-rusak";
            condIcon = "fa-times-circle";
        } else if (dev.condition === "Layak Bersyarat") {
            condBadgeClass = "badge-bersyarat";
            condIcon = "fa-exclamation-circle";
        }

        // Status Badge
        let statusBadgeClass = "badge-ready";
        if (dev.status === "Dalam Maintenance") {
            statusBadgeClass = "badge-maint";
        } else if (dev.status.includes("Karantina")) {
            statusBadgeClass = "badge-quarantine";
        }

        // Tipe Badge
        let typeBadgeClass = "badge-type-preventif";
        if (dev.maintType === "Kolektif") typeBadgeClass = "badge-type-kolektif";
        if (dev.maintType === "Korektif") typeBadgeClass = "badge-type-korektif";
        if (dev.maintType.includes("Kalibrasi")) typeBadgeClass = "badge-type-legal";

        // Cycle Indicator
        let cycleColor = "text-info";
        if (dev.cycle === "Harian") cycleColor = "text-primary";
        if (dev.cycle === "Mingguan") cycleColor = "text-warning";
        if (dev.cycle === "Bulanan") cycleColor = "text-success";
        if (dev.cycle === "Tahunan") cycleColor = "text-danger";

        const row = `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="me-2 text-primary"><i class="fa fa-stethoscope"></i></div>
                        <div>
                            <div class="fw-bold text-light">${dev.name}</div>
                            <small class="text-muted">${dev.brand} &bull; <span class="text-info">${dev.id}</span></small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="text-light fw-medium">${dev.hospital}</div>
                </td>
                <td>
                    <span class="badge bg-dark border border-secondary text-info px-2 py-1">
                        <i class="fa fa-map-marker-alt me-1"></i>${dev.room}
                    </span>
                </td>
                <td>
                    <span class="badge-status ${condBadgeClass}">
                        <i class="fa ${condIcon}"></i>${dev.condition}
                    </span>
                    <div class="small text-muted mt-1" style="font-size:0.75rem;">${dev.physical}</div>
                </td>
                <td>
                    <span class="badge-status ${statusBadgeClass}">
                        ${dev.status}
                    </span>
                </td>
                <td>
                    <span class="badge-status ${typeBadgeClass}">
                        ${dev.maintType}
                    </span>
                </td>
                <td>
                    <div>
                        <span class="badge bg-secondary ${cycleColor} px-2 py-1 mb-1">
                            <i class="fa fa-clock me-1"></i>${dev.cycle}
                        </span>
                        <div class="small text-muted" style="font-size:0.72rem;">
                            Jatuh Tempo: <span class="text-light fw-bold">${dev.nextMaint}</span>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="small text-muted" style="font-size:0.75rem;">
                        <div>Terakhir: ${dev.lastCalib}</div>
                        <div>Due: <span class="${dev.calibStatus === 'Expired' ? 'text-danger fw-bold' : 'text-success'}">${dev.nextCalib}</span></div>
                    </div>
                </td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-info" onclick="openDetailModal('${dev.id}')" title="Detail Alat">
                            <i class="fa fa-eye"></i> Detail
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });

    $("#device-count-badge").text(`${devices.length} Unit Ditampilkan`);
}

// Handler Filter & Search
function initFilterHandlers() {
    $("#filter-hospital, #filter-condition, #filter-status, #filter-type, #filter-cycle").on("change", applyFilters);
    $("#search-device").on("input", applyFilters);
}

function applyFilters() {
    const selectedHospital = $("#filter-hospital").val();
    const selectedCond = $("#filter-condition").val();
    const selectedStatus = $("#filter-status").val();
    const selectedType = $("#filter-type").val();
    const selectedCycle = $("#filter-cycle").val();
    const query = $("#search-device").val().toLowerCase().trim();

    const filtered = calibraDevices.filter((item) => {
        const matchHosp = !selectedHospital || item.hospital === selectedHospital;
        const matchCond = !selectedCond || item.condition === selectedCond;
        const matchStatus = !selectedStatus || item.status.includes(selectedStatus);
        const matchType = !selectedType || item.maintType.includes(selectedType);
        const matchCycle = !selectedCycle || item.cycle === selectedCycle;

        const matchQuery = !query || 
            item.name.toLowerCase().includes(query) ||
            item.id.toLowerCase().includes(query) ||
            item.room.toLowerCase().includes(query) ||
            item.hospital.toLowerCase().includes(query) ||
            item.brand.toLowerCase().includes(query);

        return matchHosp && matchCond && matchStatus && matchType && matchCycle && matchQuery;
    });

    renderDeviceTable(filtered);
}

// Reset Filter
function resetFilters() {
    $("#filter-hospital").val("");
    $("#filter-condition").val("");
    $("#filter-status").val("");
    $("#filter-type").val("");
    $("#filter-cycle").val("");
    $("#search-device").val("");
    renderDeviceTable(calibraDevices);
}

// Buka Modal Detail Alat
function openDetailModal(deviceId) {
    const dev = calibraDevices.find(d => d.id === deviceId);
    if (!dev) return;

    $("#modal-dev-id").text(dev.id);
    $("#modal-dev-name").text(dev.name + " (" + dev.brand + ")");
    $("#modal-dev-hospital").text(dev.hospital);
    $("#modal-dev-room").text(dev.room);
    $("#modal-dev-serial").text(dev.serial);
    $("#modal-dev-condition").text(dev.condition);
    $("#modal-dev-physical").text(dev.physical);
    $("#modal-dev-status").text(dev.status);
    $("#modal-dev-maint-type").text(dev.maintType);
    $("#modal-dev-cycle").text(dev.cycle);
    $("#modal-dev-next-maint").text(dev.nextMaint);
    $("#modal-dev-calib-cert").text(dev.certNo);
    $("#modal-dev-calib-due").text(dev.nextCalib);
    $("#modal-dev-error").text(dev.errorDev);
    $("#modal-dev-leakage").text(dev.leakageCurrent);
    $("#modal-dev-pic").text(dev.pic);

    // Setup form edit
    $("#select-update-status").val(dev.status.includes("Karantina") ? "Karantina / Rusak" : dev.status);
    $("#select-update-condition").val(dev.condition);
    $("#btn-save-status").attr("onclick", `saveDeviceStatus('${dev.id}')`);

    const modal = new bootstrap.Modal(document.getElementById('deviceDetailModal'));
    modal.show();
}

// Simpan Update Status Alat
function saveDeviceStatus(deviceId) {
    const dev = calibraDevices.find(d => d.id === deviceId);
    if (!dev) return;

    const newStatus = $("#select-update-status").val();
    const newCond = $("#select-update-condition").val();
    const notes = $("#update-notes").val();

    dev.status = newStatus;
    dev.condition = newCond;
    if (newCond === "Layak Pakai") {
        dev.physical = "Telah diperbaiki & ditest ulang: " + (notes || "Normal Operasional");
    } else {
        dev.physical = notes || dev.physical;
    }

    // Refresh tabel & modal
    applyFilters();
    bootstrap.Modal.getInstance(document.getElementById('deviceDetailModal')).hide();

    // Notifikasi toast sederhana
    alert(`Status ${dev.name} [${dev.id}] berhasil diperbarui menjadi: ${newStatus} (${newCond})`);
}
