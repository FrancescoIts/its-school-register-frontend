document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById("theme-toggle");
    const body = document.body;

    
    if (localStorage.getItem("darkMode") === "enabled") {
        body.classList.add("dark-mode");
        themeToggle.textContent = "☀️ Tema Chiaro";
    } else {
       
        themeToggle.textContent = "🌙 Tema Scuro";
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
                themeToggle.textContent = "☀️ Tema Chiaro";
            } else {
               
                localStorage.setItem("darkMode", "disabled");
                themeToggle.textContent = "🌙 Tema Scuro";
            }

            // finisce la transizione
            setTimeout(() => {
                body.classList.remove("theme-transition");
            }, 500); // durata 0.5s
        }, 10);
    });
});

document.addEventListener("DOMContentLoaded", function () {
    let image = document.getElementById("rotateImage");

    image.addEventListener("mouseenter", function () {
        image.style.transition = "transform 0.5s ease-in-out";
        image.style.transform = "rotate(360deg)";
    });

    image.addEventListener("mouseleave", function () {
        image.style.transform = "rotate(0deg)";
    });
});
