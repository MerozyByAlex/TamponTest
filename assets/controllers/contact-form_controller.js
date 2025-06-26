// assets/controllers/contact-form_controller.js

import { Controller } from '@hotwired/stimulus';

/**
 * Ce contrôleur gère le formulaire de contact, y compris :
 * - L'affichage dynamique des champs conditionnels.
 * - Le formatage automatique du numéro de téléphone.
 * - La coloration dynamique du champ de priorité.
 */
export default class extends Controller {
    // On ajoute 'priorityInput' aux cibles.
    static targets = ["subject", "prioritySection", "accessSection", "phoneInput", "priorityInput"];

    /**
     * Stimulus appelle cette méthode automatiquement chaque fois que le contrôleur
     * est connecté au DOM.
     */
    connect() {
        this.updateVisibility();
        this.updatePriorityColor(); // On l'appelle au démarrage
    }

    /**
     * Met à jour la visibilité des sections en fonction du sujet choisi.
     */
    updateVisibility() {
        if (!this.hasSubjectTarget) return;
        
        const subject = this.subjectTarget.value;
        const showPriority = ['connection', 'feature'].includes(subject);
        const showAccess = subject === 'access';

        if (this.hasPrioritySectionTarget) {
            this.prioritySectionTarget.classList.toggle('is-visible', showPriority);
        }
        if (this.hasAccessSectionTarget) {
            this.accessSectionTarget.classList.toggle('is-visible', showAccess);
        }
        this.updatePriorityColor(); // On s'assure que la couleur est correcte quand la section apparaît
    }

    /**
     * Met en forme le numéro de téléphone en temps réel.
     */
    formatPhone(event) {
        const input = event.currentTarget;
        const originalCursorPos = input.selectionStart;
        const originalValue = input.value;

        const digits = originalValue.replace(/\D/g, '');
        const formattedValue = (digits.match(/.{1,2}/g) || []).join(' ').trim();
        
        if (originalValue !== formattedValue) {
            const digitsBeforeCursor = originalValue.substring(0, originalCursorPos).replace(/\D/g, '').length;
            input.value = formattedValue;
            
            let newCursorPos = 0;
            let digitsCounted = 0;
            for (let i = 0; i < formattedValue.length; i++) {
                if (digitsCounted === digitsBeforeCursor) {
                    newCursorPos = i;
                    break;
                }
                if (/\d/.test(formattedValue[i])) {
                    digitsCounted++;
                }
            }
            if(digitsCounted === digitsBeforeCursor) {
                newCursorPos = formattedValue.length;
            }
            
            input.setSelectionRange(newCursorPos, newCursorPos);
        }
    }

    /**
     * NOUVELLE MÉTHODE : Change la couleur du champ de priorité si "Bloquant" est sélectionné.
     */
    updatePriorityColor() {
        if (!this.hasPriorityInputTarget) return;
        
        const selectElement = this.priorityInputTarget;
        const selectedValue = selectElement.value;

        if (selectedValue === 'Bloquant') {
            selectElement.style.color = 'var(--danger-color, #e53e3e)';
            selectElement.style.fontWeight = 'bold';
        } else {
            // On retire le style pour revenir à la couleur par défaut.
            selectElement.style.color = '';
            selectElement.style.fontWeight = '';
        }
    }
}