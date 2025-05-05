<?php
require_once "session_config.php";
require_once "config/database.php";
require_once "includes/functions.php";

// Verifică dacă utilizatorul este autentificat
if(!isset($_SESSION['user_id'])) {
    $_SESSION['mesaj'] = "Trebuie să fii autentificat pentru a vedea biletele tale.";
    $_SESSION['tip_mesaj'] = "warning";
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Obține toate biletele utilizatorului
$tickets_sql = "SELECT t.*, tt.nume as tip_bilet, e.titlu as eveniment_titlu, e.data_inceput, e.ora_inceput, 
                e.locatie, e.oras, e.imagine, o.data_creare as data_comanda
                FROM tickets t
                JOIN ticket_types tt ON t.ticket_type_id = tt.id
                JOIN events e ON t.event_id = e.id
                JOIN orders o ON t.order_id = o.id
                WHERE t.user_id = ?
                ORDER BY e.data_inceput DESC, t.data_achizitie DESC";

$stmt = $conn->prepare($tickets_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$tickets = [];

while($ticket = $result->fetch_assoc()) {
    $tickets[] = $ticket;
}

// Grupează biletele pe evenimente
$grouped_tickets = [];
foreach($tickets as $ticket) {
    $event_id = $ticket['event_id'];
    if(!isset($grouped_tickets[$event_id])) {
        $grouped_tickets[$event_id] = [
            'event_id' => $event_id,
            'eveniment_titlu' => $ticket['eveniment_titlu'],
            'data_inceput' => $ticket['data_inceput'],
            'ora_inceput' => $ticket['ora_inceput'],
            'locatie' => $ticket['locatie'],
            'oras' => $ticket['oras'],
            'imagine' => $ticket['imagine'],
            'tickets' => []
        ];
    }
    $grouped_tickets[$event_id]['tickets'][] = $ticket;
}

$page_title = "Biletele mele";
require_once "includes/header.php";
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">Biletele mele</h1>
            
            <?php if(count($tickets) > 0): ?>
                
                <!-- Filtrare și căutare -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <select class="form-select" id="filterStatus">
                                    <option value="all">Toate biletele</option>
                                    <option value="valid">Bilete valide</option>
                                    <option value="utilizat">Bilete utilizate</option>
                                    <option value="anulat">Bilete anulate</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchTickets" placeholder="Caută după eveniment sau cod bilet...">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Listă bilete grupate pe evenimente -->
                <?php foreach($grouped_tickets as $event): ?>
                <div class="card shadow mb-4">
                    <div class="card-header bg-white py-3">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-0"><?php echo $event['eveniment_titlu']; ?></h5>
                                <p class="text-muted mb-0">
                                    <i class="bi bi-calendar3 me-2"></i><?php echo formatData($event['data_inceput']); ?>
                                    <i class="bi bi-clock ms-3 me-2"></i><?php echo date('H:i', strtotime($event['ora_inceput'])); ?>
                                    <i class="bi bi-geo-alt ms-3 me-2"></i><?php echo $event['locatie']; ?>, <?php echo $event['oras']; ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                                <a href="event-details.php?id=<?php echo $event['event_id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-info-circle me-1"></i> Detalii eveniment
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Cod bilet</th>
                                        <th>Tip bilet</th>
                                        <th>Data achiziție</th>
                                        <th>Preț</th>
                                        <th>Status</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($event['tickets'] as $ticket): ?>
                                    <tr>
                                        <td><code><?php echo $ticket['cod_unic']; ?></code></td>
                                        <td><?php echo $ticket['tip_bilet']; ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($ticket['data_achizitie'])); ?></td>
                                        <td><?php echo $ticket['pret']; ?> lei</td>
                                        <td>
                                            <span class="badge bg-<?php echo $ticket['status'] == 'valid' ? 'success' : ($ticket['status'] == 'anulat' ? 'danger' : 'secondary'); ?>">
                                                <?php echo ucfirst($ticket['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="download-ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-ticket-perforated text-muted" style="font-size: 4rem;"></i>
                        <h3 class="mt-3">Nu ai niciun bilet</h3>
                        <p class="text-muted">Nu ai achiziționat încă bilete pentru niciun eveniment.</p>
                        <a href="events.php" class="btn btn-primary mt-2">Vezi evenimente disponibile</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtrare după status
    const filterStatus = document.getElementById('filterStatus');
    if(filterStatus) {
        filterStatus.addEventListener('change', function() {
            const status = this.value;
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const statusCell = row.querySelector('td:nth-child(5) .badge');
                const statusText = statusCell.textContent.trim().toLowerCase();
                
                if(status === 'all' || statusText === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
    
    // Căutare
    const searchInput = document.getElementById('searchTickets');
    if(searchInput) {
        searchInput.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const cards = document.querySelectorAll('.card.shadow');
            
            cards.forEach(card => {
                const title = card.querySelector('.card-header h5').textContent.toLowerCase();
                const tables = card.querySelectorAll('tbody tr');
                let found = false;
                
                if(title.includes(searchValue)) {
                    found = true;
                } else {
                    tables.forEach(row => {
                        const code = row.querySelector('td:first-child code').textContent.toLowerCase();
                        const type = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                        
                        if(code.includes(searchValue) || type.includes(searchValue)) {
                            found = true;
                        }
                    });
                }
                
                if(found) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php
require_once "includes/footer.php";
?>