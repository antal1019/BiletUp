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

// Verifică dacă ID-ul evenimentului este furnizat
if(!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    $_SESSION['mesaj'] = "ID-ul evenimentului nu a fost specificat!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: my-events.php");
    exit;
}

$event_id = $conn->real_escape_string($_GET['event_id']);
$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Obține detaliile evenimentului
$event_sql = "SELECT e.*, c.nume as categorie_nume
              FROM events e
              LEFT JOIN event_categories c ON e.categorie_id = c.id
              WHERE e.id = ?";

$stmt = $conn->prepare($event_sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows != 1) {
    $_SESSION['mesaj'] = "Evenimentul nu a fost găsit!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: my-events.php");
    exit;
}

$event = $result->fetch_assoc();

// Verifică dacă evenimentul aparține utilizatorului
if($event['organizator_id'] != $user_id && $_SESSION['user_role'] != 'admin') {
    $_SESSION['mesaj'] = "Nu ai permisiunea de a edita acest eveniment!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: my-events.php");
    exit;
}

// Funcție pentru actualizarea prețurilor evenimentului
function updateEventPrices($event_id) {
    global $conn;
    
    // Obține prețul minim și maxim din tipurile de bilete
    $price_sql = "SELECT MIN(pret) as min_price, MAX(pret) as max_price FROM ticket_types WHERE event_id = ?";
    $stmt = $conn->prepare($price_sql);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $min_price = $result['min_price'];
    $max_price = $result['max_price'];
    
    // Dacă nu există tipuri de bilete, setează prețurile la 0
    if($min_price === null) {
        $min_price = 0;
        $max_price = 0;
    }
    
    // Actualizează prețurile evenimentului
    $update_sql = "UPDATE events SET pret_minim = ?, pret_maxim = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ddi", $min_price, $max_price, $event_id);
    $stmt->execute();
}

// Procesează adăugarea unui tip de bilet
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_ticket_type'])) {
    $nume = $conn->real_escape_string($_POST['nume']);
    $descriere = $conn->real_escape_string($_POST['descriere']);
    $pret = floatval($_POST['pret']);
    $cantitate_totala = intval($_POST['cantitate']);
    
    // Validare
    if(empty($nume) || $pret <= 0 || $cantitate_totala <= 0) {
        $error = "Toate câmpurile sunt obligatorii, iar prețul și cantitatea trebuie să fie mai mari de 0.";
    } else {
        // Inserează tipul de bilet
        $insert_sql = "INSERT INTO ticket_types (event_id, nume, descriere, pret, cantitate_totala, cantitate_disponibila, status) 
                       VALUES (?, ?, ?, ?, ?, ?, 'disponibil')";
        
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("issdii", $event_id, $nume, $descriere, $pret, $cantitate_totala, $cantitate_totala);
        
        if($stmt->execute()) {
            $success = "Tipul de bilet a fost adăugat cu succes!";
            
            // Actualizează prețul minim și maxim al evenimentului
            updateEventPrices($event_id);
        } else {
            $error = "A apărut o eroare la adăugarea tipului de bilet: " . $conn->error;
        }
    }
}

// Procesează ștergerea unui tip de bilet
if(isset($_GET['delete_ticket']) && !empty($_GET['delete_ticket'])) {
    $ticket_id = $conn->real_escape_string($_GET['delete_ticket']);
    
    // Verifică dacă tipul de bilet aparține evenimentului
    $check_sql = "SELECT * FROM ticket_types WHERE id = ? AND event_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $ticket_id, $event_id);
    $stmt->execute();
    $check_result = $stmt->get_result();
    
    if($check_result->num_rows == 1) {
        // Verifică dacă există bilete vândute
        $tickets_sql = "SELECT COUNT(*) as count FROM tickets WHERE ticket_type_id = ?";
        $stmt = $conn->prepare($tickets_sql);
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $tickets_result = $stmt->get_result()->fetch_assoc();
        
        if($tickets_result['count'] > 0) {
            $error = "Nu poți șterge acest tip de bilet deoarece există bilete vândute!";
        } else {
            // Șterge tipul de bilet
            $delete_sql = "DELETE FROM ticket_types WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("i", $ticket_id);
            
            if($stmt->execute()) {
                $success = "Tipul de bilet a fost șters cu succes!";
                
                // Actualizează prețul minim și maxim al evenimentului
                updateEventPrices($event_id);
            } else {
                $error = "A apărut o eroare la ștergerea tipului de bilet: " . $conn->error;
            }
        }
    } else {
        $error = "Tipul de bilet nu există sau nu aparține acestui eveniment!";
    }
}

