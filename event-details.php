<?php

require_once "config/database.php";
require_once "includes/functions.php";

// Verifică dacă ID-ul evenimentului este furnizat
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['mesaj'] = "ID-ul evenimentului nu a fost specificat.";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: events.php");
    exit;
}

$event_id = $conn->real_escape_string($_GET['id']);

// Obține detaliile evenimentului
$event = getEventById($event_id);

if (!$event) {
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
                    <?php if ($event['data_inceput'] != $event['data_sfarsit']): ?>
                        - <?php echo formatData($event['data_sfarsit']); ?>
                    <?php endif; ?>
                    <span class="mx-2">|</span>
                    <i class="bi bi-clock me-2"></i><?php echo date('H:i', strtotime($event['ora_inceput'])); ?> - <?php echo date('H:i', strtotime($event['ora_sfarsit'])); ?>
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <span class="badge bg-primary"><?php echo $event['categorie_nume']; ?></span>
                <?php if ($event['status'] == 'activ'): ?>
                    <span class="badge bg-success ms-2">Bilete disponibile</span>
                <?php elseif ($event['status'] == 'anulat'): ?>
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

                <!-- Event Location with Google Maps -->
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Locația evenimentului</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5><?php echo $event['locatie']; ?></h5>
                                <p class="text-muted">
                                    <i class="bi bi-geo-alt me-2"></i><?php echo $event['oras']; ?>
                                </p>
                                <div class="mt-3">
                                    <button class="btn btn-outline-primary btn-sm" onclick="openDirections()">
                                        <i class="bi bi-compass me-1"></i> Cum ajung aici
                                    </button>
                                    <button class="btn btn-outline-secondary btn-sm ms-2" onclick="shareLocation()">
                                        <i class="bi bi-share me-1"></i> Partajează locația
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center mb-3">
                                    <i class="bi bi-info-circle text-primary me-2"></i>
                                    <small class="text-muted">Click pe hartă pentru a vedea în Google Maps</small>
                                </div>
                            </div>
                        </div>

                        <!-- Google Maps Container -->
                        <div class="mt-3">
                            <div id="map" style="height: 300px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);"></div>
                        </div>

                        <!-- Fallback pentru când harta nu se încarcă -->
                        <div id="map-fallback" style="display: none;">
                            <div class="alert alert-info">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                Harta nu poate fi încărcată momentan.
                                <a href="https://maps.google.com/?q=<?php echo urlencode($event['locatie'] . ', ' . $event['oras']); ?>" target="_blank" class="alert-link">
                                    Vezi locația pe Google Maps
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
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
                                <?php if ($event['data_inceput'] != $event['data_sfarsit']): ?>
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

                    <?php if (count($ticket_types) > 0): ?>
                        <form action="purchase.php" method="post">
                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

                            <?php foreach ($ticket_types as $ticket): ?>
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
                                                <?php if ($ticket['cantitate_disponibila'] > 0): ?>
                                                    <?php echo $ticket['cantitate_disponibila']; ?> bilete rămase
                                                <?php endif; ?>
                                            </small>
                                        </div>

                                        <select name="quantity[<?php echo $ticket['id']; ?>]" class="form-select form-select-sm" style="width: 80px;" <?php echo $ticket['cantitate_disponibila'] > 0 ? '' : 'disabled'; ?>>
                                            <option value="0">0</option>
                                            <?php for ($i = 1; $i <= min(10, $ticket['cantitate_disponibila']); $i++): ?>
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

<!-- Google Maps API Script -->
<script>
    // Variabile globale pentru hartă
    let map;
    let marker;
    let geocoder;

    // Datele locației din PHP
    const eventLocation = {
        name: "<?php echo addslashes($event['locatie']); ?>",
        city: "<?php echo addslashes($event['oras']); ?>",
        fullAddress: "<?php echo addslashes($event['locatie'] . ', ' . $event['oras']); ?>"
    };

    // Inițializează harta Google Maps
    function initMap() {
        try {
            // Coordonate implicite (centrul României)
            const defaultLocation = {
                lat: 45.9432,
                lng: 24.9668
            };

            // Creează harta
            map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: defaultLocation,
                mapTypeControl: false,
                streetViewControl: true,
                fullscreenControl: true,
                zoomControl: true,
                styles: [{
                    featureType: "poi",
                    elementType: "labels",
                    stylers: [{
                        visibility: "on"
                    }]
                }]
            });

            // Inițializează geocoder-ul
            geocoder = new google.maps.Geocoder();

            // Caută locația evenimentului
            geocodeAddress();

            // Adaugă click handler pentru hartă
            map.addListener('click', function() {
                openInGoogleMaps();
            });

        } catch (error) {
            console.error('Eroare la inițializarea hărții:', error);
            showMapFallback();
        }
    }

    // Convertește adresa în coordonate și plasează marker-ul
    function geocodeAddress() {
        geocoder.geocode({
            address: eventLocation.fullAddress + ', România'
        }, function(results, status) {
            if (status === 'OK' && results[0]) {
                const location = results[0].geometry.location;

                // Centrează harta pe locație
                map.setCenter(location);
                map.setZoom(16);

                // Creează marker-ul
                marker = new google.maps.Marker({
                    position: location,
                    map: map,
                    title: eventLocation.name,
                    animation: google.maps.Animation.DROP,
                    icon: {
                        url: 'data:image/svg+xml;base64,' + btoa(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="#dc3545">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                    `),
                        scaledSize: new google.maps.Size(40, 40),
                        anchor: new google.maps.Point(20, 40)
                    }
                });

                // Adaugă InfoWindow
                const infoWindow = new google.maps.InfoWindow({
                    content: `
                    <div style="padding: 10px; max-width: 250px;">
                        <h6 style="margin: 0 0 8px 0; color: #333;">${eventLocation.name}</h6>
                        <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">${eventLocation.city}</p>
                        <button onclick="openInGoogleMaps()" class="btn btn-primary btn-sm" style="background: #4285f4; border: none; padding: 6px 12px; border-radius: 4px; color: white; cursor: pointer;">
                            Vezi pe Google Maps
                        </button>
                    </div>
                `
                });

                // Deschide InfoWindow la click pe marker
                marker.addListener('click', function() {
                    infoWindow.open(map, marker);
                });

                // Deschide InfoWindow automat pentru 3 secunde
                infoWindow.open(map, marker);
                setTimeout(() => {
                    infoWindow.close();
                }, 3000);

            } else {
                console.warn('Geocoding a eșuat:', status);
                showMapFallback();
            }
        });
    }

    // Deschide Google Maps în tab nou
    function openInGoogleMaps() {
        const url = `https://maps.google.com/?q=${encodeURIComponent(eventLocation.fullAddress)}`;
        window.open(url, '_blank');
    }

    // Deschide direcțiile în Google Maps
    function openDirections() {
        const url = `https://maps.google.com/maps/dir/?api=1&destination=${encodeURIComponent(eventLocation.fullAddress)}`;
        window.open(url, '_blank');
    }

    // Partajează locația
    function shareLocation() {
        if (navigator.share) {
            navigator.share({
                title: `Locația pentru ${eventLocation.name}`,
                text: `Vezi unde se află ${eventLocation.name}`,
                url: `https://maps.google.com/?q=${encodeURIComponent(eventLocation.fullAddress)}`
            });
        } else {
            // Fallback pentru browsere care nu suportă Web Share API
            const url = `https://maps.google.com/?q=${encodeURIComponent(eventLocation.fullAddress)}`;
            navigator.clipboard.writeText(url).then(() => {
                alert('Link-ul către locație a fost copiat în clipboard!');
            }).catch(() => {
                // Fallback final
                prompt('Copiază acest link pentru a partaja locația:', url);
            });
        }
    }

    // Afișează fallback-ul dacă harta nu se poate încărca
    function showMapFallback() {
        document.getElementById('map').style.display = 'none';
        document.getElementById('map-fallback').style.display = 'block';
    }

    // Gestionează erorile de încărcare a Google Maps
    window.gm_authFailure = function() {
        console.error('Google Maps API: Eroare de autentificare');
        showMapFallback();
    };
</script>

<!-- Încarcă Google Maps API -->
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBsmjMcmHqOJozoCWkc_giREAUpeMb1yOQ&callback=initMap&libraries=geometry">
</script>

<?php
require_once "includes/footer.php";
?>