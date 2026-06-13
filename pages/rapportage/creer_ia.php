<?php 
declare(strict_types=1);

$config = require __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

$csrf = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
$userId = (int)($_SESSION['auth_user_id'] ?? 0);
$existingDraftId = 0;

try {
    $pdo = db($config);
    // On cherche un brouillon pour cet utilisateur
    // On s'assure d'utiliser la bonne colonne (user_id ou reporter_user_id)
    $stmtCol = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "reports" AND COLUMN_NAME = "reporter_user_id"');
    $stmtCol->execute();
    $userCol = ((int)$stmtCol->fetchColumn() > 0) ? 'reporter_user_id' : 'user_id';
    
    $statusExpr = 'LOWER(REPLACE(REPLACE(REPLACE(COALESCE(NULLIF(TRIM(workflow_status), ""), "brouillon"), "é", "e"), "è", "e"), "ê", "e"))';
    
    $checkStmt = $pdo->prepare('SELECT id FROM reports WHERE ' . $userCol . ' = :user_id AND ' . $statusExpr . ' = "brouillon" ORDER BY id DESC LIMIT 1');
    $checkStmt->execute(['user_id' => $userId]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $existingDraftId = (int)$row['id'];
    }
} catch (Throwable $e) {
    // Silencieux
}
?>

<div class="card border-0 shadow-sm rounded-4 ai-compose-shell" style="max-width: 800px; margin: 0 auto; background-color: #f9fafb;">
    <div class="ai-compose-header">
        <div>
            <h1 class="h4 mb-1" style="font-family: 'Inter', 'Poppins', sans-serif; font-weight: 600; color: #1f2937;">
                <i class="fa-solid fa-robot me-2 text-primary"></i>Assistant IA SyDRA
            </h1>
            <p class="text-muted mb-0" style="font-size: 0.9rem;">Discutez avec l'IA pour créer votre rapport étape par étape.</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="btn-header-reset" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                <i class="fa-solid fa-rotate-right me-1"></i>Recommencer à zéro
            </button>
            <a href="?page=rapportage-liste-user" class="btn btn-sm btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 32px; height: 32px;" title="Quitter et Annuler"><i class="fa-solid fa-xmark"></i></a>
        </div>
    </div>

    <!-- Zone de discussion -->
    <div class="ai-chat-panel" id="chatMessagesContainer">
        <?php if ($existingDraftId === 0): ?>
        <div class="d-flex mb-4" id="ai-welcome-msg">
            <div class="flex-shrink-0 me-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                    <i class="fa-solid fa-robot"></i>
                </div>
            </div>
            <div class="p-3 bg-white border shadow-sm text-dark w-100" style="border-radius: 0 16px 16px 16px; font-family: 'Inter', sans-serif;">
                <h6 class="fw-bold mb-2 text-primary">Bonjour ! Je suis l'Assistant IA de SyDRA. 👋</h6>
                <p class="mb-2 text-muted" style="font-size: 0.95rem;">
                     Je suis là pour vous aider à rédiger votre alerte (Flash ou Note) rapidement. Ne vous souciez pas de la mise en forme, racontez-moi simplement ce qui s'est passé avec vos propres mots.
                </p>
                <p class="mb-0 text-muted" style="font-size: 0.95rem;">
                    <strong>Que souhaitez-vous signaler aujourd'hui ?</strong>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Carte de Résumé dynamique injectée via JS -->

    <!-- Zone de saisie -->
    <div class="ai-compose-footer chat-input-area" id="ai-compose-footer">
        <div class="chat-suggestions d-flex gap-2 mb-3 overflow-auto pb-1" style="scrollbar-width: none;">
            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill suggestion-btn shadow-sm text-nowrap">
                <i class="fa-solid fa-person-rifle me-1"></i> Signaler des affrontements
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill suggestion-btn shadow-sm text-nowrap">
                <i class="fa-solid fa-person-walking-luggage me-1"></i> Rapporter un déplacement massif
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill suggestion-btn shadow-sm text-nowrap">
                <i class="fa-solid fa-house-crack me-1"></i> Signaler une catastrophe naturelle
            </button>
        </div>
        <form id="ai-chat-form" class="js-ai-chat-form d-flex align-items-center gap-2" novalidate>
            <input type="hidden" id="ai-csrf" value="<?= $csrf; ?>">
            <textarea id="ai-user-input" class="form-control rounded-pill px-4 py-2" rows="1" placeholder="Tapez votre réponse ici..." required style="resize: none; background-color: #f1f5f9; border: 1px solid #e2e8f0;"></textarea>
            <button type="submit" class="btn btn-primary rounded-circle" id="ai-send-btn" style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<style>
