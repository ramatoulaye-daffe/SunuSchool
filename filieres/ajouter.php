
<?php
require_once "../config/database.php";

$erreur = "";
$nom = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom = trim($_POST["nom"] ?? "");

    if ($nom === "") {
        $erreur = "Veuillez saisir le nom de la filière.";
    } else {

        try {
            $sql = "
                INSERT INTO filieres (nom)
                VALUES (:nom)
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":nom" => $nom
            ]);

            header("Location: index.php?success=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $erreur = "Cette filière existe déjà.";
            } else {
                $erreur = "Une erreur est survenue lors de l'ajout.";
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
    <title>Ajouter une filière | SunuSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="ss-crud">

    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Ajouter une filière</h1>
                <p>Enregistrez une nouvelle filière de formation.</p>
            </div>

            <a href="index.php" class="view-all">
                <i class="fa-solid fa-arrow-left"></i>
                Retour à la liste
            </a>

        </header>

        <?php if ($erreur !== ""): ?>
            <div class="form-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <section class="ss-add-container">

            <div class="ss-add-heading">
                <div class="ss-add-heading-icon">
                    <i class="fa-solid fa-layer-group"></i>
                </div>

                <div>
                    <h3>Informations de la filière</h3>
                </div>

            </div>

            <form method="POST" action="">

                <div class="ss-add-grid">

                    <div class="ss-add-field">
                        <label for="nom">Nom de la filière *</label>
                        <input
                            type="text"
                            id="nom"
                            name="nom"
                            placeholder="Ex : Génie logiciel"
                            value="<?= htmlspecialchars($nom) ?>"
                            required
                        >
                    </div>
                   
                </div>

                <div class="ss-add-actions">

                    <a href="index.php" class="ss-add-cancel">
                        <i class="fa-solid fa-xmark"></i>
                        Annuler
                    </a>

                    <button type="submit" class="ss-add-submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Enregistrer
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>
</html>
