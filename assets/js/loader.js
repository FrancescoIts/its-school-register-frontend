// Mostra il loader
function showLoader() {
  let overlay = document.getElementById('globalLoaderOverlay') || document.getElementById('loginLoaderOverlay');
  if (!overlay) {
    // Creiamo dinamicamente un overlay se non esiste
    overlay = document.createElement('div');
    overlay.id = 'globalLoaderOverlay';
    overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; justify-content: center; align-items: center;';
    // Inseriamo un contenuto di esempio (sostituisci con il tuo loader preferito)
    overlay.innerHTML = '<div class="loader">Caricamento...</div>';
    document.body.appendChild(overlay);
  }
  overlay.style.display = 'flex';
}

// Nascondi il loader
function hideLoader() {
  const overlay = document.getElementById('globalLoaderOverlay') || document.getElementById('loginLoaderOverlay');
  if (overlay) {
    overlay.style.display = 'none';
  }
}

document.addEventListener('DOMContentLoaded', function() {
  // Se la pagina non è completamente caricata, mostriamo il loader
  if (document.readyState !== 'complete') {
    showLoader();
  }
  // Attacca showLoader a tutti i form che NON hanno data-no-loader
  document.querySelectorAll('form:not([data-no-loader])').forEach(form => {
    form.addEventListener('submit', showLoader);
  });
  
  // Attacca showLoader a tutti i link con classe "get-overlay"
  document.querySelectorAll('a.get-overlay').forEach(link => {
    link.addEventListener('click', showLoader);
  });
  
  // Rimpiazziamo fetch per mostrare/nascondere il loader nelle chiamate (tranne per api_check_session.php)
  const originalFetch = window.fetch;
  window.fetch = function() {
    if (!(arguments[0] && arguments[0].includes('api_check_session.php'))) {
      showLoader();
    }
    return originalFetch.apply(this, arguments)
      .finally(() => {
        hideLoader();
      });
  };
});

window.addEventListener('load', function() {
  hideLoader();
});
