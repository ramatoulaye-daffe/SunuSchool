
<?php

require_once __DIR__ . "/../config/database.php";

// Récupérer l'identifiant de l'étudiant
$etudiantId = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$etudiantId) {
    die("Identifiant étudiant invalide.");
}

// 1. Récupérer les informations de l'étudiant
$requeteEtudiant = $pdo->prepare("
    SELECT
        e.id,
        e.matricule,
        e.nom,
        e.prenom,
        e.email,
        f.nom AS filiere
    FROM etudiants e
    INNER JOIN filieres f ON f.id = e.filiere_id
    WHERE e.id = ?
");

$requeteEtudiant->execute([$etudiantId]);

$etudiant = $requeteEtudiant->fetch(PDO::FETCH_ASSOC);

if (!$etudiant) {
    die("Étudiant introuvable.");
}

// 2. Récupérer les statistiques individuelles
$requeteStats = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(statut = 'Présent') AS presents,
        SUM(statut = 'Absent') AS absents,
        SUM(statut = 'Retard') AS retards
    FROM presences
    WHERE etudiant_id = ?
");

$requeteStats->execute([$etudiantId]);

$stats = $requeteStats->fetch(PDO::FETCH_ASSOC);

$total = (int) $stats["total"];
$presents = (int) $stats["presents"];
$absents = (int) $stats["absents"];
$retards = (int) $stats["retards"];

// Un étudiant en retard a bien assisté à la séance.
// Le taux d'assiduité compte donc Présent + Retard.
$tauxAssiduite = $total > 0
    ? round((($presents + $retards) / $total) * 100, 2)
    : 0;

// 3. Récupérer l'historique individuel
$requeteHistorique = $pdo->prepare("
    SELECT
        p.date_seance,
        p.statut,
        p.motif,
        c.code,
        c.libelle AS cours
    FROM presences p
    INNER JOIN cours c ON c.id = p.cours_id
    WHERE p.etudiant_id = ?
    ORDER BY p.date_seance DESC
");

$requeteHistorique->execute([$etudiantId]);

$historique = $requeteHistorique->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Fiche de présence | SunuSchool</title>

    <link rel="stylesheet" href="../assets/css/style.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

<main class="presence-container">

    <!-- En-tête -->
    <header class="presence-header">

        <div>
            <span class="presence-tag">
                <i class="fa-solid fa-user-graduate"></i>
                SUIVI INDIVIDUEL
            </span>

            <h1>Fiche de présence</h1>

            <p>Consultez l'assiduité de chaque étudiant.</p>
        </div>

        <div class="presence-header-actions">

            <button type="button"
                class="presence-print-btn"
                onclick="window.print()">

                <i class="fa-solid fa-print"></i>
                Imprimer la fiche

            </button>

            <a href="historique.php" class="presence-back">
                <i class="fa-solid fa-arrow-left"></i>
                Retour à l'historique
            </a>

        </div>

    </header>

    <!-- Informations étudiant -->
    <section class="presence-panel student-profile-panel">

        <div class="student-profile-avatar">
            <i class="fa-solid fa-user-graduate"></i>
        </div>

        <div class="student-profile-info">

            <h2>
                <?= htmlspecialchars(
                    $etudiant["prenom"] . " " . $etudiant["nom"]
                ) ?>
            </h2>

            <p>
                <strong>Matricule :</strong>
                <?= htmlspecialchars($etudiant["matricule"]) ?>
            </p>

            <p>
                <strong>Filière :</strong>
                <?= htmlspecialchars($etudiant["filiere"]) ?>
            </p>

            <p>
                <strong>Email :</strong>
                <?= htmlspecialchars($etudiant["email"] ?? "-") ?>
            </p>

        </div>

    </section>

    <!-- Statistiques individuelles -->
    <section class="presence-stats">

        <div class="presence-stat-card">
            <span>Total des séances</span>
            <h2><?= $total ?></h2>
            <i class="fa-solid fa-clipboard-list"></i>
        </div>

        <div class="presence-stat-card">
            <span>Présences</span>
            <h2><?= $presents ?></h2>
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <div class="presence-stat-card">
            <span>Absences</span>
            <h2><?= $absents ?></h2>
            <i class="fa-solid fa-circle-xmark"></i>
        </div>

        <div class="presence-stat-card">
            <span>Retards</span>
            <h2><?= $retards ?></h2>
            <i class="fa-solid fa-clock"></i>
        </div>

        <div class="presence-stat-card">
            <span>Taux d'assiduité</span>
            <h2><?= $tauxAssiduite ?> %</h2>
            <i class="fa-solid fa-chart-line"></i>
        </div>

    </section>

    <!-- Barre de progression -->
    <section class="presence-panel">

        <div class="presence-panel-title">
            <h2>Taux d'assiduité</h2>
            <p>Présences et retards rapportés aux séances enregistrées.</p>
        </div>

        <div class="attendance-progress">

            <div class="attendance-progress-bar"
                style="width: <?= $tauxAssiduite ?>%;">
            </div>

        </div>

        <p class="attendance-progress-label">
            <?= $tauxAssiduite ?> % d'assiduité
        </p>

    </section>

    <!-- Historique individuel -->
    <section class="presence-panel">

        <div class="presence-panel-title">
            <h2>Historique des séances</h2>
            <p><?= count($historique) ?> séance(s) enregistrée(s).</p>
        </div>

        <div class="presence-table-wrapper">

            <table class="presence-table">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Code du cours</th>
                        <th>Cours</th>
                        <th>Statut</th>
                        <th>Motif</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (!empty($historique)) : ?>

                        <?php foreach ($historique as $seance) : ?>

                            <tr>

                                <td>
                                    <?= date(
                                        "d/m/Y H:i",
                                        strtotime($seance["date_seance"])
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($seance["code"]) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($seance["cours"]) ?>
                                </td>

                                <td>
                                    <span class="presence-status">
                                        <?= htmlspecialchars($seance["statut"]) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($seance["motif"] ?? "-") ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else : ?>

                        <tr>
                            <td colspan="5">
                                Aucune séance enregistrée pour cet étudiant.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>
