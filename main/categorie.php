<?php
session_start();

$categories = [
    "vetements" => [
        "label" => "Vêtements",
        "image" => "assets/images/vetements.jpg"
    ],
    "chaussures" => [
        "label" => "Chaussures",
        "image" => "assets/images/chaussures.jpg"
    ],
    "accessoires" => [
        "label" => "Accessoires",
        "image" => "assets/images/accessoires.jpg"
    ],
    "mobilier" => [
        "label" => "Mobilier",
        "image" => "assets/images/mobilier.jpg"
    ]
];

$key = strtolower($_GET["cat"] ?? "vetements");
if (!isset($categories[$key])) $key = "vetements";

$categorie = $categories[$key]["label"];
$heroImage = $categories[$key]["image"];

$jsonPath = __DIR__ . "/assets/data/articles.json";
$articles = [];

if (file_exists($jsonPath)) {
    $content = file_get_contents($jsonPath);
    $data = json_decode($content, true);

    if (is_array($data)) {
        foreach ($data as $article) {
            if (($article["categorie"] ?? "") === $categorie) {
                $articles[] = $article;
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
<title><?= htmlspecialchars($categorie) ?></title>
<link rel="stylesheet" href="assets/style.css">
</head>

<body>

<header>
<div class="top-bar">

<div class="menu-left">
<button class="burger-btn" type="button">
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
<a href="account.php"><?= htmlspecialchars($_SESSION["username"]) ?></a>
<?php else: ?>
<a href="connexion.php">Se connecter</a>
<?php endif; ?>
</div>

</div>

<div class="menu-overlay" id="menuOverlay" hidden></div>

<aside class="menu-drawer" id="menuDrawer">

<div class="drawer-top">
<strong>Menu</strong>
<button class="drawer-close">✕</button>
</div>

<nav class="drawer-links">

<!-- Accueil ajouté ici -->
<a href="index.php" class="drawer-home">Accueil</a>

<?php foreach($categories as $slug => $cat): ?>

<a href="categorie.php?cat=<?= $slug ?>">
<?= htmlspecialchars($cat["label"]) ?>
</a>

<?php endforeach; ?>

<?php if(isset($_SESSION["user_id"])): ?>

<a class="drawer-sep" href="account.php">Mon compte</a>
<a class="drawer-logout" href="connexion.php?logout=1">Déconnexion</a>

<?php endif; ?>

</nav>
</aside>

</header>


<main class="vetements-page">

<section class="vetements-hero">

<img src="<?= htmlspecialchars($heroImage) ?>" alt="<?= htmlspecialchars($categorie) ?>">

<h1><?= htmlspecialchars($categorie) ?></h1>

</section>


<section class="vetements-layout">

<aside class="vetements-sidebar">

<a href="#" class="side-link">Pulls, gilets et sweats</a>
<a href="#" class="side-link">Pantalons, jeans et shorts</a>
<a href="#" class="side-link">Blouses et chemises</a>
<a href="#" class="side-link">T-shirts et tops</a>
<a href="#" class="side-link">Vestes et manteaux</a>

</aside>


<section class="vetements-products">

<?php if (empty($articles)): ?>

<p style="padding:10px 0;">Aucun article disponible.</p>

<?php else: ?>

<?php foreach($articles as $a): ?>

<article class="product-card">

<div class="product-img">
<img src="<?= htmlspecialchars($a["image"]) ?>">
</div>

<div class="product-info">

<h3><?= htmlspecialchars($a["titre"]) ?></h3>

<div class="product-price">
CHF <?= htmlspecialchars($a["prix"]) ?>
</div>

<a class="buy-btn" href="article.php?id=<?= urlencode($a["id"]) ?>">
Consulter l'offre
</a>

</div>

</article>

<?php endforeach; ?>

<?php endif; ?>

</section>

</section>

</main>


<footer>

<div class="footer-links">
<span>Aide et contact</span>
<span>Conditions d'utilisation</span>
<span>Politique de confidentialité</span>
<span>Concept</span>
</div>

<p>©2026 2eme-pioche Service SA tous droits réservés</p>

</footer>


<script>
(function(){
const btn = document.querySelector('.burger-btn');
const drawer = document.getElementById('menuDrawer');
const overlay = document.getElementById('menuOverlay');
const closeBtn = document.querySelector('.drawer-close');

function openMenu(){
drawer.classList.add('is-open');
overlay.hidden = false;
document.body.style.overflow = 'hidden';
}

function closeMenu(){
drawer.classList.remove('is-open');
overlay.hidden = true;
document.body.style.overflow = '';
}

btn.addEventListener('click', openMenu);
closeBtn.addEventListener('click', closeMenu);
overlay.addEventListener('click', closeMenu);

})();
</script>

</body>
</html>