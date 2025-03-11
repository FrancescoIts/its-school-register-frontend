
  function showLoader() {
    // Prova a cercare un overlay con id 'globalLoaderOverlay', altrimenti 'loginLoaderOverlay'
    let overlay = document.getElementById('globalLoaderOverlay') || document.getElementById('loginLoaderOverlay');
    if (!overlay) {
      // Se non esiste, crealo dinamicamente
      overlay = document.createElement('div');
      overlay.id = 'globalLoaderOverlay';
      overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; justify-content: center; align-items: center;';
      // Inserisci il contenuto del loader (modifica questo markup con quello desiderato)
      overlay.innerHTML = '<div class="loader">[Il contenuto del loader qui]</div>';
      document.body.appendChild(overlay);
    }
    // Se l'overlay esiste, impostalo su flex
    if (overlay) {
      overlay.style.display = 'flex';
    }
  }
  
  // Attacca il listener per il submit su tutti i form
  document.addEventListener('DOMContentLoaded', function() {
    // Se la pagina non è completamente caricata, mostra il loader immediatamente
if (document.readyState !== 'complete') {
  showLoader();
}
    document.querySelectorAll('form').forEach(form => {
      form.addEventListener('submit', showLoader);
    });
  
    // Attacca il listener per i link con la classe "get-overlay"
    document.querySelectorAll('a.get-overlay').forEach(link => {
      link.addEventListener('click', showLoader);
    });
    
    const originalFetch = window.fetch;
    window.fetch = function() {
      if (!(arguments[0] && arguments[0].includes('../utils/api_check_session.php'))) {
        showLoader();
      }
      return originalFetch.apply(this, arguments)
        .finally(() => {
          // Nasconde l'overlay se esiste
          const overlay = document.getElementById('globalLoaderOverlay') || document.getElementById('loginLoaderOverlay');
          if (overlay) {
            overlay.style.display = 'none';
          }
        });
    };
  });
  

// Funzione per nascondere il loader
function hideLoader() {
  const overlay = document.getElementById('globalLoaderOverlay') || document.getElementById('loginLoaderOverlay');
  if (overlay) {
    overlay.style.display = 'none';
  }
}

// Se la pagina non è completamente caricata, mostra il loader immediatamente
if (document.readyState !== 'complete') {
  showLoader();
}

// Nascondi il loader una volta che la pagina è completamente caricata
window.addEventListener('load', hideLoader);

document.getElementById("attendanceForm").addEventListener("submit", function(e) {
  // Ferma la propagazione per evitare che il listener globale (che mostra il loader) si attivi
  e.stopImmediatePropagation();
  e.preventDefault(); // impedisce l'invio automatico del form
  
  // Nascondi il loader, se già attivo, per mostrare correttamente lo Swal
  hideLoader();

  var form = this;
  var moduleSelect = document.getElementById("id_module");
  var moduleVal = parseInt(moduleSelect.value);
  
  if(moduleVal > 0) {
     var moduleText = moduleSelect.options[moduleSelect.selectedIndex].text;
     Swal.fire({
       title: 'Conferma modulo',
       text: 'Sei sicuro di aver scelto il modulo: ' + moduleText + '?',
       icon: 'question',
       showCancelButton: true,
       confirmButtonText: 'Sì, salva',
       cancelButtonText: 'Annulla'
     }).then((result) => {
       if(result.isConfirmed) {
           // Rimuovi temporaneamente il listener globale (se presente) per evitare doppie chiamate
           form.removeEventListener("submit", showLoader);
           form.submit();
       }
     });
  } else {
     Swal.fire({
       title: 'Modulo non selezionato',
       text: 'Non hai selezionato nessun modulo. Vuoi proseguire senza associarlo?',
       icon: 'warning',
       showCancelButton: true,
       confirmButtonText: 'Sì, salva',
       cancelButtonText: 'Annulla'
     }).then((result) => {
       if(result.isConfirmed) {
           form.removeEventListener("submit", showLoader);
           form.submit();
       }
     });
  }
});
