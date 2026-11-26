
document.addEventListener("DOMContentLoaded", function () {

    const filtreFiliere = document.getElementById("filtreFiliere");
    const filtreCours = document.getElementById("filtreCours");

    const lignesEtudiants = document.querySelectorAll(
        ".presence-student-row"
    );

    const compteur = document.querySelector(".student-count");

    // Filtrer les étudiants
    function filtrerEtudiants() {

        const filiereChoisie = filtreFiliere.value;
        let nombreVisible = 0;

        lignesEtudiants.forEach(function (ligne) {

            const filiereEtudiant = ligne.dataset.filiere;

            const visible =
                filiereChoisie === "" ||
                filiereEtudiant === filiereChoisie;

            ligne.style.display = visible ? "" : "none";

            if (visible) {
                nombreVisible++;
            }
        });

        compteur.textContent = nombreVisible + " étudiant(s)";
    }

    // Filtrer les cours
    function filtrerCours() {

        const filiereChoisie = filtreFiliere.value;
        const options = filtreCours.querySelectorAll("option");

        options.forEach(function (option) {

            if (option.value === "") {
                return;
            }

            const filieres = (option.dataset.filieres || "").split(",");

            const visible =
                filiereChoisie === "" ||
                filieres.includes(filiereChoisie);

            option.hidden = !visible;
            option.disabled = !visible;

            if (!visible && option.selected) {
                filtreCours.value = "";
            }
        });
    }

    // Déclencher les deux filtres
    filtreFiliere.addEventListener("change", function () {
        filtrerEtudiants();
        filtrerCours();
    });

});
