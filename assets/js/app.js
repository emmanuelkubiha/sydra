document.addEventListener('DOMContentLoaded', function () {
    var appLoader = document.getElementById('app-loader');
    var loaderSubtitle = document.getElementById('app-loader-subtitle');
    var body = document.body;

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
        var initialMessages = messageByContext[context] || genericMessages;
        showLoader(initialMessages);

        window.setTimeout(hideLoader, 180);
        window.addEventListener('load', function () {
            window.setTimeout(hideLoader, 30);
        });
        window.setTimeout(function () {
            hideLoader();
        }, 1400);

        document.querySelectorAll('a[href^="?page="]').forEach(function (link) {
            link.addEventListener('click', function () {
                showLoader(genericMessages);
            });
        });

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
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

});
