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

// Procesează ștergerea evenimentului
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $event_id = $conn->real_escape_string($_GET['delete']);
    
    // Verifică dacă utilizatorul are dreptul să șteargă evenimentul
    $check_sql = "SELECT * FROM events WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $event_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if($check_result->num_rows == 1) {
        $event = $check_result->fetch_assoc();
        
        // Doar admin sau organizatorul evenimentului poate șterge
        if($_SESSION['user_role'] == 'admin' || $event['organizator_id'] == $_SESSION['user_id']) {
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
        } else {
            $_SESSION['mesaj'] = "Nu ai permisiunea de a șterge acest eveniment!";
            $_SESSION['tip_mesaj'] = "danger";
        }
    } else {
        $_SESSION['mesaj'] = "Evenimentul nu există!";
        $_SESSION['tip_mesaj'] = "danger";
    }
    
    header("Location: events.php");
    exit;
}

// Obține toate evenimentele
$sql = "SELECT e.*, c.nume as categorie_nume, u.nume as organizator_nume, u.prenume as organizator_prenume
        FROM events e
        LEFT JOIN event_categories c ON e.categorie_id = c.id
        LEFT JOIN users u ON e.organizator_id = u.id";

// Filtrare pentru organizatori (văd doar evenimentele lor)
if($_SESSION['user_role'] == 'organizator') {
    $sql .= " WHERE e.organizator_id = " . $_SESSION['user_id'];
}

$sql .= " ORDER BY e.data_inceput DESC";
$result = $conn->query($sql);
$events = [];

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

$page_title = "Administrare Evenimente";
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
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="events.php">Evenimente</a>
                    </li>
                    <?php if($_SESSION['user_role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">Utilizatori</a>
                    </li>
                    
                    <?php endif; ?>
                    
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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Administrare Evenimente</h1>
            <a href="add-event.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Adaugă eveniment
            </a>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Titlu</th>
                                <th>Categorie</th>
                                <th>Data</th>
                                <th>Locație</th>
                                <th>Organizator</th>
                                <th>Status</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($events) > 0): ?>
                                <?php foreach($events as $event): ?>
                                <tr>
                                    <td><?php echo $event['id']; ?></td>
                                    <td><?php echo $event['titlu']; ?></td>
                                    <td><?php echo $event['categorie_nume']; ?></td>
                                    <td><?php echo formatData($event['data_inceput']); ?></td>
                                    <td><?php echo $event['oras']; ?></td>
                                    <td><?php echo $event['organizator_nume'] . ' ' . $event['organizator_prenume']; ?></td>
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
                                            <a href="ticket-types.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary" title="Tipuri bilete">
                                                <i class="bi bi-ticket-perforated"></i>
                                            </a>
                                            <a href="events.php?delete=<?php echo $event['id']; ?>" class="btn btn-danger" title="Șterge" onclick="return confirm('Ești sigur că vrei să ștergi acest eveniment?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Nu există evenimente.</td>
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
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> BiletUP Admin Panel. Toate drepturile rezervate.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>