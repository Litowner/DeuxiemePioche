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

/*
  Stockage :
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
function safeUnlinkIfInsideProject($relativePath) {
    $relativePath = ltrim($relativePath, "/");
    if (strpos($relativePath, "assets/") !== 0) return false;

    $abs = __DIR__ . "/" . $relativePath;
    if (file_exists($abs) && is_file($abs)) {
        return @unlink($abs);
    }
    return false;
}

/* ===== HEIC/HEIF conversion helpers ===== */
function hasCommand($cmd) {
    $isWin = (stripos(PHP_OS, 'WIN') === 0);
    $probe = $isWin ? ("where " . escapeshellarg($cmd) . " 2>NUL") : ("command -v " . escapeshellarg($cmd) . " 2>/dev/null");
    $out = @shell_exec($probe);
    return !empty($out);
}
function convertHeicToJpg($srcTmpPath, $destJpgPath, &$error = "") {
    // 1) Imagick (if available + HEIC support)
    if (class_exists('Imagick')) {
        try {
            $img = new Imagick();
            $img->readImage($srcTmpPath);
            $img->setImageFormat('jpeg');
            $img->setImageCompressionQuality(88);
            $img->writeImage($destJpgPath);
            $img->clear();
            $img->destroy();
            return true;
        } catch (Throwable $e) {
            $error = "Imagick ne supporte pas HEIC (ou erreur conversion).";
        }
    }

    // 2) CLI fallback: ImageMagick (magick/convert)
    $cmd = null;
    if (hasCommand("magick")) $cmd = "magick";
    elseif (hasCommand("convert")) $cmd = "convert";

    if ($cmd) {
        $src = escapeshellarg($srcTmpPath);
        $dst = escapeshellarg($destJpgPath);

        // auto-orient keeps iPhone orientation
        $command = $cmd . " " . $src . " -auto-orient -quality 88 " . $dst . " 2>&1";
        $out = @shell_exec($command);

        if (file_exists($destJpgPath) && filesize($destJpgPath) > 0) {
            return true;
        }

        $error = "ImageMagick n'a pas pu convertir (libheif manquant ?). Sortie: " . trim((string)$out);
        return false;
    }

    $error = "Aucun convertisseur HEIC disponible (Imagick/libheif ou ImageMagick).";
    return false;
}

/* ===================== SUPPRESSION ARTICLE (POST) ===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_article_id"])) {
    $deleteId = trim($_POST["delete_article_id"]);

    $articles = readJsonArray($jsonPath);
    $newArticles = [];
    $deleted = false;
    $imageToDelete = "";

    foreach ($articles as $a) {
        if (($a["id"] ?? "") === $deleteId) {
            if (($a["user_id"] ?? "") === $userId) {
                $deleted = true;
                $imageToDelete = $a["image"] ?? "";
                continue;
            }
        }
        $newArticles[] = $a;
    }

    if (!$deleted) {
        $message = "Suppression impossible (annonce introuvable ou non autorisée).";
    } else {
        if (!writeJsonArray($jsonPath, $newArticles)) {
            $message = "Erreur lors de la suppression (JSON).";
        } else {
            if ($imageToDelete !== "") {
                safeUnlinkIfInsideProject($imageToDelete);
            }
            $message = "Annonce supprimée.";
            $success = true;
        }
    }
}

/* ===================== Création annonce ===================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["delete_article_id"])) {
    $titre = trim($_POST["titre"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $prix = trim($_POST["prix"] ?? "");
    $categorie = trim($_POST["categorie"] ?? "");

    $categoriesValides = ["Chaussures","Vêtements","Mobilier","Accessoires","Sport","Jouets","Électronique"];

    if ($titre === "" || $description === "" || $prix === "" || $categorie === "") {
        $message = "Veuillez remplir tous les champs.";
    } elseif (!in_array($categorie, $categoriesValides, true)) {
        $message = "Catégorie invalide.";
    } elseif (!is_numeric($prix) || (float)$prix < 0) {
        $message = "Prix invalide.";
    } elseif (!isset($_FILES["image"]) || $_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
        $message = "Veuillez ajouter une image valide.";
    } else {
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

                $allowed = ["jpg", "jpeg", "png", "webp", "heic", "heif"];
                if (!in_array($ext, $allowed, true)) {
                    $message = "Format d'image non autorisé (jpg, jpeg, png, webp, heic).";
                } else {
                    $articleId = generateUUID();
                    $safeBase = sanitizeFilename(pathinfo($original, PATHINFO_FILENAME));
                    if ($safeBase === "") $safeBase = "image";

                    // HEIC/HEIF -> convert to JPG
                    if ($ext === "heic" || $ext === "heif") {
                        $fileName = $articleId . "_" . $safeBase . ".jpg";
                        $destPath = $userArticlesDir . "/" . $fileName;

                        $err = "";
                        if (!convertHeicToJpg($tmp, $destPath, $err)) {
                            $message = "Impossible de convertir l'image HEIC en JPG. " . $err;
                        } else {
                            $relativeImagePath = "assets/users/" . $userId . "/articles/" . $fileName;

                            $dataDir = dirname($jsonPath);
                            if (!is_dir($dataDir)) {
                                @mkdir($dataDir, 0755, true);
                            }

                            $articles = readJsonArray($jsonPath);

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
                                $message = "Impossible d'enregistrer l'annonce (JSON).";
                            } else {
                                $message = "Annonce créée avec succès !";
                                $success = true;
                            }
                        }
                    } else {
                        // normal upload
                        $fileName = $articleId . "_" . $safeBase . "." . $ext;
                        $destPath = $userArticlesDir . "/" . $fileName;

                        if (!move_uploaded_file($tmp, $destPath)) {
                            $message = "Erreur lors de l'upload de l'image.";
                        } else {
                            $relativeImagePath = "assets/users/" . $userId . "/articles/" . $fileName;

                            $dataDir = dirname($jsonPath);
                            if (!is_dir($dataDir)) {
                                @mkdir($dataDir, 0755, true);
                            }

                            $articles = readJsonArray($jsonPath);

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
                                $message = "Impossible d'enregistrer l'annonce (JSON).";
                            } else {
                                $message = "Annonce créée avec succès !";
                                $success = true;
                            }
                        }
                    }
                }
            }
        }
    }
}

/* ===================== Mes annonces ===================== */
$allArticles = readJsonArray($jsonPath);
$myArticles = [];

