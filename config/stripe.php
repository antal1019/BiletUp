<?php
// Încarcă Stripe PHP library
require_once __DIR__ . '/../vendor/autoload.php';

// Configurare Stripe API
// Înlocuiește cu cheile tale din Stripe Dashboard

// Chei de test (începe cu sk_test_ și pk_test_)
define('STRIPE_SECRET_KEY', 'sk_test_51RY3BzRIVfurwYgizKsatmwqT0Uca6hym15ayM4AGu99s2BtE7DNAcn4PH9rlaH4iip0mzjRdFOsUQcQWIVweT9k00LFVINdZn');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51RY3BzRIVfurwYgiisDtRyw17ap83MkjqhDV90vvLJWIs1KcoOyVPbeXLexjP6smVe0OBZB5Vcrm0NU0WgDQQf8600C9IYqXTH');

// Pentru producție (când ești gata live)
// define('STRIPE_SECRET_KEY', 'sk_live_TU_CHEIA_SECRETA_LIVE');
// define('STRIPE_PUBLISHABLE_KEY', 'pk_live_TU_CHEIA_PUBLICA_LIVE');

// Setează cheia API pentru Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Funcții helper
function getStripeSecretKey() {
    return STRIPE_SECRET_KEY;
}

function getStripePublishableKey() {
    return STRIPE_PUBLISHABLE_KEY;
}

// Moneda (lei românești)
define('STRIPE_CURRENCY', 'ron');

// Funcție pentru a crea o plată
function createStripeCharge($amount, $currency, $source, $description, $metadata = []) {
    try {
        $charge = \Stripe\Charge::create([
            'amount' => $amount,
            'currency' => $currency,
            'source' => $source,
            'description' => $description,
            'metadata' => $metadata
        ]);
        
        return [
            'success' => true,
            'charge' => $charge
        ];
    } catch (\Stripe\Exception\CardException $e) {
        // Card-ul a fost refuzat
        return [
            'success' => false,
            'error' => $e->getError()->message
        ];
    } catch (\Stripe\Exception\RateLimitException $e) {
        return [
            'success' => false,
            'error' => 'Prea multe request-uri. Încearcă din nou.'
        ];
    } catch (\Stripe\Exception\InvalidRequestException $e) {
        return [
            'success' => false,
            'error' => 'Parametri invalizi.'
        ];
    } catch (\Stripe\Exception\AuthenticationException $e) {
        return [
            'success' => false,
            'error' => 'Eroare de autentificare Stripe.'
        ];
    } catch (\Stripe\Exception\ApiConnectionException $e) {
        return [
            'success' => false,
            'error' => 'Eroare de conexiune cu Stripe.'
        ];
    } catch (\Stripe\Exception\ApiErrorException $e) {
        return [
            'success' => false,
            'error' => 'Eroare API Stripe.'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'A apărut o eroare neașteptată.'
        ];
    }
}

// Funcție pentru a crea un refund
function createStripeRefund($charge_id, $amount = null) {
    try {
        $refund_data = ['charge' => $charge_id];
        if ($amount !== null) {
            $refund_data['amount'] = $amount;
        }
        
        $refund = \Stripe\Refund::create($refund_data);
        
        return [
            'success' => true,
            'refund' => $refund
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Funcție pentru a obține detalii despre o plată
function getStripeCharge($charge_id) {
    try {
        $charge = \Stripe\Charge::retrieve($charge_id);
        return [
            'success' => true,
            'charge' => $charge
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>