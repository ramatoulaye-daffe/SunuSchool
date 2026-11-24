

<?php

require_once __DIR__ . "/config/database.php";

// ==========================================
// 1. STATISTIQUES GÉNÉRALES
// ==========================================

$nombreEtudiants = (int) $pdo->query(
    "SELECT COUNT(*) FROM etudiants"
)->fetchColumn();

$nombreFilieres = (int) $pdo->query(
    "SELECT COUNT(*) FROM filieres"
)->fetchColumn();

$nombreCours = (int) $pdo->query(
    "SELECT COUNT(*) FROM cours"
)->fetchColumn();

// ==========================================
// 2. STATISTIQUES DES NOTES
// ==========================================

$statsNotes = $pdo->query("
    SELECT
        COUNT(*) AS total_notes,
        AVG(note) AS moyenne,
        MAX(note) AS meilleure_note,
        MIN(note) AS petite_note
    FROM notes
")->fetch(PDO::FETCH_ASSOC);

$moyenneGenerale = $statsNotes["moyenne"] !== null
    ? (float) $statsNotes["moyenne"]
    : 0;

$totalNotes = (int) $statsNotes["total_notes"];

$meilleureNote = $statsNotes["meilleure_note"];
$petiteNote = $statsNotes["petite_note"];

// Taux de réussite sur les notes enregistrées
$tauxReussite = $totalNotes > 0
    ? (float) $pdo->query("
        SELECT
            (COUNT(CASE WHEN note >= 10 THEN 1 END) * 100.0)
            / COUNT(*)
        FROM notes
    ")->fetchColumn()
    : 0;

// ==========================================
// 3. DERNIERS ÉTUDIANTS
// ==========================================

$requeteEtudiants = $pdo->query("
    SELECT
        matricule,
        nom,
        prenom,
        created_at
    FROM etudiants
    ORDER BY id DESC
    LIMIT 5
");

$derniersEtudiants = $requeteEtudiants->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 4. PERFORMANCES PAR COURS
// ==========================================

$requetePerformances = $pdo->query("
    SELECT
        c.id,
        c.libelle,
        AVG(n.note) AS moyenne_cours
    FROM cours c
    INNER JOIN notes n ON n.cours_id = c.id
    GROUP BY c.id, c.libelle
    ORDER BY moyenne_cours DESC
    LIMIT 5
");

$performances = $requetePerformances->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 5. TOP 5 DES ÉTUDIANTS
// ==========================================

$requeteTopEtudiants = $pdo->query("
    SELECT
        e.id,
        e.matricule,
        e.nom,
        e.prenom,
        ROUND(
            SUM(n.note * c.coefficient)
            / NULLIF(SUM(c.coefficient), 0),
            2
        ) AS moyenne
    FROM etudiants e
    INNER JOIN notes n ON n.etudiant_id = e.id
    INNER JOIN cours c ON c.id = n.cours_id
    GROUP BY e.id, e.matricule, e.nom, e.prenom
    HAVING SUM(c.coefficient) > 0
    ORDER BY moyenne DESC
    LIMIT 5
");

$topEtudiants = $requeteTopEtudiants->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// 6. FORMATAGE
// ==========================================

function h($valeur): string
{
    return htmlspecialchars(
        (string) $valeur,
        ENT_QUOTES,
        "UTF-8"
    );
}

?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SunuSchool | Tableau de bord</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <div class="app-container">

        <aside class="sidebar" id="sidebar">

            <div class="logo">
                <div class="logo-icon">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <h2>SunuSchool</h2>
                    <span>GESTION SCOLAIRE</span>
                </div>
            </div>

            <div class="menu-label">MENU PRINCIPAL</div>

            <nav class="navigation">

                <a href="dashboard.php" class="nav-link active">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Tableau de bord</span>
                </a>

                <a href="etudiants/index.php" class="nav-link">
                    <i class="fa-solid fa-user-graduate"></i>
                    <span>Etudiants</span>
                </a>

                <a href="filieres/index.php" class="nav-link">
                    <i class="fa-solid fa-book-open"></i>
                    <span>Filières</span>
                </a>

                <a href="cours/index.php" class="nav-link">
                    <i class="fa-solid fa-chalkboard"></i>
                    <span>Cours</span>
                </a>

                <a href="notes/index.php" class="nav-link">
                    <i class="fa-solid fa-file-pen"></i>
                    <span>Notes</span>
                </a>

            </nav>

            <div class="sidebar-bottom">
            <a href="login.php" class="nav-link">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Déconnexion</span>
                </a>
            </div>

        </aside>

        <main class="main-content">
 
            <header class="header">

                <button class="menu-toggle" id="menuToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="header-welcome">
                    <span class="header-tag">
                        <i class="fa-solid fa-graduation-cap"></i>
                        ESPACE ADMINISTRATION
                    </span>

                    <h1>Bienvenue sur SunuSchool</h1>

                    <p>
                        Gérez votre établissement scolaire en toute simplicité.
                    </p>

                    <div class="header-date">
                        <i class="fa-regular fa-calendar"></i>
                        <?= date("d/m/Y") ?>
                    </div>
                </div>

                <div class="admin-profile">
                    <div class="avatar">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>

                    <div>
                        <strong>Administrateur</strong>
                        <span>Administration</span>
                    </div>
                </div>

            </header>

            <section class="stats-grid">

                <article class="stat-card">

                    <div class="stat-icon">
                        <i class="fa-solid fa-user-graduate"></i>
                    </div>

                    <p>Total étudiants</p>
                    <h2> <?= $nombreEtudiants ?> </h2>
                    <span>Etudiants inscrits</span>

                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <p>Total filières</p>
                    <h2> <?= $nombreFilieres ?> </h2>
                    <span>Formations disponibles</span>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <p>Total cours</p>
                    <h2> <?= $nombreCours ?> </h2>
                    <span>Cours enregistrés</span>
                </article>

                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <p>Moyenne générale</p>
                    <h2> <?= number_format((float)$moyenneGenerale, 2, ',', ' ') ?> </h2>
                    <span>Sur 20</span>
                </article>

            </section>

            <section class="dashboard-grid">

                <div class="panel performance">

                    <div class="panel-heading">
                        <div>
                            <h3>Performances scolaires</h3>
                            <p>Vue générale des résultats</p>
                        </div>

                        <span class="period">Semestre 1</span>
                    </div>
                    
                    <div class="chart-placeholder">

                        <?php if (!empty($performances)) : ?>

                            <?php foreach ($performances as $performance) : ?>

                                <?php
                                    $pourcentage = max(
                                        0,
                                        min(100, ((float) $performance["moyenne_cours"] / 20) * 100)
                                    );

                                    $hauteur = max(3, $pourcentage);
                                ?>

                                <div
                                    class="chart-bar"
                                    style="height: <?= $hauteur ?>%;"
                                    title="<?= h($performance["libelle"]) ?>"
                                >
                                    <span>
                                        <?= number_format($pourcentage, 0, ',', ' ') ?>%
                                    </span>
                                </div>

                            <?php endforeach; ?>

                        <?php else : ?>

                            <p class="empty-message">
                                Aucune note enregistrée pour le moment.
                            </p>

                        <?php endif; ?>

                    </div>

                    <div class="chart-labels">

                        <?php foreach ($performances as $performance) : ?>

                            <span title="<?= h($performance["libelle"]) ?>">
                                <?= h($performance["libelle"]) ?>
                            </span>

                        <?php endforeach; ?>

                    </div>

                </div>

                <div class="panel quick-info">

                    <h3>Résumé scolaire</h3>
                    <p>Informations générales</p>

                    <div class="info-item">
                        <span>Notes enregistrées</span>
                        <strong><?= $totalNotes ?></strong>
                    </div>

                    <div class="info-item">
                        <span>Meilleure note</span>
                        <strong>
                            <?= $meilleureNote !== null
                                ? number_format((float) $meilleureNote, 2, ',', ' ') . "/20"
                                : "—" ?>
                        </strong>
                    </div>

                    <div class="info-item">
                        <span>Plus petite note</span>
                        <strong>
                            <?= $petiteNote !== null
                                ? number_format((float) $petiteNote, 2, ',', ' ') . "/20"
                                : "—" ?>
                        </strong>
                    </div>

                    <div class="info-item">
                        <span>Taux de réussite</span>
                        <strong>
                            <?= number_format($tauxReussite, 1, ',', ' ') ?> %
                        </strong>
                    </div>

                    <div class="info-item">
                        <span>Semestre affiché</span>
                        <strong>S1</strong>
                    </div>

                </div>

            </section>

            <section class="panel students-panel top-students-panel">

                <div class="panel-heading">
                    <div>
                        <h3>Top 5 des étudiants</h3>
                        <p>Classement selon les moyennes pondérées</p>
                    </div>

                    <i class="fa-solid fa-trophy"></i>
                </div>

                <div class="table-wrapper">

                    <table>
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Matricule</th>
                                <th>Étudiant</th>
                                <th>Moyenne</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($topEtudiants)) : ?>

                                <?php foreach ($topEtudiants as $index => $etudiant) : ?>

                                    <tr>
                                        <td>
                                            <strong>
                                                <?= $index + 1 ?>e
                                            </strong>
                                        </td>

                                        <td><?= h($etudiant["matricule"]) ?></td>

                                        <td>
                                            <?= h($etudiant["prenom"] . " " . $etudiant["nom"]) ?>
                                        </td>

                                        <td class="status">
                                            <strong>
                                                <?= number_format(
                                                    (float) $etudiant["moyenne"],
                                                    2,
                                                    ',',
                                                    ' '
                                                ) ?> / 20
                                            </strong>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php else : ?>

                                <tr>
                                    <td colspan="4">
                                        Aucun classement disponible.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>
                    </table>

                </div>

            </section>

            <footer>
                <i class="fa-regular fa-copyright"></i>
                2026 SunuSchool - Gestion scolaire intelligente.
            </footer> 

        </main>

    </div>

    <script src="assets/js/app.js"></script>

</body>
</html>
