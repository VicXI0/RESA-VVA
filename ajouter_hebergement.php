<?php
session_start();
require_once 'connexion.php';

// Vérifier si gestionnaire
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'GES') {
    header('Location: login.php');
    exit;
}

// Récupérer les types d'hébergement
$sql_types = "SELECT * FROM type_heb ORDER BY NOMTYPEHEB";
$types_heb = $pdo->query($sql_types)->fetchAll();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $photo_name = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'img/';
                $photo_name = uniqid() . '_' . basename($_FILES['photo']['name']);
                move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name);
            }
            
            // Trouver le prochain ID
            $sql_max_id = "SELECT COALESCE(MAX(NOHEB), 0) + 1 as next_id FROM hebergement";
            $stmt_max = $pdo->query($sql_max_id);
            $next_id = $stmt_max->fetch()['next_id'];
            
            // Insertion en base
            $sql_insert = "INSERT INTO hebergement 
              (NOHEB, CODETYPEHEB, NOMHEB, NBPLACEHEB, SURFACEHEB, INTERNET, ANNEEHEB, SECTEURHEB, ORIENTATIONHEB, ETATHEB, DESCRIHEB, PHOTOHEB, TARIFSEMHEB) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql_insert);
            $success = $stmt->execute([
                $next_id,
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
                $tarif
            ]);
            
            if ($success) {
                $message = "Hébergement ajouté avec succès ! ID: " . $next_id;
                $message_type = 'success';
                $_POST = []; // Réinitialiser le formulaire
            } else {
                $message = "Erreur lors de l'ajout en base de données";
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
    <title>Gestion des hébergements - RESA VVA</title>
    <link rel="stylesheet" href="ajout_hebergement.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-retour">← Retour à l'accueil</a>
        
        <h1>Gestion des hébergements</h1>
        
        <div class="dropdown-container">
            <label class="dropdown-label">Sélectionnez une action :</label>
            <select class="action-dropdown" onchange="window.location.href=this.value;">
                <option value="ajouter_hebergement.php" selected>Ajouter un hébergement</option>
                <option value="modifier_hebergements.php">Modifier un hébergement</option>
                <option value="supprimer_hebergement.php">Supprimer un hébergement</option>
            </select>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" class="form-control" 
                       value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="type_heb">Type *</label>
                    <select id="type_heb" name="type_heb" class="form-control" required>
                        <option value="">Choisir</option>
                        <?php foreach ($types_heb as $type): ?>
                            <option value="<?php echo $type['CODETYPEHEB']; ?>">
                                <?php echo htmlspecialchars($type['NOMTYPEHEB']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="tarif">Tarif/semaine (€) *</label>
                    <input type="number" id="tarif" name="tarif" class="form-control" 
                           step="0.01" min="0" value="<?php echo $_POST['tarif'] ?? ''; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nb_places">Places *</label>
                    <input type="number" id="nb_places" name="nb_places" class="form-control" 
                           min="1" value="<?php echo $_POST['nb_places'] ?? ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="surface">Surface (m²)</label>
                    <input type="number" id="surface" name="surface" class="form-control" 
                           min="1" value="<?php echo $_POST['surface'] ?? ''; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="secteur">Secteur</label>
                    <select id="secteur" name="secteur" class="form-control">
                        <option value="Nord">Nord</option>
                        <option value="Sud">Sud</option>
                        <option value="Est">Est</option>
                        <option value="Ouest">Ouest</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="orientation">Orientation</label>
                    <select id="orientation" name="orientation" class="form-control">
                        <option value="Nord">Nord</option>
                        <option value="Sud">Sud</option>
                        <option value="Est">Est</option>
                        <option value="Ouest">Ouest</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="annee">Année</label>
                    <input type="number" id="annee" name="annee" class="form-control" 
                           value="<?php echo $_POST['annee'] ?? date('Y'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="etat">État</label>
                    <select id="etat" name="etat" class="form-control">
                        <option value="Disponible">Disponible</option>
                        <option value="En rénovation">En rénovation</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="internet" name="internet" value="1">
                    <label for="internet">Internet</label>
                </div>
            </div>
            
            <div class="form-group">
                <label for="photo">Photo</label>
                <input type="file" id="photo" name="photo" class="form-control">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Ajouter l'hébergement</button>
        </form>
    </div>
</body>
</html>