foreach ($allArticles as $a) {
    if (($a["user_id"] ?? "") === $userId) {
        $myArticles[] = $a;
    }
}

usort($myArticles, function($a, $b){
    return strcmp(($b["created_at"] ?? ""), ($a["created_at"] ?? ""));
});
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
      <a href="index.php"><img src="assets/images/logo.png" alt="2ème Pioche"></a>
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

            <a href="index.php" class="drawer-home">Accueil</a>

            <a href="categorie.php?cat=vetements">Vêtements</a>
            <a href="categorie.php?cat=chaussures">Chaussures</a>
            <a href="categorie.php?cat=accessoires">Accessoires</a>
            <a href="categorie.php?cat=mobilier">Mobilier</a>

            <a href="categorie.php?cat=sport">Sport</a>
            <a href="categorie.php?cat=jouets">Jouets</a>
            <a href="categorie.php?cat=electronique">Électronique</a>

            <?php if(isset($_SESSION["user_id"])): ?>

                <a class="drawer-sep" href="account.php">Mes annonces</a>
                <a class="drawer-logout" href="connexion.php?logout=1">Se déconnecter</a>

            <?php endif; ?>

        </nav>
  </aside>
</header>

<main class="account-page">

  <section class="connexion-page">
    <div class="connexion-box">
      <h2>Créer une annonce</h2>

      <form action="account.php" method="POST" enctype="multipart/form-data" autocomplete="on">
        <div class="input-group">
          <label>Titre</label>
          <input type="text" name="titre" placeholder="Ex: Manteau en laine" required
                 value="<?= htmlspecialchars($_POST["titre"] ?? ""); ?>">
        </div>

        <div class="input-group">
          <label>Description</label>
          <textarea name="description" rows="4" placeholder="Décrivez votre annonce" required
                    style="width:100%;padding:10px;border-radius:6px;border:1px solid #ccc;"><?= htmlspecialchars($_POST["description"] ?? ""); ?></textarea>
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
                <option value="Chaussures" <?= (($_POST["categorie"] ?? "") === "Chaussures") ? "selected" : ""; ?>>Chaussures</option>
                <option value="Vêtements" <?= (($_POST["categorie"] ?? "") === "Vêtements") ? "selected" : ""; ?>>Vêtements</option>
                <option value="Mobilier" <?= (($_POST["categorie"] ?? "") === "Mobilier") ? "selected" : ""; ?>>Mobilier</option>
                <option value="Accessoires" <?= (($_POST["categorie"] ?? "") === "Accessoires") ? "selected" : ""; ?>>Accessoires</option>
                <option value="Sport" <?= (($_POST["categorie"] ?? "") === "Sport") ? "selected" : ""; ?>>Sport</option>
                <option value="Jouets" <?= (($_POST["categorie"] ?? "") === "Jouets") ? "selected" : ""; ?>>Jouets</option>
                <option value="Électronique" <?= (($_POST["categorie"] ?? "") === "Électronique") ? "selected" : ""; ?>>Électronique</option>
                </select>
        </div>

        <div class="input-group">
          <label>Image</label>
          <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp,.heic,.heif" required>
        </div>

        <button type="submit" class="btn-login">Publier l'article</button>
      </form>
    </div>
  </section>

  <section class="my-articles">
    <div class="my-articles-head">
      <h2>Mes annonces</h2>
    </div>

    <?php if (empty($myArticles)): ?>
      <p class="my-articles-empty">Vous n'avez pas encore publié d'annonce.</p>
    <?php else: ?>
      <div class="my-articles-grid">
        <?php foreach ($myArticles as $a): ?>
          <article class="my-article-card">
            <div class="my-article-img">
              <img src="<?= htmlspecialchars($a["image"] ?? "") ?>" alt="<?= htmlspecialchars($a["titre"] ?? "") ?>">
            </div>

            <div class="my-article-info">
              <h3><?= htmlspecialchars($a["titre"] ?? "") ?></h3>

              <div class="my-article-meta">
                <span><?= htmlspecialchars($a["categorie"] ?? "") ?></span>
                <strong>CHF <?= htmlspecialchars($a["prix"] ?? "0.00") ?></strong>
              </div>

              <div class="my-article-actions">
                <a class="buy-btn" href="article.php?id=<?= urlencode($a["id"]) ?>">Consulter</a>

                <form method="POST" action="account.php" class="delete-form">
                  <input type="hidden" name="delete_article_id" value="<?= htmlspecialchars($a["id"]) ?>">
                  <button type="button" class="btn-delete js-open-delete">Supprimer</button>
                  <button type="submit" class="js-submit-delete" hidden></button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</main>

