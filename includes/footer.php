</div>
    
    <footer class="bg-dark text-white mt-5 pt-5 pb-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-4">BiletUP</h5>
                    <p class="mb-4">Platforma ta pentru bilete la evenimente din toată România. Concerte, festivaluri, teatru, sport și multe altele.</p>
                    <div class="mb-3">
                        <a href="https://www.facebook.com/" target="_blank" class="text-white me-3 fs-5"><i class="bi bi-facebook"></i></a>
                        <a href="https://www.instagram.com/" target="_blank" class="text-white me-3 fs-5"><i class="bi bi-instagram"></i></a>
                        <a href="https://www.x.com/" target="_blank" class="text-white me-3 fs-5"><i class="bi bi-twitter"></i></a>
                        <a href="https://www.youtube.com/" target="_blank" class="text-white fs-5"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-4">Link-uri rapide</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-white text-decoration-none"><i class="bi bi-chevron-right me-2"></i>Acasă</a></li>
                        <li class="mb-2"><a href="events.php" class="text-white text-decoration-none"><i class="bi bi-chevron-right me-2"></i>Evenimente</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none"><i class="bi bi-chevron-right me-2"></i>Despre noi</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none"><i class="bi bi-chevron-right me-2"></i>Contact</a></li>
                        <li class="mb-2"><a href="#" class="text-white text-decoration-none"><i class="bi bi-chevron-right me-2"></i>Termeni și condiții</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-4">Categorii populare</h5>
                    <ul class="list-unstyled">
                        <?php 
                        $categorii = getCategories();
                        foreach($categorii as $categorie): 
                        ?>
                        <li class="mb-2"><a href="events.php?categorie=<?php echo $categorie['id']; ?>" class="text-white text-decoration-none"><i class="bi bi-chevron-right me-2"></i><?php echo $categorie['nume']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5 class="text-uppercase mb-4">Contact</h5>
                    <p><i class="bi bi-geo-alt-fill me-2"></i>Str. Teodor Mihali 58-60, Cluj-Napoca</p>
                    <p><i class="bi bi-envelope-fill me-2"></i><a href="mailto:contact@biletup.ro" class="text-white">contact@biletup.ro</a></p>
                    <p><i class="bi bi-telephone-fill me-2"></i><a href="tel:+40721234567" class="text-white">0763 982 128</a></p>
                    <p><i class="bi bi-clock-fill me-2"></i>Luni - Vineri: 9:00 - 17:00</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <p class="small mb-md-0">&copy; <?php echo date('Y'); ?> BiletUP. Toate drepturile rezervate.</p>
                </div>
                <div class="col-md-5 text-md-end">
                    <p class="small mb-0">
                        <a href="#" class="text-white me-3">Politica de confidențialitate</a>
                        <a href="#" class="text-white me-3">Politica de cookies</a>
                        <a href="https://reclamatiisal.anpc.ro/" target="_blank" class="text-white">ANPC</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="js/script.js"></script>
</body>
</html>