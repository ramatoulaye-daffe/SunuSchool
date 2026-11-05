
<?php
session_start();

require_once "../config/database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Récupérer les informations de la note
$stmt = $pdo->prepare("
    SELECT
        n.id,
        n.note,
        n.semestre,
        e.nom,
        e.prenom,
        e.matricule,
        c.libelle
    FROM notes n
    INNER JOIN etudiants e
        ON n.etudiant_id = e.id
    INNER JOIN cours c
        ON n.cours_id = c.id
    WHERE n.id = ?
");

$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) {
    header("Location: index.php");
    exit;
}

// Générer un token de sécurité
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Suppression après confirmation
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        die("Requête invalide. Veuillez réessayer.");
    }

    $stmt = $pdo->prepare("
        DELETE FROM notes
        WHERE id = ?
    ");

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
    <title>Supprimer une note | SunuSchool</title>
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

                <h2>Supprimer cette note ?</h2>
                <p>
                    Voulez-vous vraiment supprimer cette note ?
                </p>
            </div>

            <div class="delete-warning">
                <p>
                    <strong>Étudiant :</strong>
                    <?= htmlspecialchars($note["prenom"] . " " . $note["nom"]) ?>
                </p>
                <p>
                    <strong>Cours :</strong>
                    <?= htmlspecialchars($note["libelle"]) ?>
                </p>
                <p>
                    <strong>Note :</strong>
                    <?= htmlspecialchars((string)$note["note"]) ?>/20
                </p>
                <p>
                    <strong>Semestre :</strong>
                    <?= htmlspecialchars($note["semestre"]) ?>
                </p>
            </div>

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
