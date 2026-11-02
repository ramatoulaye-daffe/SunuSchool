
<?php
require_once "../config/database.php";

$sql = "
    SELECT
        f.id,
        f.nom,
        COUNT(e.id) AS nombre_etudiants
    FROM filieres f
    LEFT JOIN etudiants e
        ON f.id = e.filiere_id
    GROUP BY f.id, f.nom
    ORDER BY f.nom ASC
";

$stmt = $pdo->query($sql);
$filieres = $stmt->fetchAll();

$messages = [
    "success" => "Filière ajoutée avec succès !",
    "updated" => "Filière modifiée avec succès !",
    "deleted" => "Filière supprimée avec succès !"
];

$message = null;

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
    <title>Gestion des filières | SunuSchool</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    
    <main class="main-content crud-page">

        <header class="crud-page-header">

            <div class="crud-header-title">
                <span class="filiere-subtitle">ADMINISTRATION</span>
                <h1>Gestion des filières</h1>
                <p>Consultez et gérez les filières de formation.</p>
            </div>

            <div class="crud-header-actions">

                <a href="../dashboard.php" class="btn-home">
                    <i class="fa-solid fa-house"></i>
                    Retour à l'accueil
                </a>

                <a href="ajouter.php" class="btn-add">
                    <i class="fa-solid fa-plus"></i>
                    Ajouter une filière
                </a>

            </div>

        </header>

        <?php if ($message): ?>
            <div class="alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <section class="crud-panel">

            <div class="crud-panel-heading">
                <div>
                    <h2><i class="fa-solid fa-graduation-cap"></i> Liste des filières</h2>
                    <p>Retrouvez toutes les formations enregistrées.</p>
                </div>

                <span class="crud-badge">
                    <i class="fa-solid fa-layer-group"></i>
                    Formations
                </span>
            </div>

            <div class="crud-table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom de la filière</th>
                            <th>Nombre d'étudiants</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($filieres) > 0): ?>

                            <?php foreach ($filieres as $filiere): ?>

                                <tr>
                                    <td>
                                        <span class="filiere-id">
                                            #<?= $filiere["id"] ?>
                                        </span>
                                    </td>

                                    <td class="filiere-name">
                                        <?= htmlspecialchars($filiere["nom"]) ?>
                                    </td>

                                    <td>
                                        <span class="filiere-count">
                                            <?= $filiere["nombre_etudiants"] ?> étudiant(s)
                                        </span>
                                    </td>

                                    <td class="action-buttons">

                                        <a class="filiere-edit"
                                            href="modifier.php?id=<?= $filiere["id"] ?>"
                                            title="Modifier">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <a class="filiere-delete"
                                            href="supprimer.php?id=<?= $filiere["id"] ?>"
                                            title="Supprimer">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>

                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4" class="filiere-empty">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <p>Aucune filière enregistrée.</p>
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
