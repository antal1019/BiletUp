<?php
require_once "../session_config.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

// Verifică dacă utilizatorul este organizator sau admin
if(!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'organizator' && $_SESSION['user_role'] != 'admin')) {
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

while($row = $events_result->fetch_assoc()) {
    // Calculăm rata de ocupare
    $row['fill_rate'] = ($row['capacitate_maxima'] > 0) ? 
        round(($row['tickets_sold'] / $row['capacitate_maxima']) * 100) : 0;
    $events_data[] = $row;
}

// Obține date pentru grafic simplu
$event_names = [];
$event_revenues = [];

foreach($events_data as $event) {
    $event_names[] = $event['titlu'];
    $event_revenues[] = $event['revenue'] ? $event['revenue'] : 0;
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
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
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
        <?php if(isset($_SESSION['mesaj'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tip_mesaj']; ?> alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['mesaj'];
                unset($_SESSION['mesaj']);
                unset($_SESSION['tip_mesaj']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <h1 class="mb-4">Raport Vânzări</h1>

        <!-- Statistici generale -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4">
                <div class="card shadow stat-card bg-primary text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Venituri totale</h6>
                        <h2><?php echo number_format($stats['total_revenue'], 2); ?> lei</h2>
                        <p class="card-text mb-0">Din toate biletele vândute</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card shadow stat-card bg-success text-white h-100">
                    <div class="card-body">
                        <h6 class="card-title">Bilete vândute</h6>
                        <h2><?php echo $stats['total_tickets']; ?></h2>
                        <p class="card-text mb-0">Pentru toate evenimentele tale</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grafic simplu -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Venituri pe evenimente</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="eventSalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel cu date detaliate -->
        <div class="card shadow">
            <div class="card-header bg-white">
                <h5 class="mb-0">Detalii vânzări pe evenimente</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
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
                            <?php if(count($events_data) > 0): ?>
                                <?php foreach($events_data as $event): ?>
                                <tr>
                                    <td><?php echo $event['titlu']; ?></td>
                                    <td class="text-center"><?php echo $event['tickets_sold']; ?></td>
                                    <td class="text-center"><?php echo $event['capacitate_maxima']; ?></td>
                                    <td class="text-center">
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar <?php echo $event['fill_rate'] >= 80 ? 'bg-success' : ($event['fill_rate'] >= 50 ? 'bg-info' : 'bg-warning'); ?>" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $event['fill_rate']; ?>%;" 
                                                 aria-valuenow="<?php echo $event['fill_rate']; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?php echo $event['fill_rate']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-end"><?php echo number_format($event['revenue'], 2); ?> lei</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">Nu există date de vânzări.</td>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Graficul vânzărilor pe evenimente
            const eventSalesCtx = document.getElementById('eventSalesChart').getContext('2d');
            new Chart(eventSalesCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($event_names); ?>,
                    datasets: [{
                        label: 'Venituri (lei)',
                        data: <?php echo json_encode($event_revenues); ?>,
                        backgroundColor: 'rgba(13, 110, 253, 0.7)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Venituri (lei)'
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>