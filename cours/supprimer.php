
<?php

require_once "../config/database.php";

// Vérifier l'identifiant du cours
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}

// Récupérer le cours
$requete = $pdo->prepare(
    "SELECT id, code, libelle FROM cours WHERE id = ?"
);
$requete->execute([$id]);

$cours = $requete->fetch(PDO::FETCH_ASSOC);

if (!$cours) {
    header("Location: index.php");
    exit;
}

// Compter les notes associées à ce cours
$requete = $pdo->prepare(
    "SELECT COUNT(*) FROM notes WHERE cours_id = ?"
);
$requete->execute([$id]);

$nombreNotes = (int) $requete->fetchColumn();

$message = "";
$erreur = false;

// Suppression après confirmation
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($nombreNotes > 0) {
        $message = "Impossible de supprimer ce cours : des notes y sont associées.";
        $erreur = true;

    } else {
        $requete = $pdo->prepare(
            "DELETE FROM cours WHERE id = ?"
        );
        $requete->execute([$id]);

        header("Location: index.php?deleted=1");
        exit;
    }
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer un cours | SunuSchool</title>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body class="ss-cours-form">

    <main class="main-content">

        <div class="cours-form-header">
            <a href="index.php" class="btn-home">
                <i class="fa-solid fa-arrow-left"></i>
                Retour aux cours
            </a>
        </div>

        <section class="cours-form-container">

            <div class="cours-form-heading">
                <div class="cours-form-icon">
                    <i class="fa-solid fa-trash"></i>
                </div>

                <h1>Suppression d'un cours</h1>
                <p>Vérifiez les informations avant de continuer.</p>
            </div>

            <?php if ($nombreNotes > 0): ?>

                <div class="form-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Ce cours possède <?= $nombreNotes ?> note(s).
                    Sa suppression est donc impossible.
                </div>

            <?php else: ?>

                <p>
                    Voulez-vous vraiment supprimer le cours
                    <strong>
                        <?= htmlspecialchars($cours["libelle"]) ?>
                    </strong>
                    (<?= htmlspecialchars($cours["code"]) ?>) ?
                </p>

                <form method="POST" class="cours-form-actions">

                    <a href="index.php" class="cours-form-cancel">
                        Annuler
                    </a>

                    <button type="submit" class="cours-form-submit">
                        <i class="fa-solid fa-trash"></i>
                        Confirmer la suppression
                    </button>

                </form>

            <?php endif; ?>

            <?php if ($erreur): ?>
                <a href="index.php" class="cours-form-cancel">
                    Retour aux cours
                </a>
            <?php endif; ?>

        </section>

    </main>

</body>
</html>
