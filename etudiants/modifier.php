
<?php

require_once "../config/database.php";

$erreur = "";

// Verifier l'identifiant
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: index.php");
    exit;
}

// Recuperer l'etudiant
$stmt = $pdo->prepare("SELECT * FROM etudiants WHERE id = ?");
$stmt->execute([$id]);

$etudiant = $stmt->fetch();

if (!$etudiant) {
    header("Location: index.php");
    exit;
}

// Recuperer les filieres
$filieres = $pdo->query("
    SELECT id, nom FROM filieres ORDER BY nom ASC
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
        $erreur = "Adresse email invalide.";

    } else {

        try {

            $sql = "
                UPDATE etudiants
                SET matricule = :matricule,
                    nom = :nom,
                    prenom = :prenom,
                    email = :email,
                    date_naissance = :date_naissance,
                    filiere_id = :filiere_id
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":matricule" => $matricule,
                ":nom" => $nom,
                ":prenom" => $prenom,
                ":email" => $email,
                ":date_naissance" => $dateNaissance !== "" ? $dateNaissance : null,
                ":filiere_id" => $filiereId,
                ":id" => $id
            ]);

            header("Location: index.php?updated=1");
            exit;

        } catch (PDOException $e) {

            if ($e->getCode() === "23000") {
                $erreur = "Ce matricule ou cet email est déjà utilisé.";
            } else {
                $erreur = "Une erreur est survenue lors de la modification.";
            }
        }
    }

    // Conserver les valeurs saisies en cas d'erreur
    $etudiant["matricule"] = $matricule;
    $etudiant["nom"] = $nom;
    $etudiant["prenom"] = $prenom;
    $etudiant["email"] = $email;
    $etudiant["date_naissance"] = $dateNaissance;
    $etudiant["filiere_id"] = $filiereId;
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SunuSchool | Modifier un étudiant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="ss-crud">

    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Modifier un étudiant</h1>
                <p>Mettez à jour les informations de l'étudiant.</p>
            </div>

            <a href="index.php" class="view-all">
                <i class="fa-solid fa-arrow-left"></i>
                Retour à la liste
            </a>
        </header>

        <section class="ss-add-container">

            <div class="ss-add-heading">
                
                <div class="ss-add-heading-icon">
                    <i class="fa-solid fa-user-graduate"></i>
                </div>

                <div>
                    <h3>Informations de l'étudiant</h3>
                    <p>Modifiez les champs nécessaires.</p>
                </div>
            </div>

            <?php if ($erreur !== ""): ?>
                <div class="form-error">
                    <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="ss-add-grid">

                    <div class="ss-add-field">
                        <label>Matricule *</label>
                        <input type="text" name="matricule"
                            value="<?= htmlspecialchars($etudiant["matricule"]) ?>" required>
                    </div>

                    <div class="ss-add-field">
                        <label>Nom *</label>
                        <input type="text" name="nom"
                            value="<?= htmlspecialchars($etudiant["nom"]) ?>" required>
                    </div>

                    <div class="ss-add-field">
                        <label>Prénom *</label>
                        <input type="text" name="prenom"
                            value="<?= htmlspecialchars($etudiant["prenom"]) ?>" required>
                    </div>

                    <div class="ss-add-field">
                        <label>Email *</label>
                        <input type="email" name="email"
                            value="<?= htmlspecialchars($etudiant["email"]) ?>" required>
                    </div>

                    <div class="ss-add-field">
                        <label>Date de naissance</label>
                        <input type="date" name="date_naissance"
                            value="<?= htmlspecialchars($etudiant["date_naissance"] ?? "") ?>">
                    </div>

                    <div class="ss-add-field">
                        <label>Filière *</label>
                        <select name="filiere_id" required>

                            <?php foreach ($filieres as $filiere): ?>
                                <option value="<?= $filiere["id"] ?>"
                                    <?= $etudiant["filiere_id"] == $filiere["id"] ? "selected" : "" ?>>
                                    <?= htmlspecialchars($filiere["nom"]) ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                </div>

                <div class="ss-add-actions">

                    <a href="index.php"  class="ss-add-cancel">
                        <i class="fa-solid fa-xmark"></i>
                        Annuler
                    </a>

                    <button type="submit" class="ss-add-submit">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Enregistrer les modifications
                    </button>

                </div>

            </form>

        </section>

    </main>

</body>
</html>
