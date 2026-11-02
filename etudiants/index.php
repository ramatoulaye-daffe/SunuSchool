
<?php

require_once "../config/database.php";

// Recuperer les etudiants et leurs filieres
$sql = "
    SELECT
        e.id,
        e.matricule,
        e.nom,
        e.prenom,
        e.email,
        f.nom AS filiere
    FROM etudiants e
    INNER JOIN filieres f
        ON e.filiere_id = f.id
    ORDER BY e.id DESC
";

$stmt = $pdo->query($sql);
$etudiants = $stmt->fetchAll();

$messages = [
    "success" => "Étudiant ajouté avec succès !",
    "updated" => "Étudiant modifié avec succès !",
    "deleted" => "Étudiant supprimé avec succès !"
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
    <title>SunuSchool | Gestion des étudiants</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <main class="main-content crud-page">

        <header class="crud-page-header">

            <div class="crud-header-title">
                <h1>Gestion des étudiants</h1>
                <p>Consultez et gérez les étudiants inscrits.</p>
            </div>

            <div class="crud-header-actions">
            
                <a href="../dashboard.php" class="btn-home">
                    <i class="fa-solid fa-house"></i>
                    Retour à l'accueil
                </a>
                
                <a href="ajouter.php" class="btn-add">
                    <i class="fa-solid fa-plus"></i>
                    Ajouter un étudiant
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
                    <h2><i class="fa-solid fa-user-graduate"></i> Liste des étudiants</h2>
                    <p>
                        <?= count($etudiants) ?> étudiant(s) enregistré(s)
                    </p>
                </div>
                <span class="crud-badge">
                    <i class="fa-solid fa-user-graduate"></i> 
                    Etudiants
                </span>
            </div>
            
            <div class="crud-table-wrapper">

                <table class="students-table">

                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Email</th>
                            <th>Filière</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($etudiants) > 0): ?>

                            <?php foreach ($etudiants as $etudiant): ?>

                                <tr>

                                    <td>
                                        <span class="etudiant-mat">
                                            <?= htmlspecialchars($etudiant["matricule"]) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($etudiant["nom"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($etudiant["prenom"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($etudiant["email"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($etudiant["filiere"]) ?>
                                    </td>

                                    <td class="action-buttons">

                                        <a class="etudiant-edit"
                                            href="modifier.php?id=<?= $etudiant["id"] ?>"
                                            title="Modifier">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>

                                        <a class="etudiant-delete"
                                            href="supprimer.php?id=<?= $etudiant["id"] ?>"
                                            title="Supprimer">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" class="etudiant-empty">
                                    <i class="fa-solid fa-folder-open"></i>
                                    <p> Aucun étudiant enregistré.</p>
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
