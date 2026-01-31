<?php
session_start();
require_once 'connexion.php';

// Vérifier si gestionnaire
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'GES') {
    header('Location: login.php');
    exit;
}

$message = '';
$message_type = '';
$hebergements = [];

// Récupérer la liste des hébergements avec leurs types
$sql_hebergements = "SELECT h.*, t.NOMTYPEHEB 
                     FROM hebergement h 
                     JOIN type_heb t ON h.CODETYPEHEB = t.CODETYPEHEB 
                     ORDER BY h.NOMHEB";
$hebergements = $pdo->query($sql_hebergements)->fetchAll();

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer_suppression'])) {
    $noheb = $_POST['noheb'];
    
    // Vérifier si l'hébergement existe
    $sql_verif = "SELECT * FROM hebergement WHERE NOHEB = ?";
    $stmt_verif = $pdo->prepare($sql_verif);
    $stmt_verif->execute([$noheb]);
    $hebergement = $stmt_verif->fetch();
    
    if (!$hebergement) {
        $message = "L'hébergement n'existe pas";
        $message_type = 'error';
    } else {
        // Vérifier si l'hébergement a des réservations
        $sql_reservations = "SELECT COUNT(*) as nb_reservations FROM resa WHERE NOHEB = ?";
        $stmt_res = $pdo->prepare($sql_reservations);
        $stmt_res->execute([$noheb]);
        $nb_reservations = $stmt_res->fetch()['nb_reservations'];
        
        if ($nb_reservations > 0) {
            $message = "Impossible de supprimer cet hébergement : $nb_reservations réservation(s) existante(s)";
            $message_type = 'error';
        } else {
            try {
                // Supprimer la photo si elle existe
                if (!empty($hebergement['PHOTOHEB'])) {
                    $photo_path = 'img/' . $hebergement['PHOTOHEB'];
                    if (file_exists($photo_path)) {
                        unlink($photo_path);
                    }
                }
                
                // Supprimer l'hébergement
                $sql_delete = "DELETE FROM hebergement WHERE NOHEB = ?";
                $stmt_delete = $pdo->prepare($sql_delete);
                $success = $stmt_delete->execute([$noheb]);
                
                if ($success && $stmt_delete->rowCount() > 0) {
                    $message = "Hébergement supprimé avec succès !";
                    $message_type = 'success';
                    // Recharger la liste
                    $hebergements = $pdo->query($sql_hebergements)->fetchAll();
                } else {
                    $message = "Erreur lors de la suppression";
                    $message_type = 'error';
                }
            } catch (Exception $e) {
                $message = "Erreur: " . $e->getMessage();
                $message_type = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Supprimer des hébergements - RESA VVA</title>
    <link rel="stylesheet" href="supprimer_hebergement.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-retour"><i class="fas fa-arrow-left"></i> Retour à l'accueil</a>
        
        <h1><i class="fa-solid fa-trash-can"></i> Supprimer des hébergements</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats">
            <div class="stat-card">
                <i class="fas fa-home"></i>
                <span class="stat-number"><?php echo count($hebergements); ?></span>
                <span class="stat-label">Hébergements</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <span class="stat-number">
                    <?php echo count(array_filter($hebergements, fn($h) => $h['ETATHEB'] === 'Disponible')); ?>
                </span>
                <span class="stat-label">Disponibles</span>
            </div>
            <div class="stat-card">
                <i class="fas fa-tools"></i>
                <span class="stat-number">
                    <?php echo count(array_filter($hebergements, fn($h) => $h['ETATHEB'] === 'En rénovation')); ?>
                </span>
                <span class="stat-label">En rénovation</span>
            </div>
        </div>
        
        <div class="filters">
            <input type="text" id="searchInput" placeholder="Rechercher un hébergement..." class="search-input">
            <select id="filterEtat" class="filter-select">
                <option value="">Tous les états</option>
                <option value="Disponible">Disponible</option>
                <option value="En rénovation">En rénovation</option>
            </select>
            <select id="filterType" class="filter-select">
                <option value="">Tous les types</option>
                <?php
                $types_unique = array_unique(array_column($hebergements, 'NOMTYPEHEB'));
                foreach ($types_unique as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="table-container">
            <?php if (empty($hebergements)): ?>
                <div class="no-data">
                    <i class="fas fa-info-circle"></i>
                    <p>Aucun hébergement à afficher</p>
                    <a href="ajouter_hebergement.php" class="btn-ajouter">Ajouter un hébergement</a>
                </div>
            <?php else: ?>
                <table class="hebergement-table" id="hebergementTable">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Places/Surface/Tarif/semaine</th>
                            <th>État</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hebergements as $heb): ?>
                            <tr data-etat="<?php echo htmlspecialchars($heb['ETATHEB']); ?>"
                                data-type="<?php echo htmlspecialchars($heb['NOMTYPEHEB']); ?>"
                                data-nom="<?php echo strtolower(htmlspecialchars($heb['NOMHEB'])); ?>">
                                <td class="nom-cell">
                                    <div class="nom-wrapper">
                                        <?php if (!empty($heb['PHOTOHEB'])): ?>
                                            <img src="img/<?php echo htmlspecialchars($heb['PHOTOHEB']); ?>" 
                                                 alt="<?php echo htmlspecialchars($heb['NOMHEB']); ?>"
                                                 class="thumbnail"
                                                 onerror="this.style.display='none'">
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($heb['NOMHEB']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($heb['NOMTYPEHEB']); ?></td>
                                <td class="number-cell">
                                    <i class="fas fa-user-friends"></i>
                                    <?php echo htmlspecialchars($heb['NBPLACEHEB']); ?>
                                </td>
                                <td class="number-cell">
                                    <i class="fas fa-vector-square"></i>
                                    <?php echo htmlspecialchars($heb['SURFACEHEB'] ?? 'N/A'); ?> m²
                                </td>
                                <td class="tarif-cell">
                                    <i class="fas fa-euro-sign"></i>
                                    <?php echo number_format($heb['TARIFSEMHEB'], 2); ?> €
                                </td>
                                <td>
                                    <span class="etat-badge <?php echo strtolower(str_replace(' ', '-', $heb['ETATHEB'])); ?>">
                                        <i class="fas <?php echo $heb['ETATHEB'] === 'Disponible' ? 'fa-check' : 'fa-tools'; ?>"></i>
                                        <?php echo htmlspecialchars($heb['ETATHEB']); ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <div class="actions-wrapper">
                                        <a href="modifier_hebergements.php?id=<?php echo $heb['NOHEB']; ?>" 
                                           class="btn-modifier" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn-supprimer" 
                                                data-id="<?php echo $heb['NOHEB']; ?>"
                                                data-nom="<?php echo htmlspecialchars($heb['NOMHEB']); ?>"
                                                title="Supprimer">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Modal de confirmation -->
        <div id="confirmationModal" class="confirmation-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Confirmer la suppression</h3>
                    <button class="close-modal" onclick="fermerModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer l'hébergement :</p>
                    <p class="nom-hebergement-modal" id="nomHebergementModal"></p>
                    <div class="warning-box">
                        <i class="fas fa-exclamation-circle"></i>
                        <div>
                            <strong>Cette action est irréversible !</strong>
                            <p>Toutes les données associées seront définitivement supprimées.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="suppressionForm">
                        <input type="hidden" name="noheb" id="nohebInput">
                        <button type="button" class="btn-annuler" onclick="fermerModal()">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                        <button type="submit" name="confirmer_suppression" class="btn-confirmer">
                            <i class="fas fa-trash-alt"></i> Confirmer la suppression
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let currentHebId = null;
        let currentHebNom = null;
        
        function ouvrirModal(id, nom) {
            currentHebId = id;
            currentHebNom = nom;
            
            document.getElementById('nomHebergementModal').textContent = nom;
            document.getElementById('nohebInput').value = id;
            document.getElementById('confirmationModal').style.display = 'flex';
        }
        
        function fermerModal() {
            document.getElementById('confirmationModal').style.display = 'none';
            currentHebId = null;
            currentHebNom = null;
        }
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion des boutons de suppression
            const boutons = document.querySelectorAll('.btn-supprimer');
            boutons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nom = this.getAttribute('data-nom');
                    ouvrirModal(id, nom);
                });
            });
            
            // Fermer la modal en cliquant à l'extérieur
            document.getElementById('confirmationModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    fermerModal();
                }
            });
            
            // Fermer avec Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    fermerModal();
                }
            });
            
            // Filtres de recherche
            const searchInput = document.getElementById('searchInput');
            const filterEtat = document.getElementById('filterEtat');
            const filterType = document.getElementById('filterType');
            
            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const etatFilter = filterEtat.value;
                const typeFilter = filterType.value;
                
                const rows = document.querySelectorAll('#hebergementTable tbody tr');
                
                rows.forEach(row => {
                    const nom = row.getAttribute('data-nom');
                    const etat = row.getAttribute('data-etat');
                    const type = row.getAttribute('data-type');
                    
                    let show = true;
                    
                    // Filtre par recherche
                    if (searchTerm && !nom.includes(searchTerm)) {
                        show = false;
                    }
                    
                    // Filtre par état
                    if (etatFilter && etat !== etatFilter) {
                        show = false;
                    }
                    
                    // Filtre par type
                    if (typeFilter && type !== typeFilter) {
                        show = false;
                    }
                    
                    row.style.display = show ? '' : 'none';
                });
            }
            
            // Écouter les changements
            searchInput.addEventListener('input', filterTable);
            filterEtat.addEventListener('change', filterTable);
            filterType.addEventListener('change', filterTable);
            
            // Confirmer avec Entrée dans la modal
            document.getElementById('suppressionForm').addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && currentHebId) {
                    e.preventDefault();
                    this.submit();
                }
            });
        });
    </script>
</body>
</html>