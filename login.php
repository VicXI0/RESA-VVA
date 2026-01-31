<?php
session_start();
require_once 'connexion.php';

$errors = [];

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($username === '' || $password === '') {
        $errors[] = "Veuillez remplir tous les champs.";
    } else {
        // Vérifier utilisateur dans la table COMPTE
        $sql = "SELECT * FROM COMPTE WHERE USER = :u AND MDP = :p LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':u' => $username,
            ':p' => $password
        ]);
        $user = $stmt->fetch();

        if ($user) {
            // Stockage en session - CORRECTION DES NOMS DE COLONNES
            $_SESSION['user_id'] = $user['USER'];           // USER est la clé primaire
            $_SESSION['username'] = $user['USER'];          // Nom d'utilisateur
            $_SESSION['user_type'] = $user['TYPECOMPTE'];   // VAC, GES 
            $_SESSION['nom_complet'] = trim(($user['NOMCPTE'] ?? '') . ' ' . ($user['PRENOMCPTE'] ?? ''));

            // Redirection vers la page demandée ou l'accueil
            $redirectUrl = isset($_POST['redirect']) ? $_POST['redirect'] : 'index.php';
            header("Location: " . $redirectUrl);
            exit;
        } else {
            $errors[] = "Nom d'utilisateur ou mot de passe invalide.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - RESA VVA</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>RESA VVA</h1>
            <p>Village Vacances Azur</p>
        </div>
        
        <h2>Connexion</h2>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach($errors as $e): ?>
                    <p>❌ <?php echo htmlspecialchars($e); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="login-form">
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
            
            <div class="form-group">
                <label for="username">Nom d'utilisateur</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    required 
                    placeholder="Entrez votre nom d'utilisateur"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    placeholder="Entrez votre mot de passe"
                    autocomplete="current-password">
            </div>
            
            <button type="submit">Se connecter →</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>