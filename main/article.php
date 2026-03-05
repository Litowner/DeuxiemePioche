<?php
session_start();

$articleId = trim($_GET["id"] ?? "");
if ($articleId === "") {
    header("Location: index.php");
    exit;
}

$jsonPath = __DIR__ . "/assets/data/articles.json";
$csvPath  = __DIR__ . "/assets/data/users.csv";

function readJsonArray($path) {
    if (!file_exists($path)) return [];
    $content = file_get_contents($path);
    if ($content === false || trim($content) === "") return [];
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function detectDelimiter($line) {
    $comma = substr_count($line, ",");
    $semi  = substr_count($line, ";");
    return ($semi > $comma) ? ";" : ",";
}

function getUserPhoneById($csvPath, $userId) {
    if (!file_exists($csvPath)) return "";

    $fhPeek = fopen($csvPath, "r");
    if (!$fhPeek) return "";
    $firstLine = fgets($fhPeek);
    fclose($fhPeek);

    $delimiter = $firstLine ? detectDelimiter($firstLine) : ",";

    if (($handle = fopen($csvPath, "r")) === false) return "";

    $header = fgetcsv($handle, 0, $delimiter);
    if (!$header) { fclose($handle); return ""; }

    // Normalise header
    $header = array_map("trim", $header);

    while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        if (count($row) < count($header)) continue;
        $row = array_map("trim", $row);
        $assoc = array_combine($header, $row);

        if (($assoc["id"] ?? "") === $userId) {
            fclose($handle);
            return $assoc["telephone"] ?? "";
        }
    }

    fclose($handle);
    return "";
}

/* 1) Trouver l'article */
$articles = readJsonArray($jsonPath);
$article = null;

foreach ($articles as $a) {
    if (($a["id"] ?? "") === $articleId) {
        $article = $a;
        break;
    }
}

if (!$article) {
    header("Location: index.php");
    exit;
}

/* 2) Récupérer le téléphone du vendeur via user_id */
$sellerId = $article["user_id"] ?? "";
$sellerPhone = $sellerId ? getUserPhoneById($csvPath, $sellerId) : "";

/* Champs */
$titre = $article["titre"] ?? "";
$description = $article["description"] ?? "";
$prix = $article["prix"] ?? "";
$categorie = $article["categorie"] ?? "";
$image = $article["image"] ?? "";
$username = $article["username"] ?? "";
$createdAt = $article["created_at"] ?? "";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($titre) ?></title>
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
      <?php if(isset($_SESSION["username"])): ?>
        <a href="account.php"><?= htmlspecialchars($_SESSION["username"]) ?></a>
      <?php else: ?>
        <a href="connexion.php">Se connecter</a>
      <?php endif; ?>
    </div>

  </div>

  <div class="menu-overlay" id="menuOverlay" hidden></div>
  <aside class="menu-drawer" id="menuDrawer" aria-hidden="true">
    <div class="drawer-top">
      <strong>Menu</strong>
      <button class="drawer-close" type="button" aria-label="Fermer le menu">✕</button>
    </div>
    <nav class="drawer-links">
      <a href="categorie.php?cat=vetements">Vêtements</a>
      <a href="categorie.php?cat=chaussures">Chaussures</a>
      <a href="categorie.php?cat=accessoires">Accessoires</a>
      <a href="categorie.php?cat=mobilier">Mobilier</a>

      <?php if(isset($_SESSION["user_id"])): ?>
        <a class="drawer-sep" href="account.php">Mon compte</a>
        <a class="drawer-logout" href="connexion.php?logout=1">Déconnexion</a>
      <?php endif; ?>
    </nav>
  </aside>
</header>

<main class="article-page">

  <section class="article-wrap">

    <div class="article-image">
      <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($titre) ?>">
    </div>

    <div class="article-info">
      <h1><?= htmlspecialchars($titre) ?></h1>

      <div class="article-meta">
        <span><strong>Catégorie :</strong> <?= htmlspecialchars($categorie) ?></span>
        <?php if($createdAt !== ""): ?>
          <span><strong>Publié :</strong> <?= htmlspecialchars($createdAt) ?></span>
        <?php endif; ?>
      </div>

      <div class="article-price">CHF <?= htmlspecialchars($prix) ?></div>

      <p class="article-desc"><?= nl2br(htmlspecialchars($description)) ?></p>

      <div class="article-seller">
        <h3>Vendeur</h3>
        <p><strong>Nom :</strong> <?= htmlspecialchars($username) ?></p>
        <p><strong>Téléphone :</strong> <?= htmlspecialchars($sellerPhone !== "" ? $sellerPhone : "Non disponible") ?></p>
      </div>

      <a class="buy-btn" href="javascript:history.back()">Retour</a>
    </div>

  </section>
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
    drawer.setAttribute('aria-hidden','false');
    btn.setAttribute('aria-expanded','true');
    document.body.style.overflow='hidden';
  }
  function closeMenu(){
    drawer.classList.remove('is-open');
    overlay.hidden = true;
    drawer.setAttribute('aria-hidden','true');
    btn.setAttribute('aria-expanded','false');
    document.body.style.overflow='';
  }

  btn.addEventListener('click', openMenu);
  closeBtn.addEventListener('click', closeMenu);
  overlay.addEventListener('click', closeMenu);
  document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') closeMenu(); });
})();
</script>

</body>
</html>