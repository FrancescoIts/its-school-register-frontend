document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById("theme-toggle");
    const body = document.body;

    
    if (localStorage.getItem("darkMode") === "enabled") {
        body.classList.add("dark-mode");
        themeToggle.textContent = "☀️";
    } else {
       
        themeToggle.textContent = "🌙";
    }

    themeToggle.addEventListener("click", function () {
        // aggiungo classe
        body.classList.add("theme-transition");

        setTimeout(() => {
            // alterno classe
            body.classList.toggle("dark-mode");

            // uso localStorage per salvare
            if (body.classList.contains("dark-mode")) {
                localStorage.setItem("darkMode", "enabled");
                themeToggle.textContent = "☀️";
            } else {
               
                localStorage.setItem("darkMode", "disabled");
                themeToggle.textContent = "🌙";
            }

            // finisce la transizione
            setTimeout(() => {
                body.classList.remove("theme-transition");
            }, 500); 
        }, 10);
    });
});