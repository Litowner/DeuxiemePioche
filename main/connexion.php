<?php
session_start();

$message = "";

// Déconnexion
if (isset($_GET["logout"])) {
    $_SESSION = [];
    session_destroy();
    header("Location: connexion.php");
    exit;
}

if (isset($_POST["email"]) && isset($_POST["password"])) {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $content = file_get_contents("assets/data/users.csv");
    $lines = explode("\n", $content);

    // delimiteur CSV (mets ";" si ton fichier est en point-virgule)
    $delimiter = ",";

    // On saute le header (ligne 0)
    for ($i = 1; $i < count($lines); $i++) {

        $line = trim($lines[$i]);
        if ($line === "") continue;

        $data = str_getcsv($line, $delimiter);

        // Attendu : id,username,email,telephone,password_hash,created_at
        if (count($data) < 5) continue;

        $uuid = trim($data[0]);          // UUID
        $username = trim($data[1]);      // username
        $csvEmail = trim($data[2]);      // email
        $hash = trim($data[4]);          // password_hash

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<header>
    <div class="top-bar">

        <div class="menu-left">
            <button class="burger-btn" type="button" aria-label="Ouvrir le menu" aria-expanded="false">
                <span>Menu</span>
                <span class="burger"></span>
            </button>
        </div>

        <div class="logo">
            <a href="index.php">
                <img src="assets/images/logo.png" alt="2ème Pioche">
            </a>
        </div>

        <div class="login">
            <?php if(isset($_SESSION["username"])): ?>
                <a href="account.php"><?= htmlspecialchars($_SESSION["username"]); ?></a>
            <?php else: ?>
                <a href="connexion.php">Se connecter</a>
            <?php endif; ?>
        </div>

    </div>

    <!-- Overlay + Drawer -->
    <div class="menu-overlay" id="menuOverlay" hidden></div>

    <aside class="menu-drawer" id="menuDrawer" aria-hidden="true">
        <div class="drawer-top">
            <strong>Menu</strong>
            <button class="drawer-close" type="button" aria-label="Fermer le menu">✕</button>
        </div>

        <nav class="drawer-links">
            <a href="index.php?categorie=Vetements">Vêtements</a>
            <a href="index.php?categorie=Chaussures">Chaussures</a>
            <a href="index.php?categorie=Accessoires">Accessoires</a>
            <a href="index.php?categorie=Mobilier">Mobilier</a>

            <?php if(isset($_SESSION["user_id"])): ?>
                <a class="drawer-sep" href="account.php">Mon compte</a>
                <a class="drawer-logout" href="connexion.php?logout=1">Déconnexion</a>
            <?php endif; ?>
        </nav>
    </aside>

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

<script>
(function(){
  const btn = document.querySelector('.burger-btn');
  const drawer = document.getElementById('menuDrawer');
  const overlay = document.getElementById('menuOverlay');
  const closeBtn = document.querySelector('.drawer-close');

  if(!btn || !drawer || !overlay || !closeBtn) return;

  function openMenu(){
    drawer.classList.add('is-open');
    overlay.hidden = false;
    drawer.setAttribute('aria-hidden', 'false');
    btn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeMenu(){
    drawer.classList.remove('is-open');
    overlay.hidden = true;
    drawer.setAttribute('aria-hidden', 'true');
    btn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  btn.addEventListener('click', openMenu);
  closeBtn.addEventListener('click', closeMenu);
  overlay.addEventListener('click', closeMenu);

  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape') closeMenu();
  });

  drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
})();
</script>

</body>
</html>