<?php
/*
===========================================
INSTRUCTIONS

1. Placez vos images dans : assets/images/
2. Modifiez le tableau $produits ci-dessous pour ajouter une catégorie :
   - id
   - nom
   - image (chemin relatif)
   - tags : tableau contenant "Hommes", "Femmes", et/ou "Enfants"

3. Le filtrage fonctionne via l’URL :
   index.php?tag=Hommes
===========================================
*/
session_start();

$produits = [
    [
        "id" => 1,
        "nom" => "Vêtements",
        "image" => "assets/images/vetements.jpg",
        "tags" => ["Hommes", "Femmes", "Enfants"]
    ],
    [
        "id" => 2,
        "nom" => "Chaussures",
        "image" => "assets/images/chaussures.jpg",
        "tags" => ["Femmes", "Hommes", "Enfants"]
    ],
    [
        "id" => 3,
        "nom" => "Accessoires",
        "image" => "assets/images/accessoires.jpg",
        "tags" => ["Enfants"]
    ],
    [
        "id" => 4,
        "nom" => "Mobilier",
        "image" => "assets/images/meubles.jpg",
        "tags" => ["Femmes", "Hommes"]
    ],
    [
        "id" => 5,
        "nom" => "Sport",
        "image" => "assets/images/sport.png",
        "tags" => ["Femmes", "Hommes"]
    ],
    [
        "id" => 6,
        "nom" => "Jouets",
        "image" => "assets/images/jouets.png",
        "tags" => ["Enfants"]
    ],
    [
        "id" => 7,
        "nom" => "Électronique",
        "image" => "assets/images/electronique.png",
        "tags" => ["Femmes", "Hommes"]
    ],
];

$tagSelectionne = $_GET['tag'] ?? null;

if ($tagSelectionne) {
    $tagSelectionne = trim($tagSelectionne);
    $produits = array_filter($produits, function ($produit) use ($tagSelectionne) {
        $tags = $produit["tags"] ?? [];
        if (!is_array($tags)) $tags = [$tags];
        return in_array($tagSelectionne, $tags, true);
    });
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>2ème Pioche</title>
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

        <!-- Overlay + Drawer -->
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
</header>

<nav class="categories">
    <a href="index.php?tag=Hommes" class="btn-cat">Hommes</a>
    <a href="index.php?tag=Femmes" class="btn-cat">Femmes</a>
    <a href="index.php?tag=Enfants" class="btn-cat">Enfants</a>
    <a href="index.php" class="btn-cat">Tous</a>
</nav>

<main>
    <section class="catalogue">
        <?php foreach ($produits as $produit): 
            $nameLower = strtolower($produit["nom"]);

            // mapping vers categorie.php?cat=
            $map = [
                "vêtements" => "vetements",
                "vetements" => "vetements",
                "chaussures" => "chaussures",
                "accessoires" => "accessoires",
                "mobilier" => "mobilier",
                "sport" => "sport",
                "jouets" => "jouets",
                "électronique" => "electronique",
                "electronique" => "electronique",
            ];

            $slug = $map[$nameLower] ?? "vetements";
        ?>
            <a class="card" href="categorie.php?cat=<?= urlencode($slug) ?>">
                <img src="<?= htmlspecialchars($produit['image']); ?>" alt="<?= htmlspecialchars($produit['nom']); ?>">
                <div class="overlay">
                    <h2><?= htmlspecialchars($produit['nom']); ?></h2>
                </div>
            </a>
        <?php endforeach; ?>
    </section>
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