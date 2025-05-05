<?php
require_once "../session_config.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

// Verifică dacă utilizatorul este admin
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    $_SESSION['mesaj'] = "Nu ai permisiunea de a accesa această pagină!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: ../index.php");
    exit;
}

$error = "";
$success = "";

// Procesare formular
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preia datele din formular
    $nume = $conn->real_escape_string($_POST['nume']);
    $prenume = $conn->real_escape_string($_POST['prenume']);
    $email = $conn->real_escape_string($_POST['email']);
    $telefon = $conn->real_escape_string($_POST['telefon']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $rol = $conn->real_escape_string($_POST['rol']);
    
    // Validare
    if(empty($nume) || empty($prenume) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Toate câmpurile obligatorii trebuie completate.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Te rugăm să introduci o adresă de email validă.";
    } elseif(strlen($password) < 6) {
        $error = "Parola trebuie să aibă cel puțin 6 caractere.";
    } elseif($password != $confirm_password) {
        $error = "Parolele nu coincid.";
    } else {
        // Verifică dacă email-ul există deja
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $error = "Există deja un cont cu această adresă de email.";
        } else {
            // Hash parola
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Inserează utilizatorul în baza de date
            $insert_sql = "INSERT INTO users (nume, prenume, email, telefon, parola, rol, data_inregistrare) 
                          VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssss", $nume, $prenume, $email, $telefon, $hashed_password, $rol);
            
            if($stmt->execute()) {
                $success = "Utilizatorul a fost adăugat cu succes!";
                
                // Resetează câmpurile formularului
                $nume = $prenume = $email = $telefon = "";
            } else {
                $error = "A apărut o eroare la adăugarea utilizatorului: " . $conn->error;
            }
        }
    }
}

$page_title = "Adaugă Utilizator";
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
                        <a class="nav-link" href="events.php">Evenimente</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">Utilizatori</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">Categorii</a>
                    </li>
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
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Adaugă Utilizator Nou</h5>
            </div>
            <div class="card-body">
                <form method="post" action="add-user.php">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="nume" class="form-label">Nume *</label>
                            <input type="text" class="form-control" id="nume" name="nume" value="<?php echo isset($nume) ? $nume : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="prenume" class="form-label">Prenume *</label>
                            <input type="text" class="form-control" id="prenume" name="prenume" value="<?php echo isset($prenume) ? $prenume : ''; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telefon" class="form-label">Telefon</label>
                        <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo isset($telefon) ? $telefon : ''; ?>">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Parolă *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Parola trebuie să aibă minim 6 caractere.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label">Confirmă parola *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol *</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="utilizator">Utilizator</option>
                            <option value="organizator">Organizator</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <p class="text-muted small">* Câmpuri obligatorii</p>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="users.php" class="btn btn-secondary">Înapoi la utilizatori</a>
                        <button type="submit" class="btn btn-primary">Adaugă utilizator</button>
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
</body>
</html>