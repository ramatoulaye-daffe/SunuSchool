
<?php

require_once __DIR__ . "/../config/database.php";

// Vérifier que le formulaire a été envoyé
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

try {

    // 1. Récupérer les données du formulaire
    $filiereId = filter_input(INPUT_POST, "filiere_id", FILTER_VALIDATE_INT);
    $coursId = filter_input(INPUT_POST, "cours_id", FILTER_VALIDATE_INT);

    $dateSeance = trim($_POST["date_seance"] ?? "");

    $statuts = $_POST["statuts"] ?? [];
    $motifs = $_POST["motifs"] ?? [];

    // 2. Vérifier les champs obligatoires
    if (!$filiereId || !$coursId || empty($dateSeance)) {
        throw new Exception("Veuillez renseigner tous les champs obligatoires.");
    }

    if (!is_array($statuts) || !is_array($motifs)) {
        throw new Exception("Les données du formulaire sont invalides.");
    }

    // 3. Vérifier le format de la date
    $dateObjet = DateTime::createFromFormat("Y-m-d\TH:i", $dateSeance);
    $erreursDate = DateTime::getLastErrors();

    if (
        !$dateObjet ||
        ($erreursDate !== false &&
        ($erreursDate["warning_count"] > 0 || $erreursDate["error_count"] > 0))
    ) {
        throw new Exception("La date de la séance est invalide.");
    }

    $dateSQL = $dateObjet->format("Y-m-d H:i:s");

    // 4. Vérifier que le cours appartient à la filière
    $requeteCours = $pdo->prepare("
        SELECT COUNT(*)
        FROM filiere_cours
        WHERE filiere_id = ?
        AND cours_id = ?
    ");

    $requeteCours->execute([$filiereId, $coursId]);

    if ((int)$requeteCours->fetchColumn() === 0) {
        throw new Exception("Ce cours n'appartient pas à la filière sélectionnée.");
    }

    // 5. Récupérer les étudiants de cette filière
    $requeteEtudiants = $pdo->prepare("
        SELECT id
        FROM etudiants
        WHERE filiere_id = ?
    ");

    $requeteEtudiants->execute([$filiereId]);

    $etudiants = $requeteEtudiants->fetchAll(PDO::FETCH_COLUMN);

    if (empty($etudiants)) {
        throw new Exception("Aucun étudiant n'est inscrit dans cette filière.");
    }

    // 6. Vérifier les statuts avant l'enregistrement
    $statutsAutorises = ["Présent", "Absent", "Retard"];

    foreach ($etudiants as $etudiantId) {

        if (!isset($statuts[$etudiantId])) {
            throw new Exception("Le statut d'un étudiant est manquant.");
        }

        if (!in_array($statuts[$etudiantId], $statutsAutorises, true)) {
            throw new Exception("Un statut transmis est invalide.");
        }
    }

    // 7. Préparer la requête d'insertion
    $requeteInsertion = $pdo->prepare("
        INSERT IGNORE INTO presences (
            etudiant_id,
            cours_id,
            date_seance,
            statut,
            motif
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $pdo->beginTransaction();

    $nombreEnregistre = 0;
    $nombreDoublons = 0;

    foreach ($etudiants as $etudiantId) {

        $statut = $statuts[$etudiantId];

        $motif = trim($motifs[$etudiantId] ?? "");

        $requeteInsertion->execute([
            $etudiantId,
            $coursId,
            $dateSQL,
            $statut,
            $motif !== "" ? $motif : null
        ]);

        if ($requeteInsertion->rowCount() === 1) {
            $nombreEnregistre++;
        } else {
            $nombreDoublons++;
        }
    }

    // 8. Valider la transaction
    $pdo->commit();

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $erreur = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultat de l'enregistrement | SunuSchool</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <main class="presence-container">

        <div class="presence-panel">

            <?php if (isset($erreur)) : ?>

                <h2>Erreur d'enregistrement</h2>

                <p>
                    <?= htmlspecialchars($erreur) ?>
                </p>

                <a href="index.php" class="presence-back">
                    Retour aux présences
                </a>

            <?php else : ?>

                <h2>Enregistrement terminé !</h2>

                <p>
                    <?= $nombreEnregistre ?> présence(s) enregistrée(s).
                </p>

                <?php if ($nombreDoublons > 0) : ?>
                    <p>
                        <?= $nombreDoublons ?>
                        présence(s) ignorée(s), car elles existaient déjà.
                    </p>
                <?php endif; ?>

                <a href="index.php" class="presence-back">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour aux présences
                </a>

            <?php endif; ?>

        </div>

    </main>

</body>
</html>
