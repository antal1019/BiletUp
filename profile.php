<?php
require_once "config/database.php";
require_once "includes/functions.php";

session_start();

// Verifică dacă utilizatorul este autentificat
if(!verificaLogin()) {
    $_SESSION['mesaj'] = "Trebuie să fii autentificat pentru a accesa profilul.";
    $_SESSION['tip_mesaj'] = "warning";
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Obține detaliile utilizatorului
$user_sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Procesare formular actualizare profil
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $nume = $conn->real_escape_string($_POST['nume']);
    $prenume = $conn->real_escape_string($_POST['prenume']);
    $email = $conn->real_escape_string($_POST['email']);
    $telefon = $conn->real_escape_string($_POST['telefon']);
    
    // Validare
    if(empty($nume) || empty($prenume) || empty($email)) {
        $error = "Numele, prenumele și email-ul sunt obligatorii.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Te rugăm să introduci o adresă de email validă.";
    } else {
        // Verifică dacă email-ul există deja și nu este al utilizatorului curent
        $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $error = "Există deja un alt cont cu această adresă de email.";
        } else {
            // Actualizează profilul
            $update_sql = "UPDATE users SET nume = ?, prenume = ?, email = ?, telefon = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssi", $nume, $prenume, $email, $telefon, $user_id);
            
            if($update_stmt->execute()) {
                $success = "Profilul tău a fost actualizat cu succes!";
                // Actualizează date sesiune
                $_SESSION['user_nume'] = $nume . ' ' . $prenume;
                $_SESSION['user_email'] = $email;
                
                // Reîncarcă datele utilizatorului
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error = "A apărut o eroare la actualizarea profilului: " . $conn->error;
            }
        }
    }
}

// Procesare formular schimbare parolă
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validare
    if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Toate câmpurile sunt obligatorii.";
    } elseif(strlen($new_password) < 6) {
        $error = "Noua parolă trebuie să aibă cel puțin 6 caractere.";
    } elseif($new_password != $confirm_password) {
        $error = "Parolele noi nu coincid.";
    } else {
        // Verifică parola curentă
        if(password_verify($current_password, $user['parola'])) {
            // Hash-uiește noua parolă
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Actualizează parola
            $update_sql = "UPDATE users SET parola = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if($update_stmt->execute()) {
                $success = "Parola ta a fost schimbată cu succes!";
            } else {
                $error = "A apărut o eroare la schimbarea parolei: " . $conn->error;
            }
        } else {
            $error = "Parola curentă este incorectă.";
        }
    }
}

$page_title = "Profil utilizator";
require_once "includes/header.php";
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4 mb-lg-0">
            <div class="card shadow">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="bi bi-person-circle" style="font-size: 80px; color: #0d6efd;"></i>
                    </div>
                    <h5 class="card-title"><?php echo $user['nume'] . ' ' . $user['prenume']; ?></h5>
                    <p class="card-text text-muted"><?php echo $user['email']; ?></p>
                    <p class="card-text">
                        <span class="badge bg-<?php echo $user['rol'] == 'admin' ? 'danger' : ($user['rol'] == 'organizator' ? 'warning' : 'primary'); ?>">
                            <?php echo ucfirst($user['rol']); ?>
                        </span>
                    </p>
                    <p class="card-text text-muted small">Membru din <?php echo date('F Y', strtotime($user['data_inregistrare'])); ?></p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#profile-info" class="list-group-item list-group-item-action active" data-bs-toggle="list">Informații profil</a>
                    <a href="#change-password" class="list-group-item list-group-item-action" data-bs-toggle="list">Schimbă parola</a>
                    <a href="my-tickets.php" class="list-group-item list-group-item-action">Biletele mele</a>
                    <a href="logout.php" class="list-group-item list-group-item-action text-danger">Deconectare</a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-9">
            <?php if(!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if(!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="tab-content">
                <!-- Informații profil -->
                <div class="tab-pane fade show active" id="profile-info">
                    <div class="card shadow">
                        <div class="card-header bg-white">
                            <h4 class="mb-0">Informații profil</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="profile.php">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="nume" class="form-label">Nume</label>
                                        <input type="text" class="form-control" id="nume" name="nume" value="<?php echo $user['nume']; ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="prenume" class="form-label">Prenume</label>
                                        <input type="text" class="form-control" id="prenume" name="prenume" value="<?php echo $user['prenume']; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="telefon" class="form-label">Telefon</label>
                                    <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo $user['telefon']; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Rol</label>
                                    <input type="text" class="form-control" value="<?php echo ucfirst($user['rol']); ?>" readonly>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Data înregistrării</label>
                                    <input type="text" class="form-control" value="<?php echo date('d.m.Y H:i', strtotime($user['data_inregistrare'])); ?>" readonly>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="update_profile" class="btn btn-primary">Salvează modificările</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Schimbă parola -->
                <div class="tab-pane fade" id="change-password">
                    <div class="card shadow">
                        <div class="card-header bg-white">
                            <h4 class="mb-0">Schimbă parola</h4>
                        </div>
                        <div class="card-body">
                            <form method="post" action="profile.php">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Parola curentă</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Parola nouă</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    <small class="text-muted">Parola trebuie să aibă minim 6 caractere.</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmă parola nouă</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="change_password" class="btn btn-primary">Schimbă parola</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once "includes/footer.php";
?>