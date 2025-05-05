<?php
require_once "session_config.php"; // Include configurarea sesiunii
require_once "config/database.php";
require_once "includes/functions.php";

// Verificare dacă utilizatorul este deja autentificat
if(isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

// Procesare formular
if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];
    
    // Validare
    if(empty($email) || empty($password)) {
        $error = "Te rugăm să completezi atât email-ul cât și parola.";
    } else {
        // Caută utilizatorul în baza de date
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($sql);
        
        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verifică parola
            if(password_verify($password, $user['parola'])) {
                // Autentificare reușită
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nume'] = $user['nume'] . ' ' . $user['prenume'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['rol'];
                
                $_SESSION['mesaj'] = "Bine ai revenit, " . $_SESSION['user_nume'] . "!";
                $_SESSION['tip_mesaj'] = "success";
                
                // Verifică dacă există redirecționare după login
                if(isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                    exit;
                }
                
                header("Location: index.php");
                exit;
            } else {
                $error = "Parola introdusă este incorectă.";
            }
        } else {
            $error = "Nu există niciun cont asociat cu acest email.";
        }
    }
}

$page_title = "Autentificare";
require_once "includes/header.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Autentificare</h2>
                    
                    <?php if(!empty($error)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="login.php">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Parolă</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Ține-mă minte</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Autentificare</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p>Nu ai cont? <a href="register.php">Înregistrează-te</a></p>
                        <p><a href="forgot-password.php">Ai uitat parola?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once "includes/footer.php";
?>