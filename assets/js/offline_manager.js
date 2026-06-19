/**
 * assets/js/offline_manager.js
 * 
 * Gère le stockage hors-ligne temporaire dans localStorage des rapports d'incidents
 * (TEXTE UNIQUEMENT) et assure la synchronisation automatique/manuelle.
 */

// Fonction pour sauvegarder un rapport localement (TEXTE UNIQUEMENT)
function saveReportOffline(reportData) {
    let offlineReports = JSON.parse(localStorage.getItem('sydra_offline_reports') || '[]');
    // Ajouter un ID temporaire et la date de création locale
    reportData._local_id = 'local_' + Date.now();
    reportData._saved_at = new Date().toLocaleString();
    
    // Nettoyer les éventuelles données de fichiers s'il y en a dans l'objet
    delete reportData.attachments;
    delete reportData['files[]'];
    
    offlineReports.push(reportData);
    localStorage.setItem('sydra_offline_reports', JSON.stringify(offlineReports));
    
    // Notification UI Premium
    if (typeof toastr !== 'undefined') {
        toastr.warning('Aucune connexion Internet. Le texte du rapport a été sauvegardé en sécurité sur votre appareil. Il sera envoyé automatiquement dès le retour du réseau (les photos n\'ont pas été conservées).', 'Mode Hors-Ligne', {timeOut: 10000});
    } else if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'warning',
            title: 'Mode Hors-Ligne',
            text: 'Aucune connexion Internet. Le texte du rapport a été sauvegardé en sécurité sur votre appareil. Il sera envoyé automatiquement dès le retour du réseau (les photos n\'ont pas été conservées).',
            confirmButtonColor: '#005BBB'
        });
    } else {
        alert('Aucune connexion Internet. Votre rapport a été sauvegardé en mode texte uniquement.');
    }
    updateNetworkIndicator();
}

// Fonction pour synchroniser les rapports en attente
function syncOfflineReports() {
    let offlineReports = JSON.parse(localStorage.getItem('sydra_offline_reports') || '[]');
    
    if (offlineReports.length > 0 && navigator.onLine) {
        if (typeof toastr !== 'undefined') {
            toastr.info(`Synchronisation de ${offlineReports.length} rapport(s) en cours...`, 'Réseau rétabli');
        }
        
        offlineReports.forEach((report) => {
            // Copie de l'objet pour l'envoi (sans les métadonnées locales)
            let dataToSend = { ...report };
            let localId = dataToSend._local_id;
            delete dataToSend._local_id;
            delete dataToSend._saved_at;

            // Appel AJAX vers le backend
            $.ajax({
                url: 'api/save_report.php',
                type: 'POST',
                data: dataToSend,
                success: function(response) {
                    let res = typeof response === 'string' ? JSON.parse(response) : response;
                    if (res && res.ok === true) {
                        // Supprimer du localStorage après succès
                        let currentReports = JSON.parse(localStorage.getItem('sydra_offline_reports') || '[]');
                        let updatedReports = currentReports.filter(r => r._local_id !== localId);
                        localStorage.setItem('sydra_offline_reports', JSON.stringify(updatedReports));
                        
                        if (typeof toastr !== 'undefined') {
                            toastr.success('Brouillon hors-ligne synchronisé avec succès vers le GTMP !');
                        }
                        updateNetworkIndicator();
                    } else {
                        console.error('Échec de la validation serveur pour', localId, res);
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Erreur lors de la synchronisation d\'un rapport : ' + (res.message || 'Erreur inconnue'));
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Échec de la synchronisation pour', localId, error);
                }
            });
        });
    }
}

// Fonction pour mettre à jour l'icône réseau dans l'en-tête
function updateNetworkIndicator() {
    const indicator = document.getElementById('network-indicator');
    if (!indicator) return;
    
    let offlineReports = JSON.parse(localStorage.getItem('sydra_offline_reports') || '[]');
    
    if (!navigator.onLine) {
        indicator.innerHTML = '<span class="badge bg-danger rounded-pill shadow-sm"><i class="fa-solid fa-wifi-slash me-1"></i> Hors-ligne</span>';
    } else if (offlineReports.length > 0) {
        indicator.innerHTML = `<span class="badge bg-warning text-dark rounded-pill shadow-sm" style="cursor:pointer;" onclick="syncOfflineReports()"><i class="fa-solid fa-cloud-arrow-up me-1"></i> ${offlineReports.length} en attente</span>`;
    } else {
        indicator.innerHTML = '<span class="badge bg-success rounded-pill shadow-sm"><i class="fa-solid fa-wifi me-1"></i> En ligne</span>';
    }
}

// Écouteurs de changement de réseau
window.addEventListener('online', () => {
    updateNetworkIndicator();
    syncOfflineReports();
});
window.addEventListener('offline', updateNetworkIndicator);

// Vérification au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    updateNetworkIndicator();
    if (navigator.onLine) syncOfflineReports();
});
