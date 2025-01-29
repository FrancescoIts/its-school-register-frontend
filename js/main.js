document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById("theme-toggle");
    const body = document.body;

    // Controlla se il tema scuro è già attivato
    if (localStorage.getItem("darkMode") === "enabled") {
        body.classList.add("dark-mode");
        themeToggle.textContent = "☀️ Tema Chiaro";
    }

    themeToggle.addEventListener("click", function () {
        // Aggiunge un effetto di dissolvenza al cambio tema
        body.classList.add("theme-transition");

        setTimeout(() => {
            body.classList.toggle("dark-mode");

            if (body.classList.contains("dark-mode")) {
                localStorage.setItem("darkMode", "enabled");
                themeToggle.textContent = "☀️ Tema Chiaro";
            } else {
                localStorage.setItem("darkMode", "disabled");
                themeToggle.textContent = "🌙 Tema Scuro";
            }

            // Rimuove la classe di transizione dopo il completamento
            setTimeout(() => {
                body.classList.remove("theme-transition");
            }, 400); // Durata della transizione
        }, 10);
    });
});
