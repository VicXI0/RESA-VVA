<?php
session_start();
require_once 'connexion.php';

// Vérifier si gestionnaire
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'GES') {
    header('Location: login.php');
    exit;
}

// Récupérer l'ID de l'hébergement à modifier
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupérer les types d'hébergement
$sql_types = "SELECT * FROM type_heb ORDER BY NOMTYPEHEB";
$types_heb = $pdo->query($sql_types)->fetchAll();

$message = '';
$message_type = '';
$hebergement = null;

// Récupérer les données actuelles de l'hébergement
$sql_hebergement = "SELECT h.*, t.NOMTYPEHEB 
                    FROM hebergement h 
                    JOIN type_heb t ON h.CODETYPEHEB = t.CODETYPEHEB 
                    WHERE h.NOHEB = ?";
$stmt = $pdo->prepare($sql_hebergement);
$stmt->execute([$id]);
$hebergement = $stmt->fetch();

// Si l'hébergement n'existe pas
if (!$hebergement) {
    $message = "Hébergement non trouvé !";
    $message_type = 'error';
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {
    // Récupération des données
    $nom = trim($_POST['nom']);
    $type_heb = $_POST['type_heb'];
    $nb_places = intval($_POST['nb_places']);
    $surface = intval($_POST['surface']);
    $internet = isset($_POST['internet']) ? 1 : 0;
    $annee = intval($_POST['annee']);
    $secteur = $_POST['secteur'];
    $orientation = $_POST['orientation'];
    $etat = $_POST['etat'];
    $description = trim($_POST['description']);
    $tarif = floatval($_POST['tarif']);
    
    // Validation simple
    if (empty($nom) || empty($type_heb) || $nb_places <= 0 || $tarif <= 0) {
        $message = "Veuillez remplir tous les champs obligatoires correctement";
        $message_type = 'error';
    } else {
        try {
            // Gestion de l'image
            $photo_name = $hebergement['PHOTOHEB'];
            
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                // Supprimer l'ancienne photo si elle existe
                if (!empty($photo_name)) {
                    $old_photo_path = 'img/' . $photo_name;
                    if (file_exists($old_photo_path)) {
                        unlink($old_photo_path);
                    }
                }
                
                // Upload de la nouvelle photo
                $upload_dir = 'img/';
                $photo_name = uniqid() . '_' . basename($_FILES['photo']['name']);
                move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name);
            }
            
            // Mise à jour en base
            $sql_update = "UPDATE hebergement 
                          SET CODETYPEHEB = ?, 
                              NOMHEB = ?, 
                              NBPLACEHEB = ?, 
                              SURFACEHEB = ?, 
                              INTERNET = ?, 
                              ANNEEHEB = ?, 
                              SECTEURHEB = ?, 
                              ORIENTATIONHEB = ?, 
                              ETATHEB = ?, 
                              DESCRIHEB = ?, 
                              PHOTOHEB = ?, 
                              TARIFSEMHEB = ?
                          WHERE NOHEB = ?";
            
            $stmt = $pdo->prepare($sql_update);
            $success = $stmt->execute([
                $type_heb,
                $nom,
                $nb_places,
                $surface,
                $internet,
                $annee,
                $secteur,
                $orientation,
                $etat,
                $description,
                $photo_name,
                $tarif,
                $id
            ]);
            
            if ($success) {
                $message = "Hébergement modifié avec succès !";
                $message_type = 'success';
                
                // Recharger les données mises à jour
                $stmt = $pdo->prepare($sql_hebergement);
                $stmt->execute([$id]);
                $hebergement = $stmt->fetch();
            } else {
                $message = "Erreur lors de la modification en base de données";
                $message_type = 'error';
            }
            
        } catch (Exception $e) {
            $message = "Erreur: " . $e->getMessage();
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un hébergement - RESA VVA</title>
    <link rel="stylesheet" href="modifier_hebergements.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-retour">← Retour à l'accueil</a>
        
        <h1>Modifier un hébergement</h1>
        
        <div class="dropdown-container">
            <label class="dropdown-label">Sélectionnez une action :</label>
            <select class="action-dropdown" onchange="window.location.href=this.value;">
                <option value="ajouter_hebergement.php">Ajouter un hébergement</option>
                <option value="modifier_hebergements.php" selected>Modifier un hébergement</option>
                <option value="supprimer_hebergement.php">Supprimer un hébergement</option>
            </select>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($hebergement): ?>
            <div class="current-info">
                <h3>Hébergement #<?php echo htmlspecialchars($hebergement['NOHEB']); ?></h3>
                <?php if (!empty($hebergement['PHOTOHEB'])): ?>
                    <div class="current-photo">
                        <img src="img/<?php echo htmlspecialchars($hebergement['PHOTOHEB']); ?>" 
                             alt="Photo actuelle" 
                             style="max-width: 200px; max-height: 150px; border-radius: 5px; margin: 10px 0;">
                        <p>Photo actuelle</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="modifier" value="1">
                
                <div class="form-group">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" class="form-control" 
                           value="<?php echo htmlspecialchars($hebergement['NOMHEB']); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="type_heb">Type *</label>
                        <select id="type_heb" name="type_heb" class="form-control" required>
                            <option value="">Choisir</option>
                            <?php foreach ($types_heb as $type): ?>
                                <option value="<?php echo htmlspecialchars($type['CODETYPEHEB']); ?>"
                                    <?php echo ($hebergement['CODETYPEHEB'] == $type['CODETYPEHEB']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['NOMTYPEHEB']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tarif">Tarif/semaine (€) *</label>
                        <input type="number" id="tarif" name="tarif" class="form-control" 
                               step="0.01" min="0" value="<?php echo htmlspecialchars($hebergement['TARIFSEMHEB']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nb_places">Places *</label>
                        <input type="number" id="nb_places" name="nb_places" class="form-control" 
                               min="1" value="<?php echo htmlspecialchars($hebergement['NBPLACEHEB']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="surface">Surface (m²)</label>
                        <input type="number" id="surface" name="surface" class="form-control" 
                               min="1" value="<?php echo htmlspecialchars($hebergement['SURFACEHEB']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="secteur">Secteur</label>
                        <select id="secteur" name="secteur" class="form-control">
                            <option value="Nord" <?php echo ($hebergement['SECTEURHEB'] == 'Nord') ? 'selected' : ''; ?>>Nord</option>
                            <option value="Sud" <?php echo ($hebergement['SECTEURHEB'] == 'Sud') ? 'selected' : ''; ?>>Sud</option>
                            <option value="Est" <?php echo ($hebergement['SECTEURHEB'] == 'Est') ? 'selected' : ''; ?>>Est</option>
                            <option value="Ouest" <?php echo ($hebergement['SECTEURHEB'] == 'Ouest') ? 'selected' : ''; ?>>Ouest</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="orientation">Orientation</label>
                        <select id="orientation" name="orientation" class="form-control">
                            <option value="Nord" <?php echo ($hebergement['ORIENTATIONHEB'] == 'Nord') ? 'selected' : ''; ?>>Nord</option>
                            <option value="Sud" <?php echo ($hebergement['ORIENTATIONHEB'] == 'Sud') ? 'selected' : ''; ?>>Sud</option>
                            <option value="Est" <?php echo ($hebergement['ORIENTATIONHEB'] == 'Est') ? 'selected' : ''; ?>>Est</option>
                            <option value="Ouest" <?php echo ($hebergement['ORIENTATIONHEB'] == 'Ouest') ? 'selected' : ''; ?>>Ouest</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="annee">Année</label>
                        <input type="number" id="annee" name="annee" class="form-control" 
                               value="<?php echo htmlspecialchars($hebergement['ANNEEHEB']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="etat">État</label>
                        <select id="etat" name="etat" class="form-control">
                            <option value="Disponible" <?php echo ($hebergement['ETATHEB'] == 'Disponible') ? 'selected' : ''; ?>>Disponible</option>
                            <option value="En rénovation" <?php echo ($hebergement['ETATHEB'] == 'En rénovation') ? 'selected' : ''; ?>>En rénovation</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="internet" name="internet" value="1" 
                            <?php echo ($hebergement['INTERNET'] == 1) ? 'checked' : ''; ?>>
                        <label for="internet">Internet</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="photo">Nouvelle photo (laisser vide pour garder l'actuelle)</label>
                    <input type="file" id="photo" name="photo" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($hebergement['DESCRIHEB']); ?></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Modifier l'hébergement</button>
            </form>
        <?php else: ?>
        <?php endif; ?>
    </div>
</body>
</html>