<?php
require_once "session_config.php";
require_once "config/database.php";
require_once "includes/functions.php";

// Verifică dacă utilizatorul este admin sau organizator
if (!isset($_SESSION['user_role']) || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'organizator')) {
    $_SESSION['mesaj'] = "Nu ai permisiunea de a accesa această pagină!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: index.php");
    exit;
}

$result_message = "";
$result_type = "";
$ticket_info = null;

// Procesare validare QR
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['qr_code'])) {
    $qr_data = trim($_POST['qr_code']);

    if (empty($qr_data)) {
        $result_message = "Te rugăm să introduci datele QR code-ului.";
        $result_type = "danger";
    } else {
        $ticket_info = validateQRCode($qr_data);

        if ($ticket_info) {
            if ($ticket_info['status'] == 'valid') {
                $result_message = "Bilet valid! Acces permis.";
                $result_type = "success";

                // Marchează biletul ca utilizat (opțional)
                if (isset($_POST['mark_used'])) {
                    $update_sql = "UPDATE tickets SET status = 'utilizat' WHERE id = ?";
                    $stmt = $conn->prepare($update_sql);
                    $stmt->bind_param("i", $ticket_info['id']);
                    $stmt->execute();

                    $result_message .= " Biletul a fost marcat ca utilizat.";
                }
            } elseif ($ticket_info['status'] == 'utilizat') {
                $result_message = "Bilet deja utilizat!";
                $result_type = "warning";
            } else {
                $result_message = "Bilet anulat!";
                $result_type = "danger";
            }
        } else {
            $result_message = "Cod QR invalid sau bilet inexistent!";
            $result_type = "danger";
        }
    }
}

$page_title = "Validare Cod QR";
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
    <link href="css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">BiletUP</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Înapoi</a>
                <a class="nav-link" href="logout.php">Deconectare</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="bi bi-qr-code-scan me-2"></i>
                            Validare Cod QR Bilet
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($result_message)): ?>
                            <div class="alert alert-<?php echo $result_type; ?> alert-dismissible fade show" role="alert">
                                <?php echo $result_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="validate-qr.php">
                            <div class="mb-3">
                                <label for="qr_code" class="form-label">Cod QR sau Cod Bilet</label>
                                <input type="text" class="form-control form-control-lg" id="qr_code" name="qr_code"
                                    placeholder="Scanează codul QR sau introdu manual codul biletului"
                                    value="<?php echo isset($_POST['qr_code']) ? htmlspecialchars($_POST['qr_code']) : ''; ?>"
                                    required autofocus>
                                <small class="form-text text-muted">
                                    Formatul QR: BILET-XXXX-XXXX-XXXX-EventID sau doar codul biletului: XXXX-XXXX-XXXX
                                </small>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="mark_used" name="mark_used" value="1">
                                <label class="form-check-label" for="mark_used">
                                    Marchează biletul ca utilizat după validare
                                </label>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Validează Bilet
                                </button>
                            </div>
                        </form>

                        <?php if ($ticket_info): ?>
                            <div class="mt-4">
                                <h5>Informații Bilet:</h5>
                                <div class="card">
                                    <div class="card-body">
                                        <p><strong>Eveniment:</strong> <?php echo $ticket_info['eveniment_titlu']; ?></p>
                                        <p><strong>Tip bilet:</strong> <?php echo $ticket_info['tip_bilet']; ?></p>
                                        <p><strong>Data eveniment:</strong> <?php echo formatData($ticket_info['data_inceput']); ?>, <?php echo date('H:i', strtotime($ticket_info['ora_inceput'])); ?></p>
                                        <p><strong>Cod bilet:</strong> <?php echo $ticket_info['cod_unic']; ?></p>
                                        <p><strong>Status:</strong>
                                            <span class="badge bg-<?php echo $ticket_info['status'] == 'valid' ? 'success' : ($ticket_info['status'] == 'utilizat' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($ticket_info['status']); ?>
                                            </span>
                                        </p>
                                        <p><strong>Data achiziție:</strong> <?php echo date('d.m.Y H:i', strtotime($ticket_info['data_achizitie'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Auto-focus pe câmpul de input
        document.getElementById('qr_code').focus();

        // Curăță câmpul după validare reușită
        <?php if ($result_type == 'success'): ?>
            setTimeout(function() {
                document.getElementById('qr_code').value = '';
                document.getElementById('qr_code').focus();
            }, 2000);
        <?php endif; ?>
    </script>
</body>

</html>