<?php
require_once "../session_config.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

// Verifică dacă utilizatorul este admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    $_SESSION['mesaj'] = "Nu ai permisiunea de a accesa această pagină!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: ../index.php");
    exit;
}

// Verifică dacă ID-ul utilizatorului este furnizat
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mesaj'] = "ID-ul utilizatorului nu a fost specificat!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: users.php");
    exit;
}

$user_id = $conn->real_escape_string($_GET['id']);
$error = "";
$success = "";

// Obține detaliile utilizatorului
$user_sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows != 1) {
    $_SESSION['mesaj'] = "Utilizatorul nu a fost găsit!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: users.php");
    exit;
}

$user = $result->fetch_assoc();

// Previne editarea propriului rol de admin (pentru siguranță)
$can_edit_role = ($user['id'] != $_SESSION['user_id']);

// Procesare formular
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Preia datele din formular
    $nume = $conn->real_escape_string($_POST['nume']);
    $prenume = $conn->real_escape_string($_POST['prenume']);
    $email = $conn->real_escape_string($_POST['email']);
    $telefon = $conn->real_escape_string($_POST['telefon']);
    $rol = $conn->real_escape_string($_POST['rol']);
    $status = isset($_POST['status']) ? 'activ' : 'inactiv';

    // Validare
    if (empty($nume) || empty($prenume) || empty($email)) {
        $error = "Numele, prenumele și email-ul sunt obligatorii.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Te rugăm să introduci o adresă de email validă.";
    } else {
        // Verifică dacă email-ul există deja și nu este al utilizatorului curent
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Există deja un alt utilizator cu această adresă de email.";
        } else {
            // Construiește query-ul de actualizare
            if ($can_edit_role) {
                $update_sql = "UPDATE users SET nume = ?, prenume = ?, email = ?, telefon = ?, rol = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("sssssi", $nume, $prenume, $email, $telefon, $rol, $user_id);
            } else {
                // Nu schimba rolul pentru propriul cont
                $update_sql = "UPDATE users SET nume = ?, prenume = ?, email = ?, telefon = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ssssi", $nume, $prenume, $email, $telefon, $user_id);
            }

            if ($update_stmt->execute()) {
                $success = "Utilizatorul a fost actualizat cu succes!";

                // Reîncarcă datele utilizatorului
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                // Actualizează sesiunea dacă e propriul cont
                if ($user_id == $_SESSION['user_id']) {
                    $_SESSION['user_nume'] = $nume . ' ' . $prenume;
                    $_SESSION['user_email'] = $email;
                }
            } else {
                $error = "A apărut o eroare la actualizarea utilizatorului: " . $conn->error;
            }
        }
    }

    // Procesare schimbare parolă (dacă a fost introdusă)
    if (!empty($_POST['new_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (strlen($new_password) < 6) {
            $error = "Parola trebuie să aibă cel puțin 6 caractere.";
        } elseif ($new_password != $confirm_password) {
            $error = "Parolele nu coincid.";
        } else {
            // Hash-uiește noua parolă
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Actualizează parola
            $password_sql = "UPDATE users SET parola = ? WHERE id = ?";
            $password_stmt = $conn->prepare($password_sql);
            $password_stmt->bind_param("si", $hashed_password, $user_id);

            if ($password_stmt->execute()) {
                $success .= " Parola a fost schimbată cu succes!";
            } else {
                $error = "A apărut o eroare la schimbarea parolei: " . $conn->error;
            }
        }
    }
}

