<?php
session_start();

$csvPath = __DIR__ . "/assets/data/users.csv";

$message = "";
$redirect = false;
//
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

    if (!file_exists($csvPath)) {
        return $users;
    }

    if (($handle = fopen($csvPath, "r")) !== false) {
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return $users;
        }

        while (($row = fgetcsv($handle)) !== false) {
            // ignore lignes incomplètes
            if (count($row) < count($header)) continue;

            $assoc = array_combine($header, $row);
            if (!empty($assoc["email"])) {
                $users[] = $assoc;
            }
        }
        fclose($handle);
    }

    return $users;
}

/**
 * Vérifie si l'email existe déjà (case-insensitive).
 */
function emailExists($users, $email) {
    $email = strtolower(trim($email));
    foreach ($users as $u) {
        if (strtolower(trim($u["email"])) === $email) {
            return true;
        }
    }
    return false;
}




if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"] ?? "");
    $tel      = trim($_POST["telephone"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm  = $_POST["confirm_password"] ?? "";

    if ($email === "" || $tel === "" || $password === "" || $confirm === "") {
        $message = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Adresse Email invalide.";
    } elseif ($password !== $confirm) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        // 1) Lire tous les users
        $users = readUsers($csvPath);

        // 2) Vérifier email déjà présent
        if (emailExists($users, $email)) {
            $message = "Un compte avec cette adresse mail existe déjà.";
        } else {
            // 3) Ajouter
            $id = generateUUID();
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $fp = fopen($csvPath, "a");
            if (!$fp) {
                $message = "Erreur : impossible d'écrire dans users.csv.";
            } else {
                // Lock fichier pour éviter corruption si plusieurs inscriptions
                if (flock($fp, LOCK_EX)) {
                    fputcsv($fp, [$id,$username, $email, $tel, $hash, date("Y-m-d H:i:s")]);
                    fflush($fp);
                    flock($fp, LOCK_UN);
                    $message = "Compte créé avec succès !";
                    $redirect = true; // redirection vers connexion.php
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

<main class="connexion-page">

    <div class="connexion-box">

        <h2>Créer un compte</h2>

        <form action="inscription.php" method="POST" autocomplete="on">

            <div class="input-group">
                <label>Nom d'utilisateur</label>
                <input type="text" name="username" placeholder="Votre nom d'utilisateur" required>
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
    <p>©2026 2eme-pioche Service SA tous droits réservés</p>
</footer>

    <?php if ($message !== ""): ?>
        <script>
            alert("<?= addslashes($message) ?>");
        <?php if ($redirect): ?>
            window.location.href = "connexion.php";
        <?php endif; ?>
        </script>
    <?php endif; ?>

</body>
</html>