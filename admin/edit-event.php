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

// Verifică dacă ID-ul evenimentului este furnizat
if(!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mesaj'] = "ID-ul evenimentului nu a fost specificat!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: events.php");
    exit;
}

$event_id = $conn->real_escape_string($_GET['id']);
$error = "";
$success = "";

// Obține detaliile evenimentului
$event_sql = "SELECT * FROM events WHERE id = ?";
$stmt = $conn->prepare($event_sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows != 1) {
    $_SESSION['mesaj'] = "Evenimentul nu a fost găsit!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: events.php");
    exit;
}

$event = $result->fetch_assoc();

// Verifică dacă utilizatorul are dreptul să editeze evenimentul
if($_SESSION['user_role'] != 'admin' && $event['organizator_id'] != $_SESSION['user_id']) {
    $_SESSION['mesaj'] = "Nu ai permisiunea de a edita acest eveniment!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: events.php");
    exit;
}

// Obține categoriile
$categories = getCategories();

// Procesare formular
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preia datele din formular
    $titlu = $conn->real_escape_string($_POST['titlu']);
    $descriere = $conn->real_escape_string($_POST['descriere']);
    $locatie = $conn->real_escape_string($_POST['locatie']);
    $oras = $conn->real_escape_string($_POST['oras']);
    $data_inceput = $conn->real_escape_string($_POST['data_inceput']);
    $ora_inceput = $conn->real_escape_string($_POST['ora_inceput']);
    $data_sfarsit = $conn->real_escape_string($_POST['data_sfarsit']);
    $ora_sfarsit = $conn->real_escape_string($_POST['ora_sfarsit']);
    $pret_minim = floatval($_POST['pret_minim']);
    $pret_maxim = floatval($_POST['pret_maxim']);
    $categorie_id = intval($_POST['categorie_id']);
    $capacitate_maxima = intval($_POST['capacitate_maxima']);
    $bilete_disponibile = intval($_POST['bilete_disponibile']);
    $status = $conn->real_escape_string($_POST['status']);
    
    // Păstrăm imaginea existentă
    $imagine = $event['imagine'];
    
    // Validare
    if(empty($titlu) || empty($descriere) || empty($locatie) || empty($oras) || empty($data_inceput) || 
       empty($ora_inceput) || empty($data_sfarsit) || empty($ora_sfarsit)) {
        $error = "Toate câmpurile obligatorii trebuie completate.";
    } elseif($pret_minim < 0 || $pret_maxim < 0) {
        $error = "Prețurile nu pot fi negative.";
    } elseif($pret_minim > $pret_maxim) {
        $error = "Prețul minim nu poate fi mai mare decât prețul maxim.";
    } elseif($capacitate_maxima <= 0) {
        $error = "Capacitatea maximă trebuie să fie mai mare de 0.";
    } elseif($bilete_disponibile < 0) {
        $error = "Numărul de bilete disponibile nu poate fi negativ.";
    } elseif($bilete_disponibile > $capacitate_maxima) {
        $error = "Numărul de bilete disponibile nu poate depăși capacitatea maximă.";
    } else {
        // Actualizează evenimentul în baza de date
        $sql = "UPDATE events SET 
                titlu = ?, descriere = ?, locatie = ?, oras = ?, data_inceput = ?, ora_inceput = ?, 
                data_sfarsit = ?, ora_sfarsit = ?, imagine = ?, pret_minim = ?, pret_maxim = ?, 
                categorie_id = ?, capacitate_maxima = ?, bilete_disponibile = ?, status = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssddiissi", $titlu, $descriere, $locatie, $oras, $data_inceput, $ora_inceput, 
                         $data_sfarsit, $ora_sfarsit, $imagine, $pret_minim, $pret_maxim, $categorie_id, 
                         $capacitate_maxima, $bilete_disponibile, $status, $event_id);
        
        if($stmt->execute()) {
            $success = "Evenimentul a fost actualizat cu succes!";
            
            // Reîncarcă detaliile evenimentului
            $stmt = $conn->prepare($event_sql);
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $event = $result->fetch_assoc();
            
            // Setează mesajul sesiunii și redirecționează
            $_SESSION['mesaj'] = "Evenimentul a fost actualizat cu succes!";
            $_SESSION['tip_mesaj'] = "success";
        } else {
            $error = "A apărut o eroare la actualizarea evenimentului: " . $conn->error;
        }
    }
}

