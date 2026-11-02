
<?php
require_once "../config/database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Recuperer la filière
$stmt = $pdo->prepare("
    SELECT id, nom
    FROM filieres
    WHERE id = ?
");
$stmt->execute([$id]);

$filiere = $stmt->fetch();

if (!$filiere) {
    header("Location: index.php");
    exit;
}

// Vérifier si des étudiants utilisent cette filière
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM etudiants
    WHERE filiere_id = ?
");
$stmt->execute([$id]);

$nombreEtudiants = (int) $stmt->fetchColumn();

$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if ($nombreEtudiants > 0) {
        $erreur = "Impossible de supprimer cette filière : des étudiants y sont encore inscrits.";
    } else {
        $stmt = $pdo->prepare("
            DELETE FROM filieres
            WHERE id = ?
        ");

        $stmt->execute([$id]);

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
    <title>Supprimer une filière | SunuSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="ss-crud">

    <main class="main-content">

        <section class="ss-add-container">

            <div class="delete-icon">
                <i class="fa-solid fa-trash"></i>
            </div>

            <div class="ss-delete">
                <h2>Supprimer cette filière ?</h2>
                <p>
                    Voulez-vous vraiment supprimer la filière
                    <strong><?= htmlspecialchars($filiere["nom"]) ?></strong> ?
                </p>
            </div>

            <?php if ($erreur !== ""): ?>
                <div class="form-error">
                    <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <?php if ($nombreEtudiants > 0): ?>

            <div class="delete-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>

                Cette filière compte <?= $nombreEtudiants ?>
                étudiant(s). Vous devez d'abord réaffecter ces étudiants
                à une autre filière.
                
            </div>

            <div class="ss-add-actions">
                <a href="index.php" class="ss-add-cancel">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour aux filières
                </a>
            </div>

            <?php else: ?>
            
            <form method="POST" class="ss-add-actions">
    
                <a href="index.php" class="ss-add-cancel">
                    <i class="fa-solid fa-xmark"></i>
                    Annuler
                </a>

                <button type="submit" class="btn-delete">
                    <i class="fa-solid fa-trash"></i>
                    Confirmer la suppression
                </button>

            </form>

            <?php endif; ?>

        </section>

    </main>

</body>
</html>
