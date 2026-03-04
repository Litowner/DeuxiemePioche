<?php
session_start();

$message = "";

// Déconnexion
if (isset($_GET["logout"])) {
    session_destroy();
    header("Location: connexion.php");
    exit;
}

if (isset($_POST["email"]) && isset($_POST["password"])) {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $content = file_get_contents("assets/data/users.csv");
    $lines = explode("\n", $content);

    // delimiteur CSV
    $delimiter = ",";

    // On saute le header (ligne 0)
    for ($i = 1; $i < count($lines); $i++) {

        $line = trim($lines[$i]);
        if ($line === "") continue;

        $data = str_getcsv($line, $delimiter);

        // Sécurité : évite erreurs si ligne incomplète
        if (count($data) < 5) continue;

        $uuid = trim($data[0]);         // id (UUID)
        $username = trim($data[1]);     // username (colonne 2)
        $csvEmail = trim($data[2]);      // email (colonne 3)
        $hash = trim($data[4]);          // password_hash (colonne 5)
        
        if (strtolower($email) === strtolower($csvEmail) && password_verify($password, $hash)) {

            $_SESSION["user_id"] = $uuid;
            $_SESSION["user_email"] = $csvEmail;
            $_SESSION["username"] = $username;

            header("Location: index.php");
            exit;
        }
    }

    $message = "Erreur de connexion, vérifiez vos identifiants !";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header>
    <div class="top-bar">
        <div class="menu-left">
            <span>Menu</span>
            <div class="burger"></div>
        </div>

        <div class="logo">
            <a href="index.php">
                <img src="assets/images/logo.png" alt="2ème Pioche">
            </a>
        </div>

        <div class="login">
            <a href="connexion.php">Se connecter</a>
        </div>
    </div>
</header>

<main class="connexion-page">
    <div class="connexion-box">

        <h2>Connexion</h2>

        <form action="connexion.php" method="POST" autocomplete="on">

            <div class="input-group">
                <label>Adresse mail</label>
                <input type="email" name="email" placeholder="Votre email" required>
            </div>

            <div class="input-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="Votre mot de passe" required>
            </div>

            <button type="submit" class="btn-login">Connexion</button>

        </form>

        <a href="inscription.php" class="btn-register">Pas de compte ?</a>

    </div>
</main>

<footer>
    <div class="footer-links">
        <span>Aide et contact</span>
        <span>Conditions d'utilisation</span>
        <span>Politique de confidentialité</span>
        <span>Concept</span>
    </div>
    <p>© 2008-2026 2eme-pioche Service SA tous droits réservés</p>
</footer>

<?php if ($message): ?>
<script>
alert("<?= addslashes($message) ?>");
</script>
<?php endif; ?>

</body>
</html>