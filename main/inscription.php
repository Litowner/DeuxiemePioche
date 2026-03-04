<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un compte</title>
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

        <h2>Créer un compte</h2>

        <form action="#" method="POST">

            <div class="input-group">
                <label>Adresse mail</label>
                <input type="email" name="email" placeholder="Votre adresse mail" required>
            </div>

            <div class="input-group">
                <label>Numéro de téléphone</label>
                <input type="tel" name="telephone" placeholder="Votre numéro" required>
            </div>

            <div class="input-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="Votre mot de passe" required>
            </div>

            <div class="input-group">
                <label>Répéter le mot de passe</label>
                <input type="password" name="confirm_password" placeholder="Répétez le mot de passe" required>
            </div>

            <button type="submit" class="btn-login">Créer le compte</button>

        </form>

        <a href="connexion.php" class="btn-register">Déjà un compte ?</a>

    </div>

</main>

<footer>
    <div class="footer-links">
        <span>Aide et contact</span>
        <span>Conditions d'utilisation</span>
        <span>Politique de confidentialité</span>
        <span>Concept</span>
    </div>
    <p>© 2026 2eme-pioche tous droits réservés</p>
</footer>

</body>
</html>