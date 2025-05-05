<?php
require_once "session_config.php"; // Include configurarea sesiunii
require_once "config/database.php";
require_once "includes/functions.php";

// Verifică dacă utilizatorul este autentificat
if(!isset($_SESSION['user_id'])) {
    $_SESSION['mesaj'] = "Trebuie să fii autentificat pentru a achiziționa bilete.";
    $_SESSION['tip_mesaj'] = "warning";
    $_SESSION['redirect_after_login'] = "event-details.php?id=" . $_POST['event_id'];
    header("Location: login.php");
    exit;
}

// Verifică dacă s-a trimis formularul
if($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit;
}

// Verifică dacă a fost selectat un eveniment
if(!isset($_POST['event_id']) || empty($_POST['event_id'])) {
    $_SESSION['mesaj'] = "Evenimentul nu a fost specificat.";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: events.php");
    exit;
}

$event_id = $conn->real_escape_string($_POST['event_id']);
$user_id = $_SESSION['user_id'];
$quantity = $_POST['quantity'] ?? [];

// Verifică dacă a fost selectat cel puțin un bilet
$total_tickets = array_sum($quantity);
if($total_tickets <= 0) {
    $_SESSION['mesaj'] = "Te rugăm să selectezi cel puțin un bilet.";
    $_SESSION['tip_mesaj'] = "warning";
    header("Location: event-details.php?id=$event_id");
    exit;
}

// Obține detaliile evenimentului
$event = getEventById($event_id);
if(!$event) {
    $_SESSION['mesaj'] = "Evenimentul nu a fost găsit sau nu mai este disponibil.";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: events.php");
    exit;
}

// Obține tipurile de bilete disponibile
$ticket_types = getTicketTypesByEventId($event_id);
$selected_tickets = [];
$order_total = 0;

// Calculează totalul comenzii
foreach($ticket_types as $ticket) {
    $ticket_id = $ticket['id'];
    if(isset($quantity[$ticket_id]) && $quantity[$ticket_id] > 0) {
        $qty = intval($quantity[$ticket_id]);
        
        // Verifică disponibilitatea
        if($qty > $ticket['cantitate_disponibila']) {
            $_SESSION['mesaj'] = "Ne pare rău, dar nu mai sunt disponibile suficiente bilete de tip " . $ticket['nume'] . ".";
            $_SESSION['tip_mesaj'] = "warning";
            header("Location: event-details.php?id=$event_id");
            exit;
        }
        
        $selected_tickets[$ticket_id] = [
            'ticket_type_id' => $ticket_id,
            'nume' => $ticket['nume'],
            'pret' => $ticket['pret'],
            'cantitate' => $qty,
            'subtotal' => $ticket['pret'] * $qty
        ];
        
        $order_total += $selected_tickets[$ticket_id]['subtotal'];
    }
}

$page_title = "Confirmare comandă";
require_once "includes/header.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Confirmă comanda</h3>
                </div>
                <div class="card-body">
                    <h4 class="mb-3"><?php echo $event['titlu']; ?></h4>
                    <p class="text-muted">
                        <i class="bi bi-calendar3 me-2"></i><?php echo formatData($event['data_inceput']); ?>
                        <span class="mx-2">|</span>
                        <i class="bi bi-clock me-2"></i><?php echo date('H:i', strtotime($event['ora_inceput'])); ?>
                        <span class="mx-2">|</span>
                        <i class="bi bi-geo-alt me-2"></i><?php echo $event['locatie']; ?>, <?php echo $event['oras']; ?>
                    </p>
                    
                    <hr>
                    
                    <h5 class="mb-3">Bilete selectate:</h5>
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
                                <?php foreach($selected_tickets as $ticket): ?>
                                <tr>
                                    <td><?php echo $ticket['nume']; ?></td>
                                    <td class="text-center"><?php echo $ticket['pret']; ?> lei</td>
                                    <td class="text-center"><?php echo $ticket['cantitate']; ?></td>
                                    <td class="text-end"><?php echo $ticket['subtotal']; ?> lei</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Total:</th>
                                    <th class="text-end"><?php echo $order_total; ?> lei</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Metodă de plată</h5>
                </div>
                <div class="card-body">
                    <form action="process-order.php" method="post">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <?php foreach($quantity as $ticket_id => $qty): ?>
                            <?php if($qty > 0): ?>
                                <input type="hidden" name="quantity[<?php echo $ticket_id; ?>]" value="<?php echo $qty; ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="card" value="card" checked>
                                <label class="form-check-label" for="card">
                                    Card de credit/debit
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="transfer" value="transfer_bancar">
                                <label class="form-check-label" for="transfer">
                                    Transfer bancar
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">Sunt de acord cu <a href="#">termenii și condițiile</a> și <a href="#">politica de retur</a></label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="event-details.php?id=<?php echo $event_id; ?>" class="btn btn-outline-secondary">Înapoi la eveniment</a>
                            <button type="submit" class="btn btn-primary">Finalizează comanda</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once "includes/footer.php";
?>