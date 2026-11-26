

<?php

require_once __DIR__ . "/../config/database.php";

// Récupérer les filières
$filieres = $pdo->query("
    SELECT id, nom
    FROM filieres
    ORDER BY nom ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les cours
$cours = $pdo->query("
    SELECT id, libelle
    FROM cours
    ORDER BY libelle ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les filtres envoyés
$filiereId = filter_input(INPUT_GET, "filiere_id", FILTER_VALIDATE_INT);
$coursId = filter_input(INPUT_GET, "cours_id", FILTER_VALIDATE_INT);

$dateDebut = trim($_GET["date_debut"] ?? "");
$dateFin = trim($_GET["date_fin"] ?? "");

// Construire les conditions SQL
$conditions = [];
$parametres = [];

// Filtre par filière
if ($filiereId) {
    $conditions[] = "e.filiere_id = :filiere_id";
    $parametres["filiere_id"] = $filiereId;
}

// Filtre par cours
if ($coursId) {
    $conditions[] = "p.cours_id = :cours_id";
    $parametres["cours_id"] = $coursId;
}

// Vérifier les dates
if ($dateDebut !== "") {
    $dateObjet = DateTime::createFromFormat("Y-m-d", $dateDebut);

    if (!$dateObjet || $dateObjet->format("Y-m-d") !== $dateDebut) {
        die("La date de début est invalide.");
    }

    $conditions[] = "p.date_seance >= :date_debut";
    $parametres["date_debut"] = $dateDebut . " 00:00:00";
}

if ($dateFin !== "") {
    $dateObjet = DateTime::createFromFormat("Y-m-d", $dateFin);

    if (!$dateObjet || $dateObjet->format("Y-m-d") !== $dateFin) {
        die("La date de fin est invalide.");
    }

    $conditions[] = "p.date_seance < DATE_ADD(:date_fin, INTERVAL 1 DAY)";
    $parametres["date_fin"] = $dateFin;
}

// Vérifier la cohérence de la période
if ($dateDebut !== "" && $dateFin !== "" && $dateDebut > $dateFin) {
    die("La date de début doit être antérieure ou égale à la date de fin.");
}

$whereSQL = !empty($conditions)
    ? "WHERE " . implode(" AND ", $conditions)
    : "";

// Requête de l'historique filtré
$sqlPresences = "
    SELECT
        p.id,
        e.id AS id_etudiant,
        p.date_seance,
        p.statut,
        p.motif,
        e.matricule,
        e.nom,
        e.prenom,
        f.nom AS filiere,
        c.libelle AS cours
    FROM presences p
    INNER JOIN etudiants e ON e.id = p.etudiant_id
    INNER JOIN filieres f ON f.id = e.filiere_id
    INNER JOIN cours c ON c.id = p.cours_id
    $whereSQL
    ORDER BY p.date_seance DESC, e.nom ASC
";

$requete = $pdo->prepare($sqlPresences);
$requete->execute($parametres);

$presences = $requete->fetchAll(PDO::FETCH_ASSOC);

// Statistiques correspondant aux mêmes filtres
$sqlStatistiques = "
    SELECT
        COUNT(*) AS total,
        SUM(p.statut = 'Présent') AS presents,
        SUM(p.statut = 'Absent') AS absents,
        SUM(p.statut = 'Retard') AS retards
    FROM presences p
    INNER JOIN etudiants e ON e.id = p.etudiant_id
    $whereSQL
";

$requeteStats = $pdo->prepare($sqlStatistiques);
$requeteStats->execute($parametres);

$statistiques = $requeteStats->fetch(PDO::FETCH_ASSOC);

$total = (int) $statistiques["total"];
$nombrePresents = (int) $statistiques["presents"];
$nombreAbsents = (int) $statistiques["absents"];
$nombreRetards = (int) $statistiques["retards"];

$tauxPresence = $total > 0
    ? round(($nombrePresents / $total) * 100, 2)
    : 0;

?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des présences | SunuSchool</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <main class="presence-container">

        <header class="presence-header">
            <div>
                <span class="presence-tag">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    SUIVI SCOLAIRE
                </span>

                <h1>Historique des présences</h1>
                <p>Consultez toutes les présences enregistrées.</p>
            </div>

            <a href="index.php" class="presence-back">
                <i class="fa-solid fa-arrow-left"></i>
                Nouvelle séance
            </a>
        </header>

        <!-- Statistiques -->
        <section class="presence-stats">

            <div class="presence-stat-card">
                <span>Total</span>
                <h2><?= $total ?></h2>
                <i class="fa-solid fa-clipboard-list"></i>
            </div>

            <div class="presence-stat-card">
                <span>Présents</span>
                <h2><?= $nombrePresents ?></h2>
                <i class="fa-solid fa-circle-check"></i>
            </div>

            <div class="presence-stat-card">
                <span>Absents</span>
                <h2><?= $nombreAbsents ?></h2>
                <i class="fa-solid fa-circle-xmark"></i>
            </div>

            <div class="presence-stat-card">
                <span>Retards</span>
                <h2><?= $nombreRetards ?></h2>
                <i class="fa-solid fa-clock"></i>
            </div>

            <div class="presence-stat-card">
                <span>Taux de présence</span>
                <h2><?= $tauxPresence ?> %</h2>
                <i class="fa-solid fa-chart-line"></i>
            </div>

        </section>

        <!-- Tableau -->
        <section class="presence-panel">

            <div class="presence-panel-title">
                <h2>Liste des présences</h2>
                <p><?= count($presences) ?> enregistrement(s)</p>
            </div>

            <form method="GET" action="historique.php" class="history-filters">

                <div class="presence-field">
                    <label for="filiere_id">Filière</label>

                    <select name="filiere_id" id="filiere_id">
                        <option value="">Toutes les filières</option>

                        <?php foreach ($filieres as $filiere) : ?>
                            <option
                                value="<?= (int)$filiere['id'] ?>"
                                <?= $filiereId == $filiere['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($filiere['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="presence-field">
                    <label for="cours_id">Cours</label>

                    <select name="cours_id" id="cours_id">
                        <option value="">Tous les cours</option>

                        <?php foreach ($cours as $matiere) : ?>
                            <option
                                value="<?= (int)$matiere['id'] ?>"
                                <?= $coursId == $matiere['id'] ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($matiere['libelle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="presence-field">
                    <label for="date_debut">Date de début</label>

                    <input
                        type="date"
                        name="date_debut"
                        id="date_debut"
                        value="<?= htmlspecialchars($dateDebut) ?>"
                    >
                </div>

                <div class="presence-field">
                    <label for="date_fin">Date de fin</label>

                    <input
                        type="date"
                        name="date_fin"
                        id="date_fin"
                        value="<?= htmlspecialchars($dateFin) ?>"
                    >
                </div>

                <div class="history-filter-actions">

                    <button type="submit" class="presence-submit">
                        <i class="fa-solid fa-filter"></i>
                        Filtrer
                    </button>

                    <a href="historique.php" class="presence-reset">
                        <i class="fa-solid fa-rotate-left"></i>
                        Réinitialiser
                    </a>

                </div>

            </form>


            <div class="presence-table-wrapper">

                <table class="presence-table">

                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Matricule</th>
                            <th>Étudiant</th>
                            <th>Filière</th>
                            <th>Cours</th>
                            <th>Statut</th>
                            <th>Motif</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($presences)) : ?>

                            <?php foreach ($presences as $presence) : ?>

                                <tr>
                                    <td>
                                        <?= date(
                                            "d/m/Y H:i",
                                            strtotime($presence["date_seance"])
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($presence["matricule"]) ?>
                                    </td>

                                    <td>
                                        <a
                                            href="fiche.php?id=<?= (int)$presence['id_etudiant'] ?>"
                                            class="student-history-link"
                                        >
                                            <?= htmlspecialchars(
                                                $presence["prenom"] . " " . $presence["nom"]
                                            ) ?>
                                        </a>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($presence["filiere"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($presence["cours"]) ?>
                                    </td>

                                    <td>
                                        <span class="presence-status">
                                            <?= htmlspecialchars($presence["statut"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($presence["motif"] ?? "-") ?>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else : ?>

                            <tr>
                                <td colspan="7">
                                    Aucune présence enregistrée pour le moment.
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
