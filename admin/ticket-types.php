<?php
require_once "../config/database.php";
require_once "../includes/functions.php";

session_start();

// Verifică dacă utilizatorul este admin sau organizator
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'organizator')) {
    $_SESSION['mesaj'] = "Nu ai permisiunea de a accesa această pagină!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: ../index.php");
    exit;
}

// Verifică dacă evenimentul există
if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    $_SESSION['mesaj'] = "ID-ul evenimentului nu a fost specificat!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: events.php");
    exit;
}

$event_id = $conn->real_escape_string($_GET['event_id']);

// Obține detalii despre eveniment
$event_sql = "SELECT * FROM events WHERE id = ?";
$stmt = $conn->prepare($event_sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event_result = $stmt->get_result();

if ($event_result->num_rows != 1) {
    $_SESSION['mesaj'] = "Evenimentul nu a fost găsit!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: events.php");
    exit;
}

$event = $event_result->fetch_assoc();

// Verifică permisiunile (doar admin sau organizatorul evenimentului)
if ($_SESSION['user_role'] != 'admin' && $event['organizator_id'] != $_SESSION['user_id']) {
    $_SESSION['mesaj'] = "Nu ai permisiunea de a gestiona biletele pentru acest eveniment!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: events.php");
    exit;
}

// Procesează ștergerea tipului de bilet
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $ticket_type_id = $conn->real_escape_string($_GET['delete']);

    $delete_sql = "DELETE FROM ticket_types WHERE id = ? AND event_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $ticket_type_id, $event_id);

    if ($delete_stmt->execute()) {
        $_SESSION['mesaj'] = "Tipul de bilet a fost șters cu succes!";
        $_SESSION['tip_mesaj'] = "success";
    } else {
        $_SESSION['mesaj'] = "A apărut o eroare la ștergerea tipului de bilet!";
        $_SESSION['tip_mesaj'] = "danger";
    }

    header("Location: ticket-types.php?event_id=$event_id");
    exit;
}

// Procesează adăugarea/editarea tipului de bilet
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nume = $conn->real_escape_string($_POST['nume']);
    $descriere = $conn->real_escape_string($_POST['descriere']);
    $pret = floatval($_POST['pret']);
    $cantitate_totala = intval($_POST['cantitate_totala']);
    $cantitate_disponibila = intval($_POST['cantitate_disponibila']);
    $status = $conn->real_escape_string($_POST['status']);

    // Validare
    if (empty($nume) || $pret <= 0 || $cantitate_totala <= 0) {
        $_SESSION['mesaj'] = "Toate câmpurile obligatorii trebuie completate corect!";
        $_SESSION['tip_mesaj'] = "danger";
    } elseif ($cantitate_disponibila > $cantitate_totala) {
        $_SESSION['mesaj'] = "Cantitatea disponibilă nu poate depăși cantitatea totală!";
        $_SESSION['tip_mesaj'] = "danger";
    } else {
        if (isset($_POST['ticket_type_id']) && !empty($_POST['ticket_type_id'])) {
            // Editare tip bilet existent
            $ticket_type_id = intval($_POST['ticket_type_id']);

            $update_sql = "UPDATE ticket_types SET nume = ?, descriere = ?, pret = ?, 
                           cantitate_totala = ?, cantitate_disponibila = ?, status = ? 
                           WHERE id = ? AND event_id = ?";

            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param(
                "ssdiisii",
                $nume,
                $descriere,
                $pret,
                $cantitate_totala,
                $cantitate_disponibila,
                $status,
                $ticket_type_id,
                $event_id
            );

            if ($update_stmt->execute()) {
                $_SESSION['mesaj'] = "Tipul de bilet a fost actualizat cu succes!";
                $_SESSION['tip_mesaj'] = "success";
            } else {
                $_SESSION['mesaj'] = "A apărut o eroare la actualizarea tipului de bilet!";
                $_SESSION['tip_mesaj'] = "danger";
            }
        } else {
            // Adăugare tip bilet nou
            $insert_sql = "INSERT INTO ticket_types (event_id, nume, descriere, pret, cantitate_totala, 
                           cantitate_disponibila, status) VALUES (?, ?, ?, ?, ?, ?, ?)";

            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param(
                "issdiis",
                $event_id,
                $nume,
                $descriere,
                $pret,
                $cantitate_totala,
                $cantitate_disponibila,
                $status
            );

            if ($insert_stmt->execute()) {
                $_SESSION['mesaj'] = "Tipul de bilet a fost adăugat cu succes!";
                $_SESSION['tip_mesaj'] = "success";
            } else {
                $_SESSION['mesaj'] = "A apărut o eroare la adăugarea tipului de bilet!";
                $_SESSION['tip_mesaj'] = "danger";
            }
        }

        header("Location: ticket-types.php?event_id=$event_id");
        exit;
    }
}

// Obține tipurile de bilete pentru eveniment
$ticket_types_sql = "SELECT * FROM ticket_types WHERE event_id = ? ORDER BY pret";
$stmt = $conn->prepare($ticket_types_sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$ticket_types_result = $stmt->get_result();
$ticket_types = [];

while ($ticket_type = $ticket_types_result->fetch_assoc()) {
    $ticket_types[] = $ticket_type;
}

// Pentru editare
$edit_mode = false;
$ticket_type_edit = null;

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $ticket_type_id = $conn->real_escape_string($_GET['edit']);

    $edit_sql = "SELECT * FROM ticket_types WHERE id = ? AND event_id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("ii", $ticket_type_id, $event_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();

    if ($edit_result->num_rows == 1) {
        $edit_mode = true;
        $ticket_type_edit = $edit_result->fetch_assoc();
    }
}