// Obține tipurile de bilete pentru eveniment
$ticket_types_sql = "SELECT * FROM ticket_types WHERE event_id = ? ORDER BY pret ASC";
$stmt = $conn->prepare($ticket_types_sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$ticket_types_result = $stmt->get_result();
$ticket_types = [];

while($row = $ticket_types_result->fetch_assoc()) {
    $ticket_types[] = $row;
}

$page_title = "Tipuri de Bilete - " . $event['titlu'];
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
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Tipuri de Bilete</h1>
            <a href="my-events.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Înapoi la evenimente
            </a>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Detalii Eveniment</h5>
            </div>
            <div class="card-body">
                <h4><?php echo $event['titlu']; ?></h4>
                <p class="text-muted">
                    <i class="bi bi-calendar3 me-2"></i><?php echo formatData($event['data_inceput']); ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-clock me-2"></i><?php echo date('H:i', strtotime($event['ora_inceput'])); ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-geo-alt me-2"></i><?php echo $event['locatie']; ?>, <?php echo $event['oras']; ?>
                </p>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary me-2"><?php echo $event['categorie_nume']; ?></span>
                        <span class="badge bg-<?php echo $event['status'] == 'activ' ? 'success' : ($event['status'] == 'anulat' ? 'danger' : 'secondary'); ?>">
                            <?php echo ucfirst($event['status']); ?>
                        </span>
                    </div>
                    <div>
                        <a href="../event-details.php?id=<?php echo $event_id; ?>" class="btn btn-sm btn-info" target="_blank">
                            <i class="bi bi-eye me-1"></i> Vezi evenimentul
                        </a>
                        <a href="edit-event.php?id=<?php echo $event_id; ?>" class="btn btn-sm btn-warning">
                            <i class="bi bi-pencil me-1"></i> Editează evenimentul
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Adaugă Tip de Bilet</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="ticket-types.php?event_id=<?php echo $event_id; ?>">
                            <div class="mb-3">
                                <label for="nume" class="form-label">Nume *</label>
                                <input type="text" class="form-control" id="nume" name="nume" required>
                                <small class="text-muted">Ex: Standard, VIP, Early Bird, etc.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descriere" class="form-label">Descriere</label>
                                <textarea class="form-control" id="descriere" name="descriere" rows="3"></textarea>
                                <small class="text-muted">Descrieți ce include acest tip de bilet.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pret" class="form-label">Preț (lei) *</label>
                                <input type="number" class="form-control" id="pret" name="pret" min="0.01" step="0.01" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="cantitate" class="form-label">Cantitate disponibilă *</label>
                                <input type="number" class="form-control" id="cantitate" name="cantitate" min="1" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" name="add_ticket_type" class="btn btn-success">Adaugă tip de bilet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card shadow h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Tipuri de Bilete Existente</h5>
                    </div>
                    <div class="card-body">
                        <?php if(count($ticket_types) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nume</th>
                                            <th>Preț</th>
                                            <th>Disponibile</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($ticket_types as $ticket): ?>
                                            <?php
                                            // Calculează biletele vândute
                                            $sold_tickets_sql = "SELECT COUNT(*) as count FROM tickets WHERE ticket_type_id = ?";
                                            $stmt = $conn->prepare($sold_tickets_sql);
                                            $stmt->bind_param("i", $ticket['id']);
                                            $stmt->execute();
                                            $sold_result = $stmt->get_result()->fetch_assoc();
                                            $sold_tickets = $sold_result['count'];
                                            $availability = $ticket['cantitate_disponibila'] . ' / ' . $ticket['cantitate_totala'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $ticket['nume']; ?></strong>
                                                    <?php if(!empty($ticket['descriere'])): ?>
                                                        <br><small class="text-muted"><?php echo $ticket['descriere']; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $ticket['pret']; ?> lei</td>
                                                <td><?php echo $availability; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $ticket['status'] == 'disponibil' ? 'success' : 'danger'; ?>">
                                                        <?php echo ucfirst($ticket['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if($sold_tickets == 0): ?>
                                                        <a href="ticket-types.php?event_id=<?php echo $event_id; ?>&delete_ticket=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Ești sigur că vrei să ștergi acest tip de bilet?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-danger" disabled title="Nu poți șterge acest tip de bilet deoarece există bilete vândute">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-ticket-perforated text-muted" style="font-size: 3rem;"></i>
                                <h4 class="mt-3">Niciun tip de bilet</h4>
                                <p class="text-muted">Nu ai adăugat încă niciun tip de bilet pentru acest eveniment.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4">
            <div class="d-flex justify-content-between">
                <a href="my-events.php" class="btn btn-secondary">Înapoi la evenimente</a>
                <a href="../event-details.php?id=<?php echo $event_id; ?>" class="btn btn-primary" target="_blank">
                    <i class="bi bi-eye me-1"></i> Vezi pagina evenimentului
                </a>
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