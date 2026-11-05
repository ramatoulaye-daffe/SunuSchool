
<?php

require_once "../config/database.php";

// Récupération des notes avec les étudiants et les cours
$requete = $pdo->prepare("
    SELECT
        notes.id,
        notes.note,
        notes.semestre,
        etudiants.nom,
        etudiants.prenom,
        cours.code,
        cours.libelle
    FROM notes
    INNER JOIN etudiants
        ON notes.etudiant_id = etudiants.id
    INNER JOIN cours
        ON notes.cours_id = cours.id
    ORDER BY notes.id DESC
");

$requete->execute();
$notes = $requete->fetchAll(PDO::FETCH_ASSOC);

$messages = [
    "success" => "Note ajoutée avec succès !",
    "updated" => "Note modifiée avec succès !",
    "deleted" => "Note supprimée avec succès !"
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
    <title>Gestion des notes | SunuSchool</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <main class="main-content crud-page">

        <header class="crud-page-header">

            <div class="crud-header-title">
                <h1>Gestion des notes</h1>
                <p>Consultez et gérez les notes des étudiants.</p>
            </div>

            <div class="crud-header-actions">

                <a href="../dashboard.php" class="btn-home">
                    <i class="fa-solid fa-house"></i>
                    Retour à l'accueil
                </a>

                <a href="ajouter.php" class="btn-add">
                    <i class="fa-solid fa-plus"></i>
                    Ajouter une note
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
                    <h2> <i class="fa-solid fa-chart-line"></i> Liste des notes </h2>
                    <span class="filiere-subtitle"> <?= count($notes) ?> notes enregistrées. </span>
                    <p>
                        Consultez et gérez les notes des étudiants.
                    </p>
                </div>

                <span>
                    <a href="classement.php" class="crud-badge">
                        <i class="fa-solid fa-ranking-star"></i>
                        Voir les classements
                    </a>
                </span>

            </div>

            <div class="crud-table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Cours</th>
                            <th>Note / 20</th>
                            <th>Semestre</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (!empty($notes)): ?>

                            <?php foreach ($notes as $ligne): ?>

                                <tr>
                                    <td>
                                       <span class="student-name">
                                            <?= htmlspecialchars(
                                                $ligne["prenom"] . " " . $ligne["nom"]
                                            ) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $ligne["code"] . " - " . $ligne["libelle"]
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($ligne["note"]) ?>/20
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($ligne["semestre"]) ?>
                                    </td>

                                    <td class="action-buttons">

                                        <a class="student-edit"
                                           href="modifier.php?id=<?= $ligne["id"] ?>"
                                           title="Modifier">
                                           <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <a class="student-delete"
                                           href="supprimer.php?id=<?= $ligne["id"] ?>"
                                           title="Supprimer">
                                           <i class="fa-solid fa-trash"></i>
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="student-empty">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <p>Aucune note enregistrée pour le moment.</p>
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
