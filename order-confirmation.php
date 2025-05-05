<?php
require_once "session_config.php";
require_once "config/database.php";
require_once "includes/functions.php";

// Verifică dacă utilizatorul este autentificat
if(!isset($_SESSION['user_id'])) {
    $_SESSION['mesaj'] = "Trebuie să fii autentificat pentru a vedea această pagină.";
    $_SESSION['tip_mesaj'] = "warning";
    header("Location: login.php");
    exit;
}

// Verifică dacă a fost specificat ID-ul comenzii
if(!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mesaj'] = "ID-ul comenzii nu a fost specificat.";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: index.php");
    exit;
}

$order_id = $conn->real_escape_string($_GET['id']);
$user_id = $_SESSION['user_id'];

// Obține detaliile comenzii
$order_sql = "SELECT o.*, u.email 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.id = ? AND o.user_id = ?";

$stmt = $conn->prepare($order_sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows != 1) {
    $_SESSION['mesaj'] = "Comanda nu a fost găsită sau nu ai acces la ea.";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: index.php");
    exit;
}

$order = $result->fetch_assoc();

// Obține articolele comenzii
$items_sql = "SELECT oi.*, tt.nume as tip_bilet, e.titlu as eveniment_titlu, e.data_inceput, e.ora_inceput, e.locatie, e.oras
              FROM order_items oi
              JOIN ticket_types tt ON oi.ticket_type_id = tt.id
              JOIN events e ON tt.event_id = e.id
              WHERE oi.order_id = ?";

$stmt = $conn->prepare($items_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = [];

while($item = $items_result->fetch_assoc()) {
    $order_items[] = $item;
}

// Obține biletele
$tickets_sql = "SELECT t.*
                FROM tickets t
                WHERE t.order_id = ?";

$stmt = $conn->prepare($tickets_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$tickets_result = $stmt->get_result();
$tickets = [];

while($ticket = $tickets_result->fetch_assoc()) {
    $tickets[] = $ticket;
}

$page_title = "Confirmare comandă #" . $order_id;
require_once "includes/header.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">Comandă confirmată</h3>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
                        <h4 class="mt-3">Îți mulțumim pentru comandă!</h4>
                        <p class="lead">Comanda ta cu numărul <strong>#<?php echo $order_id; ?></strong> a fost procesată cu succes.</p>
                        <p>Un email de confirmare a fost trimis la adresa <strong><?php echo $order['email']; ?></strong>.</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Detalii comandă:</h5>
                            <p>Data: <?php echo date('d.m.Y H:i', strtotime($order['data_creare'])); ?></p>
                            <p>Total: <?php echo $order['total']; ?> lei</p>
                            <p>Metoda de plată: 
                                <?php 
                                switch($order['metoda_plata']) {
                                    case 'card':
                                        echo 'Card de credit/debit';
                                        break;
                                    case 'transfer_bancar':
                                        echo 'Transfer bancar';
                                        break;
                                    case 'paypal':
                                        echo 'PayPal';
                                        break;
                                    default:
                                        echo $order['metoda_plata'];
                                }
                                ?>
                            </p>
                            <p>Status: 
                                <span class="badge bg-<?php echo $order['status'] == 'platit' ? 'success' : ($order['status'] == 'anulat' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <?php if(count($order_items) > 0): ?>
                            <h5>Detalii eveniment:</h5>
                            <p>Eveniment: <?php echo $order_items[0]['eveniment_titlu']; ?></p>
                            <p>Data: <?php echo formatData($order_items[0]['data_inceput']); ?>, <?php echo date('H:i', strtotime($order_items[0]['ora_inceput'])); ?></p>
                            <p>Locație: <?php echo $order_items[0]['locatie']; ?>, <?php echo $order_items[0]['oras']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h5 class="mb-3">Bilete comandate:</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Tip bilet</th>
                                    <th class="text-center">Preț</th>
                                    <th class="text-center">Cantitate</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($order_items as $item): ?>
                                <tr>
                                    <td><?php echo $item['tip_bilet']; ?></td>
                                    <td class="text-center"><?php echo $item['pret_unitar']; ?> lei</td>
                                    <td class="text-center"><?php echo $item['cantitate']; ?></td>
                                    <td class="text-end"><?php echo $item['pret_unitar'] * $item['cantitate']; ?> lei</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th class="text-end"><?php echo $order['total']; ?> lei</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <?php if(count($tickets) > 0): ?>
                    <h5 class="mt-4 mb-3">Biletele tale:</h5>
                    <div class="row">
                        <?php foreach($tickets as $ticket): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Cod bilet: <?php echo $ticket['cod_unic']; ?></h6>
                                    <p class="card-text">Status: 
                                        <span class="badge bg-<?php echo $ticket['status'] == 'valid' ? 'success' : ($ticket['status'] == 'anulat' ? 'danger' : 'secondary'); ?>">
                                            <?php echo ucfirst($ticket['status']); ?>
                                        </span>
                                    </p>
                                    <a href="download-ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-download me-1"></i> Descarcă bilet
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 text-center">
                        <p>Ai întrebări despre comandă? <a href="#">Contactează-ne</a></p>
                        <a href="index.php" class="btn btn-primary">Înapoi la pagina principală</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once "includes/footer.php";
?>