
<?php

require_once "../config/database.php";

$erreur = "";

// Recuperer les filieres
$filieres = $pdo->query("
    SELECT id, nom
    FROM filieres
    ORDER BY nom ASC
")->fetchAll();

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $matricule = trim($_POST["matricule"] ?? "");
    $nom = trim($_POST["nom"] ?? "");
    $prenom = trim($_POST["prenom"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $dateNaissance = $_POST["date_naissance"] ?? "";
    $filiereId = (int) ($_POST["filiere_id"] ?? 0);

    if (
        $matricule === "" ||
        $nom === "" ||
        $prenom === "" ||
        $email === "" ||
        $filiereId <= 0
    ) {
        $erreur = "Veuillez remplir tous les champs obligatoires.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "Veuillez saisir une adresse email valide.";

    } else {

        try {

            $sql = "
                INSERT INTO etudiants
                (matricule, nom, prenom, email, date_naissance, filiere_id)
                VALUES
                (:matricule, :nom, :prenom, :email, :date_naissance, :filiere_id)
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":matricule" => $matricule,
                ":nom" => $nom,
                ":prenom" => $prenom,
                ":email" => $email,
                ":date_naissance" => $dateNaissance !== "" ? $dateNaissance : null,
                ":filiere_id" => $filiereId
            ]);

            header("Location: index.php?success=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $erreur = "Ce matricule, cet email ou cette filière pose un problème. Vérifiez les informations.";
            } else {
                $erreur = "Une erreur est survenue lors de l'enregistrement.";
            }

        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SunuSchool | Ajouter un étudiant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="ss-crud">

    <main class="main-content">

        <header class="topbar">

            <div>
                <h1>Ajouter un étudiant</h1>
                <p>Enregistrez un nouvel étudiant dans SunuSchool.</p>
            </div>

            <a href="index.php" class="view-all">
                <i class="fa-solid fa-arrow-left"></i>
                Retour à la liste
            </a>

        </header>

        <?php if ($erreur !== ""): ?>

            <div class="ss-add-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($erreur) ?>
            </div>

        <?php endif; ?>

        <section class="ss-add-container" >

            <div class="ss-add-heading">

                <div class="ss-add-heading-icon">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>

                <div>
                    <h3>Informations de l'étudiant</h3>
                    <p>Les champs marqués d'un * sont obligatoires.</p>
                </div>

            </div>

            <form method="POST" action="">

                <div class="ss-add-grid">

                    <div class="ss-add-field">
                        <label for="matricule">Matricule *</label>
                        <input
                            type="text"
                            id="matricule"
                            name="matricule"
                            placeholder="Ex : ETU006"
                            value="<?= htmlspecialchars($_POST["matricule"] ?? "") ?>"
                            required
                        >
                    </div>

                    <div class="ss-add-field">
                        <label for="nom">Nom *</label>
                        <input
                            type="text"
                            id="nom"
                            name="nom"
                            placeholder="Nom de famille"
                            value="<?= htmlspecialchars($_POST["nom"] ?? "") ?>"
                            required
                        >
                    </div>

                    <div class="ss-add-field">
                        <label for="prenom">Prénom *</label>
                        <input
                            type="text"
                            id="prenom"
                            name="prenom"
                            placeholder="Prénom"
                            value="<?= htmlspecialchars($_POST["prenom"] ?? "") ?>"
                            required
                        >
                    </div>

                    <div class="ss-add-field">
                        <label for="email">Adresse email *</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="exemple@email.com"
                            value="<?= htmlspecialchars($_POST["email"] ?? "") ?>"
                            required
                        >
                    </div>

                    <div class="ss-add-field">
                        <label for="date_naissance">Date de naissance</label>
                        <input
                            type="date"
                            id="date_naissance"
                            name="date_naissance"
                            value="<?= htmlspecialchars($_POST["date_naissance"] ?? "") ?>"
                        >
                    </div>

                    <div class="ss-add-field">
                        <label for="filiere_id">Filière *</label>

                        <select name="filiere_id" id="filiere_id" required>

                            <option value="">Sélectionner une filière</option>

                            <?php foreach ($filieres as $filiere): ?>

                                <option
                                    value="<?= $filiere["id"] ?>"
                                    <?= (($_POST["filiere_id"] ?? "") == $filiere["id"]) ? "selected" : "" ?>
                                >
                                    <?= htmlspecialchars($filiere["nom"]) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                </div>

                <div class="ss-add-actions">

                    <a href="index.php" class="ss-add-cancel">
                        <i class="fa-solid fa-xmark"></i>
                        Annuler
                    </a>

                    <button type="submit" class="ss-add-submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Enregistrer l'étudiant
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>

</html>
