<?php
session_start();
require_once 'connexion.php';

// Vérifier que l'utilisateur est gestionnaire
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'GES') {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';
$hebergement = null;

// Récupérer tous les hébergements
$sql_all_hebergements = "SELECT h.*, t.NOMTYPEHEB 
                         FROM hebergement h 
                         JOIN type_heb t ON h.CODETYPEHEB = t.CODETYPEHEB 
                         ORDER BY h.NOMHEB";
$all_hebergements = $pdo->query($sql_all_hebergements)->fetchAll();

// Récupérer les types d'hébergement
$types_heb = $pdo->query("SELECT * FROM type_heb ORDER BY NOMTYPEHEB")->fetchAll();

// Action sélectionnée via le dropdown
$action = $_GET['action'] ?? 'modifier'; // default = modifier

// Si un hébergement est sélectionné pour modification ou suppression
if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT h.*, t.NOMTYPEHEB FROM hebergement h 
                           JOIN type_heb t ON h.CODETYPEHEB = t.CODETYPEHEB 
                           WHERE h.NOHEB = ?");
    $stmt->execute([$id]);
    $hebergement = $stmt->fetch();
    if (!$hebergement) {
        $message = "Hébergement non trouvé !";
        $message_type = 'error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modifier'])) {

    $id = $_POST['noheb'];
    $nom = $_POST['nom'];
    $type = $_POST['type_heb'];
    $places = $_POST['nb_places'];
    $tarif = $_POST['tarif'];
    $etat = $_POST['etat'];

    // Requête SQL UPDATE
    $sql = "UPDATE hebergement 
            SET NOMHEB = ?, 
                CODETYPEHEB = ?, 
                NBPLACEHEB = ?, 
                TARIFSEMHEB = ?, 
                ETATHEB = ?
            WHERE NOHEB = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nom, $type, $places, $tarif, $etat, $id]);

    $message = "Modification réussie";
    $message_type = "success";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des hébergements - RESA VVA</title>
    <link rel="stylesheet" href="modifier_hebergements.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<div class="container">
    <a href="index.php" class="btn-retour"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a>

    <h1><i class="fas fa-cogs"></i> Gestion des hébergements</h1>

    <?php if ($message): ?>
        <div class="message <?= $message_type ?>">
            <i class="fas <?= $message_type=='success'?'fa-check-circle':'fa-exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Dropdown Actions -->
    <div class="dropdown-container">
        <label for="actionSelect" class="dropdown-label">Choisir une action</label>
        <select id="actionSelect" class="action-dropdown" onchange="location = this.value;">
            <option value="?action=modifier" <?= $action=='modifier'?'selected':'' ?>>Modifier un hébergement</option>
            <option value="ajouter_hebergement.php" <?= $action=='ajouter'?'selected':'' ?>>Ajouter un hébergement</option>
            <option value="supprimer_hebergement.php" <?= $action=='supprimer'?'selected':'' ?>>Supprimer un hébergement</option>
        </select>
    </div>

    <?php if ($action === 'modifier'): ?>
        <!-- Tableau des hébergements pour modification -->
        <div class="current-info">
            <h3>Liste des hébergements</h3>
            <?php if (!empty($all_hebergements)): ?>
                <table class="hebergement-table" style="width:100%; border-collapse: collapse;">
                    <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Places</th>
                        <th>État</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($all_hebergements as $heb): ?>
                        <tr>
                            <td><?= htmlspecialchars($heb['NOMHEB']) ?></td>
                            <td><?= htmlspecialchars($heb['NOMTYPEHEB']) ?></td>
                            <td><?= htmlspecialchars($heb['NBPLACEHEB']) ?></td>
                            <td>
                                <span class="etat-badge <?= strtolower(str_replace(' ', '-', $heb['ETATHEB'])) ?>">
                                    <?= htmlspecialchars($heb['ETATHEB']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="?action=modifier&id=<?= $heb['NOHEB'] ?>" class="btn-submit" style="padding:8px 15px; font-size:0.9em;">
                                    <i class="fas fa-edit"></i> Modifier
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="error-message">Aucun hébergement disponible.</p>
            <?php endif; ?>
        </div>

        <!-- Formulaire de modification -->
        <?php if ($hebergement): ?>
            <div class="current-info">
                <h3>Modifier l'hébergement "<?= htmlspecialchars($hebergement['NOMHEB']) ?>"</h3>

                <?php if (!empty($hebergement['PHOTOHEB'])): ?>
                    <div class="current-photo">
                        <img src="img/<?= $hebergement['PHOTOHEB'] ?>" alt="Photo actuelle">
                        <p>Photo actuelle</p>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="modifier" value="1">
                    <input type="hidden" name="noheb" value="<?= $hebergement['NOHEB'] ?>">

                    <div class="form-group">
                        <label for="nom">Nom *</label>
                        <input type="text" name="nom" id="nom" class="form-control" required value="<?= htmlspecialchars($hebergement['NOMHEB']) ?>">
                    </div>

                    <div class="form-group">
                        <label for="type_heb">Type *</label>
                        <select name="type_heb" id="type_heb" class="form-control" required>
                            <option value="">Choisir</option>
                            <?php foreach ($types_heb as $type): ?>
                                <option value="<?= $type['CODETYPEHEB'] ?>" <?= ($hebergement['CODETYPEHEB']==$type['CODETYPEHEB'])?'selected':'' ?>>
                                    <?= htmlspecialchars($type['NOMTYPEHEB']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="tarif">Tarif/semaine (€) *</label>
                            <input type="number" name="tarif" id="tarif" class="form-control" step="0.01" min="0" value="<?= htmlspecialchars($hebergement['TARIFSEMHEB']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="nb_places">Places *</label>
                            <input type="number" name="nb_places" id="nb_places" class="form-control" min="1" value="<?= htmlspecialchars($hebergement['NBPLACEHEB']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="etat">État</label>
                        <select name="etat" id="etat" class="form-control">
                            <option value="Disponible" <?= ($hebergement['ETATHEB']=='Disponible')?'selected':'' ?>>Disponible</option>
                            <option value="En rénovation" <?= ($hebergement['ETATHEB']=='En rénovation')?'selected':'' ?>>En rénovation</option>
                        </select>
                    </div>

                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="internet" id="internet" value="1" <?= ($hebergement['INTERNET']==1)?'checked':'' ?>>
                        <label for="internet">Internet</label>
                    </div>

                    <div class="form-group">
                        <label for="photo">Nouvelle photo</label>
                        <input type="file" name="photo" id="photo">
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control"><?= htmlspecialchars($hebergement['DESCRIHEB']) ?></textarea>
                    </div>

                    <button type="submit" class="btn-submit">Modifier l'hébergement</button>
                </form>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
    // Pour le dropdown navigation
    const dropdown = document.getElementById('actionSelect');
    dropdown.addEventListener('change', () => {
        location.href = dropdown.value;
    });
</script>
</body>
</html>
