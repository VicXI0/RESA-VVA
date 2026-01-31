<?php
session_start();
require_once 'connexion.php';

// Vérifier si l'utilisateur est connecté (mais ne pas bloquer l'accès)
$isConnected = isset($_SESSION['user_id']) && isset($_SESSION['username']);
$isGestionnaire = $isConnected && in_array($_SESSION['user_type'] ?? '', ['GES', 'ADM']);

// Récupérer TOUS les hébergements disponibles (accessible à tous, même non connectés)
$sql = "SELECT H.NOHEB, H.NOMHEB, H.NBPLACEHEB, H.PHOTOHEB, H.TARIFSEMHEB, 
               H.ETATHEB, H.SURFACEHEB, H.INTERNET, T.NOMTYPEHEB,
               COUNT(DISTINCT CASE WHEN R.CODEETATRESA NOT IN ('AN', 'TE') 
                     AND R.DATEDEBSEM >= CURDATE() THEN R.NORESA END) as NB_RESA_ACTIVES
        FROM HEBERGEMENT H 
        LEFT JOIN TYPE_HEB T ON H.CODETYPEHEB = T.CODETYPEHEB
        LEFT JOIN RESA R ON H.NOHEB = R.NOHEB
        WHERE H.ETATHEB != 'en rénovation' OR H.ETATHEB IS NULL
        GROUP BY H.NOHEB
        ORDER BY H.NOMHEB ASC";
$stmt = $pdo->query($sql);
$hebergements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter les hébergements par catégorie
$totalHebergements = count($hebergements);
$hebergementsDisponibles = 0;

foreach($hebergements as $h) {
    if ($h['NB_RESA_ACTIVES'] == 0) {
        $hebergementsDisponibles++;
    }
}