$page_title = "Gestionare Tipuri Bilete";
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
                    <?php if ($_SESSION['user_role'] == 'admin'): ?>
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
        <?php if (isset($_SESSION['mesaj'])): ?>
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
            <h1>Gestionare Tipuri Bilete</h1>
            <a href="events.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Înapoi la evenimente
            </a>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Detalii Eveniment</h5>
            </div>
            <div class="card-body">
                <h4><?php echo $event['titlu']; ?></h4>
                <p><strong>Data:</strong> <?php echo formatData($event['data_inceput']); ?>, <?php echo date('H:i', strtotime($event['ora_inceput'])); ?></p>
                <p><strong>Locație:</strong> <?php echo $event['locatie']; ?>, <?php echo $event['oras']; ?></p>
                <p>
                    <strong>Status:</strong>
                    <span class="badge bg-<?php echo $event['status'] == 'activ' ? 'success' : ($event['status'] == 'anulat' ? 'danger' : 'secondary'); ?>">
                        <?php echo ucfirst($event['status']); ?>
                    </span>
                </p>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header bg-<?php echo $edit_mode ? 'warning' : 'success'; ?> text-white">
                        <h5 class="mb-0"><?php echo $edit_mode ? 'Editează Tip Bilet' : 'Adaugă Tip Bilet Nou'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="ticket-types.php?event_id=<?php echo $event_id; ?>">
                            <?php if ($edit_mode): ?>
                                <input type="hidden" name="ticket_type_id" value="<?php echo $ticket_type_edit['id']; ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="nume" class="form-label">Nume tip bilet *</label>
                                <input type="text" class="form-control" id="nume" name="nume" required
                                    value="<?php echo $edit_mode ? $ticket_type_edit['nume'] : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="descriere" class="form-label">Descriere</label>
                                <textarea class="form-control" id="descriere" name="descriere" rows="3"><?php echo $edit_mode ? $ticket_type_edit['descriere'] : ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="pret" class="form-label">Preț (lei) *</label>
                                <input type="number" class="form-control" id="pret" name="pret" min="0" step="0.01" required
                                    value="<?php echo $edit_mode ? $ticket_type_edit['pret'] : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="cantitate_totala" class="form-label">Cantitate totală *</label>
                                <input type="number" class="form-control" id="cantitate_totala" name="cantitate_totala" min="1" required
                                    value="<?php echo $edit_mode ? $ticket_type_edit['cantitate_totala'] : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="cantitate_disponibila" class="form-label">Cantitate disponibilă *</label>
                                <input type="number" class="form-control" id="cantitate_disponibila" name="cantitate_disponibila" min="0" required
                                    value="<?php echo $edit_mode ? $ticket_type_edit['cantitate_disponibila'] : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="disponibil" <?php echo $edit_mode && $ticket_type_edit['status'] == 'disponibil' ? 'selected' : ''; ?>>Disponibil</option>
                                    <option value="epuizat" <?php echo $edit_mode && $ticket_type_edit['status'] == 'epuizat' ? 'selected' : ''; ?>>Epuizat</option>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <?php if ($edit_mode): ?>
                                    <button type="submit" class="btn btn-warning">Actualizează tip bilet</button>
                                    <a href="ticket-types.php?event_id=<?php echo $event_id; ?>" class="btn btn-outline-secondary">Anulează editarea</a>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-success">Adaugă tip bilet</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Tipuri de Bilete Existente</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($ticket_types) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nume</th>
                                            <th>Preț</th>
                                            <th>Cantitate</th>
                                            <th>Disponibil</th>
                                            <th>Status</th>
                                            <th>Acțiuni</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($ticket_types as $ticket_type): ?>
                                            <tr>
                                                <td><?php echo $ticket_type['nume']; ?></td>
                                                <td><?php echo $ticket_type['pret']; ?> lei</td>
                                                <td><?php echo $ticket_type['cantitate_totala']; ?></td>
                                                <td><?php echo $ticket_type['cantitate_disponibila']; ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $ticket_type['status'] == 'disponibil' ? 'success' : 'danger'; ?>">
                                                        <?php echo $ticket_type['status'] == 'disponibil' ? 'Disponibil' : 'Epuizat'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="ticket-types.php?event_id=<?php echo $event_id; ?>&edit=<?php echo $ticket_type['id']; ?>" class="btn btn-warning" title="Editează">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <a href="ticket-types.php?event_id=<?php echo $event_id; ?>&delete=<?php echo $ticket_type['id']; ?>" class="btn btn-danger" title="Șterge" onclick="return confirm('Ești sigur că vrei să ștergi acest tip de bilet?')">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <p class="mb-0">Nu există tipuri de bilete definite pentru acest eveniment. Adaugă primul tip de bilet folosind formularul din stânga.</p>
                            </div>
                        <?php endif; ?>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Asigură-te că cantitatea disponibilă nu depășește cantitatea totală
            document.getElementById('cantitate_totala').addEventListener('change', function() {
                const total = parseInt(this.value) || 0;
                const available = parseInt(document.getElementById('cantitate_disponibila').value) || 0;

                if (available > total) {
                    document.getElementById('cantitate_disponibila').value = total;
                }
            });
        });
    </script>
</body>

</html>