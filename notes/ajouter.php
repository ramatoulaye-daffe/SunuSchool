
<?php
require_once "../config/database.php";

$erreur = "";

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $etudiantId = (int) ($_POST["etudiant_id"] ?? 0);
    $coursId = (int) ($_POST["cours_id"] ?? 0);
    $note = $_POST["note"] ?? "";
    $semestre = trim($_POST["semestre"] ?? "");

    if (
        $etudiantId <= 0 ||
        $coursId <= 0 ||
        $note === "" ||
        $semestre === ""
    ) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (
        !is_numeric($note) ||
        (float)$note < 0 ||
        (float)$note > 20
    ) {
        $erreur = "La note doit être comprise entre 0 et 20.";
    } else {

        try {

            $sql = "
                INSERT INTO notes
                (etudiant_id, cours_id, note, semestre)
                VALUES
                (:etudiant_id, :cours_id, :note, :semestre)
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":etudiant_id" => $etudiantId,
                ":cours_id" => $coursId,
                ":note" => $note,
                ":semestre" => $semestre
            ]);

            header("Location: index.php?success=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $erreur = "Une note existe déjà pour cet étudiant, ce cours et ce semestre.";
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
    <title>Ajouter une note | SunuSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="ss-crud">

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Ajouter une note</h1>
                <p>Enregistrez le résultat d'un étudiant.</p>
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
                    <i class="fa-solid fa-chart-line"></i>
                </div>

                <div>
                    <h3>Informations de la note</h3>
                </div>
            </div>

            <form method="POST" action="">

                <div class="ss-add-grid">

                    <div class="ss-add-field">

                        <label for="etudiant_id">Étudiant *</label>
                        <select name="etudiant_id" id="etudiant_id" required>
                            <option value="">Choisir un étudiant</option>

                            <?php foreach ($etudiants as $etudiant): ?>
                                <option value="<?= $etudiant["id"] ?>"
                                    <?= (($_POST["etudiant_id"] ?? "") == $etudiant["id"]) ? "selected" : "" ?>>
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
                            <option value="">Choisir un cours</option>

                            <?php foreach ($cours as $matiere): ?>
                                <option value="<?= $matiere["id"] ?>"
                                    <?= (($_POST["cours_id"] ?? "") == $matiere["id"]) ? "selected" : "" ?>>
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
                            value="<?= htmlspecialchars($_POST["note"] ?? "") ?>"
                            placeholder="Ex : 16.50"
                            required
                        >
                    </div>

                    <div class="ss-add-field">

                        <label for="semestre">Semestre *</label>
                        <select name="semestre" id="semestre" required>
                            <option value="">Choisir un semestre</option>

                            <option value="S1"
                                <?= (($_POST["semestre"] ?? "") === "S1") ? "selected" : "" ?>>
                                Semestre 1
                            </option>

                            <option value="S2"
                                <?= (($_POST["semestre"] ?? "") === "S2") ? "selected" : "" ?>>
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
                        Enregistrer la note
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>
</html>
