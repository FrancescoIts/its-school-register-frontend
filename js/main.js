document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById("theme-toggle");
    const body = document.body;

    // Se in localStorage troviamo "enabled", attiviamo il tema scuro
    if (localStorage.getItem("darkMode") === "enabled") {
        body.classList.add("dark-mode");
        themeToggle.textContent = "☀️ Tema Chiaro";
    } else {
        // Altrimenti restiamo sul tema chiaro di base
        themeToggle.textContent = "🌙 Tema Scuro";
    }

    themeToggle.addEventListener("click", function () {
        // Aggiunge la classe di transizione per un effetto graduale
        body.classList.add("theme-transition");

        // Piccolo timeout per consentire alla classe di transizione di "attivarsi"
        setTimeout(() => {
            // Alterna la classe dark-mode sul body
            body.classList.toggle("dark-mode");

            // Se ora siamo in dark-mode, salviamo la preferenza
            if (body.classList.contains("dark-mode")) {
                localStorage.setItem("darkMode", "enabled");
                themeToggle.textContent = "☀️ Tema Chiaro";
            } else {
                // Altrimenti torniamo al tema di base
                localStorage.setItem("darkMode", "disabled");
                themeToggle.textContent = "🌙 Tema Scuro";
            }

            // Rimuove la classe theme-transition dopo la durata della transizione
            setTimeout(() => {
                body.classList.remove("theme-transition");
            }, 400); // durata 0.4s
        }, 10);
    });
});
