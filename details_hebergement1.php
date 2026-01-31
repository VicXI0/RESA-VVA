<?php
session_start();
require_once 'connexion.php';

// Vérifier l'ID de l'hébergement
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id_heb = intval($_GET['id']);

// Récupérer les infos de l'hébergement
$sql_heb = "SELECT * FROM hebergement WHERE NOHEB = ?";
$stmt_heb = $pdo->prepare($sql_heb);
$stmt_heb->execute([$id_heb]);
$heb = $stmt_heb->fetch();

if (!$heb) {
    echo "Hébergement non trouvé";
    exit;
}

// Récupérer les semaines disponibles
$sql_semaines = "SELECT s.DATEDEBSEM, s.DATEFINSEM 
                 FROM semaine s 
                 WHERE s.DATEDEBSEM >= CURDATE() 
                 AND NOT EXISTS (
                     SELECT 1 FROM resa r 
                     WHERE r.DATEDEBSEM = s.DATEDEBSEM 
                     AND r.NOHEB = ? 
                     AND r.CODEETATRESA NOT IN ('AN', 'TE')
                 ) 
                 ORDER BY s.DATEDEBSEM 
                 LIMIT 12";
$stmt_sem = $pdo->prepare($sql_semaines);
$stmt_sem->execute([$id_heb]);
$semaines = $stmt_sem->fetchAll();

// Traitement réservation
$message = '';
if ($_POST && isset($_POST['reserver'])) {
    if (!isset($_SESSION['user_id'])) {
        $message = '<div class="erreur">Veuillez vous connecter pour réserver</div>';
    } else {
        $semaine = $_POST['semaine'] ?? '';
        $nb_personnes = intval($_POST['nb_personnes'] ?? 0);
        
        // Validation
        if (empty($semaine)) {
            $message = '<div class="erreur">Choisissez une semaine</div>';
        } elseif ($nb_personnes < 1 || $nb_personnes > $heb['NBPLACEHEB']) {
            $message = '<div class="erreur">Nombre de personnes invalide (1 à '.$heb['NBPLACEHEB'].')</div>';
        } else {
            // Créer la réservation
            $sql_max = "SELECT COALESCE(MAX(NORESA), 0) + 1 as nouveau_num FROM resa";
            $stmt_max = $pdo->query($sql_max);
            $num_resa = $stmt_max->fetch()['nouveau_num'];
            
            $sql_resa = "INSERT INTO resa (NORESA, USER, DATEDEBSEM, NOHEB, CODEETATRESA, DATERESA, NBOCCUPANT, TARIFSEMRESA, MONTANTARRHES) 
                         VALUES (?, ?, ?, ?, 'BL', CURDATE(), ?, ?, ?)";
            $stmt_resa = $pdo->prepare($sql_resa);
            
            $arrhes = $heb['TARIFSEMHEB'] * 0.20;
            $success = $stmt_resa->execute([
                $num_resa,
                $_SESSION['user_id'],
                $semaine,
                $id_heb,
                $nb_personnes,
                $heb['TARIFSEMHEB'],
                $arrhes
            ]);
            
            if ($success) {
                $message = '<div class="succes">Réservation #'.$num_resa.' enregistrée !</div>';
                // Recharger les semaines
                $stmt_sem->execute([$id_heb]);
                $semaines = $stmt_sem->fetchAll();
            } else {
                $message = '<div class="erreur">Erreur lors de la réservation</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($heb['NOMHEB']); ?> - Détails</title>
    <link rel="stylesheet" href="detail.css">
    <style>
        .container { max-width: 1000px; margin: 20px auto; padding: 20px; }
        .fiche-heb { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .photo-heb { width: 100%; max-height: 400px; object-fit: cover; border-radius: 8px; }
        .caracteristiques { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
        .caracteristiques li { padding: 8px 0; border-bottom: 1px solid #eee; }
        .form-reservation { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { background: #007bff; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #0056b3; }
        .succes { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin: 15px 0; }
        .erreur { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin: 15px 0; }
        .btn-retour { display: inline-block; background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-bottom: 20px; }
        .btn-retour:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-retour">← Retour à la liste des hébergements</a>
        
        <?php echo $message; ?>
        
        <div class="fiche-heb">
            <h1><?php echo htmlspecialchars($heb['NOMHEB']); ?></h1>
            
            <img src="img/<?php echo htmlspecialchars($heb['PHOTOHEB']); ?>" 
                 alt="<?php echo htmlspecialchars($heb['NOMHEB']); ?>" 
                 class="photo-heb"
                 onerror="this.src='https:via.placeholder.com/800x400/ccc/666?text=Photo+non+disponible'">
            
            <div class="caracteristiques">
                <ul>
                    <li><strong>Capacité :</strong> <?php echo $heb['NBPLACEHEB']; ?> personnes</li>
                    <li><strong>Surface :</strong> <?php echo $heb['SURFACEHEB']; ?> m²</li>
                    <li><strong>Internet :</strong> <?php echo $heb['INTERNET'] ? 'Oui' : 'Non'; ?></li>
                </ul>
                <ul>
                    <li><strong>Secteur :</strong> <?php echo htmlspecialchars($heb['SECTEURHEB']); ?></li>
                    <li><strong>Orientation :</strong> <?php echo htmlspecialchars($heb['ORIENTATIONHEB']); ?></li>
                    <li><strong>Tarif :</strong> <?php echo number_format($heb['TARIFSEMHEB'], 2, ',', ' '); ?> €/semaine</li>
                </ul>
            </div>
            
            <p><strong>Description :</strong> <?php echo nl2br(htmlspecialchars($heb['DESCRIHEB'])); ?></p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="form-reservation">
                    <h3>Réserver cet hébergement</h3>
                    
                    <?php if (empty($semaines)): ?>
                        <p class="erreur">Aucune semaine disponible</p>
                    <?php else: ?>
                        <form method="POST">
                            <div class="form-group">
                                <label>Semaine :</label>
                                <select name="semaine" class="form-control" required>
                                    <option value="">Choisir une semaine</option>
                                    <?php foreach ($semaines as $sem): ?>
                                        <option value="<?php echo $sem['DATEDEBSEM']; ?>">
                                            Du <?php echo date('d/m/Y', strtotime($sem['DATEDEBSEM'])); ?> 
                                            au <?php echo date('d/m/Y', strtotime($sem['DATEFINSEM'])); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Nombre de personnes :</label>
                                <input type="number" name="nb_personnes" class="form-control" 
                                       min="1" max="<?php echo $heb['NBPLACEHEB']; ?>" value="1" required>
                            </div>
                            
                            <button type="submit" name="reserver" class="btn">
                                Réserver (<?php echo number_format($heb['TARIFSEMHEB'], 2, ',', ' '); ?> €)
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="erreur">
                    <a href="login.php">Connectez-vous</a> pour réserver cet hébergement.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>