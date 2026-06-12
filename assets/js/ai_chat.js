(function () {
    'use strict';

    var root = document.getElementById('sydra-ai-root');
    if (!root) { return; }

    var badge      = document.getElementById('sydra-ai-mode-badge');
    var chatBox    = document.getElementById('sydra-ai-chat');
    var form       = document.getElementById('sydra-ai-form');
    var input      = document.getElementById('sydra-ai-input');
    var sendBtn    = document.getElementById('sydra-ai-send');
    var widgetBtn  = document.getElementById('global-ai-widget-btn');
    var offcanvasEl = document.getElementById('sydraAiOffcanvas');

    if (!badge || !chatBox || !form || !input || !sendBtn) { return; }

    var csrf     = String(root.getAttribute('data-csrf') || '');
    var endpoint = String(root.getAttribute('data-ai-endpoint') || 'api/ai_handler.php');
    var userName = String(root.getAttribute('data-user-name') || 'Utilisateur');
    var userRole = String(root.getAttribute('data-user-role') || 'ORG_REPORTER').toUpperCase();
    var url      = new URL(window.location.href);
    var page     = String(url.searchParams.get('page') || '').trim();
    var mode     = 'GENERIC_HELP';
    var reportId = 0;

    // ── Pages autorisées pour l'assistance approfondie ──
    var RAPPORTAGE_PAGES = [
        'rapportage-creer-wizar', 'rapportage-creer-AI',
        'rapportage-details', 'rapportage-voir'
    ];
    var isOnRapportagePage = RAPPORTAGE_PAGES.indexOf(page) !== -1;

    // ── Détection du contexte ──
    if (page === 'rapportage-creer-wizar' || page === 'rapportage-creer-AI') {
        mode = 'DRAFTING';
    } else if (page === 'rapportage-details' || page === 'rapportage-voir') {
        mode = 'ANALYSIS';
        reportId = Number(url.searchParams.get('id') || url.searchParams.get('report_id') || 0);
        if (!Number.isFinite(reportId) || reportId <= 0) {
            reportId = 0;
            mode     = 'GENERIC_HELP';
        }
    }

    // ── Mise à jour du badge de mode ──
    if (mode === 'GENERIC_HELP') {
        badge.innerHTML = '<i class="bi bi-shield-lock-fill"></i> Mode Aide Générale';
        badge.classList.remove('mode-active');
    } else {
        badge.innerHTML = '<i class="bi bi-shield-check"></i> Mode Analyse Sécurisé';
        badge.classList.add('mode-active');
    }

    // ── Offcanvas : cacher/montrer le bouton flottant ──
    if (offcanvasEl && widgetBtn) {
        offcanvasEl.addEventListener('show.bs.offcanvas', function () {
            widgetBtn.classList.add('is-hidden');
        });
        offcanvasEl.addEventListener('hidden.bs.offcanvas', function () {
            widgetBtn.classList.remove('is-hidden');
        });
    }

    var conversation = [];
    var typingBubble = null;
    var _sendInProgress = false;
    var SEND_DELAY_MS = 2500;

    // ════════════════════════════════════════════════════════════════════════
    // MISSION 2 : showWelcomeMessage — Génère le HTML avec innerHTML
    // ════════════════════════════════════════════════════════════════════════

    var WELCOME_GREETINGS = [
        'Bonjour <strong>{name}</strong>, comment puis-je vous aider aujourd\'hui ? Je suis l\'assistant SyDRA, conçu pour faciliter votre monitoring.',
        'Bienvenue <strong>{name}</strong> ! 👋 Je suis l\'assistant SyDRA, prêt à vous accompagner dans vos tâches de rapportage.',
        'Salut <strong>{name}</strong>, que faisons-nous aujourd\'hui ? Je peux vous aider à créer, analyser ou formater vos alertes.',
        'Bonjour <strong>{name}</strong> ! Besoin d\'aide pour vos rapports ? Je suis là pour ça. 🚀',
        'Ravi de vous revoir <strong>{name}</strong> ! Dites-moi ce dont vous avez besoin.'
    ];

    var SECURITY_WARNING = '<div style="margin-top:10px;padding:8px 12px;background:#fff8e1;border-left:3px solid #f59e0b;border-radius:6px;font-size:0.80rem;color:#92400e;">' +
        '🔒 Pour la sécurité du système, je ne peux vous assister en profondeur que lorsque vous êtes sur des pages spécifiques de rapportage. ' +
        'Sinon, je reste disponible en mode <strong>Aide Générale</strong> si vous voulez que je formate votre texte ou réponde à des questions simples.</div>';

    /**
     * MISSION 2 : Génère le message d'accueil avec innerHTML pour rendre le HTML.
     * @param {string} name     Nom de l'utilisateur
     * @param {string} role     Code du rôle utilisateur
     */
    function showWelcomeMessage(name, role) {
        var idx = Math.floor(Math.random() * WELCOME_GREETINGS.length);
        var greeting = WELCOME_GREETINGS[idx].replace('{name}', escapeHtml(name));

        var html = '<div class="sydra-ai-welcome-text">' + greeting + '</div>';

        // Avertissement de sécurité (Mission 4) si pas sur page de rapportage
        if (!isOnRapportagePage && mode === 'GENERIC_HELP') {
            html += SECURITY_WARNING;
        }

        // Smart Chips selon le rôle (Mission 2)
        html += '<div class="sydra-ai-smart-chips">';
        if (role === 'ADMIN' || role === 'GTMP_LEAD' || role === 'LEAD_GTMP') {
            html += '<a href="?page=tableau_de_bord" class="sydra-ai-smart-chip">📊 Dashboard</a>';
            html += '<a href="?page=admin-parametres" class="sydra-ai-smart-chip">⚙️ Paramètres IA</a>';
            html += '<a href="?page=rapportage-creer-wizar" class="sydra-ai-smart-chip">📝 Créer (Wizard)</a>';
        } else {
            html += '<a href="?page=rapportage-creer-wizar" class="sydra-ai-smart-chip">📝 Créer via le Wizard</a>';
            html += '<a href="?page=rapportage-creer-AI" class="sydra-ai-smart-chip">✨ Créer avec l\'IA</a>';
            html += '<a href="?page=rapportage-liste-user" class="sydra-ai-smart-chip">📋 Voir mes rapports</a>';
        }
        html += '</div>';

        // Injecter avec innerHTML (pas textContent !)
        var el = document.createElement('div');
        el.className = 'sydra-ai-bubble assistant';
        el.innerHTML = html;
        chatBox.appendChild(el);
        scrollToBottom();

        // Stocker le texte brut dans la conversation (pas le HTML)
        var plainText = greeting.replace(/<[^>]*>/g, '');
        conversation.push({ role: 'assistant', content: plainText });
        saveChatToSession();
    }

    function hideAiError() {
        var existing = document.getElementById('sydra-ai-error');
        if (existing) {
            existing.remove();
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // MISSION 2 : Parser de sécurité Markdown -> HTML
    // ════════════════════════════════════════════════════════════════════════
    function formatAIMessage(text) {
        // 1. Convertir le gras (**texte**) en strong coloré
        let html = text.replace(/\*\*(.*?)\*\*/g, "<strong class='text-primary'>$1</strong>");
        
        // 2. Convertir les listes à puces (* texte ou - texte) en <li>
        html = html.replace(/^[\*\-]\s+(.*$)/gim, "<li class='ms-3 mb-1'>$1</li>");
        
        // 3. Envelopper les suites de <li> dans un <ul>
        html = html.replace(/(<li class='ms-3 mb-1'>.*?<\/li>)/s, "<ul class='mb-3 text-start'>$1</ul>");
        
        // 4. Gérer les retours à la ligne classiques
        html = html.replace(/\n/g, "<br>");
        
        // Nettoyage des <br> inutiles autour des listes
        html = html.replace(/<br><ul/g, "<ul").replace(/<\/ul><br>/g, "</ul>");
        
        return html;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    // ════════════════════════════════════════════════════════════════════════
    // MISSION 4 : Persistance sessionStorage
    // ════════════════════════════════════════════════════════════════════════

    function initMessage() {
        var savedChat = sessionStorage.getItem('sydra_chat');
        if (savedChat) {
            try {
                var parsed = JSON.parse(savedChat);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    parsed.forEach(function (msg) {
                        if (msg.role === 'assistant') {
                            pushBubbleHtml(msg.role, msg.content);
                        } else {
                            pushBubble(msg.role, msg.content);
                        }
                    });
                    conversation = parsed;
                    scrollToBottom();
                    var suggestionsContainer = document.getElementById('sydra-ai-suggestions');
                    if (suggestionsContainer) {
                        suggestionsContainer.style.display = 'none';
                    }
                    return;
                }
            } catch (e) {
                console.warn('[SyDRA] Erreur sessionStorage:', e);
            }
        }

        // Pas d'historique → message d'accueil (Mission 2)
        if (mode === 'DRAFTING') {
            pushBubbleHtml('assistant', '<div class="sydra-ai-welcome-text">Mode rédaction activé, <strong>' + escapeHtml(userName) + '</strong>. Je peux vous guider pour construire votre alerte étape par étape.</div>');
            conversation.push({ role: 'assistant', content: 'Mode rédaction activé. Je peux vous guider pour construire votre alerte étape par étape.' });
            saveChatToSession();
        } else if (mode === 'ANALYSIS') {
            pushBubbleHtml('assistant', '<div class="sydra-ai-welcome-text">Mode analyse sécurisé activé, <strong>' + escapeHtml(userName) + '</strong>. Les réponses se basent sur les données codifiées de cette alerte.</div>');
            conversation.push({ role: 'assistant', content: 'Mode analyse sécurisé activé. Les réponses se basent sur les données codifiées de cette alerte.' });
            saveChatToSession();
        } else {
            showWelcomeMessage(userName, userRole);
        }
    }

    function saveChatToSession() {
        sessionStorage.setItem('sydra_chat', JSON.stringify(conversation));
    }

    // ════════════════════════════════════════════════════════════════════════
    // Bulles de chat : pushBubble (texte) et pushBubbleHtml (HTML)
    // ════════════════════════════════════════════════════════════════════════

    /** Bulle avec textContent (sécurisé, pour les messages utilisateur et IA normaux) */
    function pushBubble(role, text) {
        var el = document.createElement('div');
        el.className = 'sydra-ai-bubble text-only ' + (role === 'user' ? 'user' : 'assistant');
        el.textContent = String(text || '');
        chatBox.appendChild(el);
        scrollToBottom();
        return el;
    }

    /** Bulle avec innerHTML (pour le message d'accueil et les smart chips) */
    function pushBubbleHtml(role, html) {
        var el = document.createElement('div');
        el.className = 'sydra-ai-bubble ' + (role === 'user' ? 'user' : 'assistant');
        el.innerHTML = html;
        chatBox.appendChild(el);
        scrollToBottom();
        return el;
    }

    // ════════════════════════════════════════════════════════════════════════
    // MISSION 3 : Auto-scroll & Typing Indicator (bounce)
    // ════════════════════════════════════════════════════════════════════════

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function initTooltip() {
        var tooltip = document.getElementById('sydra-ai-tooltip');
        if (tooltip) {
            setTimeout(function() {
                tooltip.classList.add('show');
                setTimeout(function() {
                    tooltip.classList.remove('show');
                }, 8000);
            }, 2000);
        }
    }

    function showTyping() {
        if (typingBubble) { return; }
        typingBubble = document.createElement('div');
        typingBubble.className = 'sydra-ai-bubble assistant sydra-ai-typing';
        typingBubble.innerHTML = '<span></span><span></span><span></span>';
        chatBox.appendChild(typingBubble);
        scrollToBottom();
    }

    function hideTyping() {
        if (typingBubble && typingBubble.parentNode) {
            typingBubble.parentNode.removeChild(typingBubble);
        }
        typingBubble = null;
    }

    function setBusy(isBusy, label) {
        sendBtn.disabled = isBusy;
        input.disabled   = isBusy;
        if (isBusy && label) {
            sendBtn.dataset.originalHtml = sendBtn.innerHTML;
            sendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        } else if (!isBusy && sendBtn.dataset.originalHtml) {
            sendBtn.innerHTML = sendBtn.dataset.originalHtml;
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // Gestion des erreurs IA
    // ════════════════════════════════════════════════════════════════════════

    function showAiError(errorCode, message) {
        var icon  = 'error';
        var title = 'Erreur IA';
        var text  = message || 'Une erreur inattendue est survenue.';

        if (errorCode === 'rate_limit') {
            icon  = 'warning'; title = 'Limite atteinte';
            text  = 'Trop de requêtes. Veuillez patienter quelques secondes.';
        } else if (errorCode === 'auth_error') {
            title = 'Clé API invalide';
            text  = 'La clé API est invalide ou expirée. Contactez l\'administrateur.';
        } else if (errorCode === 'server_error') {
            icon  = 'warning'; title = 'Service IA indisponible';
            text  = 'Le service IA rencontre des problèmes. Réessayez dans quelques instants.';
        } else if (errorCode === 'missing_api_key') {
            title = 'Configuration manquante';
            text  = 'Aucune clé API n\'est configurée. Contactez l\'administrateur.';
        } else if (errorCode === 'network_error') {
            icon  = 'warning'; title = 'Problème réseau';
            text  = 'Impossible de joindre le service IA. Vérifiez votre connexion.';
        }

        if (window.Swal && typeof window.Swal.fire === 'function') {
            Swal.fire({ icon: icon, title: title, text: text, confirmButtonText: 'OK', confirmButtonColor: '#005bbb' });
        }

        pushBubble('assistant', '⚠️ ' + title + ' — ' + text);
        saveChatToSession();
    }

    // ════════════════════════════════════════════════════════════════════════
    // Envoi du message avec délai anti-spam
    // ════════════════════════════════════════════════════════════════════════

    function sendMessage(text) {
        if (_sendInProgress) { return; }
        _sendInProgress = true;

        var payload = {
            csrf: csrf, action: 'chat', mode: mode,
            messages: conversation.concat([{ role: 'user', content: text }])
        };
        if (mode === 'ANALYSIS' && reportId > 0) { payload.report_id = reportId; }

        conversation.push({ role: 'user', content: text });
        pushBubble('user', text);
        saveChatToSession();

        var suggestionsContainer = document.getElementById('sydra-ai-suggestions');
        if (suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
        }

        setBusy(true, "Réflexion...");
        showTyping();

        window.setTimeout(function () {
            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            })
            .then(function (res) {
                var ct = res.headers.get('content-type') || '';
                if (!res.ok || ct.indexOf('application/json') === -1) {
                    return res.text().then(function (raw) {
                        console.error('[SyDRA][AI] Réponse non-JSON:', raw);
                        throw new Error('HTTP ' + res.status + ' — ' + raw.substring(0, 300));
                    });
                }
                return res.json();
            })
            .then(function (data) {
                hideTyping();
                var ok = data && (data.success === true || data.ok === true);
                if (!ok) {
                    showAiError(String((data && data.error_code) || 'provider_error'), String((data && data.message) || 'Réponse IA indisponible.'));
                    return;
                }
                var reply = String(data.message || '').trim();
                if (reply === '') { showAiError('empty_response', 'Réponse IA vide.'); return; }
                
                // On garde la réponse brute pour l'historique
                conversation.push({ role: 'assistant', content: reply });
                
                // On la formatte pour l'affichage (Sécurité Anti-Markdown)
                var formattedReply = formatAIMessage(reply);
                pushBubbleHtml('assistant', formattedReply);
                
                saveChatToSession();
            })
            .catch(function (error) {
                hideTyping();
                showAiError('network_error', (error && error.message) ? error.message : null);
            })
            .finally(function () {
                _sendInProgress = false;
                setBusy(false);
                input.focus();
            });
        }, SEND_DELAY_MS);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Event listeners
    // ════════════════════════════════════════════════════════════════════════

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = String(input.value || '').trim();
        if (text === '') { return; }
        input.value = '';
        sendMessage(text);
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            var text = String(input.value || '').trim();
            if (text === '' || _sendInProgress) { return; }
            input.value = '';
            sendMessage(text);
        }
    });

    // ── Init ──
    initTooltip();
    initMessage();

    // ════════════════════════════════════════════════════════════════════════
    // MISSION 4 : Suggestions permanentes (Smart Chips) au-dessus de l'input
    // ════════════════════════════════════════════════════════════════════════
    var suggestionsContainer = document.getElementById('sydra-ai-suggestions');
    if (suggestionsContainer) {
        var suggestions = [];
        if (userRole === 'ADMIN' || userRole === 'GTMP_LEAD' || userRole === 'LEAD_GTMP' || userRole === 'CLUSTER_LEADER' || userRole === 'GTMP_COLEAD') {
            suggestions = [
                "Combien d'alertes en attente ?",
                "Fais-moi un résumé des incidents"
            ];
        } else {
            suggestions = [
                "Où en sont mes alertes ?",
                "Aide-moi à rédiger une note",
                "Qui est le Lead du Cluster ?"
            ];
        }
        
        suggestions.forEach(function(text) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'badge bg-light text-dark border rounded-pill me-1 py-2 px-3 sydra-ai-suggestion-btn';
            btn.textContent = text;
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (_sendInProgress) return;
                input.value = text;
                sendMessage(text);
            });
            suggestionsContainer.appendChild(btn);
        });
    }

})();
