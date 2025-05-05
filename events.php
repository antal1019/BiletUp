<?php
$page_title = "Evenimente";
require_once "includes/header.php";

// Inițializare variabile pentru filtrare
$where_conditions = [];
$params = [];
$types = "";

// Verifică dacă există filtrare după categorie
if(isset($_GET['categorie']) && !empty($_GET['categorie'])) {
    $category_id = $conn->real_escape_string($_GET['categorie']);
    $where_conditions[] = "e.categorie_id = '$category_id'";
    $category_info = null;
    
    // Obține informații despre categoria selectată
    $cat_result = $conn->query("SELECT * FROM event_categories WHERE id = '$category_id'");
    if($cat_result->num_rows > 0) {
        $category_info = $cat_result->fetch_assoc();
        $page_title = "Evenimente - " . $category_info['nume'];
    }
}

// Verifică dacă există căutare
if(isset($_GET['q']) && !empty($_GET['q'])) {
    $search_term = $conn->real_escape_string($_GET['q']);
    $where_conditions[] = "(e.titlu LIKE '%$search_term%' OR e.descriere LIKE '%$search_term%' OR e.locatie LIKE '%$search_term%' OR e.oras LIKE '%$search_term%')";
    $page_title = "Rezultate căutare: " . $_GET['q'];
}

// Adaugă condiția pentru evenimente active și viitoare
$where_conditions[] = "e.status = 'activ' AND e.data_inceput >= CURDATE()";

// Construiește interogarea SQL
$sql_where = "";
if(count($where_conditions) > 0) {
    $sql_where = " WHERE " . implode(" AND ", $where_conditions);
}

$sql = "SELECT e.*, c.nume as categorie_nume 
        FROM events e 
        LEFT JOIN event_categories c ON e.categorie_id = c.id 
        $sql_where 
        ORDER BY e.data_inceput";

$result = $conn->query($sql);
$events = [];

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

// Obține toate categoriile pentru filtrare
$categories = getCategories();
?>

<!-- Banner Section -->
<section class="py-4 bg-light">
    <div class="container">
        <h1 class="mb-0"><?php echo $page_title; ?></h1>
        <?php if(isset($category_info)): ?>
            <p class="lead"><?php echo $category_info['descriere']; ?></p>
        <?php elseif(isset($_GET['q'])): ?>
            <p class="lead">Rezultate pentru: "<?php echo htmlspecialchars($_GET['q']); ?>"</p>
        <?php else: ?>
            <p class="lead">Descoperă toate evenimentele disponibile</p>
        <?php endif; ?>
    </div>
</section>

<!-- Filtre Section -->
<section class="py-4 border-bottom">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <form action="events.php" method="GET" class="d-flex">
                    <input type="text" name="q" class="form-control" placeholder="Caută evenimente..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button type="submit" class="btn btn-primary ms-2"><i class="bi bi-search"></i></button>
                </form>
            </div>
            <div class="col-md-8">
                <div class="d-flex flex-wrap justify-content-md-end">
                    <a href="events.php" class="btn <?php echo !isset($_GET['categorie']) ? 'btn-primary' : 'btn-outline-secondary'; ?> me-2 mb-2">Toate</a>
                    <?php foreach($categories as $category): ?>
                    <a href="events.php?categorie=<?php echo $category['id']; ?>" class="btn <?php echo (isset($_GET['categorie']) && $_GET['categorie'] == $category['id']) ? 'btn-primary' : 'btn-outline-secondary'; ?> me-2 mb-2"><?php echo $category['nume']; ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Evenimente Section -->
<section class="py-5">
    <div class="container">
        <?php if(count($events) > 0): ?>
            <div class="row">
                <?php foreach($events as $event): ?>
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
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-calendar-x" style="font-size: 3rem;"></i>
                <h2 class="mt-3">Niciun eveniment găsit</h2>
                <p class="text-muted">Nu există evenimente care să corespundă criteriilor tale de căutare.</p>
                <a href="events.php" class="btn btn-primary mt-3">Vezi toate evenimentele</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
require_once "includes/footer.php";
?>