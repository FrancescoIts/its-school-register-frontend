
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
  