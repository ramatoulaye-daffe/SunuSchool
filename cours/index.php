
<?php
require_once "../config/database.php";

$sql = "
    SELECT
        c.id,
        c.code,
        c.libelle,
        c.coefficient,
        COUNT(n.id) AS nombre_notes
    FROM cours c
    LEFT JOIN notes n
        ON c.id = n.cours_id
    GROUP BY c.id, c.code, c.libelle, c.coefficient
    ORDER BY c.libelle ASC
";

$stmt = $pdo->query($sql);
$cours = $stmt->fetchAll();


$messages = [
    "success" => "Cours ajouté avec succès !",
    "updated" => "Cours modifié avec succès !",
    "deleted" => "Cours supprimé avec succès !"
];

$message = "";

foreach ($messages as $parametre => $texte) {
    if (($_GET[$parametre] ?? "") === "1") {
        $message = $texte;
        break;
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des cours | SunuSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <main class="main-content crud-page">

        <header class="crud-page-header">

            <div class="crud-header-title">
                <h1>Gestion des cours</h1>
                <p>Consultez et gérez les cours de formation.</p>
            </div>

            <div class="crud-header-actions">

                <a href="../dashboard.php" class="btn-home">
                    <i class="fa-solid fa-house"></i>
                    Accueil à l'accueil
                </a>

                <a href="ajouter.php" class="btn-add">
                    <i class="fa-solid fa-plus"></i>
                    Ajouter un cours
                </a>

            </div>

        </header>
        
        <?php if ($message): ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <section class="panel crud-panel">

            <div class="crud-panel-heading">
                <div>
                    <h2><i class="fa-solid fa-book-open"></i> Liste des cours </h2>
                    <p>Retrouvez toutes les cours enregistrés.</p>
                </div>

                <span class="crud-badge">
                    <i class="fa-solid fa-book-open"></i>
                    Cours
                </span>
            </div>
            
            <div class="crud-table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Intitulé du cours</th>
                            <th>Coefficient</th>
                            <th>Nombre de notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($cours) > 0): ?>

                            <?php foreach ($cours as $matiere): ?>

                                <tr>

                                    <td>
                                        <span class="cours-code">
                                            <?= htmlspecialchars($matiere["code"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($matiere["libelle"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($matiere["coefficient"]) ?>
                                    </td>

                                    <td>
                                        <?= $matiere["nombre_notes"] ?>
                                    </td>

                                    <td class="action-buttons">

                                        <a class="cours-edit"
                                            href="modifier.php?id=<?= $matiere["id"] ?>"
                                            title="Modifier">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <a class="cours-delete"
                                            href="supprimer.php?id=<?= $matiere["id"] ?>"
                                            title="Supprimer">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>

                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="cours-empty">
                                    <i class="fa-solid fa-book-open"></i>
                                    <p>Aucun cours enregistré pour le moment.</p>
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