$typesDisponibles = [];
foreach($hebergements as $h) {
    $type = $h['NOMTYPEHEB'] ?? 'Non défini';
    if (!isset($typesDisponibles[$type])) {
        $typesDisponibles[$type] = 0;
    }
    $typesDisponibles[$type]++;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Village Vacances Azur - Réservation d'hébergements</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">
                RESA VVA
            </a>
            <nav>
                <?php if ($isConnected): ?>
                    <div class="user-menu" id="userMenu">
                        <button class="user-button" onclick="toggleMenu()">
                            <span>☰</span>
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                            </div>
                        </button>
                        
                        <div class="dropdown-menu">
                            <a href="#" class="dropdown-item">
                                <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong><br>
                                <small style="color: #666;">
                                    <?php echo $isGestionnaire ? 'Gestionnaire' : 'Vacancier'; ?>
                                </small>
                            </a>
                            
                            <a href="index.php" class="dropdown-item">
                                Accueil
                            </a>
                            
                            <?php if ($isGestionnaire): ?>
                                <a href="gestion_reservations.php" class="dropdown-item">
                                    Gérer les réservations
                                </a>
                                <a href="ajouter_hebergement.php" class="dropdown-item">
                                    Gérer les hébergements
                                </a>
                            <?php else: ?>
                                <a href="mes_reservations.php" class="dropdown-item">
                                    Mes réservations
                                </a>
                            <?php endif; ?>
                            
                            <a href="logout.php" class="dropdown-item danger">
                                Déconnexion
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-login">
                        Connexion
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="hero">
        <h1>Bienvenue au Village Vacances Azur</h1>
        <p>Découvrez nos hébergements de qualité au cœur d'un cadre exceptionnel</p>
        <?php if (!$isConnected): ?>
            <a href="login.php" class="hero-cta">Se connecter pour réserver →</a>
        <?php endif; ?>
    </div>

    <div class="stats">
        <div class="stats-content">
            <div class="stat-item">
                <h3><?php echo $totalHebergements; ?></h3>
                <p>Hébergements au total</p>
            </div>
            
            <div class="stat-item">
                <h3><?php echo $hebergementsDisponibles; ?></h3>
                <p>Entièrement disponibles</p>
            </div>
            
            <?php 
            $count = 0;
            foreach($typesDisponibles as $type => $nb): 
                if ($count < 2):
            ?>
                <div class="stat-item">
                    <h3><?php echo $nb; ?></h3>
                    <p><?php echo htmlspecialchars($type); ?><?php echo $nb > 1 ? 's' : ''; ?></p>
                </div>
            <?php 
                    $count++;
                endif;
            endforeach; 
            ?>
        </div>
    </div>

    <main>
        <div class="section-header">
            <h2>Explorez nos hébergements</h2>
            <p>
                <?php if ($isConnected): ?>
                    Cliquez sur un hébergement pour voir les détails et réserver votre séjour
                <?php else: ?>
                    Cliquez sur un hébergement pour voir les détails. <strong>Connectez-vous pour réserver !</strong>
                <?php endif; ?>
            </p>
        </div>

        <?php if(empty($hebergements)): ?>
            <div class="empty-state">
                <h3>Aucun hébergement disponible</h3>
                <p>Revenez bientôt pour découvrir nos nouvelles offres !</p>
            </div>
        <?php else: ?>
            <div class="listings-grid">
                <?php foreach($hebergements as $h): ?>
                    <?php 
                    $hasActiveReservations = $h['NB_RESA_ACTIVES'] > 0;
                    $cardClass = $hasActiveReservations ? 'listing-card reserved' : 'listing-card';
                    ?>
                    <a href="details_hebergement1.php?id=<?php echo $h['NOHEB']; ?>" class="<?php echo $cardClass; ?>">
                        <?php 
                        // Gestion de l'image
                        if (!empty($h['PHOTOHEB'])) {
                            if (strpos($h['PHOTOHEB'], 'http') === 0) {
                                $photoPath = htmlspecialchars($h['PHOTOHEB']);
                            } else {
                                $photoPath = 'img/' . htmlspecialchars($h['PHOTOHEB']);
                            }
                        } else {
                            $photoPath = 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop';
                        }
                        ?>
                        <img src="<?php echo $photoPath; ?>" 
                             alt="<?php echo htmlspecialchars($h['NOMHEB']); ?>" 
                             class="listing-image"
                             onerror="this.src='https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop'">
                        
                        <div class="listing-content">
                            <div class="listing-type">
                                <?php echo htmlspecialchars($h['NOMTYPEHEB'] ?? 'Hébergement'); ?>
                            </div>
                            
                            <div class="listing-title">
                                <?php echo htmlspecialchars($h['NOMHEB']); ?>
                            </div>
                            
                            <div class="listing-features">
                                <span><?php echo (int)$h['NBPLACEHEB']; ?> personnes</span>
                                <?php if ($h['SURFACEHEB']): ?>
                                    <span><?php echo (int)$h['SURFACEHEB']; ?> m²</span>
                                <?php endif; ?>
                                <?php if ($h['INTERNET']): ?>
                                    <span>WiFi</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="listing-price">
                                <span class="price-amount"><?php echo number_format($h['TARIFSEMHEB'], 0, ',', ' '); ?> €</span>
                                <span class="price-period">/ semaine</span>
                            </div>
                            
                            <!-- Affichage du statut de disponibilité -->
                            <?php if ($hasActiveReservations): ?>
                                <span class="badge-reserve">Attention! : Partiellement réservé</span>
                            <?php else: ?>
                                <span class="badge-disponible">Disponible</span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <script>
        function toggleMenu() {
            const menu = document.getElementById('userMenu');
            if (menu) {
                menu.classList.toggle('active');
            }
        }

        // Fermer le menu si on clique en dehors
        document.addEventListener('click', function(event) {
            const menu = document.getElementById('userMenu');
            if (menu && !menu.contains(event.target)) {
                menu.classList.remove('active');
            }
        });
    </script>
</body>
</html>