$page_title = "Editare Eveniment";
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

        <div class="card shadow">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Editare Eveniment</h5>
            </div>
            <div class="card-body">
                <form method="post" action="edit-event.php?id=<?php echo $event_id; ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="titlu" class="form-label">Titlu eveniment *</label>
                            <input type="text" class="form-control" id="titlu" name="titlu" value="<?php echo $event['titlu']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="categorie_id" class="form-label">Categorie *</label>
                            <select class="form-select" id="categorie_id" name="categorie_id" required>
                                <option value="">Selectează categoria</option>
                                <?php foreach($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $event['categorie_id']) ? 'selected' : ''; ?>>
                                        <?php echo $category['nume']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descriere" class="form-label">Descriere *</label>
                        <textarea class="form-control" id="descriere" name="descriere" rows="5" required><?php echo $event['descriere']; ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="locatie" class="form-label">Locație *</label>
                            <input type="text" class="form-control" id="locatie" name="locatie" value="<?php echo $event['locatie']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="oras" class="form-label">Oraș *</label>
                            <input type="text" class="form-control" id="oras" name="oras" value="<?php echo $event['oras']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="data_inceput" class="form-label">Data început *</label>
                            <input type="date" class="form-control" id="data_inceput" name="data_inceput" value="<?php echo $event['data_inceput']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="ora_inceput" class="form-label">Ora început *</label>
                            <input type="time" class="form-control" id="ora_inceput" name="ora_inceput" value="<?php echo $event['ora_inceput']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="data_sfarsit" class="form-label">Data sfârșit *</label>
                            <input type="date" class="form-control" id="data_sfarsit" name="data_sfarsit" value="<?php echo $event['data_sfarsit']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="ora_sfarsit" class="form-label">Ora sfârșit *</label>
                            <input type="time" class="form-control" id="ora_sfarsit" name="ora_sfarsit" value="<?php echo $event['ora_sfarsit']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label for="pret_minim" class="form-label">Preț minim (lei) *</label>
                            <input type="number" class="form-control" id="pret_minim" name="pret_minim" min="0" step="0.01" value="<?php echo $event['pret_minim']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="pret_maxim" class="form-label">Preț maxim (lei) *</label>
                            <input type="number" class="form-control" id="pret_maxim" name="pret_maxim" min="0" step="0.01" value="<?php echo $event['pret_maxim']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="capacitate_maxima" class="form-label">Capacitate maximă *</label>
                            <input type="number" class="form-control" id="capacitate_maxima" name="capacitate_maxima" min="1" value="<?php echo $event['capacitate_maxima']; ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="bilete_disponibile" class="form-label">Bilete disponibile *</label>
                            <input type="number" class="form-control" id="bilete_disponibile" name="bilete_disponibile" min="0" value="<?php echo $event['bilete_disponibile']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status *</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="activ" <?php echo ($event['status'] == 'activ') ? 'selected' : ''; ?>>Activ</option>
                            <option value="anulat" <?php echo ($event['status'] == 'anulat') ? 'selected' : ''; ?>>Anulat</option>
                            <option value="incheiat" <?php echo ($event['status'] == 'incheiat') ? 'selected' : ''; ?>>Încheiat</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <p class="text-muted small">* Câmpuri obligatorii</p>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="events.php" class="btn btn-secondary">Înapoi la evenimente</a>
                        <div>
                            <a href="ticket-types.php?event_id=<?php echo $event_id; ?>" class="btn btn-info me-2">
                                <i class="bi bi-ticket-perforated me-1"></i> Gestionare bilete
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-save me-1"></i> Salvează modificările
                            </button>
                        </div>
                    </div>
                </form>
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
        // Asigură-te că data de sfârșit nu este anterioară datei de început
        document.getElementById('data_inceput').addEventListener('change', function() {
            const startDate = this.value;
            const endDate = document.getElementById('data_sfarsit').value;
            
            if(endDate < startDate) {
                document.getElementById('data_sfarsit').value = startDate;
            }
        });
        
        // Asigură-te că biletele disponibile nu depășesc capacitatea maximă
        document.getElementById('capacitate_maxima').addEventListener('change', function() {
            const maxCapacity = parseInt(this.value) || 0;
            const availableTickets = parseInt(document.getElementById('bilete_disponibile').value) || 0;
            
            if(availableTickets > maxCapacity) {
                document.getElementById('bilete_disponibile').value = maxCapacity;
            }
        });
        
        // Asigură-te că prețul maxim nu este mai mic decât prețul minim
        document.getElementById('pret_minim').addEventListener('change', function() {
            const minPrice = parseFloat(this.value) || 0;
            const maxPrice = parseFloat(document.getElementById('pret_maxim').value) || 0;
            
            if(maxPrice < minPrice) {
                document.getElementById('pret_maxim').value = minPrice;
            }
        });
    });
    </script>
</body>
</html>