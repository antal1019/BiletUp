<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

session_start();

// Verifică dacă utilizatorul este admin sau organizator
if(!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'organizator')) {
    $_SESSION['mesaj'] = "Nu ai permisiunea de a accesa această pagină!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: ../index.php");
    exit;
}

// Obține numărul total de evenimente
$events_sql = "SELECT COUNT(*) as total FROM events";
if($_SESSION['user_role'] == 'organizator') {
    $events_sql .= " WHERE organizator_id = " . $_SESSION['user_id'];
}
$events_result = $conn->query($events_sql);
$events_count = $events_result->fetch_assoc()['total'];

// Obține numărul total de utilizatori (doar pentru admin)
$users_count = 0;
if($_SESSION['user_role'] == 'admin') {
    $users_sql = "SELECT COUNT(*) as total FROM users";
    $users_result = $conn->query($users_sql);
    $users_count = $users_result->fetch_assoc()['total'];
}

// Obține numărul total de comenzi
$orders_sql = "SELECT COUNT(*) as total FROM orders";
if($_SESSION['user_role'] == 'organizator') {
    $orders_sql = "SELECT COUNT(DISTINCT o.id) as total 
                   FROM orders o
                   JOIN tickets t ON o.id = t.order_id
                   JOIN events e ON t.event_id = e.id
                   WHERE e.organizator_id = " . $_SESSION['user_id'];
}
$orders_result = $conn->query($orders_sql);
$orders_count = $orders_result->fetch_assoc()['total'];

// Obține numărul total de bilete vândute
$tickets_sql = "SELECT COUNT(*) as total FROM tickets";
if($_SESSION['user_role'] == 'organizator') {
    $tickets_sql = "SELECT COUNT(*) as total 
                   FROM tickets t
                   JOIN events e ON t.event_id = e.id
                   WHERE e.organizator_id = " . $_SESSION['user_id'];
}
$tickets_result = $conn->query($tickets_sql);
$tickets_count = $tickets_result->fetch_assoc()['total'];

// Obține evenimente recente
$recent_events_sql = "SELECT e.*, c.nume as categorie_nume 
                     FROM events e 
                     LEFT JOIN event_categories c ON e.categorie_id = c.id";
if($_SESSION['user_role'] == 'organizator') {
    $recent_events_sql .= " WHERE e.organizator_id = " . $_SESSION['user_id'];
}
$recent_events_sql .= " ORDER BY e.data_creare DESC LIMIT 5";
$recent_events_result = $conn->query($recent_events_sql);
$recent_events = [];
if($recent_events_result->num_rows > 0) {
    while($row = $recent_events_result->fetch_assoc()) {
        $recent_events[] = $row;
    }
}

// Obține comenzi recente
$recent_orders_sql = "SELECT o.*, u.nume, u.prenume 
                      FROM orders o 
                      LEFT JOIN users u ON o.user_id = u.id";
if($_SESSION['user_role'] == 'organizator') {
    $recent_orders_sql = "SELECT DISTINCT o.*, u.nume, u.prenume 
                         FROM orders o
                         JOIN tickets t ON o.id = t.order_id
                         JOIN events e ON t.event_id = e.id
                         LEFT JOIN users u ON o.user_id = u.id
                         WHERE e.organizator_id = " . $_SESSION['user_id'];
}
$recent_orders_sql .= " ORDER BY o.data_creare DESC LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_sql);
$recent_orders = [];
if($recent_orders_result->num_rows > 0) {
    while($row = $recent_orders_result->fetch_assoc()) {
        $recent_orders[] = $row;
    }
}

$page_title = "Dashboard";
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - BiletUP Admin</title>
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
        .stat-icon {
            font-size: 3rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <!-- Admin Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">BiletUP Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Evenimente</a>
                    </li>
                    <?php if($_SESSION['user_role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Utilizatori</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">Categorii</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="tickets.php">Bilete</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php" target="_blank">
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

        <div class="row mb-4">
            <div class="col-12">
                <h1>Dashboard</h1>
                <p class="lead">Bine ai venit, <?php echo $_SESSION['user_nume']; ?>! Aici poți vedea o prezentare generală a platformei BiletUP.</p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-3 mb-4">
                <div class="card shadow stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Evenimente</h6>
                                <h2><?php echo $events_count; ?></h2>
                            </div>
                            <i class="bi bi-calendar-event stat-icon"></i>
                        </div>
                        <a href="events.php" class="text-white">Vezi detalii <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            
            <?php if($_SESSION['user_role'] == 'admin'): ?>
            <div class="col-md-3 mb-4">
                <div class="card shadow stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Utilizatori</h6>
                                <h2><?php echo $users_count; ?></h2>
                            </div>
                            <i class="bi bi-people stat-icon"></i>
                        </div>
                        <a href="users.php" class="text-white">Vezi detalii <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="col-md-3 mb-4">
                <div class="card shadow stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Total Comenzi</h6>
                                <h2><?php echo $orders_count; ?></h2>
                            </div>
                            <i class="bi bi-cart stat-icon"></i>
                        </div>
                        <a href="#" class="text-white">Vezi detalii <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card shadow stat-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Bilete Vândute</h6>
                                <h2><?php echo $tickets_count; ?></h2>
                            </div>
                            <i class="bi bi-ticket-perforated stat-icon"></i>
                        </div>
                        <a href="#" class="text-white">Vezi detalii <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Evenimente recente</h5>
                            <a href="events.php" class="btn btn-sm btn-primary">Vezi toate</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Titlu</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($recent_events) > 0): ?>
                                        <?php foreach($recent_events as $event): ?>
                                        <tr>
                                            <td><?php echo $event['titlu']; ?></td>
                                            <td><?php echo formatData($event['data_inceput']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $event['status'] == 'activ' ? 'success' : ($event['status'] == 'anulat' ? 'danger' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst($event['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-warning">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Nu există evenimente.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Comenzi recente</h5>
                            <a href="#" class="btn btn-sm btn-primary">Vezi toate</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($recent_orders) > 0): ?>
                                        <?php foreach($recent_orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo $order['id']; ?></td>
                                            <td><?php echo $order['nume'] . ' ' . $order['prenume']; ?></td>
                                            <td><?php echo $order['total']; ?> lei</td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $order['status'] == 'platit' ? 'success' : ($order['status'] == 'anulat' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Nu există comenzi.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Acțiuni rapide</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="add-event.php" class="btn btn-primary w-100 py-3">
                                    <i class="bi bi-plus-circle mb-2 d-block" style="font-size: 2rem;"></i>
                                    Adaugă eveniment
                                </a>
                            </div>
                            <?php if($_SESSION['user_role'] == 'admin'): ?>
                            <div class="col-md-3 mb-3">
                                <a href="categories.php" class="btn btn-success w-100 py-3">
                                    <i class="bi bi-tags mb-2 d-block" style="font-size: 2rem;"></i>
                                    Gestionare categorii
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="users.php" class="btn btn-info w-100 py-3 text-white">
                                    <i class="bi bi-people mb-2 d-block" style="font-size: 2rem;"></i>
                                    Gestionare utilizatori
                                </a>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="btn btn-warning w-100 py-3">
                                    <i class="bi bi-bar-chart mb-2 d-block" style="font-size: 2rem;"></i>
                                    Rapoarte și statistici
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> BiletUP Admin Panel. Toate drepturile rezervate.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>