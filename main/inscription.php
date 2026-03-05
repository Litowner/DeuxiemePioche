<?php
session_start();

$csvPath = __DIR__ . "/assets/data/users.csv";

$message = "";
$redirect = false;

function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Lis tous les utilisateurs depuis le CSV (avec en-tête).
 */
function readUsers($csvPath) {
    $users = [];

    if (!file_exists($csvPath)) return $users;

    if (($handle = fopen($csvPath, "r")) !== false) {
        $header = fgetcsv($handle);
        if (!$header) { fclose($handle); return $users; }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < count($header)) continue;
            $assoc = array_combine($header, $row);
            if (!empty($assoc["email"])) $users[] = $assoc;
        }
        fclose($handle);
    }
    return $users;
}

function emailExists($users, $email) {
    $email = strtolower(trim($email));
    foreach ($users as $u) {
        if (strtolower(trim($u["email"])) === $email) return true;
    }
    return false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $tel      = trim($_POST["telephone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm  = $_POST["confirm_password"] ?? "";

    if ($username === "" || $email === "" || $tel === "" || $password === "" || $confirm === "") {
        $message = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse Email invalide.";
    } elseif ($password !== $confirm) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        $users = readUsers($csvPath);

        if (emailExists($users, $email)) {
            $message = "Un compte avec cette adresse mail existe déjà.";
        } else {
            $id = generateUUID();
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $fp = fopen($csvPath, "a");
            if (!$fp) {
                $message = "Erreur : impossible d'écrire dans users.csv.";
            } else {
                if (flock($fp, LOCK_EX)) {
                    // CSV attendu : id,username,email,telephone,password_hash,created_at
                    fputcsv($fp, [$id, $username, $email, $tel, $hash, date("Y-m-d H:i:s")]);
                    fflush($fp);
                    flock($fp, LOCK_UN);
                    $message = "Compte créé avec succès !";
                    $redirect = true;
                } else {
                    $message = "Erreur : fichier occupé, réessayez.";
                }
                fclose($fp);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
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

        <h2>Créer un compte</h2>

        <form action="inscription.php" method="POST" autocomplete="on">

            <div class="input-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" placeholder="Votre nom d'utilisateur" required
                       value="<?= htmlspecialchars($_POST["username"] ?? ""); ?>">
            </div>

            <div class="input-group">
                <label>Adresse mail</label>
                <input type="email" name="email" placeholder="Votre adresse mail" required
                       value="<?= htmlspecialchars($_POST["email"] ?? ""); ?>">
            </div>

            <div class="input-group">
                <label>Numéro de téléphone</label>
                <input type="tel" name="telephone" placeholder="012 345 67 89" required
                       value="<?= htmlspecialchars($_POST["telephone"] ?? ""); ?>">
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
    <p>©2008-2026 2eme-pioche Service SA tous droits réservés</p>
</footer>

<?php if ($message !== ""): ?>
<script>
alert("<?= addslashes($message) ?>");
<?php if ($redirect): ?>
window.location.href = "connexion.php";
<?php endif; ?>
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