
<?php

session_start();

require_once "../config/database.php";

// Verifier l'identifiant
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Recuperer l'etudiant
$stmt = $pdo->prepare("
    SELECT id, matricule, nom, prenom
    FROM etudiants
    WHERE id = ?
");

$stmt->execute([$id]);
$etudiant = $stmt->fetch();

if (!$etudiant) {
    header("Location: index.php");
    exit;
}

// Generer un jeton de securite
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Traitement de la confirmation
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        die("Requête invalide. Veuillez réessayer.");
    }

    $stmt = $pdo->prepare("DELETE FROM etudiants WHERE id = ?");
    $stmt->execute([$id]);

    unset($_SESSION["csrf_token"]);

    header("Location: index.php?deleted=1");
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SunuSchool | Supprimer un étudiant</title>
    <link rel="stylesheet"href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="ss-crud">

    <main class="main-content">

        <section class="ss-add-container">

            <div class="delete-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <div class="ss-delete">
                <h2>Confirmer la suppression</h2>
                <p>
                    Voulez-vous vraiment supprimer
                    <strong>
                        <?= htmlspecialchars($etudiant["prenom"] . " " . $etudiant["nom"]) ?>
                    </strong>
                    (<?= htmlspecialchars($etudiant["matricule"]) ?>) ?
                </p>
            </div>
            
            <p class="delete-warning">
                Attention : les notes associées à cet étudiant seront également supprimées.
            </p>

            <form method="POST" class="ss-add-actions">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>"
                >

                <a href="index.php" class="ss-add-cancel">
                    <i class="fa-solid fa-xmark"></i>
                    Annuler
                </a>

                <button type="submit" class="btn-delete">
                    <i class="fa-solid fa-trash"></i>
                    Confirmer la suppression
                </button>

            </form>

        </section>

    </main>

</body>
</html>
