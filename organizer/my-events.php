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

// Procesează ștergerea evenimentului
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $event_id = $conn->real_escape_string($_GET['delete']);
    
    // Verifică dacă evenimentul aparține utilizatorului
    $check_sql = "SELECT * FROM events WHERE id = ? AND organizator_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $event_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if($check_result->num_rows == 1) {
        // Verifică dacă există bilete vândute
        $tickets_sql = "SELECT COUNT(*) as count FROM tickets WHERE event_id = ?";
        $tickets_stmt = $conn->prepare($tickets_sql);
        $tickets_stmt->bind_param("i", $event_id);
        $tickets_stmt->execute();
        $tickets_result = $tickets_stmt->get_result()->fetch_assoc();
        
        if($tickets_result['count'] > 0) {
            $_SESSION['mesaj'] = "Nu poți șterge acest eveniment deoarece există bilete vândute!";
            $_SESSION['tip_mesaj'] = "danger";
        } else {
            // Șterge tipurile de bilete asociate
            $delete_tickets_sql = "DELETE FROM ticket_types WHERE event_id = ?";
            $delete_tickets_stmt = $conn->prepare($delete_tickets_sql);
            $delete_tickets_stmt->bind_param("i", $event_id);
            $delete_tickets_stmt->execute();
            
            // Șterge evenimentul
            $delete_sql = "DELETE FROM events WHERE id = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("i", $event_id);
            
            if($delete_stmt->execute()) {
                $_SESSION['mesaj'] = "Evenimentul a fost șters cu succes!";
                $_SESSION['tip_mesaj'] = "success";
            } else {
                $_SESSION['mesaj'] = "A apărut o eroare la ștergerea evenimentului!";
                $_SESSION['tip_mesaj'] = "danger";
            }
        }
    } else {
        $_SESSION['mesaj'] = "Evenimentul nu există sau nu ai permisiunea de a-l șterge!";
        $_SESSION['tip_mesaj'] = "danger";
    }
    
    header("Location: my-events.php");
    exit;
}

// Procesează modificarea statusului
if(isset($_GET['status']) && !empty($_GET['status']) && isset($_GET['id']) && !empty($_GET['id'])) {
    $event_id = $conn->real_escape_string($_GET['id']);
    $status = $conn->real_escape_string($_GET['status']);
    
    // Verifică dacă statusul este valid
    if($status == 'activ' || $status == 'anulat' || $status == 'incheiat') {
        // Verifică dacă evenimentul aparține utilizatorului
        $check_sql = "SELECT * FROM events WHERE id = ? AND organizator_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $event_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows == 1) {
            // Actualizează statusul
            $update_sql = "UPDATE events SET status = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $status, $event_id);
            
            if($update_stmt->execute()) {
                $_SESSION['mesaj'] = "Statusul evenimentului a fost actualizat cu succes!";
                $_SESSION['tip_mesaj'] = "success";
            } else {
                $_SESSION['mesaj'] = "A apărut o eroare la actualizarea statusului!";
                $_SESSION['tip_mesaj'] = "danger";
            }
        } else {
            $_SESSION['mesaj'] = "Evenimentul nu există sau nu ai permisiunea de a-l modifica!";
            $_SESSION['tip_mesaj'] = "danger";
        }
    } else {
        $_SESSION['mesaj'] = "Status invalid!";
        $_SESSION['tip_mesaj'] = "danger";
    }
    
    header("Location: my-events.php");
    exit;
}

// Obține toate evenimentele organizatorului
$events_sql = "SELECT e.*, c.nume as categorie_nume 
              FROM events e 
              LEFT JOIN event_categories c ON e.categorie_id = c.id 
              WHERE e.organizator_id = ? 
              ORDER BY e.data_inceput DESC";
$stmt = $conn->prepare($events_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$events_result = $stmt->get_result();
$events = [];

while($row = $events_result->fetch_assoc()) {
    $events[] = $row;
}

$page_title = "Evenimentele Mele";
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
                        <a class="nav-link active" href="my-events.php">Evenimentele mele</a>
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Evenimentele Mele</h1>
            <a href="create-event.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Creează eveniment
            </a>
        </div>

        <?php if(!empty($events)): ?>
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Titlu</th>
                                    <th>Categorie</th>
                                    <th>Data</th>
                                    <th>Locație</th>
                                    <th>Bilete vândute</th>
                                    <th>Status</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($events as $event): ?>
                                    <?php
                                    // Calculează biletele vândute
                                    $sold_tickets_sql = "SELECT COUNT(*) as count FROM tickets WHERE event_id = ?";
                                    $stmt = $conn->prepare($sold_tickets_sql);
                                    $stmt->bind_param("i", $event['id']);
                                    $stmt->execute();
                                    $sold_result = $stmt->get_result()->fetch_assoc();
                                    $sold_tickets = $sold_result['count'];
                                    $availability = $sold_tickets . ' / ' . $event['capacitate_maxima'];
                                    ?>
                                    <tr>
                                        <td><?php echo $event['titlu']; ?></td>
                                        <td><?php echo $event['categorie_nume']; ?></td>
                                        <td><?php echo formatData($event['data_inceput']); ?></td>
                                        <td><?php echo $event['oras']; ?></td>
                                        <td><?php echo $availability; ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-<?php 
                                                    echo $event['status'] == 'activ' ? 'success' : ($event['status'] == 'anulat' ? 'danger' : 'secondary'); 
                                                ?> dropdown-toggle" type="button" id="statusDropdown<?php echo $event['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <?php echo ucfirst($event['status']); ?>
                                                </button>
                                                <ul class="dropdown-menu" aria-labelledby="statusDropdown<?php echo $event['id']; ?>">
                                                    <li><a class="dropdown-item <?php echo $event['status'] == 'activ' ? 'active' : ''; ?>" href="my-events.php?id=<?php echo $event['id']; ?>&status=activ">Activ</a></li>
                                                    <li><a class="dropdown-item <?php echo $event['status'] == 'anulat' ? 'active' : ''; ?>" href="my-events.php?id=<?php echo $event['id']; ?>&status=anulat">Anulat</a></li>
                                                    <li><a class="dropdown-item <?php echo $event['status'] == 'incheiat' ? 'active' : ''; ?>" href="my-events.php?id=<?php echo $event['id']; ?>&status=incheiat">Încheiat</a></li>
                                                </ul>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="../event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-info" target="_blank" title="Vezi">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="btn btn-warning" title="Editează">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="ticket-types.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary" title="Tipuri bilete">
                                                    <i class="bi bi-ticket-perforated"></i>
                                                </a>
                                                <?php if($sold_tickets == 0): ?>
                                                <a href="my-events.php?delete=<?php echo $event['id']; ?>" class="btn btn-danger" title="Șterge" onclick="return confirm('Ești sigur că vrei să ștergi acest eveniment?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
                    <h3 class="mt-3">Nu ai creat încă niciun eveniment</h3>
                    <p class="text-muted">Aici vei vedea toate evenimentele create de tine.</p>
                    <a href="create-event.php" class="btn btn-primary mt-2">Creează primul tău eveniment</a>
                </div>
            </div>
        <?php endif; ?>
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