
<?php

require_once "../config/database.php";

$erreur = "";

$code = "";
$libelle = "";
$coefficient = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $code = strtoupper(trim($_POST["code"] ?? ""));
    $libelle = trim($_POST["libelle"] ?? "");
    $coefficient = trim($_POST["coefficient"] ?? "");

    if ($code === "" || $libelle === "" || $coefficient === "") {

        $erreur = "Veuillez remplir tous les champs.";

    } elseif (!is_numeric($coefficient) || (float)$coefficient <= 0) {

        $erreur = "Le coefficient doit être un nombre supérieur à zéro.";

    } else {

        try {

            $sql = "
                INSERT INTO cours (code, libelle, coefficient)
                VALUES (:code, :libelle, :coefficient)
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":code" => $code,
                ":libelle" => $libelle,
                ":coefficient" => $coefficient
            ]);

            header("Location: index.php?success=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $erreur = "Ce code de cours existe déjà.";
            } else {
                $erreur = "Une erreur est survenue lors de l'enregistrement.";
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

    <title>Ajouter un cours | SunuSchool</title>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="ss-cours-form">

    <main class="main-content">

        <header class="cours-form-header">

            <div>
                <h1>Ajouter un cours</h1>
                <p>Enregistrez un nouveau cours de formation.</p>
            </div>

            <a href="index.php" class="view-all">
                <i class="fa-solid fa-arrow-left"></i>
                Retour à la liste
            </a>

        </header>

        <?php if ($erreur !== ""): ?>

            <div class="ss-add-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($erreur) ?>
            </div>

        <?php endif; ?>

        <section class="cours-form-container">

            <div class="cours-form-heading">

                <div class="cours-form-icon">
                    <i class="fa-solid fa-book"></i>
                </div>

                <div>
                    <h3>Informations du cours</h3>
                    <p>Renseignez les informations nécessaires.</p>
                </div>

            </div>

            <form method="POST" action="">

                <div class="cours-form-grid">

                    <div class="cours-form-field">

                        <label for="code">Code du cours *</label>

                        <input
                            type="text"
                            id="code"
                            name="code"
                            placeholder="Ex : INF101"
                            value="<?= htmlspecialchars($code) ?>"
                            required
                        >

                    </div>

                    <div class="cours-form-field">

                        <label for="libelle">Intitulé du cours *</label>

                        <input
                            type="text"
                            id="libelle"
                            name="libelle"
                            placeholder="Ex : Algorithmique"
                            value="<?= htmlspecialchars($libelle) ?>"
                            required
                        >

                    </div>

                    <div class="cours-form-field">

                        <label for="coefficient">Coefficient *</label>

                        <input
                            type="number"
                            id="coefficient"
                            name="coefficient"
                            placeholder="Ex : 2"
                            min="0.01"
                            step="0.01"
                            value="<?= htmlspecialchars($coefficient) ?>"
                            required
                        >

                    </div>

                </div>

                <div class="cours-form-actions">

                    <a href="index.php" class="cours-form-cancel">
                        <i class="fa-solid fa-xmark"></i>
                        Annuler
                    </a>

                    <button type="submit" class="cours-form-submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Enregistrer le cours
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>

</html>