<footer>
  <div class="footer-links">
    <span>Aide et contact</span>
    <span>Conditions d'utilisation</span>
    <span>Politique de confidentialité</span>
    <span>Concept</span>
  </div>
  <p>© 2026 2eme-pioche</p>
</footer>

<!-- Modal suppression -->
<div class="modal-overlay" id="deleteModal" hidden>
  <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="deleteTitle">
    <h3 id="deleteTitle">Supprimer l’article ?</h3>
    <p>Cette action est définitive. Voulez-vous continuer ?</p>

    <div class="modal-actions">
      <button type="button" class="modal-btn cancel" id="deleteCancel">Annuler</button>
      <button type="button" class="modal-btn confirm" id="deleteConfirm">Supprimer</button>
    </div>
  </div>
</div>

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
  // ===== Burger =====
  const btn = document.querySelector('.burger-btn');
  const drawer = document.getElementById('menuDrawer');
  const overlay = document.getElementById('menuOverlay');
  const closeBtn = document.querySelector('.drawer-close');

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

  if(btn && drawer && overlay && closeBtn){
    btn.addEventListener('click', openMenu);
    closeBtn.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);
    document.addEventListener('keydown', (e) => { if(e.key === 'Escape') closeMenu(); });
    drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', closeMenu));
  }

  // ===== Modal suppression + animation =====
  const modal = document.getElementById('deleteModal');
  const cancel = document.getElementById('deleteCancel');
  const confirmBtn = document.getElementById('deleteConfirm');

  if(!modal || !cancel || !confirmBtn) return;

  let currentForm = null;

  function openModal(form){
    currentForm = form;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    confirmBtn.focus();
  }

  function closeModal(){
    modal.hidden = true;
    document.body.style.overflow = '';
    currentForm = null;
  }

  document.querySelectorAll('.js-open-delete').forEach(b => {
    b.addEventListener('click', () => {
      const form = b.closest('form');
      if(form) openModal(form);
    });
  });

  cancel.addEventListener('click', closeModal);
  modal.addEventListener('click', (e) => {
    if(e.target === modal) closeModal();
  });

  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape' && !modal.hidden) closeModal();
  });

  confirmBtn.addEventListener('click', () => {
    if(!currentForm) return;

    const card = currentForm.closest(".my-article-card");
    if(card) card.classList.add("removing");

    setTimeout(() => {
      const hiddenSubmit = currentForm.querySelector('.js-submit-delete');
      if(hiddenSubmit) hiddenSubmit.click();
    }, 300);
  });
})();
</script>

</body>
</html>