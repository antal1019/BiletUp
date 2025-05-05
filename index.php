<?php
$page_title = "Pagina principală";
require_once "includes/header.php";

// Obține evenimente viitoare
$upcoming_events = getUpcomingEvents(6);

// Obține categorii
$categories = getCategories();
?>

<!-- Hero Section -->
<section class="hero-section">
    <!-- Adaugă această linie pentru watermark -->
    <div class="hero-watermark">BiletUP</div>
    
    <div class="hero-content">
        <h1>Găsește și cumpără bilete la cele mai tari evenimente</h1>
        <p class="lead">Concerte, festivaluri, teatru, sport și multe altele - toate într-un singur loc</p>
        <a href="events.php" class="btn btn-primary btn-lg">VEZI TOATE EVENIMENTELE</a>
    </div>
</section>

<!-- Categorii Section -->
<section class="categories-section py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-center">Categorii de evenimente</h2>
                <p class="text-center text-muted">Găsește evenimentul perfect pentru tine</p>
            </div>
        </div>
        <div class="row">
            <?php foreach($categories as $category): ?>
            <div class="col-md-4 mb-4">
                <a href="events.php?categorie=<?php echo $category['id']; ?>" class="text-decoration-none">
                    <div class="category-box">
                        <img src="images/<?php echo strtolower(str_replace(' ', '-', $category['nume'])); ?>.jpg" alt="<?php echo $category['nume']; ?>" onerror="this.src='images/placeholder.jpg'">
                        <div class="category-overlay">
                            <h5 class="category-name"><?php echo $category['nume']; ?></h5>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Evenimente Viitoare Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-center">Evenimente Viitoare</h2>
                <p class="text-center text-muted">Nu rata cele mai așteptate evenimente</p>
            </div>
        </div>
        <div class="row">
            <?php if(count($upcoming_events) > 0): ?>
                <?php foreach($upcoming_events as $event): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card event-card">
                        <img src="<?php echo $event['imagine'] ? $event['imagine'] : 'images/placeholder.jpg'; ?>" class="card-img-top" alt="<?php echo $event['titlu']; ?>">
                        <div class="card-body">
                            <span class="category-badge"><?php echo $event['categorie_nume']; ?></span>
                            <div class="event-date">
                                <i class="bi bi-calendar3"></i> <?php echo formatData($event['data_inceput']); ?>
                            </div>
                            <h5 class="card-title"><?php echo $event['titlu']; ?></h5>
                            <div class="event-location">
                                <i class="bi bi-geo-alt"></i> <?php echo $event['oras']; ?>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="event-price">
                                    <?php if($event['pret_minim'] == $event['pret_maxim']): ?>
                                        <?php echo $event['pret_minim']; ?> lei
                                    <?php else: ?>
                                        <?php echo $event['pret_minim']; ?> - <?php echo $event['pret_maxim']; ?> lei
                                    <?php endif; ?>
                                </div>
                                <a href="event-details.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary">Detalii</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p>Nu există evenimente viitoare momentan.</p>
                    <p>Revino în curând pentru cele mai noi evenimente!</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="row mt-4">
            <div class="col-12 text-center">
                <a href="events.php" class="btn btn-outline-primary">Vezi toate evenimentele</a>
            </div>
        </div>
    </div>
    <?php if(isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 'organizator' || $_SESSION['user_role'] == 'admin')): ?>
<section class="py-3">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-primary shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">Ești organizator de evenimente?</h4>
                            <p class="text-muted mb-0">Administrează evenimentele și biletele tale din panoul de control.</p>
                        </div>
                        <a href="organizer/index.php" class="btn btn-primary">
                            <i class="bi bi-speedometer2 me-1"></i> Panou Organizator
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
</section>


<!-- Newsletter Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8 text-center">
                <h2>Abonează-te la newsletter</h2>
                <p class="text-muted mb-4">Primește notificări despre cele mai noi evenimente și oferte speciale.</p>
                <form class="row g-3 justify-content-center">
                    <div class="col-8">
                        <input type="email" class="form-control form-control-lg" placeholder="Adresa ta de email">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-lg">Abonează-te</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php
require_once "includes/footer.php";
?>