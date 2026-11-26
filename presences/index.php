
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
    SELECT
        c.id,
        c.libelle,
        GROUP_CONCAT(fc.filiere_id) AS filieres_ids
    FROM cours c
    INNER JOIN filiere_cours fc ON fc.cours_id = c.id
    GROUP BY c.id, c.libelle
    ORDER BY c.libelle ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les étudiants
$requeteEtudiants = $pdo->query("
    SELECT 
        e.id,
        e.matricule,
        e.nom,
        e.prenom,
        e.filiere_id,
        f.nom AS filiere
    FROM etudiants e
    INNER JOIN filieres f ON e.filiere_id = f.id
    ORDER BY e.nom ASC
");
$etudiants = $requeteEtudiants->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des présences | SunuSchool</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>

<body>

    <main class="presence-container">

        <header class="presence-header">

            <div>
                <span class="presence-tag">
                    <i class="fa-solid fa-calendar-check"></i>
                    GESTION SCOLAIRE
                </span>

                <h1>Gestion des présences</h1>

                <p>Enregistrez et suivez les présences des étudiants.</p>
            </div>

            <a href="../dashboard.php" class="presence-back">
                <i class="fa-solid fa-arrow-left"></i>
                Retour au dashboard
            </a>

        </header>

        <div class="presence-panel">

            <div class="presence-panel-title">
                <h2>Nouvelle séance</h2>
                <p>Sélectionnez les informations de la séance.</p>
            </div>

            <form action="enregistrer.php" method="POST">

                <div class="presence-filters">

                    <div class="presence-field">
                        <label>Filière</label>

                        <select name="filiere_id" id="filtreFiliere" required>
                            <option value="">Choisir une filière</option>

                            <?php foreach ($filieres as $filiere) : ?>
                               <option value="<?= $filiere['id'] ?>">
                                    <?= htmlspecialchars($filiere['nom']) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="presence-field">
                        <label>Cours</label>

                        <select name="cours_id" id="filtreCours" required>
                            <option value="">Choisir un cours</option>

                            <?php foreach ($cours as $matiere) : ?>
                                <option
                                    value="<?= (int)$matiere['id'] ?>"
                                    data-filieres="<?= htmlspecialchars($matiere['filieres_ids']) ?>"
                                >
                                    <?= htmlspecialchars($matiere['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                    </div>

                    <div class="presence-field">
                        <label>Date et heure de la séance</label>

                        <input
                            type="datetime-local"
                            name="date_seance"
                            value="<?= date('Y-m-d\TH:i') ?>"
                            required
                        >
                    </div>

                </div>

                <div class="presence-students-heading">
                    <div>
                        <h3>Liste des étudiants</h3>
                        <p>Sélectionnez le statut de chaque étudiant.</p>
                    </div>

                    <span class="student-count">
                        <?= count($etudiants) ?> étudiants
                    </span>
                </div>

                <div class="presence-table-wrapper">

                    <table class="presence-table">

                        <thead>
                            <tr>
                                <th>Matricule</th>
                                <th>Étudiant</th>
                                <th>Filière</th>
                                <th>Statut</th>
                                <th>Motif</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($etudiants)) : ?>

                                <?php foreach ($etudiants as $etudiant) : ?>

                                    <tr class="presence-student-row"
                                        data-filiere="<?= (int)$etudiant["filiere_id"] ?>">
                                        <td>
                                            <?= htmlspecialchars($etudiant["matricule"]) ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $etudiant["prenom"] . " " . $etudiant["nom"]
                                                ) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($etudiant["filiere"]) ?>
                                        </td>

                                        <td>
                                            <select
                                                name="statuts[<?= (int)$etudiant["id"] ?>]"
                                                class="status-select"
                                                required
                                            >
                                                <option value="Présent">Présent</option>
                                                <option value="Absent">Absent</option>
                                                <option value="Retard">Retard</option>
                                            </select>
                                        </td>

                                        <td>
                                            <input
                                                type="text"
                                                name="motifs[<?= (int)$etudiant["id"] ?>]"
                                                placeholder="Motif facultatif"
                                            >
                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else : ?>

                                <tr>
                                    <td colspan="5">
                                        Aucun étudiant enregistré.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

                <div class="presence-actions">

                    <button type="reset" class="presence-reset">
                        <i class="fa-solid fa-rotate-left"></i>
                        Réinitialiser
                    </button>

                    <button type="submit" class="presence-submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Enregistrer les présences
                    </button>

                </div>

            </form>

        </div>

    </div>

    <script src="../assets/js/presences.js"></script>

</body>
</html>