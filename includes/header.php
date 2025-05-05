<?php
require_once __DIR__ . '/../session_config.php'; // Include configurarea sesiunii
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - BiletUP' : 'BiletUP'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">BiletUP</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Acasă</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            Categorii
                        </a>
                        <ul class="dropdown-menu">
                            <?php 
                            $categorii = getCategories();
                            foreach($categorii as $categorie): 
                            ?>
                            <li><a class="dropdown-item" href="events.php?categorie=<?php echo $categorie['id']; ?>"><?php echo $categorie['nume']; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Toate evenimentele</a>
                    </li>
                    <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/index.php">Admin</a>
                    </li>
                    <?php endif; ?>
                    <?php if(isset($_SESSION['user_role']) && ($_SESSION['user_role'] == 'organizator' || $_SESSION['user_role'] == 'admin')): ?>
<li class="nav-item">
    <a class="nav-link" href="organizer/index.php">Panou Organizator</a>
</li>
<?php endif; ?>
                </ul>
                
                <div class="d-flex">
                    <!-- Căutare -->
                    <form class="d-flex me-2" action="events.php" method="GET">
                        <input class="form-control me-2" type="search" name="q" placeholder="Caută evenimente..." aria-label="Caută">
                        <button class="btn btn-outline-light" type="submit"><i class="bi bi-search"></i></button>
                    </form>
                    
                    <!-- User menu -->
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <a class="btn btn-outline-light dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo $_SESSION['user_nume']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="my-tickets.php"><i class="bi bi-ticket-perforated me-2"></i>Biletele mele</a></li>
                                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Deconectare</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-2">Autentificare</a>
                        <a href="register.php" class="btn btn-light">Înregistrare</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php 
        if(isset($_SESSION['mesaj'])): 
        ?>
        <div class="alert alert-<?php echo isset($_SESSION['tip_mesaj']) ? $_SESSION['tip_mesaj'] : 'primary'; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['mesaj']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php 
        unset($_SESSION['mesaj']);
        unset($_SESSION['tip_mesaj']);
        endif; 
        ?>