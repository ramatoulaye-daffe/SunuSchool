
<?php

require_once __DIR__ . "/config/database.php";

// Nombre d'etudiants
$nombreEtudiants = $pdo->query(
    "SELECT COUNT(*) FROM etudiants"
)->fetchColumn();

// Nombre de filieres
$nombreFilieres = $pdo->query(
    "SELECT COUNT(*) FROM filieres"
)->fetchColumn();

// Nombre de cours
$nombreCours = $pdo->query(
    "SELECT COUNT(*) FROM cours"
)->fetchColumn();

// Statistiques des notes
$statsNotes = $pdo->query("
    SELECT
        COUNT(*) AS total_notes,
        AVG(note) AS moyenne,
        MAX(note) AS meilleure_note,
        MIN(note) AS petite_note
    FROM notes
")->fetch();

$moyenneGenerale = $statsNotes["moyenne"] ?? 0;

// Recuperer les 5 derniers etudiants
$requeteEtudiants = $pdo->query("
    SELECT matricule, nom, prenom
    FROM etudiants
    ORDER BY id DESC
    LIMIT 5
");

$derniersEtudiants = $requeteEtudiants->fetchAll();

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

        <!-- SIDEBAR -->
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

        <!-- CONTENU PRINCIPAL -->
        <main class="main-content">

            <header class="header">

                <button class="menu-toggle" id="menuToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div>
                    <h1>Tableau de bord</h1>
                    <p>Bienvenue dans votre espace d'administration.</p>
                </div>

                <div class="admin-profile">
                    <div class="avatar">A</div>
                    <div>
                        <strong>Administrateur</strong>
                        <span>Administration</span>
                    </div>
                </div>

            </header>

            <!-- STATISTIQUES -->
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

            <!-- SECTION CONTENU -->
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
                        <div class="chart-bar" style="height:65%">
                            <span>65%</span>
                        </div>

                        <div class="chart-bar" style="height:85%">
                            <span>85%</span>
                        </div>

                        <div class="chart-bar" style="height:55%">
                            <span>55%</span>
                        </div>

                        <div class="chart-bar" style="height:95%">
                            <span>95%</span>
                        </div>

                        <div class="chart-bar" style="height:75%">
                            <span>75%</span>
                        </div>
                    </div>

                    <div class="chart-labels">
                        <span>PHP</span>
                        <span>SQL</span>
                        <span>WEB</span>
                        <span>PYTHON</span>
                        <span>AUTRES</span>
                    </div>

                </div>

                <div class="panel quick-info">

                    <h3>Résumé scolaire</h3>
                    <p>Informations générales</p>

                    <div class="info-item">
                        <span>Notes enregistrées</span>
                        <strong><?= $statsNotes["total_notes"] ?></strong>
                    </div>

                    <div class="info-item">
                        <span>Meilleure note</span>
                        <strong><?= number_format((float)$statsNotes["meilleure_note"], 2, ',', ' ') ?>/20</strong>
                    </div>

                    <div class="info-item">
                        <span>Plus petite note</span>
                        <strong><?= number_format((float)$statsNotes["petite_note"], 2, ',', ' ') ?>/20</strong>
                    </div>

                    <div class="info-item">
                        <span>Semestre actif</span>
                        <strong>S1</strong>
                    </div>

                </div>

            </section>

            <!-- DERNIERS ETUDIANTS -->
            <section class="panel students-panel">

                <div class="panel-heading">
                    <div>
                        <h3>Derniers étudiants</h3>
                        <p>Liste des étudiants enregistrés</p>
                    </div>

                    <a href="etudiants/index.php" class="view-all">
                        Voir tout
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>

                <div class="table-wrapper">

                    <table>
                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Statut</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php foreach ($derniersEtudiants as $etudiant): ?>

                                <tr>
                                    <td><?= htmlspecialchars($etudiant["matricule"]) ?></td>

                                    <td><?= htmlspecialchars($etudiant["nom"]) ?></td>

                                    <td><?= htmlspecialchars($etudiant["prenom"]) ?></td>

                                    <td>
                                        <span class="status">Inscrit</span>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

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
