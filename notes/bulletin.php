
<?php
require_once "../config/database.php";

// ==========================================
// 1. RÉCUPÉRATION DES ÉTUDIANTS
// ==========================================

$sqlEtudiants = "
    SELECT id, matricule, nom, prenom
    FROM etudiants
    ORDER BY nom ASC, prenom ASC
";

$etudiants = $pdo->query($sqlEtudiants)->fetchAll();

// ==========================================
// 2. PARAMÈTRES DE SÉLECTION
// ==========================================

$etudiantId = filter_input(INPUT_GET, "etudiant_id", FILTER_VALIDATE_INT);
$semestre = $_GET["semestre"] ?? "";

if (!in_array($semestre, ["S1", "S2"], true)) {
    $semestre = "";
}

// Initialisation des variables
$bulletin = [];
$etudiantSelectionne = null;
$rangsMatieres = [];
$rang = null;
$moyenne = null;
$moyenneAnnuelle = null;
$appreciation = null;
$appreciationAnnuelle = null;
$erreurSelection = false;

// ==========================================
// 3. TRAITEMENT DU BULLETIN
// ==========================================

if ($etudiantId && $semestre !== "") {

    // Récupérer l'étudiant et sa filière
    $sqlEtudiant = "
        SELECT
            e.id,
            e.filiere_id,
            e.matricule,
            e.nom,
            e.prenom,
            f.nom AS filiere
        FROM etudiants e
        LEFT JOIN filieres f ON f.id = e.filiere_id
        WHERE e.id = :id
        LIMIT 1
    ";

    $stmtEtudiant = $pdo->prepare($sqlEtudiant);
    $stmtEtudiant->execute(["id" => $etudiantId]);

    $etudiantSelectionne = $stmtEtudiant->fetch();

    if (!$etudiantSelectionne) {

        $erreurSelection = true;

    } else {

        // ==========================================
        // 4. NOTES DE L'ÉTUDIANT
        // ==========================================

        $sqlBulletin = "
            SELECT
                c.id AS cours_id,
                c.code,
                c.libelle,
                c.coefficient,
                n.note,
                n.semestre
            FROM notes n
            INNER JOIN cours c ON c.id = n.cours_id
            WHERE n.etudiant_id = :etudiant_id
              AND n.semestre = :semestre
            ORDER BY c.libelle ASC
        ";

        $stmtBulletin = $pdo->prepare($sqlBulletin);

        $stmtBulletin->execute([
            "etudiant_id" => $etudiantId,
            "semestre" => $semestre
        ]);

        $bulletin = $stmtBulletin->fetchAll();

        if (!empty($bulletin)) {

            // ==========================================
            // 5. MOYENNE SEMESTRIELLE PONDÉRÉE
            // ==========================================

            $totalPoints = 0;
            $totalCoefficients = 0;

            foreach ($bulletin as $ligne) {

                $coefficient = (float) $ligne["coefficient"];
                $note = (float) $ligne["note"];

                $totalPoints += $note * $coefficient;
                $totalCoefficients += $coefficient;
            }

            if ($totalCoefficients > 0) {
                $moyenne = round(
                    $totalPoints / $totalCoefficients,
                    2
                );
            }

            // ==========================================
            // 6. RANG PAR MATIÈRE DANS LA FILIÈRE
            // ==========================================

            $sqlRangsMatieres = "
                SELECT
                    classement.cours_id,
                    classement.rang_matiere
                FROM (
                    SELECT
                        n.etudiant_id,
                        n.cours_id,
                        DENSE_RANK() OVER (
                            PARTITION BY n.cours_id
                            ORDER BY n.note DESC
                        ) AS rang_matiere
                    FROM notes n
                    INNER JOIN etudiants e
                        ON e.id = n.etudiant_id
                    WHERE n.semestre = :semestre
                      AND e.filiere_id = :filiere_id
                ) AS classement
                WHERE classement.etudiant_id = :etudiant_id
            ";

            $stmtRangs = $pdo->prepare($sqlRangsMatieres);

            $stmtRangs->execute([
                "semestre" => $semestre,
                "filiere_id" => $etudiantSelectionne["filiere_id"],
                "etudiant_id" => $etudiantId
            ]);

            foreach ($stmtRangs->fetchAll() as $ligne) {

                $rangsMatieres[(int) $ligne["cours_id"]] =
                    (int) $ligne["rang_matiere"];
            }

            // ==========================================
            // 7. RANG GÉNÉRAL DANS LA FILIÈRE
            // ==========================================

            $sqlClassement = "
                SELECT classement.id, classement.rang
                FROM (
                    SELECT
                        resultats.id,
                        DENSE_RANK() OVER (
                            ORDER BY resultats.moyenne DESC
                        ) AS rang
                    FROM (
                        SELECT
                            e.id,
                            ROUND(
                                SUM(n.note * c.coefficient) /
                                NULLIF(SUM(c.coefficient), 0),
                                2
                            ) AS moyenne
                        FROM etudiants e
                        INNER JOIN notes n
                            ON n.etudiant_id = e.id
                        INNER JOIN cours c
                            ON c.id = n.cours_id
                        WHERE e.filiere_id = :filiere_id
                          AND n.semestre = :semestre
                        GROUP BY e.id
                    ) AS resultats
                ) AS classement
                WHERE classement.id = :etudiant_id
            ";

            $stmtClassement = $pdo->prepare($sqlClassement);

            $stmtClassement->execute([
                "filiere_id" => $etudiantSelectionne["filiere_id"],
                "semestre" => $semestre,
                "etudiant_id" => $etudiantId
            ]);

            $resultatRang = $stmtClassement->fetch();

            $rang = $resultatRang
                ? (int) $resultatRang["rang"]
                : null;

            // ==========================================
            // 8. MOYENNE ANNUELLE AU SEMESTRE 2
            // ==========================================

            if ($semestre === "S2") {

                $sqlAnnee = "
                    SELECT
                        n.semestre,
                        SUM(n.note * c.coefficient) /
                        NULLIF(SUM(c.coefficient), 0) AS moyenne
                    FROM notes n
                    INNER JOIN cours c
                        ON c.id = n.cours_id
                    WHERE n.etudiant_id = :etudiant_id
                      AND n.semestre IN ('S1', 'S2')
                    GROUP BY n.semestre
                ";

                $stmtAnnee = $pdo->prepare($sqlAnnee);

                $stmtAnnee->execute([
                    "etudiant_id" => $etudiantId
                ]);

                $moyennesSemestres = [];

                foreach ($stmtAnnee->fetchAll() as $ligne) {

                    $moyennesSemestres[$ligne["semestre"]] =
                        (float) $ligne["moyenne"];
                }

                if (
                    isset(
                        $moyennesSemestres["S1"],
                        $moyennesSemestres["S2"]
                    )
                ) {

                    $moyenneAnnuelle = round(
                        (
                            $moyennesSemestres["S1"] +
                            $moyennesSemestres["S2"]
                        ) / 2,
                        2
                    );
                }
            }

            // ==========================================
            // 9. APPRÉCIATION SEMESTRIELLE
            // ==========================================

            if ($moyenne !== null) {

                if ($moyenne >= 16) {
                    $appreciation = "Très bien";
                } elseif ($moyenne >= 14) {
                    $appreciation = "Bien";
                } elseif ($moyenne >= 12) {
                    $appreciation = "Assez bien";
                } elseif ($moyenne >= 10) {
                    $appreciation = "Passable";
                } else {
                    $appreciation = "Insuffisant";
                }
            }

            // Appréciation annuelle

            if ($moyenneAnnuelle !== null) {

                if ($moyenneAnnuelle >= 16) {
                    $appreciationAnnuelle = "Très bien";
                } elseif ($moyenneAnnuelle >= 14) {
                    $appreciationAnnuelle = "Bien";
                } elseif ($moyenneAnnuelle >= 12) {
                    $appreciationAnnuelle = "Assez bien";
                } elseif ($moyenneAnnuelle >= 10) {
                    $appreciationAnnuelle = "Passable";
                } else {
                    $appreciationAnnuelle = "Insuffisant";
                }
            }

            $observationPedagogique = "";

            if ($moyenne !== null) {
                if ($moyenne >= 16) {
                    $observationPedagogique = "Excellent travail. L'étudiant fait preuve de sérieux, de régularité et d'une très bonne maîtrise des connaissances. Félicitations !";
                } elseif ($moyenne >= 14) {
                    $observationPedagogique = "Très bon travail. L'étudiant possède de bonnes compétences. Il doit poursuivre ses efforts.";
                } elseif ($moyenne >= 12) {
                    $observationPedagogique = "Bon travail dans l'ensemble. Les résultats sont satisfaisants. Davantage de régularité permettra de progresser.";
                } elseif ($moyenne >= 10) {
                    $observationPedagogique = "Résultats passables. Des efforts supplémentaires et un travail plus régulier sont nécessaires.";
                } else {
                    $observationPedagogique = "Résultats insuffisants. L'étudiant doit renforcer ses acquis et fournir davantage d'efforts.";
                }
            }
        }
    }
}

