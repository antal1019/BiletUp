<?php
require_once "session_config.php";
require_once "config/database.php";
require_once "includes/functions.php";

// Verifică dacă utilizatorul este autentificat
if(!isset($_SESSION['user_id'])) {
    $_SESSION['mesaj'] = "Trebuie să fii autentificat pentru a descărca bilete.";
    $_SESSION['tip_mesaj'] = "warning";
    header("Location: login.php");
    exit;
}

// Verifică dacă ID-ul biletului este furnizat
if(!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mesaj'] = "ID-ul biletului nu a fost specificat.";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: my-tickets.php");
    exit;
}

$ticket_id = $conn->real_escape_string($_GET['id']);
$user_id = $_SESSION['user_id'];

// Obține informațiile despre bilet
$ticket_sql = "SELECT t.*, tt.nume as tip_bilet, e.titlu as eveniment_titlu, e.data_inceput, e.ora_inceput, 
                e.data_sfarsit, e.ora_sfarsit, e.locatie, e.oras
                FROM tickets t
                JOIN ticket_types tt ON t.ticket_type_id = tt.id
                JOIN events e ON t.event_id = e.id
                WHERE t.id = ? AND t.user_id = ?";

$stmt = $conn->prepare($ticket_sql);
$stmt->bind_param("ii", $ticket_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows != 1) {
    $_SESSION['mesaj'] = "Biletul nu a fost găsit sau nu ai acces la el.";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: my-tickets.php");
    exit;
}

$ticket = $result->fetch_assoc();

// Obține informații despre utilizator
$user_sql = "SELECT nume, prenume, email FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Generează un bilet HTML care poate fi printat
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilet pentru <?php echo $ticket['eveniment_titlu']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .ticket-container {
            max-width: 800px;
            margin: 20px auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .ticket-header {
            background-color: #0d6efd;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .ticket-body {
            padding: 30px;
        }
        .ticket-info {
            margin-bottom: 30px;
        }
        .ticket-info h2 {
            margin-top: 0;
            color: #333;
        }
        .ticket-info p {
            margin: 5px 0;
            color: #666;
        }
        .ticket-code {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }
        .ticket-code h3 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
        }
        .ticket-code .code {
            font-family: monospace;
            font-size: 22px;
            letter-spacing: 2px;
            color: #0d6efd;
            font-weight: bold;
        }
        .ticket-details {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .ticket-details .detail {
            width: 48%;
            margin-bottom: 15px;
        }
        .ticket-details .detail h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .ticket-details .detail p {
            margin: 0;
            color: #666;
        }
        .ticket-footer {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            font-size: 14px;
            color: #666;
            border-top: 1px solid #eee;
        }
        @media print {
            body {
                background-color: white;
            }
            .ticket-container {
                box-shadow: none;
                margin: 0;
            }
            .print-button {
                display: none;
            }
        }
        .print-button {
            display: block;
            text-align: center;
            margin: 20px auto;
        }
        .print-button button {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .print-button button:hover {
            background-color: #0a58ca;
        }
        .ticket-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .status-valid {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .status-utilizat {
            background-color: #e2e3e5;
            color: #41464b;
        }
        .status-anulat {
            background-color: #f8d7da;
            color: #842029;
        }
    </style>
</head>
<body>
    <div class="print-button">
        <button onclick="window.print()">Printează bilet</button>
    </div>
    
    <div class="ticket-container">
        <div class="ticket-header">
            <h1>BiletUP</h1>
            <p>Bilet electronic pentru eveniment</p>
        </div>
        
        <div class="ticket-body">
            <div class="ticket-info">
                <h2><?php echo $ticket['eveniment_titlu']; ?></h2>
                <p>
                    <strong>Data și ora:</strong> 
                    <?php echo formatData($ticket['data_inceput']); ?>, 
                    <?php echo date('H:i', strtotime($ticket['ora_inceput'])); ?> - 
                    <?php 
                        if($ticket['data_inceput'] != $ticket['data_sfarsit']) {
                            echo formatData($ticket['data_sfarsit']) . ', ';
                        }
                        echo date('H:i', strtotime($ticket['ora_sfarsit']));
                    ?>
                </p>
                <p><strong>Locație:</strong> <?php echo $ticket['locatie']; ?>, <?php echo $ticket['oras']; ?></p>
                <p><strong>Tip bilet:</strong> <?php echo $ticket['tip_bilet']; ?></p>
                <p>
                    <strong>Status:</strong> 
                    <span class="ticket-status status-<?php echo $ticket['status']; ?>">
                        <?php echo ucfirst($ticket['status']); ?>
                    </span>
                </p>
            </div>
            
            <div class="ticket-code">
                <h3>Cod unic bilet</h3>
                <p class="code"><?php echo $ticket['cod_unic']; ?></p>
            </div>
            
            <div class="ticket-details">
                <div class="detail">
                    <h4>Detalii participant</h4>
                    <p><strong>Nume:</strong> <?php echo $user['nume'] . ' ' . $user['prenume']; ?></p>
                    <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
                </div>
                
                <div class="detail">
                    <h4>Detalii achiziție</h4>
                    <p><strong>ID Bilet:</strong> #<?php echo $ticket['id']; ?></p>
                    <p><strong>Data achiziției:</strong> <?php echo date('d.m.Y', strtotime($ticket['data_achizitie'])); ?></p>
                    <p><strong>Preț:</strong> <?php echo $ticket['pret']; ?> lei</p>
                </div>
            </div>
        </div>
        
        <div class="ticket-footer">
            <p>Prezintă acest bilet la intrarea în locație. Organizatorul va scana codul pentru validare.</p>
            <p>Pentru întrebări: contact@biletup.ro | 0721 234 567</p>
            <p>&copy; <?php echo date('Y'); ?> BiletUP. Toate drepturile rezervate.</p>
        </div>
    </div>
    
    <script>
        // Auto-print pentru download
        window.onload = function() {
            // window.print();
        }
    </script>
</body>
</html>
<?php
// În practică, pentru a crea un PDF real, ai folosi o bibliotecă precum FPDF sau TCPDF
// În acest exemplu, oferim doar un HTML care poate fi printat
?>