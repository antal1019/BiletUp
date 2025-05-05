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

// Obține numărul de evenimente ale organizatorului
$events_sql = "SELECT COUNT(*) as total FROM events WHERE organizator_id = ?";
$stmt = $conn->prepare($events_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events_result = $stmt->get_result();
$events_count = $events_result->fetch_assoc()['total'];

// Obține numărul de bilete vândute pentru evenimentele organizatorului
$tickets_sql = "SELECT COUNT(*) as total 
               FROM tickets t
               JOIN events e ON t.event_id = e.id
               WHERE e.organizator_id = ?";
$stmt = $conn->prepare($tickets_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets_result = $stmt->get_result();
$tickets_count = $tickets_result->fetch_assoc()['total'];

// Obține suma totală din vânzări
$sales_sql = "SELECT SUM(t.pret) as total 
              FROM tickets t
              JOIN events e ON t.event_id = e.id
              WHERE e.organizator_id = ?";
$stmt = $conn->prepare($sales_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sales_result = $stmt->get_result();
$sales_total = $sales_result->fetch_assoc()['total'] ?? 0;

// Obține evenimentele recente ale organizatorului
$recent_events_sql = "SELECT e.*, c.nume as categorie_nume 
                     FROM events e 
                     LEFT JOIN event_categories c ON e.categorie_id = c.id
                     WHERE e.organizator_id = ?
                     ORDER BY e.data_creare DESC LIMIT 5";
$stmt = $conn->prepare($recent_events_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_events_result = $stmt->get_result();
$recent_events = [];
while($row = $recent_events_result->fetch_assoc()) {
    $recent_events[] = $row;
}

$page_title = "Panou Organizator";
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
        .stat-icon {
            font-size: 3rem;
            opacity: 0.8;
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
                        <a class="nav-link active" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="my-events.php">Evenimentele mele</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="create-event.php">Creează eveniment</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sales.php">Vânzări</a>
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

        <div class="row mb-4">
            <div class="col-12">
                <h1>Dashboard Organizator</h1>
                <p class="lead">Bine ai venit, <?php echo $_SESSION['user_nume']; ?>! Aici poți gestiona evenimentele și vânzările tale.</p>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4 mb-4">
                <div class="card shadow stat-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Evenimentele tale</h6>
                                <h2><?php echo $events_count; ?></h2>
                            </div>
                            <i class="bi bi-calendar-event stat-icon"></i>
                        </div>
                        <a href="my-events.php" class="text-white">Vezi detalii <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card shadow stat-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Bilete vândute</h6>
                                <h2><?php echo $tickets_count; ?></h2>
                            </div>
                            <i class="bi bi-ticket-perforated stat-icon"></i>
                        </div>
                        <a href="sales.php" class="text-white">Vezi detalii <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card shadow stat-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title">Venituri totale</h6>
                                <h2><?php echo number_format($sales_total, 2); ?> lei</h2>
                            </div>
                            <i class="bi bi-cash-coin stat-icon"></i>
                        </div>
                        <a href="sales.php" class="text-white">Vezi detalii <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Evenimentele tale recente</h5>
                            <a href="my-events.php" class="btn btn-sm btn-primary">Vezi toate</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Titlu</th>
                                        <th>Data</th>
                                        <th>Locație</th>
                                        <th>Bilete vândute</th>
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
                                            <td><?php echo $event['oras']; ?></td>
                                            <td>
                                                <?php 
                                                    $sold_tickets_sql = "SELECT COUNT(*) as count FROM tickets WHERE event_id = ?";
                                                    $stmt = $conn->prepare($sold_tickets_sql);
                                                    $stmt->bind_param("i", $event['id']);
                                                    $stmt->execute();
                                                    $sold_result = $stmt->get_result()->fetch_assoc();
                                                    echo $sold_result['count'] . ' / ' . $event['capacitate_maxima'];
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $event['status'] == 'activ' ? 'success' : ($event['status'] == 'anulat' ? 'danger' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst($event['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-info" target="_blank" title="Vezi">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="btn btn-warning" title="Editează">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="ticket-types.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary" title="Bilete">
                                                        <i class="bi bi-ticket-perforated"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Nu ai creat încă niciun eveniment.</td>
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
                            <div class="col-md-4 mb-3">
                                <a href="create-event.php" class="btn btn-primary w-100 py-3">
                                    <i class="bi bi-plus-circle mb-2 d-block" style="font-size: 2rem;"></i>
                                    Creează eveniment nou
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="my-events.php" class="btn btn-success w-100 py-3">
                                    <i class="bi bi-calendar-event mb-2 d-block" style="font-size: 2rem;"></i>
                                    Gestionează evenimente
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="sales.php" class="btn btn-info w-100 py-3 text-white">
                                    <i class="bi bi-graph-up mb-2 d-block" style="font-size: 2rem;"></i>
                                    Rapoarte de vânzări
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
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> BiletUP. Toate drepturile rezervate.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>