// ==========================================
// 10. FORMATAGE DES RANGS
// ==========================================

$formatRang = static function ($valeur): string {

    if ($valeur === null) {
        return "Non classé";
    }

    return ((int) $valeur === 1)
        ? "1er"
        : (int) $valeur . "e";
};

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SunuSchool | Bulletins scolaires</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<main class="main-content crud-page">

    <section class="crud-panel">

        <div class="crud-panel-heading">

            <div>
                <h2>
                    <i class="fa-solid fa-file-lines"></i>
                    Bulletins scolaires
                </h2>

                <p>
                    Générez les bulletins des étudiants avec leurs
                    moyennes et classements par filière.
                </p>
            </div>

            <span class="crud-badge">
                <i class="fa-solid fa-file-lines"></i>
                Résultats académiques
            </span>

        </div>

        <form method="GET" action="bulletin.php" class="student-form-panel no-print">

            <div class="form-grid">

                <div class="ss-add-field">

                    <label for="etudiant_id">Étudiant</label>

                    <select id="etudiant_id" name="etudiant_id" required>

                        <option value="">
                            Choisir un étudiant
                        </option>

                        <?php foreach ($etudiants as $etudiant) : ?>

                            <option
                                value="<?= (int) $etudiant["id"] ?>"
                                <?= (string) $etudiantId === (string) $etudiant["id"]
                                    ? "selected"
                                    : "" ?>
                            >
                                <?= htmlspecialchars(
                                    $etudiant["matricule"] . " - " .
                                    $etudiant["prenom"] . " " .
                                    $etudiant["nom"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="ss-add-field">

                    <label for="semestre">Semestre</label>

                    <select id="semestre" name="semestre" required>

                        <option value="">
                            Choisir un semestre
                        </option>

                        <option value="S1"
                            <?= $semestre === "S1" ? "selected" : "" ?>>
                            Semestre 1
                        </option>

                        <option value="S2"
                            <?= $semestre === "S2" ? "selected" : "" ?>>
                            Semestre 2
                        </option>

                    </select>

                </div>

            </div>

            <div class="ss-add-actions">

                <button type="submit" class="view-all">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Consulter le bulletin
                </button>

                <a href="index.php" class="ss-add-cancel">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour aux notes
                </a>

            </div>

        </form>

        <!-- MESSAGES -->
        <?php if ($erreurSelection) : ?>

            <p class="form-error">
                Étudiant introuvable. Vérifie ta sélection.
            </p>

        <?php elseif ($etudiantSelectionne && empty($bulletin)) : ?>

            <p class="alert-success">
                Aucune note enregistrée pour cet étudiant
                dans ce semestre.
            </p>

        <?php elseif ($etudiantSelectionne && !empty($bulletin)) : ?>

            <article class="bulletin-sheet">

                <header class="bulletin-header">

                    <div class="bulletin-brand">

                        <div class="bulletin-logo">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>

                        <div>
                            <h1>SunuSchool</h1>
                            <p>GESTION SCOLAIRE</p>
                            <small>
                                L'excellence au service de l'éducation
                            </small>
                        </div>

                    </div>

                    <div class="bulletin-year">
                        <strong>2026 - 2027</strong>
                        <span>ANNÉE ACADÉMIQUE</span>
                    </div>

                </header>

                <div class="bulletin-title">

                    <h2>BULLETIN DE NOTES</h2>

                    <p>
                        RELEVÉ ACADÉMIQUE —
                        <?= htmlspecialchars($semestre, ENT_QUOTES, "UTF-8") ?>
                    </p>

                </div>

                <section class="bulletin-section">

                    <h3>Informations de l'étudiant</h3>

                    <div class="bulletin-student">

                        <div>
                            <span>Nom et prénom</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $etudiantSelectionne["prenom"] . " " .
                                    $etudiantSelectionne["nom"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Matricule</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $etudiantSelectionne["matricule"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Filière</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $etudiantSelectionne["filiere"] ?? "Non renseignée",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>
                        </div>

                        <div>
                            <span>Semestre</span>

                            <strong>
                                <?= htmlspecialchars($semestre, ENT_QUOTES, "UTF-8") ?>
                            </strong>
                        </div>

                    </div>

                </section>

                <section class="bulletin-section">

                    <h3>Résultats académiques</h3>

                    <table class="bulletin-table">

                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Matières</th>
                                <th>Note /20</th>
                                <th>Coefficient</th>
                                <th>Rang</th>
                                <th>Appréciation</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($bulletin as $index => $ligne) : ?>

                                <?php

                                $note = (float) $ligne["note"];

                                if ($note >= 16) {
                                    $appreciationMatiere = "Très bien";
                                } elseif ($note >= 14) {
                                    $appreciationMatiere = "Bien";
                                } elseif ($note >= 12) {
                                    $appreciationMatiere = "Assez bien";
                                } elseif ($note >= 10) {
                                    $appreciationMatiere = "Passable";
                                } else {
                                    $appreciationMatiere = "Insuffisant";
                                }

                                ?>

                                <tr>
                                    <td><?= $index + 1 ?></td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars(
                                                $ligne["libelle"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </strong>

                                        <small>
                                            <?= htmlspecialchars(
                                                $ligne["code"],
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?= number_format(
                                            $note,
                                            2,
                                            ",",
                                            " "
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            (string) $ligne["coefficient"],
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= htmlspecialchars(
                                                $formatRang(
                                                    $rangsMatieres[(int) $ligne["cours_id"]] ?? null
                                                ),
                                                ENT_QUOTES,
                                                "UTF-8"
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $appreciationMatiere,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </section>

                <section class="bulletin-section">

                    <h3>Bilan du semestre</h3>

                    <div class="bulletin-summary">

                        <div class="summary-card">
                            <span>Moyenne générale</span>

                            <strong>
                                <?= $moyenne !== null
                                    ? number_format($moyenne, 2, ",", " ") . "/20"
                                    : "—" ?>
                            </strong>
                        </div>

                        <div class="summary-card">
                            <span>Rang dans la filière</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $formatRang($rang),
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>
                        </div>

                        <div class="summary-card">
                            <span>Appréciation</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $appreciation ?? "—",
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </strong>
                        </div>

                    </div>

                    <p class="bulletin-decision">

                        Décision semestrielle :

                        <strong>
                            <?= $moyenne !== null && $moyenne >= 10
                                ? "Admis"
                                : "Non admis" ?>
                        </strong>

                    </p>

                </section>

                <!-- BILAN ANNUEL AU S2 -->
                <?php if ($semestre === "S2") : ?>

                    <section class="bulletin-section annual-section">

                        <h3>Bilan annuel</h3>

                        <?php if ($moyenneAnnuelle !== null) : ?>

                            <div class="annual-average">

                                <span>Moyenne annuelle</span>

                                <strong>
                                    <?= number_format(
                                        $moyenneAnnuelle,
                                        2,
                                        ",",
                                        " "
                                    ) ?>/20
                                </strong>

                            </div>

                            <div class="annual-details">

                                <p>
                                    Appréciation annuelle :

                                    <strong>
                                        <?= htmlspecialchars(
                                            $appreciationAnnuelle,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        ) ?>
                                    </strong>
                                </p>

                                <p>
                                    Décision annuelle :

                                    <strong>
                                        <?= $moyenneAnnuelle >= 10
                                            ? "Admis"
                                            : "Non admis" ?>
                                    </strong>
                                </p>

                            </div>

                        <?php else : ?>

                            <p>
                                Bilan annuel indisponible :
                                les notes des deux semestres sont nécessaires.
                            </p>

                        <?php endif; ?>

                    </section>

                <?php endif; ?>

                <!-- OBSERVATIONS -->
                <section class="bulletin-section">

                    <h3>Observations pédagogiques</h3>

                    <div class="bulletin-observations">
                        <div class="observation-content">
                            <?= htmlspecialchars($observationPedagogique) ?>
                        </div>
                    </div>

                </section>

                <section class="bulletin-signatures">

                    <div>
                        <strong>Responsable pédagogique</strong>
                        <span>Signature et cachet</span>
                    </div>

                    <div>
                        <strong>Direction</strong>
                        <span>Signature et cachet</span>
                    </div>

                </section>

                <!-- PIED DE PAGE -->

                <footer class="bulletin-footer">

                    <strong>SunuSchool</strong>
                    <p>Former, accompagner, réussir.</p>

                </footer>

            </article>

            <!-- BOUTON D'IMPRESSION -->
            <div class="bulletin-actions no-print">

                <button
                    type="button"
                    class="ss-add-submit"
                    onclick="window.print()"
                >
                    <i class="fa-solid fa-print"></i>
                    Imprimer / Enregistrer en PDF

                </button>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>
