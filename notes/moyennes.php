
<?php
require_once "../config/database.php";

// Calcul des moyennes pondérées par étudiant et par semestre
$sql = "
    SELECT
        e.id,
        e.matricule,
        e.nom,
        e.prenom,
        n.semestre,
        COUNT(n.id) AS nombre_notes,
        ROUND(
            SUM(n.note * c.coefficient) /
            NULLIF(SUM(c.coefficient), 0),
            2
        ) AS moyenne
    FROM etudiants e

    INNER JOIN notes n
        ON e.id = n.etudiant_id

    INNER JOIN cours c
        ON n.cours_id = c.id

    GROUP BY
        e.id,
        e.matricule,
        e.nom,
        e.prenom,
        n.semestre

    ORDER BY n.semestre ASC, moyenne DESC
";

$stmt = $pdo->query($sql);
$moyennes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moyennes des étudiants | SunuSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <main class="main-content crud-page">

        <header class="crud-page-header">

            <div class="crud-header-title">
                <h1>Moyennes des étudiants</h1>
                <p>Consultez les résultats pondérés par semestre.</p>
            </div>

            <div class="crud-header-actions">

                <a href="../dashboard.php" class="btn-home">
                    <i class="fa-solid fa-house"></i>
                    Retour à l'accueil
                </a>

                <a href="index.php" class="btn-add">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour aux notes
                </a>

            </div>

        </header>

        <section class="crud-panel">

            <div class="crud-panel-heading">
                <div>
                    <h2> <i class="fa-solid fa-chart-column"></i> Résultats académiques</h2>
                    <p>Retrouvez les moyennes pondérées par semestre.</p>
                </div>

                <span class="crud-badge">
                    <i class="fa-solid fa-chart-column"></i>
                    Moyennes pondérées
                </span>
            </div>

            <div class="crud-table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Étudiant</th>
                            <th>Semestre</th>
                            <th>Nombre de notes</th>
                            <th>Moyenne / 20</th>
                            <th>Résultat</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($moyennes) > 0): ?>

                            <?php foreach ($moyennes as $ligne): ?>

                                <tr>
                                    <td>
                                        <span class="etudiant-mat">
                                            <?= htmlspecialchars($ligne["matricule"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $ligne["prenom"] . " " . $ligne["nom"]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($ligne["semestre"]) ?>
                                    </td>

                                    <td>
                                        <?= $ligne["nombre_notes"] ?>
                                    </td>

                                    <td>
                                        <strong>
                                            <?= number_format(
                                                (float)$ligne["moyenne"],
                                                2,
                                                ',',
                                                ' '
                                            ) ?>
                                        </strong>
                                    </td>

                                    <td>
                                        <?php if ($ligne["moyenne"] >= 10): ?>
                                            <span class="result-admitted">
                                                Admis
                                            </span>
                                        <?php else: ?>
                                            <span class="result-failed">
                                                Non admis
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="student-empty">
                                    <i class="fa-solid fa-folder-open"></i>
                                    Aucune moyenne disponible.
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