.ai-compose-shell {
    border: 1px solid #e2e8f0 !important;
    overflow: hidden;
    height: calc(100vh - 120px);
    display: flex;
    flex-direction: column;
}
.ai-compose-header {
    padding: 20px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
}
.ai-chat-panel {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
    display: flex;
    flex-direction: column;
    gap: 16px;
    scroll-behavior: smooth;
}
.ai-bubble {
    max-width: 80%;
    border-radius: 16px;
    padding: 12px 18px;
    line-height: 1.5;
    font-size: 0.95rem;
    font-family: 'Inter', 'Poppins', sans-serif;
    box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    white-space: pre-wrap;
    word-wrap: break-word;
}
.ai-bubble.user {
    align-self: flex-end;
    border-bottom-right-radius: 4px;
}
.ai-bubble.assistant {
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}
.ai-compose-footer {
    padding: 16px 24px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
}
#ai-user-input:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
    background-color: #ffffff !important;
}
.typing-indicator {
    display: inline-flex;
    gap: 4px;
    align-items: center;
    height: 24px;
}
.typing-dot {
    width: 6px;
    height: 6px;
    background: #64748b;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out;
}
.typing-dot:nth-child(1) { animation-delay: 0s; }
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing {
    0%, 100% { transform: translateY(0); opacity: 0.4; }
    50% { transform: translateY(-4px); opacity: 1; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check for explicit reset parameter in URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('reset') === '1') {
        sessionStorage.removeItem('sydra_ai_chat');
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname + '?page=rapportage-creer-AI');
    }

    var chatPanel = document.getElementById('chatMessagesContainer');
    var form = document.getElementById('ai-chat-form');
    var input = document.getElementById('ai-user-input');
    var sendBtn = document.getElementById('ai-send-btn');
    var csrfInput = document.getElementById('ai-csrf');
    var composeFooter = document.getElementById('ai-compose-footer');

    // Gestion des suggestions
    var suggestionBtns = document.querySelectorAll('.suggestion-btn');
    var suggestionsContainer = document.querySelector('.chat-suggestions');

    var existingDraftId = <?= $existingDraftId ?>;
    var csrf = String(csrfInput.value || '');
    let chatHistory = [];
    var extractedData = null;

    // Ajustement hauteur textarea
    input.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Reset UI helper function
    function doResetUi() {
        sessionStorage.removeItem('sydra_ai_chat');
        chatHistory = [];
        chatPanel.innerHTML = '';
        showInitialWelcome();

        document.querySelector('.chat-input-area').classList.remove('d-none');
        form.classList.remove('d-none');
        input.disabled = false;
        sendBtn.disabled = false;
        input.value = '';
        if (suggestionsContainer) {
            suggestionsContainer.classList.remove('d-none');
        }
    }

    // Gestion du Bouton Recommencer du Header
    var headerResetBtn = document.getElementById('btn-header-reset');
    if (headerResetBtn) {
        headerResetBtn.addEventListener('click', function() {
            if (typeof premiumAlert !== 'undefined') {
                premiumAlert.fire({
                    title: 'Attention',
                    text: 'Attention : Nous allons annuler tout ce brouillon et recommencer à zéro. Êtes-vous sûr ?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Oui, recommencer',
                    cancelButtonText: 'Annuler',
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        doResetUi();
                    }
                });
            } else {
                if (confirm('Attention : Nous allons annuler tout ce brouillon et recommencer à zéro. Êtes-vous sûr ?')) {
                    doResetUi();
                }
            }
        });
    }

    // Gestion du Brouillon Existant
    if (existingDraftId > 0 && typeof premiumAlert !== 'undefined') {
        premiumAlert.fire({
            title: 'Brouillon détecté',
            text: "Vous avez un brouillon en cours. Voulez-vous le reprendre manuellement ou le supprimer pour recommencer avec l'IA ?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Reprendre le brouillon',
            cancelButtonText: 'Recommencer (Supprimer)',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirection
                window.location.href = '?page=rapportage-creer-wizar';
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                // Supprimer le brouillon
                fetch('api/delete_draft.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ draft_id: existingDraftId, csrf: csrf })
                }).then(res => res.json()).then(data => {
                    if(data.ok) {
                        doResetUi();
                        premiumAlert.fire('Supprimé', 'Le brouillon a été supprimé. Vous pouvez commencer.', 'success');
                    } else {
                        premiumAlert.fire('Erreur', data.message || 'Impossible de supprimer le brouillon.', 'error');
                    }
                });
            }
        });
    }

    function bubble(role, text, isTyping = false) {
        var div = document.createElement('div');
        div.className = 'ai-bubble ' + role + (role === 'user' ? ' bg-primary text-white' : ' bg-light text-dark');
        if (isTyping) {
            div.id = 'ai-typing';
            div.innerHTML = '<div class="typing-indicator"><div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div></div>';
        } else {
            div.textContent = text;
        }
        chatPanel.appendChild(div);
        chatPanel.scrollTop = chatPanel.scrollHeight;
    }

    function removeTyping() {
        var typing = document.getElementById('ai-typing');
        if (typing) typing.remove();
    }

    function showInitialWelcome() {
        var div = document.createElement('div');
        div.className = 'd-flex mb-4';
        div.id = 'ai-welcome-msg';
        div.innerHTML = `
            <div class="flex-shrink-0 me-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                    <i class="fa-solid fa-robot"></i>
                </div>
            </div>
            <div class="p-3 bg-white border shadow-sm text-dark w-100" style="border-radius: 0 16px 16px 16px; font-family: 'Inter', sans-serif;">
                <h6 class="fw-bold mb-2 text-primary">Bonjour ! Je suis l'Assistant IA de SyDRA. 👋</h6>
                <p class="mb-2 text-muted" style="font-size: 0.95rem;">
                    Je suis là pour vous aider à rédiger votre alerte (Flash ou Note) rapidement. Ne vous souciez pas de la mise en forme, racontez-moi simplement ce qui s'est passé avec vos propres mots.
                </p>
                <p class="mb-0 text-muted" style="font-size: 0.95rem;">
                    <strong>Que souhaitez-vous signaler aujourd'hui ?</strong>
                </p>
            </div>
        `;
        chatPanel.appendChild(div);
        chatPanel.scrollTop = chatPanel.scrollHeight;
    }

    function callAi(payload) {
        return fetch('api/ai_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ action: 'assist_creation', mode: 'DRAFTING', csrf: csrf, messages: payload })
        }).then(res => res.json());
    }

    function appendMessage(role, text) {
        bubble(role, text);
    }

    function processAssistantResponse(aiResponseText) {
        // 1. Regex pour extraire le JSON même s'il est entouré de texte ou de balises Markdown (```json)
        const jsonRegex = /\{[\s\S]*"status"\s*:\s*"complete"[\s\S]*\}/;
        const match = aiResponseText.match(jsonRegex);

        if (match) {
            try {
                // Nettoyer les backslashes invalides avant le parsing pour éviter les erreurs d'échappement
                let cleanedJson = match[0].replace(/\\(?!["\\\/bfnrtu])/g, '\\\\');
                // Parse le JSON intercepté
                const finalData = JSON.parse(cleanedJson);
                const report = finalData.report_data;

                // 2. Masquer définitivement la zone de saisie du chat
                document.querySelector('.chat-input-area').classList.add('d-none');

                // 3. Créer et injecter la carte de résumé UI Premium avec les 3 boutons
                const summaryCardHTML = `
                    <div class="card border-0 shadow-sm mt-4 ai-validation-card" style="border-radius: 16px; background-color: #f8f9fa; border: 1px solid #e2e8f0 !important; width: 100%;">
                        <div class="card-body p-4">
                            <h5 class="text-primary mb-3"><i class="fa-solid fa-list-check me-2"></i>Résumé des informations collectées</h5>
                            <ul class="list-group list-group-flush mb-4 rounded">
                                <li class="list-group-item bg-transparent"><strong>Province :</strong> ${report.province || ''}</li>
                                <li class="list-group-item bg-transparent"><strong>Territoire :</strong> ${report.territory || ''}</li>
                                <li class="list-group-item bg-transparent"><strong>Zone de santé :</strong> ${report.health_zone || ''}</li>
                                <li class="list-group-item bg-transparent"><strong>Village :</strong> ${report.village || ''}</li>
                                <li class="list-group-item bg-transparent"><strong>Incident :</strong> ${report.incident_type || ''}</li>
                                <li class="list-group-item bg-transparent"><strong>Victimes :</strong> ${report.victims_count || 0}</li>
                                <li class="list-group-item bg-transparent"><strong>Ménages déplacés :</strong> ${report.displaced_households || 0}</li>
                                <li class="list-group-item bg-transparent"><strong>Résumé :</strong> ${report.facts_text || ''}</li>
                                <li class="list-group-item bg-transparent"><strong>Analyse :</strong> ${report.analysis_text || ''}</li>
                                <li class="list-group-item bg-transparent"><strong>Recommandations :</strong> ${report.recommendations_text || ''}</li>
                            </ul>
                            
                            <div class="mt-4 p-3 rounded-3 border-start border-4 d-flex align-items-center mb-4" style="background-color: #f0fdf4; border-color: #16a34a !important;">
                                <i class="fa-solid fa-circle-info text-success me-3 fs-4"></i>
                                <p class="mb-0 text-success fw-medium" style="font-size: 0.95rem;">
                                    J'ai compilé toutes les informations. Veuillez vérifier le résumé ci-dessus.
                                </p>
                            </div>

                            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center align-items-center mt-3 pt-3 border-top action-buttons-container">
                                <button id="btnSubmitAI" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm fw-semibold d-flex align-items-center" style="font-size: 1.05rem; transition: all 0.2s;">
                                    <i class="fa-solid fa-paper-plane me-2"></i>Terminer et Soumettre
                                </button>
                                <button id="btnEditAI" class="btn btn-light border-secondary-subtle rounded-pill px-4 fw-medium text-dark d-flex align-items-center shadow-sm" style="transition: all 0.2s;">
                                    <i class="fa-solid fa-pen-to-square me-2 text-secondary"></i>Modifier
                                </button>
                                <button id="btnRestartAI" class="btn btn-link text-danger text-decoration-none px-3 fw-medium d-flex align-items-center" style="transition: all 0.2s; opacity: 0.85;">
                                    <i class="fa-solid fa-rotate-right me-1"></i>Recommencer
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                // Ajouter la carte au conteneur des messages
                document.getElementById('chatMessagesContainer').insertAdjacentHTML('beforeend', summaryCardHTML);
                
                // Faire défiler vers le bas
                const chatContainer = document.getElementById('chatMessagesContainer');
                chatContainer.scrollTop = chatContainer.scrollHeight;

                // 4. Activer les écouteurs des boutons (Listeners)
                const btnSubmit = document.getElementById('btnSubmitAI');
                const btnEdit = document.getElementById('btnEditAI');
                const btnRestart = document.getElementById('btnRestartAI');
                const buttonsContainer = chatContainer.querySelector('.ai-validation-card:last-of-type .action-buttons-container');

                btnSubmit.addEventListener('click', function() {
                    btnSubmit.disabled = true;
                    btnEdit.disabled = true;
                    btnRestart.disabled = true;
                    btnSubmit.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Soumission...';

                    if (typeof toastr !== 'undefined') {
                        toastr.info("Je vous emmène sur la page de visualisation. Vous pourrez soumettre juste une dernière étape et nous aurons fini.");
                    } else if (typeof premiumAlert !== 'undefined') {
                        premiumAlert.fire({
                            icon: 'info',
                            title: 'Redirection...',
                            text: "Je vous emmène sur la page de visualisation. Vous pourrez soumettre juste une dernière étape et nous aurons fini.",
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }

                    var formData = new URLSearchParams();
                    formData.append('csrf', csrf);
                    formData.append('status_action', 'Brouillon');
                    formData.append('province', report.province || '');
                    formData.append('territory', report.territory || '');
                    formData.append('health_zone', report.health_zone || '');
                    formData.append('village', report.village || '');
                    formData.append('incident_type', report.incident_type || '');
                    formData.append('victims_count', report.victims_count || 0);
                    formData.append('displaced_households', report.displaced_households || 0);
                    formData.append('facts_text', report.facts_text || '');
                    formData.append('analysis_text', report.analysis_text || '');
                    formData.append('recommendations_text', report.recommendations_text || '');
                    formData.append('is_ai_generated', '1');

                    fetch('api/save_report.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData
                    })
                    .then(res => res.json())
                    .then(resData => {
                        if (resData.ok) {
                            sessionStorage.removeItem('sydra_ai_chat');
                            setTimeout(function() {
                                window.location.href = '?page=rapportage-creer-wizar&id=' + resData.report_id + '&id_brouillon=' + resData.report_id + '&step=4';
                            }, 1000);
                        } else {
                            btnSubmit.disabled = false;
                            btnEdit.disabled = false;
                            btnRestart.disabled = false;
                            btnSubmit.innerHTML = '<i class="fa-solid fa-check me-2"></i>Terminer et Soumettre';
                            if (typeof premiumAlert !== 'undefined') {
                                premiumAlert.fire('Erreur', resData.message || 'Impossible de sauvegarder le rapport.', 'error');
                            }
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        btnSubmit.disabled = false;
                        btnEdit.disabled = false;
                        btnRestart.disabled = false;
                        btnSubmit.innerHTML = '<i class="fa-solid fa-check me-2"></i>Terminer et Soumettre';
                    });
                });

                btnEdit.addEventListener('click', function() {
                    // Réafficher la zone de saisie
                    document.querySelector('.chat-input-area').classList.remove('d-none');
                    // Cacher les boutons de la carte
                    if (buttonsContainer) {
                        buttonsContainer.classList.add('d-none');
                    } else {
                        this.parentElement.classList.add('d-none');
                    }
                    
                    // Ajouter une bulle IA demandant quoi modifier
                    const editPrompt = "Quelles informations n'avez-vous pas trouvées correctes ou souhaitez-vous modifier ?";
                    
                    chatHistory.push({ role: 'assistant', content: editPrompt });
                    sessionStorage.setItem('sydra_ai_chat', JSON.stringify(chatHistory));
                    
                    appendMessage('assistant', editPrompt);
                });

                btnRestart.addEventListener('click', function() {
                    if (typeof premiumAlert !== 'undefined') {
                        premiumAlert.fire({
                            title: 'Êtes-vous sûr ?',
                            text: "Nous allons annuler tout ce brouillon et recommencer à zéro.",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Oui, recommencer',
                            cancelButtonText: 'Annuler',
                            reverseButtons: true
                        }).then((result) => {
                            if (result.isConfirmed) {
                                sessionStorage.removeItem('sydra_ai_chat');
                                window.location.reload();
                            }
                        });
                    } else {
                        if (confirm("Nous allons annuler tout ce brouillon et recommencer à zéro. Êtes-vous sûr ?")) {
                            sessionStorage.removeItem('sydra_ai_chat');
                            window.location.reload();
                        }
                    }
                });

                // 5. STOPPER l'exécution de la fonction ici pour ne PAS afficher le JSON en texte
                return; 
                
            } catch (e) {
                console.error("Erreur de parsing JSON", e);
            }
        }

        // 6. Si aucun JSON 'complete' n'est détecté, afficher le texte normalement sous forme de bulle de chat
        appendMessage('assistant', aiResponseText);
    }

    function sendMessage() {
        var content = input.value.trim();
        if (!content) return;

        chatHistory.push({ role: 'user', content: content });
        sessionStorage.setItem('sydra_ai_chat', JSON.stringify(chatHistory));
        bubble('user', content);
        input.value = '';
        input.style.height = 'auto';
        input.disabled = true;
        sendBtn.disabled = true;

        bubble('assistant', '', true);

        callAi(chatHistory).then(function(data) {
            removeTyping();
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();

            if (!data || !data.ok) {
                premiumAlert.fire('Erreur', data.message || 'Erreur IA', 'error');
                return;
            }

            var aiResponseText = data.message || '';
            chatHistory.push({ role: 'assistant', content: aiResponseText });
            sessionStorage.setItem('sydra_ai_chat', JSON.stringify(chatHistory));

            processAssistantResponse(aiResponseText);

        }).catch(function(err) {
            removeTyping();
            input.disabled = false;
            sendBtn.disabled = false;
            console.error(err);
            if (typeof premiumAlert !== 'undefined') {
                premiumAlert.fire('Erreur', 'Erreur de connexion avec l\'assistant IA.', 'error');
            }
        });
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (suggestionsContainer && !suggestionsContainer.classList.contains('d-none')) {
            suggestionsContainer.classList.add('d-none');
        }
        var welcomeMsg = document.getElementById('ai-welcome-msg');
        if (welcomeMsg) welcomeMsg.style.display = 'none';

        sendMessage();
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
    });

    // Gestion des suggestions
    suggestionBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            input.value = this.textContent.trim();
            if (suggestionsContainer) {
                suggestionsContainer.classList.add('d-none');
            }
            sendMessage();
        });
    });

    // Restaurer l'historique au chargement
    var storedChat = sessionStorage.getItem('sydra_ai_chat');
    if (storedChat && existingDraftId === 0) {
        try {
            var parsedHistory = JSON.parse(storedChat);
            if (Array.isArray(parsedHistory) && parsedHistory.length > 0) {
                chatHistory = parsedHistory;
                var welcomeMsg = document.getElementById('ai-welcome-msg');
                if (welcomeMsg) welcomeMsg.style.display = 'none';
                if (suggestionsContainer) suggestionsContainer.classList.add('d-none');
                
                chatHistory.forEach(function(msg) {
                    if (msg.role === 'assistant') {
                        processAssistantResponse(msg.content);
                    } else {
                        bubble(msg.role, msg.content);
                    }
                });
            }
        } catch(e) {
            console.error('Erreur parsing chat history', e);
            sessionStorage.removeItem('sydra_ai_chat');
        }
    }
});
</script>
