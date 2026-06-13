// 1. Configuration globale de Toastr pour les petites notifications
if (typeof toastr !== 'undefined') {
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "4000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
}

// 2. Mixin SweetAlert2 — Design SaaS Premium (Notion/Asana)
// Usage : window.premiumAlert.fire({ icon, title, text }) partout dans SyDRA
// à la place de Swal.fire() pour garantir le design cohérent.
window.premiumAlert = (typeof Swal !== 'undefined') ? Swal.mixin({
    customClass: {
        popup:         'sydra-swal-popup',
        title:         'sydra-swal-title',
        htmlContainer: 'sydra-swal-text',
        confirmButton: 'btn btn-primary mx-2',
        cancelButton:  'btn btn-outline-secondary mx-2'
    },
    buttonsStyling: false, // Désactive les gros boutons colorés natifs de SweetAlert2
    showClass: {
        popup: 'animate__animated animate__fadeInUp animate__faster'
    },
    hideClass: {
        popup: 'animate__animated animate__fadeOutDown animate__faster'
    }
}) : null;

document.addEventListener('DOMContentLoaded', function () {
    var appLoader = document.getElementById('app-loader');
    var loaderSubtitle = document.getElementById('app-loader-subtitle');
    var body = document.body;
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

    function pickRandom(list) {
        if (!Array.isArray(list) || list.length === 0) {
            return '';
        }
        var idx = Math.floor(Math.random() * list.length);
        return list[idx];
    }

    var context = (body && body.dataset && body.dataset.loaderContext) ? body.dataset.loaderContext : 'connexion';
    var messageByContext = {
        connexion: [
            'Bienvenue, préparation de votre espace.',
            'Nous vérifions votre session...',
            'Connexion sécurisée en cours...'
        ],
        tableau_de_bord: [
            'Bienvenue à l\'accueil.',
            'Nous vérifions votre session...',
            'Chargement de vos données principales...'
        ],
        utilisateurs: [
            'Chargement de la gestion des utilisateurs...',
            'Nous vérifions votre session...',
            'Préparation du module d\'administration...'
        ],
        profil: [
            'Chargement de votre profil...',
            'Nous vérifions votre session...',
            'Préparation des informations personnelles...'
        ],
        mot_de_passe_oublie: [
            'Vérification de votre demande...',
            'Préparation du formulaire de récupération...',
            'Sécurisation de la page en cours...'
        ],
        reinitialiser_mot_de_passe: [
            'Vérification du lien de réinitialisation...',
            'Préparation de la page sécurisée...',
            'Chargement du formulaire...'
        ]
    };

    var genericMessages = [
        'Chargement en cours...',
        'Nous vérifions votre session...',
        'Préparation de la page...'
    ];

    var ticker = null;

    function startMessageTicker(list) {
        if (!loaderSubtitle) {
            return;
        }
        loaderSubtitle.textContent = pickRandom(list);
        if (ticker !== null) {
            window.clearInterval(ticker);
        }
        ticker = window.setInterval(function () {
            loaderSubtitle.textContent = pickRandom(list);
        }, 520);
    }

    function stopMessageTicker() {
        if (ticker !== null) {
            window.clearInterval(ticker);
            ticker = null;
        }
    }

    function hideLoader() {
        if (!appLoader) {
            return;
        }
        appLoader.classList.add('hide');
        stopMessageTicker();
    }

    function showLoader(messages) {
        if (!appLoader) {
            return;
        }
        appLoader.classList.remove('hide');
        startMessageTicker(messages && messages.length ? messages : genericMessages);
    }

    if (appLoader) {
        var skipLoaderOnce = false;
        try {
            skipLoaderOnce = window.sessionStorage.getItem('sydraSkipLoaderOnce') === '1';
            if (skipLoaderOnce) {
                window.sessionStorage.removeItem('sydraSkipLoaderOnce');
            }
        } catch (e) {
            skipLoaderOnce = false;
        }

        var initialMessages = messageByContext[context] || genericMessages;
        var loaderShownAt = Date.now();

        function hideLoaderWithMinDelay() {
            var minVisibleMs = 520;
            var elapsed = Date.now() - loaderShownAt;
            var wait = Math.max(0, minVisibleMs - elapsed);
            window.setTimeout(hideLoader, wait);
        }

        if (skipLoaderOnce) {
            hideLoader();
        } else {
            showLoader(initialMessages);
            window.addEventListener('load', function () {
                hideLoaderWithMinDelay();
            });
            window.setTimeout(function () {
                hideLoader();
            }, 3000);
        }

        document.querySelectorAll('a[href^="?page="]').forEach(function (link) {
            link.addEventListener('click', function () {
                showLoader(genericMessages);
            });
        });

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (form.classList.contains('js-ai-chat-form')) {
                    return;
                }

                if (form.classList.contains('js-create-confirm-form')) {
                    return;
                }

                // Ce formulaire a sa propre confirmation SweetAlert avant soumission finale.
                if (form.id === 'user-edit-form') {
                    return;
                }

                if (form.classList.contains('js-decision-form')) {
                    return;
                }

                var actionInput = form.querySelector('input[name="action"]');
                var action = actionInput ? actionInput.value : '';

                if (action === 'request_password_reset') {
                    showLoader([
                        'Nous tentons de vous envoyer un email...',
                        'Génération d\'un lien de réinitialisation...',
                        'Vérification de la sécurité de la demande...'
                    ]);
                    return;
                }

                if (action === 'test_smtp') {
                    showLoader([
                        'Test SMTP en cours...',
                        'Tentative de connexion au serveur SMTP...',
                        'Vérification de la configuration email...'
                    ]);
                    return;
                }

                showLoader(genericMessages);
            });
        });
    }

    var email = document.querySelector('input[name="email"]');
    if (email) {
        email.autocomplete = 'email';
    }

    var mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    var mobileSidebar = document.getElementById('mobile-sidebar');
    var mobileOverlay = document.getElementById('sidebar-mobile-overlay');

    if (mobileMenuToggle && mobileSidebar && mobileOverlay) {
        function closeMobileSidebar() {
            mobileSidebar.classList.remove('is-open');
            mobileOverlay.classList.remove('is-open');
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('sidebar-mobile-open');
        }

        function openMobileSidebar() {
            mobileSidebar.classList.add('is-open');
            mobileOverlay.classList.add('is-open');
            mobileMenuToggle.setAttribute('aria-expanded', 'true');
            document.body.classList.add('sidebar-mobile-open');
        }

        mobileMenuToggle.addEventListener('click', function () {
            if (mobileSidebar.classList.contains('is-open')) {
                closeMobileSidebar();
            } else {
                openMobileSidebar();
            }
        });

        mobileOverlay.addEventListener('click', function () {
            closeMobileSidebar();
        });

        mobileSidebar.querySelectorAll('a[href]').forEach(function (link) {
            link.addEventListener('click', function () {
                closeMobileSidebar();
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeMobileSidebar();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 760) {
                closeMobileSidebar();
            }
        });
    }

    document.querySelectorAll('[data-rotate-text="1"]').forEach(function (el) {
        var rawMessages = (el.getAttribute('data-rotate-messages') || '').split('|').map(function (item) {
            return item.trim();
        }).filter(function (item) {
            return item !== '';
        });

        if (rawMessages.length === 0) {
            return;
        }

        var current = 0;
        var fadeTimeout = null;

        function nextMessage() {
            el.classList.add('is-fading');
            window.setTimeout(function () {
                current = (current + 1) % rawMessages.length;
                el.textContent = rawMessages[current];
                el.classList.remove('is-fading');
            }, 240);

            fadeTimeout = window.setTimeout(nextMessage, 3200);
        }

        el.textContent = rawMessages[current];
        fadeTimeout = window.setTimeout(nextMessage, 2600);

        window.addEventListener('beforeunload', function () {
            if (fadeTimeout !== null) {
                window.clearTimeout(fadeTimeout);
            }
        });
    });

    function showToast(message, type) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                timer: 3500,
                timerProgressBar: true,
                showConfirmButton: false,
                icon: type === 'error' ? 'error' : 'success',
                title: message
            });
            return;
        }

        var stack = document.querySelector('.toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            document.body.appendChild(stack);
        }

        var toast = document.createElement('div');
        toast.className = 'toast-item ' + (type === 'error' ? 'error' : 'success');
        toast.setAttribute('role', 'status');
        toast.textContent = message;

        stack.appendChild(toast);

        window.setTimeout(function () {
            toast.classList.add('hide');
            window.setTimeout(function () {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
                if (stack && stack.childElementCount === 0 && stack.parentNode) {
                    stack.parentNode.removeChild(stack);
                }
            }, 260);
        }, 4200);
    }

    function sydraPopupClasses() {
        return {
            popup: 'rounded-4 border-0 shadow-lg',
            title: 'fw-bold text-dark',
            htmlContainer: 'text-start',
            confirmButton: 'btn btn-primary rounded-3 px-4 py-2 fw-semibold me-2',
            cancelButton: 'btn btn-light border rounded-3 px-4 py-2 fw-semibold'
        };
    }

    function sydraLogoIconHtml() {
        return '<img src="assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png" alt="SyDRA" style="width:56px;height:56px;object-fit:contain;">';
    }

    var hasUserEditPopup = !!(window.SYDRA_USER_EDIT_POPUP && typeof window.SYDRA_USER_EDIT_POPUP === 'object' && window.SYDRA_USER_EDIT_POPUP.show === true);
    var hasEmailChangePopup = !!(window.SYDRA_EMAIL_CHANGE_POPUP && typeof window.SYDRA_EMAIL_CHANGE_POPUP === 'object' && window.SYDRA_EMAIL_CHANGE_POPUP.show === true);

    if (Array.isArray(window.SYDRA_FLASHES) && window.SYDRA_FLASHES.length > 0) {
        window.SYDRA_FLASHES.forEach(function (flash, idx) {
            var msg = flash && typeof flash.message === 'string' ? flash.message : '';
            var type = flash && flash.type === 'error' ? 'error' : 'success';
            if (hasUserEditPopup && context === 'utilisateurs' && msg.indexOf('Utilisateur mis à jour.') === 0) {
                return;
            }
            if (hasEmailChangePopup && context === 'utilisateurs' && (msg.indexOf('Un email de confirmation a été envoyé à la nouvelle adresse.') === 0 || msg.indexOf('Demande enregistrée mais email non envoyé:') === 0)) {
                return;
            }
            if (msg.trim() !== '') {
                window.setTimeout(function () {
                    showToast(msg, type);
                }, idx * 130);
            }
        });
    }

    if (hasUserEditPopup && context === 'utilisateurs' && window.Swal && typeof window.Swal.fire === 'function') {
        var popup = window.SYDRA_USER_EDIT_POPUP;
        var mailAttempted = popup.mail_attempted === true;
        var mailSuccess = popup.mail_success === true;
        var recipient = String(popup.recipient || '').trim();
        var smtpError = String(popup.error || '').trim();

        var icon = 'info';
        var title = 'Modification enregistrée';
        var body = '<p style="margin:0;">Les informations utilisateur ont été mises à jour avec succès.</p>';

        if (mailAttempted && mailSuccess) {
            icon = 'success';
            title = 'Modification et email envoyés';
            body = '<p style="margin:0 0 8px;">Les modifications ont été enregistrées.</p>'
                + '<p style="margin:0;"><strong>Email :</strong> confirmation envoyée à <strong>' + escapeHtml(recipient || 'la nouvelle adresse') + '</strong>.</p>';
        } else if (mailAttempted && !mailSuccess) {
            icon = 'warning';
            title = 'Modification enregistrée, email non envoyé';
            body = '<p style="margin:0 0 8px;">Les modifications ont été enregistrées.</p>'
                + '<p style="margin:0 0 8px;"><strong>Email :</strong> échec d\'envoi à <strong>' + escapeHtml(recipient || 'la nouvelle adresse') + '</strong>.</p>'
                + (smtpError !== ''
                    ? ('<p style="margin:0;color:#b91c1c;"><strong>Détail SMTP :</strong> ' + escapeHtml(smtpError) + '</p>')
                    : '');
        }

        window.setTimeout(function () {
            window.Swal.fire({
                icon: icon,
                title: title,
                html: '<div style="text-align:left;padding:2px 2px 0;">' + body + '</div>',
                confirmButtonText: 'Continuer',
                buttonsStyling: false,
                customClass: {
                    popup: 'rounded-4 border-0 shadow-lg',
                    title: 'fw-bold text-dark',
                    htmlContainer: 'text-start',
                    confirmButton: 'btn btn-primary rounded-3 px-4 py-2 fw-semibold'
                },
                iconHtml: sydraLogoIconHtml()
            });
        }, 120);
    }

    if (hasEmailChangePopup && context === 'utilisateurs' && window.Swal && typeof window.Swal.fire === 'function') {
        var emailPopup = window.SYDRA_EMAIL_CHANGE_POPUP;
        var emailSuccess = emailPopup.success === true;
        var emailRecipient = String(emailPopup.recipient || '').trim();
        var emailError = String(emailPopup.error || '').trim();
        var expHours = Number(emailPopup.expires_hours || 48);

        window.setTimeout(function () {
            window.Swal.fire({
                icon: emailSuccess ? 'success' : 'warning',
                iconHtml: sydraLogoIconHtml(),
                title: emailSuccess ? 'Demande email envoyée' : 'Demande enregistrée, email non envoyé',
                html: '<div style="text-align:left;">'
                    + '<p style="margin:0 0 8px;">Le changement d\'email est suivi via le flux dédié.</p>'
                    + '<p style="margin:0 0 8px;"><strong>Destinataire :</strong> ' + escapeHtml(emailRecipient || '-') + '</p>'
                    + (emailSuccess
                        ? ('<p style="margin:0;">Le lien de confirmation a été envoyé. Expiration: <strong>' + escapeHtml(String(expHours)) + 'h</strong>.</p>')
                        : ('<p style="margin:0;color:#b91c1c;"><strong>Détail SMTP :</strong> ' + escapeHtml(emailError || 'Erreur inconnue.') + '</p>'))
                    + '</div>',
                confirmButtonText: 'Continuer',
                buttonsStyling: false,
                customClass: {
                    popup: 'rounded-4 border-0 shadow-lg',
                    title: 'fw-bold text-dark',
                    htmlContainer: 'text-start',
                    confirmButton: 'btn btn-primary rounded-3 px-4 py-2 fw-semibold'
                }
            });
        }, 180);
    }

    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.DataTable === 'function') {
        var usersTable = document.getElementById('users-table');
        if (usersTable) {
            var usersDataTable = window.jQuery(usersTable).DataTable({
                pageLength: 10,
                order: [[1, 'asc']],
                dom: 'rt<"users-table-footer"lip>',
                language: {
                    search: '',
                    searchPlaceholder: 'Rechercher une organisation, un email, un rôle...',
                    lengthMenu: 'Afficher _MENU_ lignes',
                    info: 'Affichage _START_ à _END_ sur _TOTAL_ lignes',
                    paginate: { previous: 'Précédent', next: 'Suivant' }
                }
            });

            var roleFilter = document.getElementById('filter-role');
            var statusFilter = document.getElementById('filter-status');
            var searchFilter = document.getElementById('filter-search');

            var dataTableWrapper = usersTable.closest('.dataTables_wrapper');
            if (dataTableWrapper) {
                var searchContainer = dataTableWrapper.querySelector('.dataTables_filter');
                if (searchContainer) {
                    searchContainer.style.display = 'none';
                }

                var lengthContainer = dataTableWrapper.querySelector('.dataTables_length');
                var infoContainer = dataTableWrapper.querySelector('.dataTables_info');
                var paginateContainer = dataTableWrapper.querySelector('.dataTables_paginate');

                if (lengthContainer) {
                    lengthContainer.classList.add('users-length-container');
                }
                if (infoContainer) {
                    infoContainer.classList.add('users-info-container');
                }
                if (paginateContainer) {
                    paginateContainer.classList.add('users-paginate-container');
                }
            }

            if (roleFilter) {
                roleFilter.addEventListener('change', function () {
                    var value = roleFilter.value || '';
                    usersDataTable.column(2).search(value).draw();
                });
            }

            if (statusFilter) {
                statusFilter.addEventListener('change', function () {
                    var value = statusFilter.value || '';
                    usersDataTable.column(3).search(value).draw();
                });
            }

            if (searchFilter) {
                searchFilter.addEventListener('input', function () {
                    usersDataTable.search(searchFilter.value || '').draw();
                });
            }
        }

        var leadTable = document.getElementById('lead-alert-table');
        if (leadTable) {
            window.jQuery(leadTable).DataTable({
                pageLength: 10,
                order: [[5, 'desc']]
            });
        }

        var rapportageUserTable = document.getElementById('rapportage-user-table');
        if (rapportageUserTable) {
            // La page rapportage-mes-alertes utilise une barre de filtres Bootstrap custom.
            // On n'initialise pas DataTables ici pour éviter les doublons UI
            // ("Afficher/Rechercher") et les chevauchements de mise en page.
        }

        var rapportageAdminTable = document.getElementById('rapportage-admin-table');
        if (rapportageAdminTable) {
            // La page admin utilise une barre de filtres Bootstrap custom,
            // donc on évite les contrôles natifs DataTables en doublon.
        }
    }

    function exportTableToCsv(tableId, filename) {
        var table = document.getElementById(tableId);
        if (!table) {
            return;
        }

        var rows = Array.prototype.slice.call(table.querySelectorAll('tr'));
        var csv = rows.map(function (row) {
            var cells = Array.prototype.slice.call(row.querySelectorAll('th,td'));
            return cells.map(function (cell) {
                var raw = (cell.textContent || '').trim().replace(/\s+/g, ' ');
                return '"' + raw.replace(/"/g, '""') + '"';
            }).join(';');
        }).join('\n');

        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    document.querySelectorAll('.js-print-report').forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.print();
        });
    });

    document.querySelectorAll('.js-export-report-excel').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tableId = btn.getAttribute('data-table-id') || '';
            if (tableId === '') {
                return;
            }
            exportTableToCsv(tableId, 'rapport-sydra-' + Date.now() + '.csv');
        });
    });

    var adminReportTable = document.getElementById('rapportage-admin-table');
    if (adminReportTable && csrfToken) {
        var storageKey = 'sydra_last_submitted_alert_id';
        var seedId = Number(adminReportTable.getAttribute('data-last-submitted-id') || 0);
        var stored = Number(window.localStorage.getItem(storageKey) || 0);
        var lastSeen = stored > 0 ? stored : seedId;

        if (stored <= 0 && seedId > 0) {
            window.localStorage.setItem(storageKey, String(seedId));
        }

        function checkNewSubmittedAlerts() {
            var formData = new FormData();
            formData.append('action', 'check_new_submitted_alert');
            formData.append('csrf', csrfToken);
            formData.append('last_seen_id', String(lastSeen));

            fetch('?page=rapportage-admin-list', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data || data.ok !== true) {
                        return;
                    }

                    var latestId = Number(data.latest_id || 0);
                    if (latestId > lastSeen) {
                        if (data.has_new) {
                            var audio = new Audio('assets/sounds/alert.mp3');
                            audio.play().catch(function () { });

                            if (window.Swal && typeof window.Swal.fire === 'function') {
                                window.Swal.fire({
                                    icon: 'info',
                                    title: 'Nouvelle alerte reçue',
                                    text: 'Nouvelle alerte reçue de ' + String(data.organization_name || 'Organisation inconnue'),
                                    confirmButtonColor: '#005bbb'
                                });
                            }
                        }

                        lastSeen = latestId;
                        window.localStorage.setItem(storageKey, String(lastSeen));
                    }
                })
                .catch(function () { });
        }

        checkNewSubmittedAlerts();
        window.setInterval(checkNewSubmittedAlerts, 60000);
    }

    var notifToggle = document.getElementById('notif-toggle');
    var notifMenu = document.getElementById('notif-menu');
    if (notifToggle && notifMenu) {
        notifToggle.addEventListener('click', function () {
            notifMenu.classList.toggle('open');
        });

        notifMenu.querySelectorAll('.js-notif-item').forEach(function (item) {
            item.addEventListener('click', function (event) {
                var notifId = Number(item.getAttribute('data-notif-id') || 0);
                var targetUrl = item.getAttribute('href') || '?page=tableau_de_bord';
                if (notifId <= 0 || !csrfToken) {
                    return;
                }

                event.preventDefault();
                fetch('api/mark_notification_read.php?id=' + encodeURIComponent(String(notifId)) + '&csrf=' + encodeURIComponent(csrfToken), {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .finally(function () {
                        window.location.href = targetUrl;
                    });
            });
        });

        document.addEventListener('click', function (event) {
            if (!notifMenu.contains(event.target) && !notifToggle.contains(event.target)) {
                notifMenu.classList.remove('open');
            }
        });
    }

    var profileDropdownWrapper = document.getElementById('profile-dropdown-wrapper');
    var profileDropdownToggle = document.getElementById('profile-dropdown-toggle');
    var profileDropdownMenu = document.getElementById('profile-dropdown-menu');
    if (profileDropdownWrapper && profileDropdownToggle && profileDropdownMenu) {
        profileDropdownToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            profileDropdownWrapper.classList.toggle('open');
            var isOpen = profileDropdownWrapper.classList.contains('open');
            profileDropdownToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!profileDropdownWrapper.contains(event.target)) {
                profileDropdownWrapper.classList.remove('open');
                profileDropdownToggle.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                profileDropdownWrapper.classList.remove('open');
                profileDropdownToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    if (window.bootstrap && typeof window.bootstrap.Tooltip === 'function') {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new window.bootstrap.Tooltip(el);
        });
    }

    function parseJsonAttr(raw, fallback) {
        try {
            if (!raw || raw.trim() === '') {
                return fallback;
            }
            return JSON.parse(raw);
        } catch (err) {
            return fallback;
        }
    }

    function urgencyColor(level) {
        var normalized = (level || '').toLowerCase();
        if (normalized.indexOf('crit') >= 0) {
            return '#E53E3E';
        }
        if (normalized.indexOf('ele') >= 0) {
            return '#F97316';
        }
        if (normalized.indexOf('moy') >= 0) {
            return '#FACC15';
        }
        return '#14B8A6';
    }

    function normalizeText(value) {
        return (value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    // Associe les localisations texte frequentes a des coordonnees cartographiques.
    function resolveLocationFromText(rawLocation) {
        var cityCoords = {
            bukavu: [-2.5099, 28.8428],
            uvira: [-3.4067, 29.1458],
            goma: [-1.6792, 29.2228],
            minova: [-2.1975, 28.9924],
            kalehe: [-2.2581, 28.6765],
            idjwi: [-2.1198, 28.9961],
            walungu: [-2.7082, 28.6133],
            kabare: [-2.4741, 28.7619],
            shabunda: [-2.6978, 27.3358],
            fizi: [-4.3014, 28.9448],
            baraka: [-4.0976, 29.0958],
            kamituga: [-3.0509, 28.1858]
        };

        var location = normalizeText(rawLocation);
        if (location === '') {
            return null;
        }

        for (var city in cityCoords) {
            if (Object.prototype.hasOwnProperty.call(cityCoords, city) && location.indexOf(city) >= 0) {
                return cityCoords[city];
            }
        }

        return null;
    }

    var mapContainer = document.getElementById('decision-map');
    if (mapContainer && window.L) {
        // Carte operationnelle: chaque alerte herite de la couleur de son niveau d'urgence.
        var map = window.L.map(mapContainer).setView([-2.5, 28.8], 7);
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        var alerts = parseJsonAttr(mapContainer.getAttribute('data-alerts'), []);
        var markers = [];

        alerts.forEach(function (alertItem) {
            var coords = resolveLocationFromText(alertItem.location_text || '');
            if (!coords) {
                return;
            }

            var level = String(alertItem.urgency_level || 'Moyenne');
            var color = urgencyColor(level);
            var reportId = Number(alertItem.id || 0);
            var detailHref = 'pages/reports/alerte_details.php?id=' + reportId;
            var reportDate = alertItem.created_at ? String(alertItem.created_at) : '-';

            var marker = window.L.circleMarker(coords, {
                radius: 9,
                color: color,
                weight: 2,
                fillColor: color,
                fillOpacity: 0.75
            }).addTo(map);

            marker.bindPopup(
                '<div class="map-popup">'
                + '<strong>' + String(alertItem.report_type || 'FLASH') + '</strong><br>'
                + '<span><b>Organisation:</b> ' + String(alertItem.organization_name || 'N/A') + '</span><br>'
                + '<span><b>Date:</b> ' + reportDate + '</span><br>'
                + '<a class="btn btn-small mt-2" href="' + detailHref + '">Voir Details</a>'
                + '</div>'
            );

            markers.push(marker);
        });

        if (markers.length > 0) {
            var group = window.L.featureGroup(markers);
            map.fitBounds(group.getBounds().pad(0.2));
        }
    }

    var urgencyChart = document.getElementById('urgency-chart');
    if (urgencyChart && window.Chart) {
        var urgencyValues = [
            Number(urgencyChart.dataset.faible || 0),
            Number(urgencyChart.dataset.moyenne || 0),
            Number(urgencyChart.dataset.elevee || 0),
            Number(urgencyChart.dataset.critique || 0)
        ];

        new window.Chart(urgencyChart, {
            type: 'bar',
            data: {
                labels: ['Faible', 'Moyenne', 'Elevee', 'Critique'],
                datasets: [{
                    data: urgencyValues,
                    backgroundColor: ['#14B8A6', '#FACC15', '#F97316', '#E53E3E'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    var orgTrendCanvas = document.getElementById('org-reports-trend');
    if (orgTrendCanvas && window.Chart) {
        var orgTrend = parseJsonAttr(orgTrendCanvas.getAttribute('data-trend'), { labels: [], flash: [], note: [] });
        new window.Chart(orgTrendCanvas, {
            type: 'line',
            data: {
                labels: orgTrend.labels || [],
                datasets: [
                    {
                        label: 'FLASH',
                        data: orgTrend.flash || [],
                        borderColor: '#005bbb',
                        backgroundColor: 'rgba(0, 91, 187, 0.15)',
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'NOTE',
                        data: orgTrend.note || [],
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.10)',
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    var topOrganizationsChart = document.getElementById('top-organizations-chart');
    if (topOrganizationsChart && window.Chart) {
        var topChartData = parseJsonAttr(topOrganizationsChart.getAttribute('data-chart'), { labels: [], values: [] });
        new window.Chart(topOrganizationsChart, {
            type: 'bar',
            data: {
                labels: topChartData.labels || [],
                datasets: [{
                    data: topChartData.values || [],
                    backgroundColor: '#005bbb',
                    borderRadius: 8
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    var globalEvolutionChart = document.getElementById('global-evolution-chart');
    if (globalEvolutionChart && window.Chart) {
        var globalChartData = parseJsonAttr(globalEvolutionChart.getAttribute('data-chart'), { labels: [], totals: [], flash: [], note: [] });
        // Courbe "nuage" demandee: line + tension + fill sur fond transparent bleu SyDRA.
        new window.Chart(globalEvolutionChart, {
            type: 'line',
            data: {
                labels: globalChartData.labels || [],
                datasets: [
                    {
                        label: 'Total',
                        data: globalChartData.totals || [],
                        borderColor: '#005bbb',
                        backgroundColor: 'rgba(0, 91, 187, 0.2)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'FLASH',
                        data: globalChartData.flash || [],
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.08)',
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'NOTE',
                        data: globalChartData.note || [],
                        borderColor: '#06b6d4',
                        backgroundColor: 'rgba(6, 182, 212, 0.08)',
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    var urgencyDistributionChart = document.getElementById('urgency-distribution-chart');
    if (urgencyDistributionChart && window.Chart) {
        var urgencyData = parseJsonAttr(urgencyDistributionChart.getAttribute('data-chart'), { labels: [], values: [] });
        new window.Chart(urgencyDistributionChart, {
            type: 'doughnut',
            data: {
                labels: urgencyData.labels || [],
                datasets: [{
                    data: urgencyData.values || [],
                    backgroundColor: ['#14B8A6', '#FACC15', '#F97316', '#E53E3E'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    document.querySelectorAll('.js-toggle-status').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var userId = btn.getAttribute('data-user-id') || '';
            var currentStatus = String(btn.getAttribute('data-user-status') || 'Actif').toLowerCase();
            var csrf = btn.getAttribute('data-csrf') || csrfToken;
            if (!userId || !csrf) {
                return;
            }

            var isBlocked = currentStatus === 'bloque';
            var confirmText = isBlocked
                ? 'Voulez-vous vraiment réactiver cet utilisateur / organisation ?'
                : 'Voulez-vous vraiment rendre inactif cet utilisateur / organisation ?';
            var confirmButton = isBlocked ? 'Oui, réactiver' : 'Oui, bloquer';

            // Validation metier: confirmation visuelle puis bascule de statut via endpoint AJAX dedie.
            var proceed = function () {
                var formData = new FormData();
                formData.append('csrf', csrf);
                formData.append('user_id', userId);

                fetch('toggle_status.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.ok) {
                            showToast(data.message || 'Statut mis à jour.', 'success');
                            window.setTimeout(function () { window.location.reload(); }, 450);
                        } else {
                            showToast((data && data.message) ? data.message : 'Échec de mise à jour.', 'error');
                        }
                    })
                    .catch(function () {
                        showToast('Erreur réseau lors de la mise à jour du statut.', 'error');
                    });
            };

            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'Confirmation',
                    text: confirmText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: confirmButton,
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#005bbb'
                }).then(function (result) {
                    if (result && result.isConfirmed) {
                        proceed();
                    }
                });
                return;
            }

            if (window.confirm(confirmText)) {
                proceed();
            }
        });
    });

    document.querySelectorAll('.js-delete-user').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var userId = btn.getAttribute('data-user-id') || '';
            var userName = btn.getAttribute('data-user-name') || 'organisation';
            var csrf = btn.getAttribute('data-csrf') || csrfToken;
            if (!userId || !csrf) {
                return;
            }

            var proceedDelete = function () {
                var formData = new FormData();
                formData.append('action', 'delete_user_permanently');
                formData.append('csrf', csrf);
                formData.append('user_id', userId);

                fetch('?page=utilisateurs', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.ok) {
                            showToast(data.message || 'Compte supprimé.', 'success');
                            window.setTimeout(function () { window.location.reload(); }, 450);
                        } else {
                            showToast((data && data.message) ? data.message : 'Suppression impossible.', 'error');
                        }
                    })
                    .catch(function () {
                        showToast('Erreur réseau pendant la suppression.', 'error');
                    });
            };

            var warnText = 'Supprimer définitivement ' + userName + ' ? Cette action est irréversible.';
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire({
                    title: 'Confirmation forte',
                    text: warnText,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, supprimer définitivement',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#b91c1c'
                }).then(function (result) {
                    if (result && result.isConfirmed) {
                        proceedDelete();
                    }
                });
                return;
            }

            if (window.confirm(warnText)) {
                proceedDelete();
            }
        });
    });

    document.querySelectorAll('.js-create-confirm-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                return;
            }

            event.preventDefault();

            var acronymInput = form.querySelector('input[name="org_acronym"]');
            var orgNameInput = form.querySelector('input[name="organization_name"]');
            var emailInput = form.querySelector('input[name="email"]');
            var roleSelect = form.querySelector('select[name="role"]');

            var acronym = acronymInput ? acronymInput.value.trim() : '';
            var orgName = orgNameInput ? orgNameInput.value.trim() : '';
            var email = emailInput ? emailInput.value.trim() : '';
            var role = roleSelect ? roleSelect.value : '';

            var organizationDisplay = orgName || acronym || '-';

            var summaryHtml = '<div style="text-align:left">'
                + '<p>Vous êtes sur le point de créer un accès au système de rapportage et d\'alerte GTMP. Voulez-vous confirmer ?</p>'
                + '<p><strong>Organisation:</strong> ' + escapeHtml(organizationDisplay) + '</p>'
                + '<p><strong>Email de rapportage:</strong> ' + escapeHtml(email || '-') + '</p>'
                + '<p><strong>Rôle:</strong> ' + escapeHtml(role || '-') + '</p>'
                + '<p>Un lien de validation sera envoyé et expirera dans 48 heures. Si l\'organisation ne valide pas sa création, le système supprimera automatiquement cet accès.</p>'
                + '</div>';

            window.Swal.fire({
                title: 'Confirmation de création',
                html: summaryHtml,
                icon: 'question',
                iconHtml: sydraLogoIconHtml(),
                showCancelButton: true,
                confirmButtonText: 'Valider et envoyer',
                cancelButtonText: 'Annuler',
                buttonsStyling: false,
                customClass: sydraPopupClasses()
            }).then(function (result) {
                if (result && result.isConfirmed) {
                    showLoader([
                        'Création en cours...',
                        'Préparation du compte organisation...',
                        'Validation et enregistrement...'
                    ]);
                    form.submit();
                }
            });
        });
    });

    var detailModalElement = document.getElementById('userDetailModal');
    var detailModal = (detailModalElement && window.bootstrap && window.bootstrap.Modal)
        ? new window.bootstrap.Modal(detailModalElement)
        : null;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    document.querySelectorAll('.js-user-detail').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!detailModalElement) {
                return;
            }

            var logoWrap = document.getElementById('user-detail-logo-wrap');
            var nameEl = document.getElementById('user-detail-name');
            var emailEl = document.getElementById('user-detail-email');
            var roleEl = document.getElementById('user-detail-role');
            var statusEl = document.getElementById('user-detail-status');
            var phoneEl = document.getElementById('user-detail-phone');
            var siteEl = document.getElementById('user-detail-site');
            var bioEl = document.getElementById('user-detail-bio');
            var kpiEl = document.getElementById('user-detail-kpi');

            var name = btn.getAttribute('data-user-name') || 'Organisation';
            var email = btn.getAttribute('data-user-email') || '-';
            var role = btn.getAttribute('data-user-role') || '-';
            var status = btn.getAttribute('data-user-status') || '-';
            var phone = btn.getAttribute('data-user-phone') || 'Non renseigné';
            var site = btn.getAttribute('data-user-site') || 'Non renseigné';
            var bio = btn.getAttribute('data-user-bio') || 'Aucune bio disponible.';
            var logo = btn.getAttribute('data-user-logo') || '';
            var monthly = Number(btn.getAttribute('data-user-monthly') || '0');

            if (logoWrap) {
                if (logo) {
                    logoWrap.innerHTML = '<img src="' + escapeHtml(logo) + '" alt="Logo organisation" class="user-detail-logo rounded-circle">';
                } else {
                    var initials = (name.split(' ').map(function (part) {
                        return part && part[0] ? part[0].toUpperCase() : '';
                    }).join('').slice(0, 2) || 'OG');
                    logoWrap.innerHTML = '<span class="user-detail-fallback rounded-circle">' + escapeHtml(initials) + '</span>';
                }
            }

            if (nameEl) { nameEl.textContent = name; }
            if (emailEl) { emailEl.textContent = email; }
            if (roleEl) { roleEl.textContent = role; }
            if (statusEl) { statusEl.textContent = status.toLowerCase() === 'bloque' ? 'Inactif' : 'Actif'; }
            if (phoneEl) { phoneEl.textContent = phone; }
            if (siteEl) { siteEl.textContent = site; }
            if (bioEl) { bioEl.textContent = bio; }
            if (kpiEl) { kpiEl.textContent = 'A soumis ' + monthly + ' alerte(s) ce mois-ci'; }

            if (detailModal) {
                detailModal.show();
            }
        });
    });

    var emailChangeModal = document.getElementById('emailChangeModal');
    if (emailChangeModal) {
        var emailChangeForm = emailChangeModal.querySelector('form');
        var emailChangeUserId = document.getElementById('email-change-user-id');
        var emailChangeTarget = document.getElementById('email-change-target');
        var emailChangeInput = document.getElementById('email-change-new-email');
        var emailChangeExpiry = document.getElementById('email-change-expiry');

        document.querySelectorAll('.js-email-change').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var userId = btn.getAttribute('data-user-id') || '';
                var userName = btn.getAttribute('data-user-name') || 'Organisation';
                var userEmail = btn.getAttribute('data-user-email') || '';

                if (emailChangeUserId) {
                    emailChangeUserId.value = userId;
                }
                if (emailChangeTarget) {
                    emailChangeTarget.textContent = 'Compte ciblé: ' + userName + ' (' + userEmail + ')';
                }
                if (emailChangeInput) {
                    emailChangeInput.value = '';
                    emailChangeInput.focus();
                }
            });
        });

        if (emailChangeForm) {
            emailChangeForm.addEventListener('submit', function (event) {
                if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                    return;
                }

                event.preventDefault();
                var targetText = emailChangeTarget ? String(emailChangeTarget.textContent || '').trim() : 'Compte ciblé';
                var newEmail = emailChangeInput ? String(emailChangeInput.value || '').trim() : '';
                var expiry = emailChangeExpiry ? String(emailChangeExpiry.value || '48').trim() : '48';

                if (newEmail === '') {
                    showToast('Veuillez saisir une nouvelle adresse email.', 'error');
                    return;
                }

                window.Swal.fire({
                    title: 'Confirmer la demande email',
                    icon: 'question',
                    iconHtml: sydraLogoIconHtml(),
                    html: '<div style="text-align:left;">'
                        + '<p style="margin:0 0 8px;">Cette action n\'applique pas l\'email immédiatement.</p>'
                        + '<p style="margin:0 0 8px;"><strong>' + escapeHtml(targetText) + '</strong></p>'
                        + '<p style="margin:0 0 6px;"><strong>Nouvelle adresse :</strong> ' + escapeHtml(newEmail) + '</p>'
                        + '<p style="margin:0;"><strong>Expiration lien :</strong> ' + escapeHtml(expiry) + 'h</p>'
                        + '</div>',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, envoyer',
                    cancelButtonText: 'Annuler',
                    buttonsStyling: false,
                    customClass: sydraPopupClasses()
                }).then(function (result) {
                    if (result && result.isConfirmed) {
                        emailChangeForm.submit();
                    }
                });
            });
        }
    }

    var userEditModal = document.getElementById('userEditModal');
    if (userEditModal) {
        var userEditForm = document.getElementById('user-edit-form');
        var userEditTarget = document.getElementById('user-edit-target');
        var userEditId = document.getElementById('user-edit-id');
        var userEditFullName = document.getElementById('user-edit-full-name');
        var userEditOrgName = document.getElementById('user-edit-org-name');
        var userEditRole = document.getElementById('user-edit-role');
        var userEditStatus = document.getElementById('user-edit-status');
        var userEditPhone = document.getElementById('user-edit-phone');
        var userEditSite = document.getElementById('user-edit-site');
        var userEditBio = document.getElementById('user-edit-bio');

        document.querySelectorAll('.js-user-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (userEditId) {
                    userEditId.value = btn.getAttribute('data-user-id') || '';
                }
                if (userEditFullName) {
                    userEditFullName.value = btn.getAttribute('data-user-full-name') || '';
                }
                if (userEditOrgName) {
                    userEditOrgName.value = btn.getAttribute('data-user-org-name') || btn.getAttribute('data-user-full-name') || '';
                }
                if (userEditRole) {
                    userEditRole.value = btn.getAttribute('data-user-role') || 'REPORTER';
                }
                if (userEditStatus) {
                    var rawStatus = (btn.getAttribute('data-user-status') || '').toLowerCase();
                    userEditStatus.value = rawStatus.indexOf('attente') >= 0 ? 'Actif' : (rawStatus.indexOf('inactif') >= 0 ? 'Bloque' : 'Actif');
                }
                if (userEditPhone) {
                    userEditPhone.value = btn.getAttribute('data-user-phone') || '';
                }
                if (userEditSite) {
                    userEditSite.value = btn.getAttribute('data-user-site') || '';
                }
                if (userEditBio) {
                    userEditBio.value = btn.getAttribute('data-user-bio') || '';
                }
                if (userEditTarget) {
                    var name = btn.getAttribute('data-user-org-name') || btn.getAttribute('data-user-full-name') || 'Organisation';
                    var email = btn.getAttribute('data-user-email') || '-';
                    userEditTarget.textContent = 'Compte ciblé: ' + name + ' (' + email + ')';
                }
            });
        });

        if (userEditForm) {
            userEditForm.addEventListener('submit', function (event) {
                if (!(window.Swal && typeof window.Swal.fire === 'function')) {
                    return;
                }

                event.preventDefault();
                var summary = 'Les informations générales (nom, rôle, statut, contacts, bio) seront mises à jour immédiatement.';

                window.Swal.fire({
                    title: 'Confirmer la mise à jour',
                    iconHtml: sydraLogoIconHtml(),
                    html: '<div style="text-align:left;">'
                        + '<p style="margin:0 0 10px;">Vous êtes sur le point de modifier ce compte organisation.</p>'
                        + '<div style="padding:10px;border:1px solid #dbe8f7;border-radius:12px;background:#f8fbff;">'
                        + '<strong style="display:block;margin-bottom:6px;color:#0f172a;">Impact</strong>'
                        + '<span style="color:#334155;">' + escapeHtml(summary) + '</span>'
                        + '</div>'
                        + '<p style="margin:10px 0 0;color:#1d4ed8;"><i class="fa-solid fa-envelope-circle-check me-1"></i>Pour changer l\'email, utilisez le bouton dédié <strong>Modifier l\'adresse email</strong>.</p>'
                        + '</div>',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, enregistrer',
                    cancelButtonText: 'Annuler',
                    buttonsStyling: false,
                    customClass: sydraPopupClasses()
                }).then(function (result) {
                    if (result && result.isConfirmed) {
                        userEditForm.submit();
                    }
                });
            });
        }
    }

});
