
<?php
require_once "../config/database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Récupérer la filière
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

$erreur = "";
$nom = $filiere["nom"];

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST["nom"] ?? "");

    if ($nom === "") {
        $erreur = "Veuillez saisir le nom de la filière.";
    } else {

        try {
            $stmt = $pdo->prepare("
                UPDATE filieres
                SET nom = :nom
                WHERE id = :id
            ");

            $stmt->execute([
                ":nom" => $nom,
                ":id" => $id
            ]);

            header("Location: index.php?updated=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $erreur = "Cette filière existe déjà.";
            } else {
                $erreur = "Une erreur est survenue lors de la modification.";
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
    <title>Modifier une filière | SunuSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="ss-crud">

    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Modifier une filière</h1>
                <p>Modifiez les informations de cette filière.</p>
            </div>

            <a href="index.php" class="view-all">
                <i class="fa-solid fa-arrow-left"></i>
                Retour à la liste
            </a>

        </header>

        <section class="ss-add-container">

            <div class="ss-add-heading">
                <h3> Informations de la filière </h3>
            </div>

            <?php if ($erreur !== ""): ?>
                <div class="form-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="ss-add-grid">

                    <div class="ss-add-field">
                        <label for="nom">Nom de la filière *</label>
                        <input
                            type="text"
                            id="nom"
                            name="nom"
                            value="<?= htmlspecialchars($nom) ?>"
                            required
                        >
                    </div>

                </div>

                <div class="ss-add-actions">

                    <a href="index.php"  class="ss-add-cancel">
                        <i class="fa-solid fa-xmark"></i>
                        Annuler
                    </a>

                    <button type="submit" class="ss-add-submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Enregistrer les modifications
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>
</html>
