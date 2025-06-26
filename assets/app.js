import './bootstrap.js';

/*
 * Fichier JavaScript principal de votre application.
 * Il est chargé sur toutes les pages via la fonction importmap() dans base.html.twig.
 */

// ==================================================================
// LOGIQUE POUR LE CHANGEMENT DE THÈME (CLAIR/SOMBRE)
// ==================================================================

/**
 * Initialise le bouton de changement de thème s'il est présent sur la page.
 */
function setupThemeSwitcher() {
    const themeToggle = document.getElementById('theme-toggle');
    if (!themeToggle) {
        return;
    }
    themeToggle.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    });
}


// ==================================================================
// LOGIQUE POUR LE MENU DÉROULANT DES LANGUES
// ==================================================================

/**
 * Gère l'ouverture/fermeture au clic des menus déroulants.
 * Conçu pour être réutilisable pour d'autres menus à l'avenir.
 */
function setupDropdowns() {
    // Gère le clic sur le bouton pour ouvrir/fermer LE menu concerné
    document.querySelectorAll('.language-dropdown-toggle').forEach(toggleButton => {
        // Vérifie si un écouteur n'a pas déjà été attaché pour éviter les doublons
        if (toggleButton.dataset.listenerAttached) return;

        toggleButton.addEventListener('click', (event) => {
            // Empêche le clic de se propager et de fermer le menu immédiatement
            event.stopPropagation();
            // Ajoute/retire la classe 'is-open' sur le conteneur parent
            toggleButton.parentElement.classList.toggle('is-open');
        });
        toggleButton.dataset.listenerAttached = 'true';
    });

    // Gère le clic n'importe où sur la page pour fermer TOUS les menus ouverts
    window.addEventListener('click', () => {
        document.querySelectorAll('.language-dropdown.is-open').forEach(dropdown => {
            dropdown.classList.remove('is-open');
        });
    });
}


// ==================================================================
// POINT D'ENTRÉE PRINCIPAL
// ==================================================================

// On écoute l'événement de Turbo pour s'assurer que nos scripts
// se ré-exécutent après chaque changement de page.
document.addEventListener('turbo:load', () => {
    setupThemeSwitcher();
    setupDropdowns(); // <-- On appelle notre nouvelle fonction ici
});