<?php $csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>

<div class="card border-0 shadow-sm rounded-4 ai-compose-shell">
    <div class="ai-compose-header">
        <div>
            <h1 class="h4 mb-1"><i class="fa-solid fa-robot me-2 text-primary"></i>Assistant IA de création d'alerte</h1>
            <p class="text-muted mb-0">Décrivez l'incident, l'IA vous guidera question par question pour finaliser un rapport exploitable.</p>
        </div>
        <a href="?page=rapportage" class="btn btn-outline-secondary">Retour</a>
    </div>

    <div class="ai-chat-panel" id="ai-chat-panel"></div>

    <div class="ai-compose-footer">
        <div class="d-flex gap-2 flex-wrap mb-2">
            <button type="button" id="btn-generate-structured" class="btn btn-success d-none">
                <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Generer le rapport structure
            </button>
            <small class="text-muted" id="ai-ready-hint">L'IA vous posera des questions pour completer le rapport.</small>
        </div>

        <form id="ai-chat-form" class="d-flex gap-2" novalidate>
            <input type="hidden" id="ai-csrf" value="<?= $csrf; ?>">
            <textarea id="ai-user-input" class="form-control" rows="2" placeholder="Ex: Le 04/06/2026, des affrontements ont provoque un deplacement massif..." required></textarea>
            <button type="submit" class="btn btn-primary px-4" id="ai-send-btn">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<style>
.ai-compose-shell {
    border: 1px solid #dbeafe;
    overflow: hidden;
}

