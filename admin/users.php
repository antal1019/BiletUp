<?php
require_once "../session_config.php";
require_once "../config/database.php";
require_once "../includes/functions.php";

// Verifică dacă utilizatorul este admin
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    $_SESSION['mesaj'] = "Nu ai permisiunea de a accesa această pagină!";
    $_SESSION['tip_mesaj'] = "danger";
    header("Location: ../index.php");
    exit;
}

// Procesează ștergerea utilizatorului
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $user_id = $conn->real_escape_string($_GET['delete']);
    
    // Nu permitem ștergerea propriului cont
    if($user_id == $_SESSION['user_id']) {
        $_SESSION['mesaj'] = "Nu poți șterge propriul cont!";
        $_SESSION['tip_mesaj'] = "danger";
    } else {
        $delete_sql = "DELETE FROM users WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        
        if($delete_stmt->execute()) {
            $_SESSION['mesaj'] = "Utilizatorul a fost șters cu succes!";
            $_SESSION['tip_mesaj'] = "success";
        } else {
            $_SESSION['mesaj'] = "A apărut o eroare la ștergerea utilizatorului!";
            $_SESSION['tip_mesaj'] = "danger";
        }
    }
    
    header("Location: users.php");
    exit;
}

// Obține toți utilizatorii
$sql = "SELECT * FROM users ORDER BY data_inregistrare DESC";
$result = $conn->query($sql);
$users = [];

if($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$page_title = "Administrare Utilizatori";
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - BiletUP Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Admin Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">BiletUP Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarAdmin">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Evenimente</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">Utilizatori</a>
                    </li>
                
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php" target="_blank">
                            <i class="bi bi-box-arrow-up-right me-1"></i>Vezi site-ul
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Deconectare
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if(isset($_SESSION['mesaj'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tip_mesaj']; ?> alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['mesaj'];
                unset($_SESSION['mesaj']);
                unset($_SESSION['tip_mesaj']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Administrare Utilizatori</h1>
            <a href="add-user.php" class="btn btn-primary">
                <i class="bi bi-person-plus-fill me-1"></i> Adaugă utilizator
            </a>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nume</th>
                                <th>Email</th>
                                <th>Telefon</th>
                                <th>Rol</th>
                                <th>Data înregistrării</th>
                                <th>Acțiuni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($users) > 0): ?>
                                <?php foreach($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo $user['nume'] . ' ' . $user['prenume']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo $user['telefon'] ? $user['telefon'] : '-'; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $user['rol'] == 'admin' ? 'danger' : ($user['rol'] == 'organizator' ? 'warning' : 'primary'); 
                                        ?>">
                                            <?php echo ucfirst($user['rol']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($user['data_inregistrare'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning" title="Editează">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-danger" title="Șterge" onclick="return confirm('Ești sigur că vrei să ștergi acest utilizator?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Nu există utilizatori.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="small mb-0">&copy; <?php echo date('Y'); ?> BiletUP Admin Panel. Toate drepturile rezervate.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>