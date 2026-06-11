(function () {
    'use strict';

    var root = document.getElementById('sydra-ai-root');
    if (!root) { return; }

    var badge = document.getElementById('sydra-ai-mode-badge');
    var chatBox = document.getElementById('sydra-ai-chat');
    var form = document.getElementById('sydra-ai-form');
    var input = document.getElementById('sydra-ai-input');
    var sendBtn = document.getElementById('sydra-ai-send');
    var widgetBtn = document.getElementById('global-ai-widget-btn');
    var offcanvasEl = document.getElementById('sydraAiOffcanvas');

    if (!badge || !chatBox || !form || !input || !sendBtn) { return; }

    var csrf = String(root.getAttribute('data-csrf') || '');
    var endpoint = String(root.getAttribute('data-ai-endpoint') || 'api/ai_handler.php');
    var url = new URL(window.location.href);
    var page = String(url.searchParams.get('page') || '').trim();
    var mode = 'GENERIC_HELP';
    var reportId = 0;

    if (page === 'rapportage-creer-wizar' || page === 'rapportage-creer-AI') {
        mode = 'DRAFTING';
    } else if (page === 'rapportage-details' || page === 'rapportage-voir') {
        mode = 'ANALYSIS';
        reportId = Number(url.searchParams.get('id') || url.searchParams.get('report_id') || 0);
        if (!Number.isFinite(reportId) || reportId <= 0) {
            reportId = 0;
            mode = 'GENERIC_HELP';
        }
    }

    if (mode === 'GENERIC_HELP') {
        badge.className = 'badge text-bg-secondary';
        badge.textContent = 'Mode Aide Générale (Aucune donnée partagée)';
    } else {
        badge.className = 'badge text-bg-success';
        badge.textContent = 'Mode Analyse (Données anonymisées et codifiées)';
    }

    // Cacher/montrer le bouton flottant quand l'offcanvas s'ouvre/ferme
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

    function pushBubble(role, text) {
        var el = document.createElement('div');
        el.className = 'sydra-ai-bubble ' + (role === 'user' ? 'user' : 'assistant');
        el.textContent = String(text || '');
        chatBox.appendChild(el);
        chatBox.scrollTop = chatBox.scrollHeight;
        return el;
    }

    function showTyping() {
        if (typingBubble) { return; }
        typingBubble = document.createElement('div');
        typingBubble.className = 'sydra-ai-bubble assistant sydra-ai-typing';
        typingBubble.innerHTML = '<span></span><span></span><span></span>';
        chatBox.appendChild(typingBubble);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function hideTyping() {
        if (typingBubble && typingBubble.parentNode) {
            typingBubble.parentNode.removeChild(typingBubble);
        }
        typingBubble = null;
    }

    function setBusy(isBusy) {
        sendBtn.disabled = isBusy;
        input.disabled = isBusy;
    }

    function initMessage() {
        if (mode === 'DRAFTING') {
            pushBubble('assistant', 'Mode rédaction activé. Je peux vous guider pour construire votre alerte étape par étape.');
            return;
        }
        if (mode === 'ANALYSIS') {
            pushBubble('assistant', 'Mode analyse sécurisé activé. Les réponses se basent sur les données codifiées de cette alerte.');
            return;
        }
        pushBubble('assistant', 'Mode aide générale activé. Aucune donnée d\'incident n\'est partagée avec l\'IA sur cette page.');
    }

    function sendMessage(text) {
        var payload = {
            csrf: csrf,
            action: 'chat',
            mode: mode,
            messages: conversation.concat([{ role: 'user', content: text }])
        };

        if (mode === 'ANALYSIS' && reportId > 0) {
            payload.report_id = reportId;
        }

        conversation.push({ role: 'user', content: text });
        pushBubble('user', text);
        setBusy(true);
        showTyping();

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data || data.ok !== true) {
                    throw new Error((data && data.message) ? data.message : 'Réponse IA indisponible.');
                }
                var reply = String(data.message || '').trim();
                if (reply === '') { throw new Error('Réponse IA vide.'); }
                conversation.push({ role: 'assistant', content: reply });
                hideTyping();
                pushBubble('assistant', reply);
            })
            .catch(function (error) {
                hideTyping();
                var message = (error && error.message) ? error.message : 'Impossible de contacter le service IA.';
                pushBubble('assistant', 'Erreur : ' + message);
            })
            .finally(function () {
                setBusy(false);
                input.focus();
            });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var text = String(input.value || '').trim();
        if (text === '') { return; }
        input.value = '';
        sendMessage(text);
    });

    initMessage();
})();
