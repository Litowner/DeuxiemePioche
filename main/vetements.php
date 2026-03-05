<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vêtements</title>
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
      <a href="vetements.php">Vêtements</a>
      <a href="chaussures.php">Chaussures</a>
      <a href="accessoires.php">Accessoires</a>
      <a href="mobilier.php">Mobilier</a>

      <?php if(isset($_SESSION["user_id"])): ?>
        <a class="drawer-sep" href="account.php">Mon compte</a>
        <a class="drawer-logout" href="connexion.php?logout=1">Déconnexion</a>
      <?php endif; ?>
    </nav>
  </aside>
</header>

<main class="vetements-page">

  <!-- HERO -->
  <section class="vetements-hero">
    <!-- Image à placer : assets/images/vetements-hero.jpg -->
    <img src="assets/images/vetements-hero.jpg" alt="Vêtements">
    <h1>Vêtements</h1>
  </section>

  <!-- CONTENU -->
  <section class="vetements-layout">

    <!-- Sidebar -->
    <aside class="vetements-sidebar">
      <a href="#" class="side-link">Pulls, gilets et sweats</a>
      <a href="#" class="side-link">Pantalons, jeans et shorts</a>
      <a href="#" class="side-link">Blouses et chemises</a>
      <a href="#" class="side-link">T-shirts et tops</a>
      <a href="#" class="side-link">Vestes et manteaux</a>
    </aside>

    <!-- Produits -->
    <section class="vetements-products">

      <article class="product-card">
        <div class="product-img">
          <!-- Image : assets/images/manteau-carreaux.jpg -->
          <img src="assets/images/manteau-carreaux.jpg" alt="Manteau à carreaux">
        </div>

        <div class="product-info">
          <h3>Manteau a carreaux</h3>
          <div class="product-price">CHF 75.00</div>
          <a class="buy-btn" href="#">Acheter</a>
        </div>
      </article>

      <article class="product-card">
        <div class="product-img">
          <!-- Image : assets/images/manteau-noir.jpg -->
          <img src="assets/images/manteau-noir.jpg" alt="Manteau Noir">
        </div>

        <div class="product-info">
          <h3>Manteau Noir</h3>
          <div class="product-price">CHF 52.00</div>
          <a class="buy-btn" href="#">Acheter</a>
        </div>
      </article>

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