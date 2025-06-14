<?php
require_once "session_config.php";
require_once "config/database.php";
require_once "includes/functions.php";

// Verifică dacă utilizatorul este autentificat
if(!isset($_SESSION['user_id'])) {
    $_SESSION['mesaj'] = "Trebuie să fii autentificat pentru a achiziționa bilete.";
    $_SESSION['tip_mesaj'] = "warning";
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

// Verifică dacă a fost selectată o metodă de plată
if(!isset($_POST['payment_method']) || empty($_POST['payment_method'])) {
    $_SESSION['mesaj'] = "Te rugăm să selectezi o metodă de plată.";
    $_SESSION['tip_mesaj'] = "warning";
    header("Location: purchase.php");
    exit;
}

$event_id = $conn->real_escape_string($_POST['event_id']);
$user_id = $_SESSION['user_id'];
$quantity = $_POST['quantity'] ?? [];
$payment_method = $conn->real_escape_string($_POST['payment_method']);

// Redirecționează plățile Stripe către procesatorul dedicat
if($payment_method === 'stripe') {
    $_SESSION['mesaj'] = "Pentru plata cu cardul, te rugăm să folosești formularul de mai sus.";
    $_SESSION['tip_mesaj'] = "info";
    header("Location: purchase.php");
    exit;
}

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

// Calculează totalul comenzii și verifică disponibilitatea
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

// Determinează statusul comenzii pe baza metodei de plată
$order_status = 'platit'; // Transfer bancar = confirmat direct (pentru test)
if($payment_method === 'paypal') {
    $order_status = 'in_asteptare'; // PayPal rămâne în așteptare
}

// Începe tranzacția
$conn->begin_transaction();

try {
    // Creează comanda
    $order_sql = "INSERT INTO orders (user_id, total, metoda_plata, status, data_creare) 
                  VALUES (?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($order_sql);
    $stmt->bind_param("idss", $user_id, $order_total, $payment_method, $order_status);
    $stmt->execute();
    
    $order_id = $conn->insert_id;
    
    if($payment_method === 'transfer_bancar') {
        // Transfer bancar: generează biletele direct (test mode)
        foreach($selected_tickets as $ticket) {
            $order_item_sql = "INSERT INTO order_items (order_id, ticket_type_id, cantitate, pret_unitar) 
                              VALUES (?, ?, ?, ?)";
            
            $stmt = $conn->prepare($order_item_sql);
            $stmt->bind_param("iiid", $order_id, $ticket['ticket_type_id'], $ticket['cantitate'], $ticket['pret']);
            $stmt->execute();
            
            // Actualizează cantitatea disponibilă
            $update_qty_sql = "UPDATE ticket_types 
                              SET cantitate_disponibila = cantitate_disponibila - ? 
                              WHERE id = ?";
            
            $stmt = $conn->prepare($update_qty_sql);
            $stmt->bind_param("ii", $ticket['cantitate'], $ticket['ticket_type_id']);
            $stmt->execute();
            
            // Verifică dacă biletele sunt epuizate
            $check_avail_sql = "SELECT cantitate_disponibila FROM ticket_types WHERE id = ?";
            $stmt = $conn->prepare($check_avail_sql);
            $stmt->bind_param("i", $ticket['ticket_type_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $avail = $result->fetch_assoc();
            
            if($avail['cantitate_disponibila'] <= 0) {
                $update_status_sql = "UPDATE ticket_types SET status = 'epuizat' WHERE id = ?";
                $stmt = $conn->prepare($update_status_sql);
                $stmt->bind_param("i", $ticket['ticket_type_id']);
                $stmt->execute();
            }
            
            // Generează biletele individuale
            for($i = 0; $i < $ticket['cantitate']; $i++) {
                $cod_unic = generateUniqueCode();
                
                $ticket_sql = "INSERT INTO tickets (cod_unic, ticket_type_id, event_id, user_id, order_id, pret, data_achizitie, status) 
                              VALUES (?, ?, ?, ?, ?, ?, NOW(), 'valid')";
                
                $stmt = $conn->prepare($ticket_sql);
                $stmt->bind_param("siiidi", $cod_unic, $ticket['ticket_type_id'], $event_id, $user_id, $order_id, $ticket['pret']);
                $stmt->execute();
            }
        }
        
        // Actualizează numărul de bilete disponibile pentru eveniment
        $update_event_sql = "UPDATE events 
                             SET bilete_disponibile = bilete_disponibile - ? 
                             WHERE id = ?";
                             
        $stmt = $conn->prepare($update_event_sql);
        $stmt->bind_param("ii", $total_tickets, $event_id);
        $stmt->execute();
        
        // Commit tranzacția
        $conn->commit();
        
        $_SESSION['mesaj'] = "Comanda ta a fost confirmată cu succes! Biletele sunt disponibile pentru descărcare.";
        $_SESSION['tip_mesaj'] = "success";
        
        // Redirecționează către pagina de confirmare
        header("Location: order-confirmation.php?id=$order_id");
        exit;
        
    } elseif($payment_method === 'paypal') {
        // PayPal: doar înregistrează comanda fără bilete
        foreach($selected_tickets as $ticket) {
            $order_item_sql = "INSERT INTO order_items (order_id, ticket_type_id, cantitate, pret_unitar) 
                              VALUES (?, ?, ?, ?)";
            
            $stmt = $conn->prepare($order_item_sql);
            $stmt->bind_param("iiid", $order_id, $ticket['ticket_type_id'], $ticket['cantitate'], $ticket['pret']);
            $stmt->execute();
        }
        
        // Commit tranzacția
        $conn->commit();
        
        $_SESSION['mesaj'] = "Comanda ta a fost înregistrată! Vei fi contactat în curând cu detaliile pentru plata PayPal.";
        $_SESSION['tip_mesaj'] = "info";
        
        // Redirecționează către pagina de confirmare
        header("Location: order-confirmation.php?id=$order_id");
        exit;
        
    } else {
        // Pentru alte metode (în cazul în care se adaugă în viitor)
        $conn->rollback();
        $_SESSION['mesaj'] = "Metodă de plată nepermisă.";
        $_SESSION['tip_mesaj'] = "danger";
        header("Location: event-details.php?id=$event_id");
        exit;
    }
    
} catch (Exception $e) {
    // Rollback în caz de eroare
    $conn->rollback();
    
    $_SESSION['mesaj'] = "A apărut o eroare la procesarea comenzii. Te rugăm să încerci din nou.";
    $_SESSION['tip_mesaj'] = "danger";
    
    header("Location: event-details.php?id=$event_id");
    exit;
}
?>