$page_title = "Editare Utilizator";
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
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Editare Utilizator</h1>
            <a href="users.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Înapoi la utilizatori
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="bi bi-person-gear me-2"></i>
                            Editare utilizator: <?php echo $user['nume'] . ' ' . $user['prenume']; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="edit-user.php?id=<?php echo $user_id; ?>">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="nume" class="form-label">Nume *</label>
                                    <input type="text" class="form-control" id="nume" name="nume" value="<?php echo htmlspecialchars($user['nume']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="prenume" class="form-label">Prenume *</label>
                                    <input type="text" class="form-control" id="prenume" name="prenume" value="<?php echo htmlspecialchars($user['prenume']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="telefon" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo htmlspecialchars($user['telefon']); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="rol" class="form-label">Rol *</label>
                                <select class="form-select" id="rol" name="rol" <?php echo $can_edit_role ? '' : 'disabled'; ?> required>
                                    <option value="utilizator" <?php echo ($user['rol'] == 'utilizator') ? 'selected' : ''; ?>>Utilizator</option>
                                    <option value="organizator" <?php echo ($user['rol'] == 'organizator') ? 'selected' : ''; ?>>Organizator</option>
                                    <option value="admin" <?php echo ($user['rol'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                </select>
                                <?php if (!$can_edit_role): ?>
                                    <small class="text-muted">Nu poți schimba propriul rol.</small>
                                    <input type="hidden" name="rol" value="<?php echo $user['rol']; ?>">
                                <?php endif; ?>
                            </div>

                            <hr>
                            <h6 class="text-muted">Schimbare parolă (opțional)</h6>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label">Parolă nouă</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password">
                                    <small class="text-muted">Lasă gol dacă nu vrei să schimbi parola.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirmă parola nouă</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                                </div>
                            </div>

                            <div class="mb-3">
                                <p class="text-muted small">* Câmpuri obligatorii</p>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="users.php" class="btn btn-secondary">Anulează</a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-save me-1"></i> Salvează modificările
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Informații utilizator
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>ID:</strong> #<?php echo $user['id']; ?></p>
                        <p><strong>Data înregistrării:</strong><br><?php echo date('d.m.Y H:i', strtotime($user['data_inregistrare'])); ?></p>
                        <p><strong>Rol curent:</strong><br>
                            <span class="badge bg-<?php echo $user['rol'] == 'admin' ? 'danger' : ($user['rol'] == 'organizator' ? 'warning' : 'primary'); ?>">
                                <?php echo ucfirst($user['rol']); ?>
                            </span>
                        </p>

                        <hr>

                        <h6>Statistici</h6>
                        <?php
                        // Obține statistici utilizator
                        if ($user['rol'] == 'organizator' || $user['rol'] == 'admin') {
                            $events_sql = "SELECT COUNT(*) as count FROM events WHERE organizator_id = ?";
                            $stmt = $conn->prepare($events_sql);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $events_count = $stmt->get_result()->fetch_assoc()['count'];
                            echo "<p><strong>Evenimente create:</strong> $events_count</p>";
                        }

                        $tickets_sql = "SELECT COUNT(*) as count FROM tickets WHERE user_id = ?";
                        $stmt = $conn->prepare($tickets_sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $tickets_count = $stmt->get_result()->fetch_assoc()['count'];
                        echo "<p><strong>Bilete cumpărate:</strong> $tickets_count</p>";

                        $orders_sql = "SELECT COUNT(*) as count FROM orders WHERE user_id = ?";
                        $stmt = $conn->prepare($orders_sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $orders_count = $stmt->get_result()->fetch_assoc()['count'];
                        echo "<p><strong>Comenzi plasate:</strong> $orders_count</p>";
                        ?>
                    </div>
                </div>

                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                    <div class="card shadow mt-3">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Zona periculoasă
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="small text-muted">Acțiuni ireversibile asupra acestui utilizator.</p>
                            <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm w-100" onclick="return confirm('Ești sigur că vrei să ștergi acest utilizator? Această acțiune nu poate fi anulată!')">
                                <i class="bi bi-trash me-1"></i> Șterge utilizatorul
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
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
        // Validare formular
        document.addEventListener('DOMContentLoaded', function() {
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');

            function validatePasswords() {
                if (newPassword.value !== '' && confirmPassword.value !== '') {
                    if (newPassword.value !== confirmPassword.value) {
                        confirmPassword.setCustomValidity('Parolele nu coincid');
                    } else if (newPassword.value.length < 6) {
                        newPassword.setCustomValidity('Parola trebuie să aibă cel puțin 6 caractere');
                    } else {
                        confirmPassword.setCustomValidity('');
                        newPassword.setCustomValidity('');
                    }
                } else {
                    confirmPassword.setCustomValidity('');
                    newPassword.setCustomValidity('');
                }
            }

            newPassword.addEventListener('input', validatePasswords);
            confirmPassword.addEventListener('input', validatePasswords);
        });
    </script>
</body>

</html>