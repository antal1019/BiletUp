<?php
require_once "session_config.php";
require_once "config/database.php";
require_once "includes/functions.php";
require_once "config/stripe.php";

// Verifică dacă utilizatorul este autentificat
if (!isset($_SESSION['user_id'])) {
    $_SESSION['mesaj'] = "Trebuie să fii autentificat pentru a achiziționa bilete.";
    $_SESSION['tip_mesaj'] = "warning";
    $_SESSION['redirect_after_login'] = "event-details.php?id=" . $_POST['event_id'];
    header("Location: login.php");
    exit;
}

// Verifică dacă s-a trimis formularul
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit;
}

// Verifică dacă a fost selectat un eveniment
if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
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
if ($total_tickets <= 0) {
    $_SESSION['mesaj'] = "Te rugăm să selectezi cel puțin un bilet.";
    $_SESSION['tip_mesaj'] = "warning";
    header("Location: event-details.php?id=$event_id");
    exit;
}

// Obține detaliile evenimentului
$event = getEventById($event_id);
if (!$event) {
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
foreach ($ticket_types as $ticket) {
    $ticket_id = $ticket['id'];
    if (isset($quantity[$ticket_id]) && $quantity[$ticket_id] > 0) {
        $qty = intval($quantity[$ticket_id]);

        // Verifică disponibilitatea
        if ($qty > $ticket['cantitate_disponibila']) {
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

$page_title = "Plată - " . $event['titlu'];
require_once "includes/header.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Sumar comandă -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="bi bi-ticket-perforated me-2"></i>
                        Comanda ta
                    </h4>
                </div>
                <div class="card-body">
                    <h5 class="mb-3"><?php echo $event['titlu']; ?></h5>
                    <p class="text-muted">
                        <i class="bi bi-calendar3 me-2"></i><?php echo formatData($event['data_inceput']); ?>
                        <span class="mx-2">|</span>
                        <i class="bi bi-clock me-2"></i><?php echo date('H:i', strtotime($event['ora_inceput'])); ?>
                        <span class="mx-2">|</span>
                        <i class="bi bi-geo-alt me-2"></i><?php echo $event['locatie']; ?>, <?php echo $event['oras']; ?>
                    </p>

                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tip bilet</th>
                                    <th class="text-center">Preț</th>
                                    <th class="text-center">Cantitate</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($selected_tickets as $ticket): ?>
                                    <tr>
                                        <td><?php echo $ticket['nume']; ?></td>
                                        <td class="text-center"><?php echo $ticket['pret']; ?> lei</td>
                                        <td class="text-center"><?php echo $ticket['cantitate']; ?></td>
                                        <td class="text-end"><?php echo $ticket['subtotal']; ?> lei</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th colspan="3" class="text-end">Total de plată:</th>
                                    <th class="text-end"><?php echo $order_total; ?> lei</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Formular de plată Stripe -->
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-credit-card me-2"></i>
                        Plată securizată
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-shield-check me-2"></i>
                        Plata este procesată securizat prin <strong>Stripe</strong>. Nu stocăm datele cardului tău.
                    </div>

                    <form id="payment-form">
                        <!-- Datele comenzii (hidden) -->
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        <?php foreach ($quantity as $ticket_id => $qty): ?>
                            <?php if ($qty > 0): ?>
                                <input type="hidden" name="quantity[<?php echo $ticket_id; ?>]" value="<?php echo $qty; ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <input type="hidden" name="total_amount" value="<?php echo $order_total; ?>">

                        <!-- Selecție metodă de plată -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Alege metoda de plată:</label>

                            <!-- Stripe Card Payment (Recomandat) -->
                            <div class="form-check mb-3 p-3 border rounded" style="background-color: #f8f9ff;">
                                <input class="form-check-input" type="radio" name="payment_method" id="stripe_card" value="stripe" checked>
                                <label class="form-check-label w-100" for="stripe_card">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>💳 Card de credit/debit</strong>
                                            <span class="badge bg-success ms-2">Recomandat</span>
                                            <br>
                                            <small class="text-muted">Plată securizată instant prin Stripe</small>
                                        </div>
                                        <div class="text-end">
                                            <small>💳 💳 💳</small>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Transfer Bancar -->
                            <div class="form-check mb-3 p-3 border rounded">
                                <input class="form-check-input" type="radio" name="payment_method" id="transfer_bancar" value="transfer_bancar">
                                <label class="form-check-label w-100" for="transfer_bancar">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>🏦 Transfer bancar</strong>
                                            <br>
                                            <small class="text-muted">Procesare în 1-2 zile lucrătoare</small>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-success">Fără taxe</small>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- PayPal -->
                            <div class="form-check mb-3 p-3 border rounded">
                                <input class="form-check-input" type="radio" name="payment_method" id="paypal" value="paypal">
                                <label class="form-check-label w-100" for="paypal">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>💰 PayPal</strong>
                                            <br>
                                            <small class="text-muted">Plată prin contul PayPal</small>
                                        </div>
                                        <div class="text-end">
                                            <small>🔒 Securizat</small>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Element Stripe pentru card (se afișează doar când e selectat Stripe) -->
                        <div id="stripe-card-section" class="mb-3">
                            <label class="form-label">Detalii card de plată</label>
                            <div id="card-element" class="form-control" style="height: 50px; padding: 12px;">
                                <!-- Stripe Elements va crea input-ul aici -->
                            </div>
                            <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                            <small class="text-muted">
                                <i class="bi bi-shield-check me-1"></i>
                                Cardul tău este procesat securizat. Nu stocăm datele cardului.
                            </small>
                        </div>

                        <!-- Instrucțiuni pentru transfer bancar -->
                        <div id="transfer-section" class="mb-3" style="display: none;">
                            <div class="alert alert-success">
                                <h6><i class="bi bi-check-circle me-2"></i>Transfer bancar - Mod Test</h6>
                                <p class="mb-1">✅ Pentru testare, transferul bancar va fi confirmat automat</p>
                                <p class="mb-1">✅ Biletele vor fi generate instant după confirmare</p>
                                <p class="mb-0">✅ Nu este nevoie de transfer real</p>
                                <hr>
                                <small><strong>Notă:</strong> Aceasta este o simulare pentru testare. În mod normal ai face transferul bancar real.</small>
                            </div>
                        </div>

                        <!-- Instrucțiuni pentru PayPal -->
                        <div id="paypal-section" class="mb-3" style="display: none;">
                            <div class="alert alert-warning">
                                <h6><i class="bi bi-paypal me-2"></i>Plată PayPal:</h6>
                                <p class="mb-1">Vei fi redirecționat către PayPal pentru a finaliza plata.</p>
                                <p class="mb-0"><strong>Email PayPal:</strong> payments@biletup.ro</p>
                                <hr>
                                <small>Biletele vor fi trimise instant după confirmarea plății PayPal.</small>
                            </div>
                        </div>

                        <!-- Opțiuni suplimentare -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                Sunt de acord cu <a href="#" target="_blank">termenii și condițiile</a> și <a href="#" target="_blank">politica de retur</a>
                            </label>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="newsletter">
                            <label class="form-check-label" for="newsletter">
                                Vreau să primesc newsletter cu evenimente noi
                            </label>
                        </div>

                        <!-- Butoane -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="event-details.php?id=<?php echo $event_id; ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Înapoi
                            </a>

                            <button type="submit" id="submit-button" class="btn btn-success btn-lg">
                                <span id="button-text">
                                    <i class="bi bi-credit-card me-2"></i>
                                    <span id="button-amount">Plătește <?php echo $order_total; ?> lei</span>
                                </span>
                                <span id="spinner" class="spinner-border spinner-border-sm ms-2" style="display: none;"></span>
                            </button>
                        </div>

                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="bi bi-lock me-1"></i>
                                Plata ta este protejată de criptare SSL și procesată securizat de Stripe
                            </small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Cards acceptate -->
            <div class="text-center mt-3">
                <small class="text-muted">Acceptăm:</small><br>
                <div class="mt-2">
                    <span class="badge bg-light text-dark me-1">💳 Visa</span>
                    <span class="badge bg-light text-dark me-1">💳 Mastercard</span>
                    <span class="badge bg-light text-dark me-1">💳 American Express</span>
                    <span class="badge bg-light text-dark">💳 Maestro</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stripe.js -->
<script src="https://js.stripe.com/v3/"></script>

<script>
    // Gestionează schimbarea metodei de plată
    document.querySelectorAll('input[name="payment_method"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const stripeSection = document.getElementById('stripe-card-section');
            const transferSection = document.getElementById('transfer-section');
            const paypalSection = document.getElementById('paypal-section');
            const submitButton = document.getElementById('submit-button');
            const buttonText = document.getElementById('button-text');
            const buttonAmount = document.getElementById('button-amount');

            // Ascunde toate secțiunile
            stripeSection.style.display = 'none';
            transferSection.style.display = 'none';
            paypalSection.style.display = 'none';

            // Afișează secțiunea corespunzătoare
            if (this.value === 'stripe') {
                stripeSection.style.display = 'block';
                submitButton.className = 'btn btn-success btn-lg';
                buttonText.innerHTML = '<i class="bi bi-credit-card me-2"></i><span id="button-amount">Plătește <?php echo $order_total; ?> lei</span>';
            } else if (this.value === 'transfer_bancar') {
                transferSection.style.display = 'block';
                submitButton.className = 'btn btn-success btn-lg';
                buttonText.innerHTML = '<i class="bi bi-check-circle me-2"></i>Confirmă comanda (Test)';
            } else if (this.value === 'paypal') {
                paypalSection.style.display = 'block';
                submitButton.className = 'btn btn-warning btn-lg text-dark';
                buttonText.innerHTML = '<i class="bi bi-paypal me-2"></i>Plătește cu PayPal';
            }
        });
    });

    // Inițializează Stripe doar dacă e selectat
    const stripe = Stripe('<?php echo getStripePublishableKey(); ?>');
    const elements = stripe.elements();

    // Stilizare pentru elementul card
    const cardStyle = {
        style: {
            base: {
                color: '#32325d',
                fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        }
    };

    // Creează elementul card
    const cardElement = elements.create('card', cardStyle);
    cardElement.mount('#card-element');

    // Gestionează erorile în timp real
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Gestionează submit-ul formularului
    const form = document.getElementById('payment-form');
    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        const selectedPayment = document.querySelector('input[name="payment_method"]:checked').value;
        const submitButton = document.getElementById('submit-button');
        const buttonText = document.getElementById('button-text');
        const spinner = document.getElementById('spinner');

        // Dezactivează butonul și arată loading
        submitButton.disabled = true;
        const originalButtonContent = buttonText.innerHTML;
        buttonText.style.display = 'none';
        spinner.style.display = 'inline-block';

        if (selectedPayment === 'stripe') {
            // Procesează plata Stripe
            const {
                token,
                error
            } = await stripe.createToken(cardElement);

            if (error) {
                // Arată eroarea
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = error.message;

                // Reactivează butonul
                submitButton.disabled = false;
                buttonText.innerHTML = originalButtonContent;
                buttonText.style.display = 'inline';
                spinner.style.display = 'none';
            } else {
                // Trimite token-ul la server pentru Stripe
                submitStripeTokenToServer(token);
            }
        } else {
            // Pentru alte metode de plată (transfer, PayPal)
            submitOtherPaymentMethod(selectedPayment);
        }
    });

    // Trimite token-ul Stripe la server pentru procesare
    function submitStripeTokenToServer(token) {
        const form = document.getElementById('payment-form');
        const formData = new FormData(form);
        formData.append('stripeToken', token.id);

        fetch('process-stripe-payment.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Plata a reușit - redirecționează
                    window.location.href = data.redirect_url;
                } else {
                    showPaymentError(data.error || 'A apărut o eroare la procesarea plății.');
                }
            })
            .catch(error => {
                console.error('Eroare:', error);
                showPaymentError('A apărut o eroare de conexiune. Te rugăm să încerci din nou.');
            });
    }

    // Trimite datele pentru alte metode de plată
    function submitOtherPaymentMethod(paymentMethod) {
        const form = document.getElementById('payment-form');
        const formData = new FormData(form);
        formData.append('payment_method', paymentMethod);

        fetch('process-order.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Verifică dacă răspunsul este de tip redirect
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }

                // Dacă nu e redirect, încearcă să citești răspunsul
                return response.text();
            })
            .then(data => {
                if (data) {
                    // Dacă primim conținut, înseamnă că a fost o eroare
                    console.log('Response data:', data);

                    // Încearcă să găsești URL-ul de redirect în răspuns
                    if (data.includes('order-confirmation.php')) {
                        const match = data.match(/order-confirmation\.php\?id=(\d+)/);
                        if (match) {
                            window.location.href = 'order-confirmation.php?id=' + match[1];
                            return;
                        }
                    }

                    // Dacă nu găsim redirect, reîncarcă pagina
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Eroare:', error);

                // În caz de eroare, încercăm să redirecționăm către index
                // sau să afișăm o eroare
                showPaymentError('A apărut o eroare. Te rugăm să verifici secțiunea "Biletele mele" sau să încerci din nou.');

                // Reactivează butonul după o scurtă pauză
                setTimeout(() => {
                    window.location.href = 'my-tickets.php';
                }, 3000);
            });
    }

    // Afișează erori de plată
    function showPaymentError(message) {
        const errorElement = document.getElementById('card-errors');
        errorElement.textContent = message;

        // Reactivează butonul
        const submitButton = document.getElementById('submit-button');
        const buttonText = document.getElementById('button-text');
        const spinner = document.getElementById('spinner');

        submitButton.disabled = false;
        buttonText.style.display = 'inline';
        spinner.style.display = 'none';
    }
</script>

<?php
require_once "includes/footer.php";
?>