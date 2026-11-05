
<?php
require_once "../config/database.php";

$sql = "
    SELECT
        resultats.*,
        DENSE_RANK() OVER (
            PARTITION BY semestre
            ORDER BY moyenne DESC
        ) AS rang
    FROM (
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
    ) AS resultats

    ORDER BY semestre ASC, rang ASC
";

$stmt = $pdo->query($sql);
$classement = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classement des étudiants | SunuSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <main class="main-content crud-page">

        <header class="crud-page-header">

            <div class="crud-header-title">
                <h1>Classement des étudiants</h1>
                <p>Classement selon les moyennes pondérées par semestre.</p>
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
                    <h2> <i class="fa-solid fa-ranking-star"></i> Classement académique </h2>
                    <p>Retrouvez les étudiants classés selon leurs moyennes pondérées par semestre.</p>
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
                            <th>Rang</th>
                            <th>Matricule</th>
                            <th>Étudiant</th>
                            <th>Semestre</th>
                            <th>Nombre de notes</th>
                            <th>Moyenne / 20</th>
                            <th>Résultat</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($classement) > 0): ?>

                            <?php foreach ($classement as $ligne): ?>

                                <tr>

                                    <td>
                                        <span class="student-rank">
                                            <?= $ligne["rang"] ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($ligne["matricule"]) ?>
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
                                    Aucun résultat disponible.
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
