<?php
require_once "config/database.php";
require_once "includes/functions.php";

// Verificare dacă utilizatorul este deja autentificat
if(verificaLogin()) {
    header("Location: index.php");
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
    
    // Validare
    if(empty($nume) || empty($prenume) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Toate câmpurile sunt obligatorii.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Te rugăm să introduci o adresă de email validă.";
    } elseif(strlen($password) < 6) {
        $error = "Parola trebuie să aibă cel puțin 6 caractere.";
    } elseif($password != $confirm_password) {
        $error = "Parolele nu coincid.";
    } else {
        // Verifică dacă email-ul există deja
        $check_sql = "SELECT id FROM users WHERE email = '$email'";
        $check_result = $conn->query($check_sql);
        
        if($check_result->num_rows > 0) {
            $error = "Există deja un cont cu această adresă de email.";
        } else {
            // Hash parola
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Inserează utilizatorul în baza de date
            $insert_sql = "INSERT INTO users (nume, prenume, email, telefon, parola, rol) VALUES ('$nume', '$prenume', '$email', '$telefon', '$hashed_password', 'utilizator')";
            
            if($conn->query($insert_sql) === TRUE) {
                $user_id = $conn->insert_id;
                
                // Autentifică utilizatorul
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_nume'] = $nume . ' ' . $prenume;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'utilizator';
                
                $_SESSION['mesaj'] = "Contul tău a fost creat cu succes! Bine ai venit, $nume!";
                $_SESSION['tip_mesaj'] = "success";
                
                header("Location: index.php");
                exit;
            } else {
                $error = "A apărut o eroare la crearea contului: " . $conn->error;
            }
        }
    }
}

$page_title = "Înregistrare";
require_once "includes/header.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Creează un cont nou</h2>
                    
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
                    
                    <form method="post" action="register.php">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nume" class="form-label">Nume</label>
                                <input type="text" class="form-control" id="nume" name="nume" value="<?php echo isset($_POST['nume']) ? htmlspecialchars($_POST['nume']) : ''; ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="prenume" class="form-label">Prenume</label>
                                <input type="text" class="form-control" id="prenume" name="prenume" value="<?php echo isset($_POST['prenume']) ? htmlspecialchars($_POST['prenume']) : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="telefon" class="form-label">Telefon</label>
                            <input type="tel" class="form-control" id="telefon" name="telefon" value="<?php echo isset($_POST['telefon']) ? htmlspecialchars($_POST['telefon']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Parolă</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <small class="text-muted">Parola trebuie să aibă minim 6 caractere.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmă parola</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">Sunt de acord cu <a href="#">termenii și condițiile</a></label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Înregistrare</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p>Ai deja un cont? <a href="login.php">Autentifică-te</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once "includes/footer.php";
?>