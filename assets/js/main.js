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

document.addEventListener("DOMContentLoaded", function () {
    let image = document.getElementById("rotateImage");

    // stile css per fluttuare
    image.style.animation = "float 2s infinite ease-in-out";
});

// lo applico 
const style = document.createElement('style');
style.innerHTML = `
    @keyframes float {
        0% {
            transform: translateY(0px);
        }
        50% {
            transform: translateY(-5px);
        }
        100% {
            transform: translateY(0px);
        }
    }
`;
document.head.appendChild(style);