.ai-compose-header {
    padding: 18px 20px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    border-bottom: 1px solid #e2e8f0;
    background: linear-gradient(150deg, #f8fbff 0%, #eef5ff 100%);
}

.ai-chat-panel {
    height: 56vh;
    overflow-y: auto;
    padding: 16px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.ai-bubble {
    max-width: min(78%, 720px);
    border-radius: 16px;
    padding: 10px 12px;
    line-height: 1.45;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
    white-space: pre-wrap;
}

.ai-bubble.user {
    align-self: flex-end;
    background: #005BBB;
    color: #ffffff;
    border-bottom-right-radius: 8px;
}

.ai-bubble.assistant {
    align-self: flex-start;
    background: #e2e8f0;
    color: #0f172a;
    border-bottom-left-radius: 8px;
}

.ai-compose-footer {
    border-top: 1px solid #e2e8f0;
    padding: 12px 16px 14px;
    background: #ffffff;
}

#ai-user-input {
    resize: none;
}
</style>

<script>
(function () {
    var chatPanel = document.getElementById('ai-chat-panel');
    var form = document.getElementById('ai-chat-form');
    var input = document.getElementById('ai-user-input');
    var sendBtn = document.getElementById('ai-send-btn');
    var generateBtn = document.getElementById('btn-generate-structured');
    var readyHint = document.getElementById('ai-ready-hint');
    var csrfInput = document.getElementById('ai-csrf');

    if (!chatPanel || !form || !input || !sendBtn || !generateBtn || !readyHint || !csrfInput) {
        return;
    }

    var csrf = String(csrfInput.value || '');
    var messages = [];
    var aiReady = false;

    function bubble(role, text) {
        var div = document.createElement('div');
        div.className = 'ai-bubble ' + (role === 'user' ? 'user' : 'assistant');
        div.textContent = text;
        chatPanel.appendChild(div);
        chatPanel.scrollTop = chatPanel.scrollHeight;
    }

    function showToast(icon, title) {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                toast: true,
                position: 'top-end',
                timer: 2600,
                timerProgressBar: true,
                showConfirmButton: false,
                icon: icon,
                title: title
            });
            return;
        }
        window.alert(title);
    }

    function setBusy(isBusy) {
        sendBtn.disabled = isBusy;
        input.disabled = isBusy;
        generateBtn.disabled = isBusy;
    }

    function callAi(action, payload) {
        var body = {
            action: action,
            csrf: csrf,
            messages: payload
        };

        return fetch('api/ai_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(body)
        }).then(function (res) {
            return res.json();
        });
    }

    function sanitizeAssistantMessage(text) {
        return String(text || '').replace(/\[\[READY_TO_GENERATE\]\]/g, '').trim();
    }

    function extractJson(text) {
        var raw = String(text || '').trim();
        if (raw === '') {
            return null;
        }

        var cleaned = raw;
        if (cleaned.indexOf('```') >= 0) {
            cleaned = cleaned.replace(/^```json\s*/i, '').replace(/^```\s*/i, '').replace(/```$/i, '').trim();
        }

        try {
            return JSON.parse(cleaned);
        } catch (e) {
            var start = cleaned.indexOf('{');
            var end = cleaned.lastIndexOf('}');
            if (start >= 0 && end > start) {
                try {
                    return JSON.parse(cleaned.slice(start, end + 1));
                } catch (e2) {
                    return null;
                }
            }
        }
        return null;
    }

    function updateReadyState(messageText) {
        if (String(messageText || '').indexOf('[[READY_TO_GENERATE]]') >= 0) {
            aiReady = true;
            generateBtn.classList.remove('d-none');
            readyHint.textContent = 'Informations suffisantes detectees. Vous pouvez generer le rapport structure.';
        }
    }

    function sendMessage() {
        var content = String(input.value || '').trim();
        if (content === '') {
            return;
        }

        messages.push({ role: 'user', content: content });
        bubble('user', content);
        input.value = '';
        setBusy(true);

        callAi('assist_creation', messages)
            .then(function (data) {
                if (!data || data.ok !== true) {
                    throw new Error((data && data.message) ? data.message : 'Erreur IA.');
                }

                var assistantRaw = String(data.message || '');
                updateReadyState(assistantRaw);
                var assistantText = sanitizeAssistantMessage(assistantRaw);
                messages.push({ role: 'assistant', content: assistantText });
                bubble('assistant', assistantText);
            })
            .catch(function (error) {
                showToast('error', error.message || 'Erreur pendant la reponse IA.');
            })
            .finally(function () {
                setBusy(false);
                input.focus();
            });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        sendMessage();
    });

    generateBtn.addEventListener('click', function () {
        if (!aiReady) {
            showToast('info', 'Continuez encore un peu la discussion avec l\'IA.');
            return;
        }

        setBusy(true);

        callAi('generate_structured', messages)
            .then(function (data) {
                if (!data || data.ok !== true) {
                    throw new Error((data && data.message) ? data.message : 'Generation impossible.');
                }

                var parsed = extractJson(data.message || '');
                if (!parsed) {
                    throw new Error('Le format de reponse structuree est invalide.');
                }

                var prefill = {
                    incident_type: String(parsed.incident_type || ''),
                    urgency_level: String(parsed.urgency_level || 'Moyenne'),
                    description: String(parsed.contexte || ''),
                    analyse: String(parsed.analyse || ''),
                    priority_needs: String(parsed.besoins_prioritaires || ''),
                    recommandations: String(parsed.recommandations || ''),
                    victims_count: Number(parsed.victims_count || 0),
                    displaced_households: Number(parsed.displaced_households || 0)
                };

                try {
                    window.sessionStorage.setItem('sydra_ia_prefill', JSON.stringify(prefill));
                } catch (e) {
                    // stockage indisponible
                }

                if (window.Swal && typeof window.Swal.fire === 'function') {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Rapport structure genere',
                        text: 'Redirection vers le wizard pour validation finale...',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(function () {
                        window.location.href = '?page=rapportage-creer-wizar';
                    });
                    return;
                }

                window.location.href = '?page=rapportage-creer-wizar';
            })
            .catch(function (error) {
                showToast('error', error.message || 'Erreur pendant la generation structuree.');
            })
            .finally(function () {
                setBusy(false);
            });
    });

    bubble('assistant', 'Bonjour. Je suis votre assistant de monitoring de protection. Pour commencer, decrivez en quelques lignes ce qui s\'est passe (lieu, date, incident, impact).');
})();
</script>
