<?php

require_once "config/database.php";
require_once "includes/functions.php";

// Verifică dacă ID-ul evenimentului este furnizat
if(!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mesaj'] = "ID-ul evenimentului nu a fost specificat.";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: events.php");
    exit;
}

$event_id = $conn->real_escape_string($_GET['id']);

// Obține detaliile evenimentului
$event = getEventById($event_id);

if(!$event) {
    $_SESSION['mesaj'] = "Evenimentul nu a fost găsit sau nu mai este disponibil.";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: events.php");
    exit;
}

// Setează titlul paginii
$page_title = $event['titlu'];

// Obține tipurile de bilete disponibile
$ticket_types = getTicketTypesByEventId($event_id);

// Acum includem header-ul după ce am făcut toate verificările
require_once "includes/header.php";
?>

<!-- Event Header -->
<section class="event-header">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Acasă</a></li>
                        <li class="breadcrumb-item"><a href="events.php">Evenimente</a></li>
                        <li class="breadcrumb-item"><a href="events.php?categorie=<?php echo $event['categorie_id']; ?>"><?php echo $event['categorie_nume']; ?></a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo $event['titlu']; ?></li>
                    </ol>
                </nav>
                <h1><?php echo $event['titlu']; ?></h1>
                <p class="text-muted">
                    <i class="bi bi-calendar3 me-2"></i><?php echo formatData($event['data_inceput']); ?>
                    <?php if($event['data_inceput'] != $event['data_sfarsit']): ?>
                    - <?php echo formatData($event['data_sfarsit']); ?>
                    <?php endif; ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-clock me-2"></i><?php echo date('H:i', strtotime($event['ora_inceput'])); ?> - <?php echo date('H:i', strtotime($event['ora_sfarsit'])); ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="badge bg-primary"><?php echo $event['categorie_nume']; ?></span>
                <?php if($event['status'] == 'activ'): ?>
                    <span class="badge bg-success ms-2">Bilete disponibile</span>
                <?php elseif($event['status'] == 'anulat'): ?>
                    <span class="badge bg-danger ms-2">Anulat</span>
                <?php else: ?>
                    <span class="badge bg-secondary ms-2">Încheiat</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Event Details -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Left Column - Event Details -->
            <div class="col-lg-8 mb-4 mb-lg-0">
                <!-- Event Image -->
                <img src="<?php echo $event['imagine'] ? $event['imagine'] : 'images/placeholder.jpg'; ?>" alt="<?php echo $event['titlu']; ?>" class="event-image shadow">
                
                <!-- Event Description -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Descriere eveniment</h4>
                    </div>
                    <div class="card-body">
                        <?php echo nl2br($event['descriere']); ?>
                    </div>
                </div>

                <!-- Event Location -->
                
            </div>
            
            <!-- Right Column - Ticket Info -->
            <div class="col-lg-4">
                <!-- Event Info Box -->
                <div class="event-info shadow mb-4">
                    <h4 class="border-bottom pb-3">Informații eveniment</h4>
                    
                    <div class="event-info-item d-flex">
                        <div class="event-info-icon">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <div>
                            <strong>Data</strong>
                            <p class="mb-0"><?php echo formatData($event['data_inceput']); ?>
                            <?php if($event['data_inceput'] != $event['data_sfarsit']): ?>
                            - <?php echo formatData($event['data_sfarsit']); ?>
                            <?php endif; ?></p>
                        </div>
                    </div>
                    
                    <div class="event-info-item d-flex">
                        <div class="event-info-icon">
                            <i class="bi bi-clock"></i>
                        </div>
                        <div>
                            <strong>Ora</strong>
                            <p class="mb-0"><?php echo date('H:i', strtotime($event['ora_inceput'])); ?> - <?php echo date('H:i', strtotime($event['ora_sfarsit'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="event-info-item d-flex">
                        <div class="event-info-icon">
                            <i class="bi bi-geo-alt"></i>
                        </div>
                        <div>
                            <strong>Locație</strong>
                            <p class="mb-0"><?php echo $event['locatie']; ?></p>
                            <p class="mb-0"><?php echo $event['oras']; ?></p>
                        </div>
                    </div>
                    
                    <div class="event-info-item d-flex">
                        <div class="event-info-icon">
                            <i class="bi bi-ticket-perforated"></i>
                        </div>
                        <div>
                            <strong>Organizator</strong>
                            <p class="mb-0">BiletUP</p>
                        </div>
                    </div>
                </div>
                
                <!-- Ticket Types Box -->
                <div class="event-info shadow">
                    <h4 class="border-bottom pb-3">Bilete</h4>
                    
                    <?php if(count($ticket_types) > 0): ?>
                        <form action="purchase.php" method="post">
                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                            
                            <?php foreach($ticket_types as $ticket): ?>
                                <div class="event-info-item">
                                    <div class="d-flex justify-content-between mb-2">
                                        <h5 class="mb-0"><?php echo $ticket['nume']; ?></h5>
                                        <span class="fw-bold"><?php echo $ticket['pret']; ?> lei</span>
                                    </div>
                                    
                                    <p class="text-muted small mb-2"><?php echo $ticket['descriere']; ?></p>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-<?php echo $ticket['cantitate_disponibila'] > 0 ? 'success' : 'danger'; ?>">
                                                <?php echo $ticket['cantitate_disponibila'] > 0 ? 'Disponibil' : 'Epuizat'; ?>
                                            </span>
                                            <small class="text-muted ms-2">
                                                <?php if($ticket['cantitate_disponibila'] > 0): ?>
                                                    <?php echo $ticket['cantitate_disponibila']; ?> bilete rămase
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        
                                        <select name="quantity[<?php echo $ticket['id']; ?>]" class="form-select form-select-sm" style="width: 80px;" <?php echo $ticket['cantitate_disponibila'] > 0 ? '' : 'disabled'; ?>>
                                            <option value="0">0</option>
                                            <?php for($i = 1; $i <= min(10, $ticket['cantitate_disponibila']); $i++): ?>
                                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <button type="submit" class="btn btn-primary w-100 mt-3" <?php echo ($event['status'] == 'activ') ? '' : 'disabled'; ?>>
                                <?php echo ($event['status'] == 'activ') ? 'Cumpără bilete' : 'Bilete indisponibile'; ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p>Nu există bilete disponibile pentru acest eveniment.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Share Buttons -->
                <div class="mt-4">
                    <p class="mb-2"><strong>Distribuie:</strong></p>
                    <div class="d-flex">
                        <a href="#" class="btn btn-sm btn-outline-primary me-2"><i class="bi bi-facebook"></i> Facebook</a>
                        <a href="#" class="btn btn-sm btn-outline-info me-2"><i class="bi bi-twitter"></i> Twitter</a>
                        <a href="#" class="btn btn-sm btn-outline-secondary"><i class="bi bi-envelope"></i> Email</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once "includes/footer.php";
?>