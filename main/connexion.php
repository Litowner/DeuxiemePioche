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
            <h1>2<span>ÈME</span><br>PIOCHE</h1>
        </div>

        <div class="login">
            <a href="connexion.php">Se connecter</a>
        </div>
    </div>
</header>

<main class="connexion-page">

    <div class="connexion-box">

        <h2>Connexion</h2>

        <form action="#" method="POST">

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
    <p>© 2026 2eme-pioche tous droits réservés</p>
</footer>

</body>
</html>