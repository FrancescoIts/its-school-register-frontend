document.addEventListener("DOMContentLoaded", function () {
    const themeToggle = document.getElementById("darkmode-toggle");
    const body = document.body;

    // Controllo dello stato del tema in localStorage
    if (localStorage.getItem("darkMode") === "enabled") {
        body.classList.add("dark-mode");
     
    } else {
     
    }

    themeToggle.addEventListener("click", function () {
        // Aggiungo una classe per la transizione del tema
        body.classList.add("theme-transition");

        setTimeout(() => {
            // Alterno la classe dark-mode
            body.classList.toggle("dark-mode");

            // Aggiorno localStorage e l'icona in base allo stato
            if (body.classList.contains("dark-mode")) {
                localStorage.setItem("darkMode", "enabled");
               
            } else {
                localStorage.setItem("darkMode", "disabled");
              
            }

            // Rimuovo la classe di transizione dopo 500ms
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
if(mybutton){
mybutton.addEventListener("click", smoothScrollToTop);
}

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

if (!window.location.pathname.endsWith("index.php")) {
    document.addEventListener("DOMContentLoaded", function () {
        function checkSession() {
            fetch('../utils/api_check_session.php')
                .then(response => {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.indexOf("application/json") !== -1) {
                        return response.json();
                    } else {
                        throw new Error("Risposta non JSON");
                    }
                })
                .then(data => {
                    if (!data.session_active) {
                        Swal.fire({
                            title: "Sessione Scaduta",
                            text: data.error || "La tua sessione è scaduta. Verrai disconnesso.",
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
}



document.addEventListener('DOMContentLoaded', function() {
    // Seleziona tutti i paragrafi all'interno di ".course-card.info"
    const paragraphs = document.querySelectorAll('.course-card.info p');
  
    // Aggiunge un listener per il click su ogni paragrafo
    paragraphs.forEach(p => {
      p.addEventListener('click', function() {
        // Toggle della classe "visible" per rimuovere o ripristinare il blur
        this.classList.toggle('visible');
      });
    });
  });
  
  
  

 // Hover icona 
 function toggleIcon(element, isHover) {
    let icon = element.querySelector("i");
    if (isHover) {
        icon.classList.remove("fa-google");
        icon.classList.add("fa-google");
        icon.style.color = "#ffffff";
    } else {
        icon.classList.remove("fa-google");
        icon.classList.add("fa-google");
        icon.style.color = "#7875b5";
    }
}


const infoButton = document.getElementById('tutorialBtn');
if (infoButton) {
        // Bottone info per aprire il tutorial
        document.getElementById('tutorialBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'Tutorial Presenze',
                html: '<p>Se l\'alunno è <strong>presente</strong> bisogna segnare la checkbox corrispondente.</p>' +
                      '<p>Se l\'alunno è <strong>assente</strong>, lasciare la checkbox deselezionata.</p>' +
                      '<p>Se l\'alunno è presente ma ha effettuato l\'ingresso o l\'uscita fuori orario, segna la presenza tramite la checkbox e poi modifica gli orari usando gli appositi campi.</p>',
                icon: 'info',
                confirmButtonText: 'OK'
            });
        });
    }