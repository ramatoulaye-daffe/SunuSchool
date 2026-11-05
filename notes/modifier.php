
<?php
require_once "../config/database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Récupérer la note
$stmt = $pdo->prepare("
    SELECT *
    FROM notes
    WHERE id = ?
");
$stmt->execute([$id]);

$noteExistante = $stmt->fetch();

if (!$noteExistante) {
    header("Location: index.php");
    exit;
}

// Récupérer les étudiants et les cours
$etudiants = $pdo->query("
    SELECT id, matricule, nom, prenom
    FROM etudiants
    ORDER BY nom ASC
")->fetchAll();

$cours = $pdo->query("
    SELECT id, code, libelle
    FROM cours
    ORDER BY libelle ASC
")->fetchAll();

$erreur = "";

$etudiantId = $noteExistante["etudiant_id"];
$coursId = $noteExistante["cours_id"];
$valeurNote = $noteExistante["note"];
$semestre = $noteExistante["semestre"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $etudiantId = (int) ($_POST["etudiant_id"] ?? 0);
    $coursId = (int) ($_POST["cours_id"] ?? 0);
    $valeurNote = $_POST["note"] ?? "";
    $semestre = trim($_POST["semestre"] ?? "");

    if (
        $etudiantId <= 0 ||
        $coursId <= 0 ||
        $valeurNote === "" ||
        $semestre === ""
    ) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (
        !is_numeric($valeurNote) ||
        (float)$valeurNote < 0 ||
        (float)$valeurNote > 20
    ) {
        $erreur = "La note doit être comprise entre 0 et 20.";
    } else {

        try {

            $stmt = $pdo->prepare("
                UPDATE notes
                SET
                    etudiant_id = :etudiant_id,
                    cours_id = :cours_id,
                    note = :note,
                    semestre = :semestre
                WHERE id = :id
            ");

            $stmt->execute([
                ":etudiant_id" => $etudiantId,
                ":cours_id" => $coursId,
                ":note" => $valeurNote,
                ":semestre" => $semestre,
                ":id" => $id
            ]);

            header("Location: index.php?updated=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $erreur = "Une note existe déjà pour cet étudiant, ce cours et ce semestre.";
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
    <title>Modifier une note | SunuSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="ss-crud">

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Modifier une note</h1>
                <p>Actualisez les résultats de l'étudiant.</p>
            </div>

            <a href="index.php" class="view-all">
                <i class="fa-solid fa-arrow-left"></i>
                Retour à la liste
            </a>
        </header>

        <section class="ss-add-container">

            <div class="ss-add-heading">
                <h3>Informations de la note</h3>
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

                        <label for="etudiant_id">Étudiant *</label>
                        <select name="etudiant_id" id="etudiant_id" required>

                            <?php foreach ($etudiants as $etudiant): ?>
                                <option
                                    value="<?= $etudiant["id"] ?>"
                                    <?= $etudiantId == $etudiant["id"] ? "selected" : "" ?>>

                                    <?= htmlspecialchars(
                                        $etudiant["matricule"] . " - " .
                                        $etudiant["prenom"] . " " . $etudiant["nom"]
                                    ) ?>

                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="ss-add-field">

                        <label for="cours_id">Cours *</label>
                        <select name="cours_id" id="cours_id" required>

                            <?php foreach ($cours as $matiere): ?>
                                <option
                                    value="<?= $matiere["id"] ?>"
                                    <?= $coursId == $matiere["id"] ? "selected" : "" ?>>

                                    <?= htmlspecialchars(
                                        $matiere["code"] . " - " . $matiere["libelle"]
                                    ) ?>

                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="ss-add-field">

                        <label for="note">Note sur 20 *</label>
                        <input
                            type="number"
                            name="note"
                            id="note"
                            min="0"
                            max="20"
                            step="0.01"
                            value="<?= htmlspecialchars((string)$valeurNote) ?>"
                            required
                        >
                    </div>

                    <div class="ss-add-field">

                        <label for="semestre">Semestre *</label>
                        <select name="semestre" id="semestre" required>

                            <option value="S1" <?= $semestre === "S1" ? "selected" : "" ?>>
                                Semestre 1
                            </option>

                            <option value="S2" <?= $semestre === "S2" ? "selected" : "" ?>>
                                Semestre 2
                            </option>

                        </select>
                    </div>

                </div>

                <div class="ss-add-actions">

                    <a href="index.php" class="ss-add-cancel">
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
