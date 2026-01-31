<?php
session_start();
require_once 'connexion.php';

// Vérifier connexion
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer réservations du vacancier
$sql = "SELECT r.*, e.NOMETATRESA, h.NOMHEB, h.PHOTOHEB, s.DATEFINSEM
        FROM resa r
        JOIN etat_resa e ON r.CODEETATRESA = e.CODEETATRESA
        JOIN hebergement h ON r.NOHEB = h.NOHEB
        JOIN semaine s ON r.DATEDEBSEM = s.DATEDEBSEM
        WHERE r.USER = ?
        ORDER BY r.DATEDEBSEM DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$reservations = $stmt->fetchAll();

// Calculer les statistiques
$total_reservations = count($reservations);
$reservations_actives = 0;
$total_depense = 0;

foreach ($reservations as $resa) {
    if (!in_array($resa['CODEETATRESA'], ['AN', 'TE'])) {
        $reservations_actives++;
    }
    if ($resa['CODEETATRESA'] != 'AN') {
        $total_depense += $resa['TARIFSEMRESA'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes réservations - RESA VVA</title>
    <link rel="stylesheet" href="mes_reservations.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-retour">← Retour à l'accueil</a>
        
        <h1>Mes réservations</h1>

        <!-- Statistiques -->
        <div class="stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_reservations; ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $reservations_actives; ?></div>
                <div class="stat-label">Actives</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($total_depense, 0, ',', ' '); ?> €</div>
                <div class="stat-label">Dépensé</div>
            </div>
        </div>
        
        <?php if (empty($reservations)): ?>
            <div class="empty-message">
                <p>Vous n'avez aucune réservation.</p>
                <a href="index.php" class="btn-details">Voir les hébergements</a>
            </div>
        <?php else: ?>
            <?php foreach ($reservations as $resa): ?>
            <div class="reservation-card">
                <!-- Image de l'hébergement -->
                <img src="img/<?php echo htmlspecialchars($resa['PHOTOHEB']); ?>" 
                     alt="<?php echo htmlspecialchars($resa['NOMHEB']); ?>" 
                     class="reservation-image"
                     onerror="this.src='https://via.placeholder.com/150x100/cccccc/666666?text=Image+non+disponible'">
                
                <div class="reservation-content">
                    <div class="reservation-header">
                        <div class="reservation-title">
                            Réservation #<?php echo $resa['NORESA']; ?> - <?php echo htmlspecialchars($resa['NOMHEB']); ?>
                        </div>
                        <span class="badge badge-<?php echo strtolower($resa['CODEETATRESA']); ?>">
                            <?php echo htmlspecialchars($resa['NOMETATRESA']); ?>
                        </span>
                    </div>
                    
                    <div class="reservation-info">
                        <div class="info-group">
                            <p><span class="info-label">Dates :</span> Du <?php echo date('d/m/Y', strtotime($resa['DATEDEBSEM'])); ?> au <?php echo date('d/m/Y', strtotime($resa['DATEFINSEM'])); ?></p>
                            <p><span class="info-label">Personnes :</span> <?php echo $resa['NBOCCUPANT']; ?></p>
                        </div>
                        <div class="info-group">
                            <p><span class="info-label">Prix total :</span> <?php echo number_format($resa['TARIFSEMRESA'], 2, ',', ' '); ?> €</p>
                            <p><span class="info-label">Arrhes :</span> <?php echo number_format($resa['MONTANTARRHES'], 2, ',', ' '); ?> €</p>
                        </div>
                    </div>
                    
                    <a href="details_hebergement1.php?id=<?php echo $resa['NOHEB']; ?>" class="btn-details">
                        Voir l'hébergement
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>