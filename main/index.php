<?php
/*
===========================================
INSTRUCTIONS

1. Placez vos images dans : assets/images/
2. Modifiez le tableau $produits ci-dessous pour ajouter un produit :
   - id
   - nom
   - prix
   - image (chemin relatif)
   - tag : "Hommes", "Femmes", ou "Enfants"

3. Le filtrage fonctionne via l’URL :
   index.php?tag=Hommes
===========================================
*/

$produits = [
    [
        "id" => 1,
        "nom" => "Veste Rouge",
        "prix" => 49.99,
        "image" => "assets/images/vetements.jpg",
        "tag" => "Femmes"
    ],
    [
        "id" => 2,
        "nom" => "Bottes Cuir",
        "prix" => 79.99,
        "image" => "assets/images/chaussures.jpg",
        "tag" => "Hommes"
    ],
    [
        "id" => 3,
        "nom" => "Accessoires",
        "prix" => 29.99,
        "image" => "assets/images/accessoires.jpg",
        "tag" => "Enfants"
    ],
    [
        "id" => 4,
        "nom" => "Commode Design",
        "prix" => 149.99,
        "image" => "assets/images/meubles.jpg",
        "tag" => "Hommes"
    ]
];

$tagSelectionne = $_GET['tag'] ?? null;

if ($tagSelectionne) {
    $produits = array_filter($produits, function ($produit) use ($tagSelectionne) {
        return $produit['tag'] === $tagSelectionne;
    });
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>2ème Pioche</title>
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

<nav class="categories">
    <a href="index.php?tag=Hommes" class="btn-cat">Hommes</a>
    <a href="index.php?tag=Femmes" class="btn-cat">Femmes</a>
    <a href="index.php?tag=Enfants" class="btn-cat">Enfants</a>
</nav>

<main>
    <section class="catalogue">
        <?php foreach ($produits as $produit): ?>
            <div class="card">
                <img src="<?= $produit['image']; ?>" alt="<?= $produit['nom']; ?>">
                <div class="overlay">
                    <h2><?= $produit['nom']; ?></h2>
                    <p><?= number_format($produit['prix'], 2); ?> €</p>
                </div>
            </div>
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
    <p>© 2008-2026 2eme-pioche Service SA tous droits réservés</p>
</footer>

</body>
</html>
