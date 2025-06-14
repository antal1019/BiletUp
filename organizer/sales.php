<?php
require_once "../session_config.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

// Verifică dacă utilizatorul este organizator sau admin
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'organizator' && $_SESSION['user_role'] != 'admin')) {
    $_SESSION['mesaj'] = "Nu ai permisiunea de a accesa această pagină!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Obține statistici generale
$stats_sql = "SELECT 
                COUNT(DISTINCT t.id) as total_tickets,
                SUM(t.pret) as total_revenue
              FROM tickets t
              JOIN events e ON t.event_id = e.id
              WHERE e.organizator_id = ?";

$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Obține vânzări pe evenimente
$events_sales_sql = "SELECT 
                        e.id,
                        e.titlu,
                        COUNT(t.id) as tickets_sold,
                        SUM(t.pret) as revenue,
                        e.capacitate_maxima
                     FROM events e
                     LEFT JOIN tickets t ON e.id = t.event_id
                     WHERE e.organizator_id = ?
                     GROUP BY e.id
                     ORDER BY revenue DESC";

$stmt = $conn->prepare($events_sales_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events_result = $stmt->get_result();
$events_data = [];

while ($row = $events_result->fetch_assoc()) {
    // Calculăm rata de ocupare
    $row['fill_rate'] = ($row['capacitate_maxima'] > 0) ?
        round(($row['tickets_sold'] / $row['capacitate_maxima']) * 100) : 0;
    $events_data[] = $row;
}

// Obține date pentru vânzări zilnice (ultimele 30 de zile)
$daily_sales_sql = "SELECT 
                        DATE(t.data_achizitie) as sale_date,
                        SUM(t.pret) as daily_revenue
                     FROM tickets t
                     JOIN events e ON t.event_id = e.id
                     WHERE e.organizator_id = ? 
                     AND t.data_achizitie >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     GROUP BY DATE(t.data_achizitie)
                     ORDER BY sale_date ASC";

$stmt = $conn->prepare($daily_sales_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$daily_result = $stmt->get_result();

$daily_dates = [];
$daily_revenues = [];

while ($row = $daily_result->fetch_assoc()) {
    $daily_dates[] = date('d.m', strtotime($row['sale_date']));
    $daily_revenues[] = floatval($row['daily_revenue']);
}

// Obține date pentru graficul cu ratele de ocupare
$fill_rates = [];
$event_names_short = [];

foreach ($events_data as $event) {
    $event_names_short[] = strlen($event['titlu']) > 20 ? substr($event['titlu'], 0, 20) . '...' : $event['titlu'];
    $fill_rates[] = $event['fill_rate'];
}

$page_title = "Raport vânzări";
?>
<!DOCTYPE html>
<html lang="ro">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - BiletUP</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="../css/style.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.3s;
            border-radius: 15px;
            border: none;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
        }

        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            border-radius: 15px 15px 0 0 !important;
            border-bottom: none;
            font-weight: 600;
        }

        .progress {
            height: 8px;
            border-radius: 10px;
        }

        .progress-bar {
            border-radius: 10px;
        }

        .table {
            border-radius: 10px;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #6c757d;
            font-size: 0.875rem;
        }
    </style>
</head>

<body>
    <!-- Organizer Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">BiletUP Organizator</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarOrganizer">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarOrganizer">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-events.php">Evenimentele mele</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create-event.php">Creează eveniment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="sales.php">Vânzări</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Vezi site-ul
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Deconectare
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Raport Vânzări</h1>

        <!-- Statistici generale -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-primary text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Venituri totale</h6>
                        <h2 class="mb-0"><?php echo number_format($stats['total_revenue'], 2); ?> lei</h2>
                        <p class="card-text mb-0 mt-2 opacity-75">Din toate biletele vândute</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-success text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Bilete vândute</h6>
                        <h2 class="mb-0"><?php echo $stats['total_tickets']; ?></h2>
                        <p class="card-text mb-0 mt-2 opacity-75">Pentru toate evenimentele tale</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card stat-card bg-info text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Evenimente active</h6>
                        <h2 class="mb-0"><?php echo count($events_data); ?></h2>
                        <p class="card-text mb-0 mt-2 opacity-75">Evenimente cu bilete vândute</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graficele -->
        <div class="row mb-4">
            <!-- Graficul cu vânzări zilnice -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Vânzări zilnice (ultimele 30 zile)</h5>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" onclick="switchChart('daily')">Zilnic</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="switchChart('events')">Bilete</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rate de ocupare -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 text-muted">Rate de ocupare (%)</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="occupancyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel cu date detaliate -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Detalii vânzări pe evenimente</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Eveniment</th>
                                <th class="text-center">Bilete vândute</th>
                                <th class="text-center">Capacitate</th>
                                <th class="text-center">Rata de ocupare</th>
                                <th class="text-end">Venituri</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($events_data) > 0): ?>
                                <?php foreach ($events_data as $event): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                        <i class="bi bi-calendar-event text-white"></i>
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo $event['titlu']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary"><?php echo $event['tickets_sold']; ?></span>
                                        </td>
                                        <td class="text-center"><?php echo $event['capacitate_maxima']; ?></td>
                                        <td class="text-center">
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar <?php echo $event['fill_rate'] >= 80 ? 'bg-success' : ($event['fill_rate'] >= 50 ? 'bg-warning' : 'bg-danger'); ?>"
                                                        role="progressbar"
                                                        style="width: <?php echo $event['fill_rate']; ?>%;"
                                                        aria-valuenow="<?php echo $event['fill_rate']; ?>"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <small class="text-muted"><?php echo $event['fill_rate']; ?>%</small>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold"><?php echo number_format($event['revenue'], 0); ?> lei</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                                        <p class="text-muted mt-2">Nu există date de vânzări.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> BiletUP. Toate drepturile rezervate.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        let salesChart;
        let occupancyChart;

        // Date pentru grafice
        const dailyData = {
            labels: <?php echo json_encode($daily_dates); ?>,
            datasets: [{
                label: 'Venituri (lei)',
                data: <?php echo json_encode($daily_revenues); ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#0d6efd',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        };

        const eventsData = {
            labels: <?php echo json_encode($event_names_short); ?>,
            datasets: [{
                label: 'Bilete vândute',
                data: <?php echo json_encode(array_column($events_data, 'tickets_sold')); ?>,
                backgroundColor: [
                    '#0d6efd', '#198754', '#fd7e14', '#dc3545', '#6f42c1',
                    '#20c997', '#ffc107', '#e83e8c', '#6c757d', '#495057'
                ],
                borderRadius: 8,
                borderWidth: 0
            }]
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Graficul principal (vânzări zilnice)
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            salesChart = new Chart(salesCtx, {
                type: 'line',
                data: dailyData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            cornerRadius: 8,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y + ' lei';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#6c757d',
                                font: {
                                    size: 12
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                color: '#6c757d',
                                font: {
                                    size: 12
                                },
                                callback: function(value) {
                                    return value + ' lei';
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });

            // Graficul cu rate de ocupare (circular)
            const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
            occupancyChart = new Chart(occupancyCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($event_names_short); ?>,
                    datasets: [{
                        data: <?php echo json_encode($fill_rates); ?>,
                        backgroundColor: [
                            '#0d6efd', '#198754', '#fd7e14', '#dc3545', '#6f42c1',
                            '#20c997', '#ffc107', '#e83e8c', '#6c757d', '#495057'
                        ],
                        borderWidth: 0,
                        cutout: '70%'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            cornerRadius: 8,
                            displayColors: true,
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed + '%';
                                }
                            }
                        }
                    }
                }
            });
        });

        // Funcție pentru schimbarea tipului de grafic
        function switchChart(type) {
            if (type === 'daily') {
                salesChart.data = dailyData;
                salesChart.options.scales.y.ticks.callback = function(value) {
                    return value + ' lei';
                };
                salesChart.options.plugins.tooltip.callbacks.label = function(context) {
                    return context.parsed.y + ' lei';
                };
            } else {
                salesChart.data = eventsData;
                salesChart.options.scales.y.ticks.callback = function(value) {
                    return value;
                };
                salesChart.options.plugins.tooltip.callbacks.label = function(context) {
                    return context.parsed.y + ' bilete';
                };
            }

            salesChart.update();

            // Actualizează butoanele
            document.querySelectorAll('.btn-outline-secondary').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }
    </script>
</body>

</html>