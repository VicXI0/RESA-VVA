<?php
session_start();
require_once 'connexion.php';

// Vérifier gestionnaire
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'GES') {
    header('Location: login.php');
    exit;
}

if ($_POST && isset($_POST['num_resa']) && isset($_POST['nouvel_etat'])) {
    $num_resa = intval($_POST['num_resa']);
    $nouvel_etat = $_POST['nouvel_etat'];
    
    // Mettre à jour l'état
    $sql = "UPDATE resa SET CODEETATRESA = ? WHERE NORESA = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$nouvel_etat, $num_resa]);
    
    if ($success) {
        $_SESSION['message'] = "État de la réservation #$num_resa mis à jour";
    } else {
        $_SESSION['message'] = "Erreur lors de la modification";
    }
}

header('Location: gestion_reservations.php');
exit;