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
    var userRole = typeof sydraUser !== 'undefined' && sydraUser.role ? sydraUser.role : String(root.getAttribute('data-user-role') || 'ORG_REPORTER').toUpperCase();
    
    var readableRole = 'Utilisateur';
    if (userRole === 'ORG_REPORTER') readableRole = 'Rapporteur';
    else if (userRole === 'ADMIN') readableRole = 'Administrateur';
    else if (userRole === 'GTMP_LEAD' || userRole === 'LEAD_GTMP') readableRole = 'Leader GTMP';
    else if (userRole === 'CLUSTER_LEADER' || userRole === 'CLUSTER_PROTECTION') readableRole = 'Coordinateur Cluster';

    var userName = readableRole;
    if (typeof sydraUser !== 'undefined') {
        var n = (sydraUser.name || '').trim();
        var o = (sydraUser.org || '').trim();
        var nLower = n.toLowerCase();
        userName = (n && nLower !== 'utilisateur' && nLower !== 'collègue') ? n : (o ? o : readableRole);
    } else {
        var rName = String(root.getAttribute('data-user-name') || '').trim();
        userName = (rName && rName.toLowerCase() !== 'utilisateur') ? rName : readableRole;
    }
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
     * MISSION 2 & 3 : Génère le message d'accueil dynamique avec les Smart Chips
     */
    function showWelcomeMessage(name, role) {
        // 1. Message de bienvenue personnalisé
        var welcomeMessage = 'Bonjour <strong>' + escapeHtml(name) + '</strong> ! 👋 Je suis l\'Assistant SyDRA. Comment puis-je vous aider aujourd\'hui ?';

        // 2. Génération des suggestions (Navigation et Chat) selon le rôle
        var suggestionsHTML = '';
        
        // Navigation (Anciens boutons)
        suggestionsHTML += '<div class="sydra-ai-smart-chips mb-2">';
        if (role === 'ADMIN' || role === 'GTMP_LEAD' || role === 'LEAD_GTMP') {
            suggestionsHTML += '<a href="?page=tableau_de_bord" class="sydra-ai-smart-chip">📊 Dashboard</a>';
            suggestionsHTML += '<a href="?page=admin-parametres" class="sydra-ai-smart-chip">⚙️ Paramètres IA</a>';
            suggestionsHTML += '<a href="?page=rapportage-creer-wizar" class="sydra-ai-smart-chip">📝 Créer (Wizard)</a>';
        } else {
            suggestionsHTML += '<a href="?page=rapportage-creer-wizar" class="sydra-ai-smart-chip">📝 Créer via le Wizard</a>';
            suggestionsHTML += '<a href="?page=rapportage-creer-AI" class="sydra-ai-smart-chip">✨ Créer avec l\'IA</a>';
            suggestionsHTML += '<a href="?page=rapportage-liste-user" class="sydra-ai-smart-chip">📋 Voir mes rapports</a>';
        }
        suggestionsHTML += '</div>';

        // Suggestions de discussion (Nouveaux boutons)
        suggestionsHTML += '<div class="chat-suggestion-chips">';
        if (role === 'ORG_REPORTER') {
            suggestionsHTML += '<button type="button" class="chat-suggestion-chip" onclick="sendSuggestion(this)">🚨 Signaler un nouvel incident</button>';
            suggestionsHTML += '<button type="button" class="chat-suggestion-chip" onclick="sendSuggestion(this)">📝 Rédiger une note de monitoring</button>';
            suggestionsHTML += '<button type="button" class="chat-suggestion-chip" onclick="sendSuggestion(this)">❓ Que dois-je inclure dans mon alerte ?</button>';
        } else {
            suggestionsHTML += '<button type="button" class="chat-suggestion-chip" onclick="sendSuggestion(this)">📊 Aide-moi à résumer une alerte reçue</button>';
            suggestionsHTML += '<button type="button" class="chat-suggestion-chip" onclick="sendSuggestion(this)">🔍 Analyser une tendance sécuritaire</button>';
            suggestionsHTML += '<button type="button" class="chat-suggestion-chip" onclick="sendSuggestion(this)">✅ Comment codifier un rapport ?</button>';
        }
        suggestionsHTML += '</div>';

        var html = '<div class="sydra-ai-welcome-text">' + welcomeMessage + '</div>';
        
        // Avertissement de sécurité (Mission 4) si pas sur page de rapportage
        if (!isOnRapportagePage && mode === 'GENERIC_HELP') {
            html += SECURITY_WARNING;
        }

        html += suggestionsHTML;

        // Injecter avec innerHTML
        var el = document.createElement('div');
        el.className = 'sydra-ai-bubble assistant';
        el.innerHTML = html;
        chatBox.appendChild(el);
        scrollToBottom();

        // Stocker le texte brut dans la conversation (pas le HTML)
        var plainText = welcomeMessage.replace(/<[^>]*>/g, '');
        conversation.push({ role: 'assistant', content: plainText });
        saveChatToSession();
    }
    
    // Fonction pour traiter le clic sur une suggestion (Mission 3)
    window.sendSuggestion = function(buttonElement) {
        // Enlève l'émoji du début
        const text = buttonElement.innerText.replace(/^[^\w\sÀ-ÿ]+/, '').trim();
        
        // Cacher toutes les suggestions une fois qu'on a cliqué
        const chipsContainer = buttonElement.closest('.chat-suggestion-chips');
        if(chipsContainer) chipsContainer.style.display = 'none';

        // Remplir l'input
        const chatInput = document.getElementById('sydra-ai-input');
        if (chatInput) {
            chatInput.value = text;
            // Simuler l'envoi
            const sendBtn = document.getElementById('sydra-ai-send');
            if (sendBtn) sendBtn.click();
        }
    };

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
        var currentContext = mode + '_' + reportId;
        var lastContext = sessionStorage.getItem('sydra_ai_context');
        
        if (lastContext !== currentContext) {
            sessionStorage.removeItem('sydra_ai_chat');
        }
        sessionStorage.setItem('sydra_ai_context', currentContext);

        var savedChat = sessionStorage.getItem('sydra_ai_chat');
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
            var analysisWelcome = 'Bonjour <strong>' + escapeHtml(userName) + '</strong> ! 👋 Je vois que vous consultez l\'alerte <strong>#' + reportId + '</strong>. Je peux vous aider à l\'analyser, la résumer ou en discuter ici si vous le souhaitez.';
            pushBubbleHtml('assistant', '<div class="sydra-ai-welcome-text">' + analysisWelcome + '</div>');
            conversation.push({ role: 'assistant', content: analysisWelcome.replace(/<[^>]*>/g, '') });
            saveChatToSession();
        } else {
            showWelcomeMessage(userName, userRole);
        }
    }

    function saveChatToSession() {
        sessionStorage.setItem('sydra_ai_chat', JSON.stringify(conversation));
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
                
                // MISSION 3 : Interception du JSON final
                const jsonRegex = /\{[\s\S]*"status"\s*:\s*"complete"[\s\S]*\}/;
                var match = reply.match(jsonRegex);
                
                if (match) {
                    try {
                        var parsedJson = JSON.parse(match[0]);
                        // Cache la zone de texte
                        var formArea = document.querySelector('.sydra-ai-input-wrapper');
                        if (formArea) formArea.classList.add('d-none');
                        
                        // Injecte la belle carte de résumé dans le chatbot
                        var summaryHtml = '<div class="card border-primary mb-3 shadow-sm" style="border-radius:12px; font-family:\'Inter\', sans-serif;">' +
                            '<div class="card-header bg-primary text-white" style="border-radius:12px 12px 0 0;"><i class="fa-solid fa-clipboard-check me-2"></i> Résumé de l\'alerte</div>' +
                            '<div class="card-body p-3 bg-white">' +
                            '<h6 class="text-primary fw-bold mb-2">' + escapeHtml(parsedJson.title || 'Incident') + '</h6>' +
                            '<p class="small mb-1"><strong>Date :</strong> ' + escapeHtml(parsedJson.incident_date || 'N/A') + '</p>' +
                            '<p class="small mb-2"><strong>Lieu :</strong> ' + escapeHtml(parsedJson.location || 'N/A') + '</p>' +
                            '<p class="small text-muted mb-0">L\'assistant a extrait toutes les données nécessaires.</p>' +
                            '</div>' +
                            '<div class="card-footer bg-light p-2 d-flex flex-column gap-2" style="border-radius:0 0 12px 12px;">' +
                            '<a href="?page=rapportage-creer-wizar&step=4" class="btn btn-primary btn-sm w-100 fw-bold"><i class="fa-solid fa-paper-plane me-1"></i> Terminer et Soumettre</a>' +
                            '<button type="button" class="btn btn-outline-secondary btn-sm w-100 js-modify-btn"><i class="fa-solid fa-pen-to-square me-1"></i> Modifier</button>' +
                            '<button type="button" class="btn btn-outline-danger btn-sm w-100 js-restart-btn"><i class="fa-solid fa-rotate-right me-1"></i> Recommencer</button>' +
                            '</div>' +
                            '</div>';
                        
                        var el = pushBubbleHtml('assistant', summaryHtml);
                        
                        // Evénement Modifier
                        var modifyBtn = el.querySelector('.js-modify-btn');
                        if (modifyBtn) {
                            modifyBtn.addEventListener('click', function() {
                                if (formArea) formArea.classList.remove('d-none');
                                pushBubble('assistant', 'Qu\'est-ce que vous souhaitez corriger dans ce rapport ?');
                                conversation.push({ role: 'assistant', content: 'Qu\'est-ce que vous souhaitez corriger dans ce rapport ?' });
                                saveChatToSession();
                                setTimeout(function(){ input.focus(); }, 100);
                            });
                        }
                        
                        // Evénement Recommencer
                        var restartBtn = el.querySelector('.js-restart-btn');
                        if (restartBtn) {
                            restartBtn.addEventListener('click', function() {
                                var btnReset = document.getElementById('btnResetFloatingChat');
                                if (btnReset) btnReset.click();
                            });
                        }
                        
                        // Garde la réponse JSON brute en historique interne
                        conversation.push({ role: 'assistant', content: reply });
                        saveChatToSession();
                        
                        // Transmet au prefill existant
                        sessionStorage.setItem('sydra_ia_prefill', JSON.stringify(parsedJson));
                        return; // Stoppe ici
                        
                    } catch (e) {
                        console.error("[SyDRA] Erreur de parsing JSON intercepté", e);
                    }
                }
                
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

    // ── Bouton Réinitialiser (Mission 3) ──
    var btnReset = document.getElementById('btnResetFloatingChat');
    if (btnReset) {
        btnReset.addEventListener('click', function() {
            var doReset = function() {
                sessionStorage.removeItem('sydra_ai_chat');
                sessionStorage.removeItem('sydra_ia_prefill');
                conversation = [];
                chatBox.innerHTML = '';
                var formArea = document.querySelector('.sydra-ai-input-wrapper');
                if (formArea) formArea.classList.remove('d-none');
                initMessage();
            };
            
            if (window.Swal && typeof window.Swal.fire === 'function') {
                Swal.fire({
                    title: 'Réinitialiser la conversation ?',
                    text: 'Tout l\'historique avec l\'assistant sera effacé.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Oui, effacer',
                    cancelButtonText: 'Annuler'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        doReset();
                    }
                });
            } else {
                if (confirm('Voulez-vous vraiment effacer la conversation ?')) {
                    doReset();
                }
            }
        });
    }

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
