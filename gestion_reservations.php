<?php
session_start();
require_once 'connexion.php';

// Vérifier si gestionnaire
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'GES') {
    header('Location: login.php');
    exit;
}

// Récupérer toutes les réservations
$sql = "SELECT r.*, e.NOMETATRESA, h.NOMHEB, c.NOMCPTE, c.PRENOMCPTE 
        FROM resa r
        JOIN etat_resa e ON r.CODEETATRESA = e.CODEETATRESA
        JOIN hebergement h ON r.NOHEB = h.NOHEB
        JOIN compte c ON r.USER = c.USER
        ORDER BY r.DATEDEBSEM DESC";
$reservations = $pdo->query($sql)->fetchAll();

// États disponibles
$sql_etats = "SELECT * FROM etat_resa";
$etats = $pdo->query($sql_etats)->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des réservations - RESA VVA</title>
    <link rel="stylesheet" href="gestions_reservations.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-retour">← Retour à l'acceuil</a>
        
        <h1>Gestion des réservations</h1>
        
        <?php if (!empty($reservations)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Hébergement</th>
                        <th>Dates</th>
                        <th>Personnes</th>
                        <th>Prix</th>
                        <th>État</th>
                        <th>Modifier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $resa): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($resa['PRENOMCPTE'].' '.$resa['NOMCPTE']); ?></td>
                        <td><?php echo htmlspecialchars($resa['NOMHEB']); ?></td>
                        <td>
                            <?php echo date('d/m/Y', strtotime($resa['DATEDEBSEM'])); ?><br>
                            <small>Réservé le : <?php echo date('d/m/Y', strtotime($resa['DATERESA'])); ?></small>
                        </td>
                        <td><?php echo $resa['NBOCCUPANT']; ?></td>
                        <td><?php echo number_format($resa['TARIFSEMRESA'], 2, ',', ' '); ?> €</td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($resa['CODEETATRESA']); ?>">
                                <?php echo htmlspecialchars($resa['NOMETATRESA']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="modifier_etat.php" class="form-modif">
                                <input type="hidden" name="num_resa" value="<?php echo $resa['NORESA']; ?>">
                                <select name="nouvel_etat" class="select-etat" onchange="this.form.submit()">
                                    <?php foreach ($etats as $etat): ?>
                                    <option value="<?php echo $etat['CODEETATRESA']; ?>" 
                                            <?php if ($etat['CODEETATRESA'] == $resa['CODEETATRESA']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($etat['NOMETATRESA']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-message">
                <p>Aucune réservation trouvée.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>