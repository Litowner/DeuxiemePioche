<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: connexion.php");
    exit;
}

$message = "";
$success = false;

$userId = $_SESSION["user_id"];
$userName = $_SESSION["username"] ?? "";

/* Stockage :
   - images : assets/users/<UUID>/articles/
   - json   : assets/data/articles.json
*/
$baseUsersDir = __DIR__ . "/assets/users";
$userArticlesDir = $baseUsersDir . "/" . $userId . "/articles";
$jsonPath = __DIR__ . "/assets/data/articles.json";

function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function sanitizeFilename($name) {
    $name = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $name);
    return trim($name, "._");
}

function readJsonArray($path) {
    if (!file_exists($path)) return [];
    $content = file_get_contents($path);
    if ($content === false || trim($content) === "") return [];
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function writeJsonArray($path, $arr) {
    $json = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $fp = fopen($path, "c+");
    if (!$fp) return false;
    if (!flock($fp, LOCK_EX)) { fclose($fp); return false; }

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, $json);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $titre = trim($_POST["titre"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $prix = trim($_POST["prix"] ?? "");
    $categorie = trim($_POST["categorie"] ?? "");

    $categoriesValides = ["Chaussures", "Vêtements", "Mobilier", "Accessoires"];

    if ($titre === "" || $description === "" || $prix === "" || $categorie === "") {
        $message = "Veuillez remplir tous les champs.";
    } elseif (!in_array($categorie, $categoriesValides, true)) {
        $message = "Catégorie invalide.";
    } elseif (!is_numeric($prix) || (float)$prix < 0) {
        $message = "Prix invalide.";
    } elseif (!isset($_FILES["image"]) || $_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
        $message = "Veuillez ajouter une image valide.";
    } else {
        // Préparer les dossiers de stockage
        if (!is_dir($baseUsersDir)) {
            $message = "Dossier assets/users introuvable.";
        } else {
            if (!is_dir($userArticlesDir)) {
                @mkdir($userArticlesDir, 0755, true);
            }

            if (!is_dir($userArticlesDir)) {
                $message = "Impossible de créer le dossier utilisateur pour les images.";
            } else {
                $tmp = $_FILES["image"]["tmp_name"];
                $original = $_FILES["image"]["name"] ?? "image";
                $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

                $allowed = ["jpg", "jpeg", "png", "webp"];
                if (!in_array($ext, $allowed, true)) {
                    $message = "Format d'image non autorisé (jpg, jpeg, png, webp).";
                } else {
                    $articleId = generateUUID();
                    $safeBase = sanitizeFilename(pathinfo($original, PATHINFO_FILENAME));
                    if ($safeBase === "") $safeBase = "image";

                    $fileName = $articleId . "_" . $safeBase . "." . $ext;
                    $destPath = $userArticlesDir . "/" . $fileName;

                    if (!move_uploaded_file($tmp, $destPath)) {
                        $message = "Erreur lors de l'upload de l'image.";
                    } else {
                        // JSON : s'assurer que assets/data existe
                        $dataDir = dirname($jsonPath);
                        if (!is_dir($dataDir)) {
                            @mkdir($dataDir, 0755, true);
                        }

                        $articles = readJsonArray($jsonPath);

                        $relativeImagePath = "assets/users/" . $userId . "/articles/" . $fileName;

                        $article = [
                            "id" => $articleId,
                            "user_id" => $userId,
                            "username" => $userName,
                            "titre" => $titre,
                            "description" => $description,
                            "prix" => number_format((float)$prix, 2, ".", ""),
                            "categorie" => $categorie,
                            "image" => $relativeImagePath,
                            "created_at" => date("Y-m-d H:i:s")
                        ];

                        $articles[] = $article;

                        if (!writeJsonArray($jsonPath, $articles)) {
                            $message = "Impossible d'enregistrer l'article (JSON).";
                        } else {
                            $message = "Article créé avec succès !";
                            $success = true;
                        }
                    }
                }
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
    <title>Mon compte</title>
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
            <a href="account.php"><?= htmlspecialchars($_SESSION["username"] ?? "Mon compte"); ?></a>
        </div>

    </div>

    <div class="menu-overlay" id="menuOverlay" hidden></div>

    <aside class="menu-drawer" id="menuDrawer" aria-hidden="true">
        <div class="drawer-top">
            <strong>Menu</strong>
            <button class="drawer-close" type="button" aria-label="Fermer le menu">✕</button>
        </div>

        <nav class="drawer-links">
            <a href="vetements.php">Vêtements</a>
            <a href="chaussures.php">Chaussures</a>
            <a href="accessoires.php">Accessoires</a>
            <a href="mobilier.php">Mobilier</a>

            <a class="drawer-sep" href="account.php">Mon compte</a>
            <a class="drawer-logout" href="connexion.php?logout=1">Déconnexion</a>
        </nav>
    </aside>
</header>

<main class="connexion-page">
    <div class="connexion-box">

        <h2>Créer un article</h2>

        <form action="account.php" method="POST" enctype="multipart/form-data" autocomplete="on">

            <div class="input-group">
                <label>Titre</label>
                <input type="text" name="titre" placeholder="Ex: Manteau en laine" required
                       value="<?= htmlspecialchars($_POST["titre"] ?? ""); ?>">
            </div>

            <div class="input-group">
                <label>Description</label>
                <textarea name="description" rows="4" placeholder="Décrivez votre article" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #ccc;"><?= htmlspecialchars($_POST["description"] ?? ""); ?></textarea>
            </div>

            <div class="input-group">
                <label>Prix (CHF)</label>
                <input type="number" step="0.01" min="0" name="prix" placeholder="Ex: 25.00" required
                       value="<?= htmlspecialchars($_POST["prix"] ?? ""); ?>">
            </div>

            <div class="input-group">
                <label>Catégorie</label>
                <select name="categorie" required style="width:100%;padding:10px;border-radius:6px;border:1px solid #ccc;">
                    <option value="" disabled <?= empty($_POST["categorie"]) ? "selected" : ""; ?>>Choisir une catégorie</option>
                    <option value="Chaussures"  <?= (($_POST["categorie"] ?? "") === "Chaussures") ? "selected" : ""; ?>>Chaussure</option>
                    <option value="Vêtements"   <?= (($_POST["categorie"] ?? "") === "Vêtements") ? "selected" : ""; ?>>Vêtement</option>
                    <option value="Mobilier"    <?= (($_POST["categorie"] ?? "") === "Mobilier") ? "selected" : ""; ?>>Mobilier</option>
                    <option value="Accessoires" <?= (($_POST["categorie"] ?? "") === "Accessoires") ? "selected" : ""; ?>>Accessoires</option>
                </select>
            </div>

            <div class="input-group">
                <label>Image</label>
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp" required>
            </div>

            <button type="submit" class="btn-login">Publier l'article</button>

        </form>

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

<?php if ($message !== ""): ?>
<script>
alert("<?= addslashes($message) ?>");
<?php if ($success): ?>
window.location.href = "account.php";
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