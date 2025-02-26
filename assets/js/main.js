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

    // Barra di progresso e animazioni...
    document.addEventListener("scroll", function() {
        const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        const scrollHeight = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrollPercent = (scrollTop / scrollHeight) * 100;
        document.getElementById("scrollProgress").style.width = scrollPercent + "%";
    });
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.dashboard').forEach(dashboard => {
            const section = dashboard.dataset.section;
            const isClosed = Cookies.get(`dashboard_${section}`) === 'closed';
            if (isClosed) {
                dashboard.classList.add('closed');
            }
    
            // Controllo se l'elemento esiste prima di aggiungere l'evento
            const header = dashboard.querySelector('.dashboard-header');
            if (header) {
                header.addEventListener('click', () => {
                    dashboard.classList.toggle('closed');
                    const state = dashboard.classList.contains('closed') ? 'closed' : 'open';
                    Cookies.set(`dashboard_${section}`, state, { expires: 7 });
                });
            }
        });
    });

let mybutton = document.getElementById("btn-back-to-top");

// Mostra o nascondi il bottone quando l'utente scorre
window.onscroll = function () {
  scrollFunction();
};

function scrollFunction() {
  if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
    mybutton.style.opacity = "1"; // Appare gradualmente
    mybutton.style.pointerEvents = "auto"; // Abilita i click
  } else {
    mybutton.style.opacity = "0"; // Scompare gradualmente
    mybutton.style.pointerEvents = "none"; // Disabilita i click
  }
}

// Scroll lento e fluido
mybutton.addEventListener("click", smoothScrollToTop);

function smoothScrollToTop() {
  const scrollDuration = 800; // Durata dello scroll in ms
  const scrollStep = -window.scrollY / (scrollDuration / 15); // Passo per ogni intervallo
  const scrollInterval = setInterval(() => {
    if (window.scrollY !== 0) {
      window.scrollBy(0, scrollStep);
    } else {
      clearInterval(scrollInterval);
    }
  }, 15);
}


document.addEventListener("DOMContentLoaded", function () {
    function checkSession() {
        fetch('../utils/check_session.php')
            .then(response => response.json())
            .then(data => {
                if (!data.session_active) {
                    Swal.fire({
                        title: "Sessione Scaduta",
                        text: "La tua sessione è scaduta. Verrai disconnesso.",
                        icon: "warning",
                        confirmButtonText: "OK",
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        window.location.href = "../utils/logout.php";
                    });
                }
            })
            .catch(error => console.error("Errore nel controllo della sessione:", error));
    }

    checkSession();
    setInterval(checkSession, 60000); 
});
