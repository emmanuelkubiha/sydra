<?php if (!isset($_SESSION['auth_user_id']) || (int) ($_SESSION['auth_user_id'] ?? 0) <= 0): ?>
	<div class="text-center mt-5 mb-4">
		<?php $currentLang = function_exists('current_lang') ? current_lang() : ($lang ?? 'fr'); ?>
		<form action="" method="get" class="d-inline-block text-muted" style="font-size: 0.85rem;">
			<input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page'] ?? 'login', ENT_QUOTES, 'UTF-8'); ?>">
			<i class="fa-solid fa-globe me-1"></i>Langue :
			<select name="lang" class="form-select form-select-sm border-0 bg-transparent text-muted fw-bold d-inline-block w-auto p-0 ms-1" onchange="this.form.submit()" aria-label="Language" style="cursor: pointer; box-shadow: none;">
				<option value="fr" <?= $currentLang === 'fr' ? 'selected' : ''; ?>>Français</option>
				<option value="en" <?= $currentLang === 'en' ? 'selected' : ''; ?>>English</option>
			</select>
		</form>
	</div>
<?php endif; ?>
		</div>
	</main>
</div>
<?php
$aiLogoPath = 'assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png';
$aiCsrfToken = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
$aiJsVersion = @filemtime(__DIR__ . '/../assets/js/ai_chat.js') ?: time();
?>
<?php if (isset($_SESSION['auth_user_id']) && (int) ($_SESSION['auth_user_id'] ?? 0) > 0): ?>
<!-- Conteneur du bouton flottant et de l'infobulle -->
<div class="sydra-ai-widget-container" id="sydra-ai-widget-container">
	<div class="sydra-ai-tooltip" id="sydra-ai-tooltip">
		Besoin d'aide ? Cliquez ici 👋
		<div class="sydra-ai-tooltip-arrow"></div>
	</div>
	<button type="button"
			id="global-ai-widget-btn"
			class="sydra-ai-widget-btn"
			data-bs-toggle="offcanvas"
			data-bs-target="#sydraAiOffcanvas"
			aria-controls="sydraAiOffcanvas"
			aria-label="Ouvrir Assistant IA SyDRA">
		<span class="sydra-ai-widget-icon">
			<i class="fa-solid fa-robot"></i>
		</span>
	</button>
</div>

<div class="offcanvas offcanvas-end sydra-ai-offcanvas" tabindex="-1" id="sydraAiOffcanvas" aria-labelledby="sydraAiOffcanvasLabel">
	<!-- ═══ HEADER PREMIUM (Mission 1) ═══ -->
	<div class="sydra-ai-header">
		<div class="sydra-ai-header-left">
			<div class="sydra-ai-avatar-ring">
				<img src="<?= htmlspecialchars($aiLogoPath, ENT_QUOTES, 'UTF-8'); ?>" alt="SyDRA" class="sydra-ai-header-logo">
			</div>
			<div class="sydra-ai-header-info">
				<strong id="sydraAiOffcanvasLabel">Assistant SyDRA</strong>
				<span class="sydra-ai-status"><span class="sydra-ai-status-dot"></span>En ligne</span>
			</div>
		</div>
		<button type="button" class="sydra-ai-close-btn" data-bs-dismiss="offcanvas" aria-label="Fermer">
			<i class="bi bi-x-lg"></i>
		</button>
	</div>

	<!-- ═══ BODY ═══ -->
	<div class="sydra-ai-body"
		 id="sydra-ai-root"
		 data-csrf="<?= $aiCsrfToken; ?>"
		 data-ai-endpoint="api/ai_handler.php"
		 data-user-role="<?= htmlspecialchars($_SESSION['role_code'] ?? 'ORG_REPORTER', ENT_QUOTES, 'UTF-8'); ?>"
		 data-user-name="<?= htmlspecialchars($_SESSION['user_full_name'] ?? $_SESSION['full_name'] ?? 'Utilisateur', ENT_QUOTES, 'UTF-8'); ?>">
		<div class="sydra-ai-mode-bar">
			<span id="sydra-ai-mode-badge" class="sydra-ai-mode-badge">
				<i class="bi bi-shield-lock-fill"></i> Mode Aide Générale
			</span>
		</div>
		<div id="sydra-ai-chat" class="sydra-ai-chat"></div>
		
		<!-- Mission 4 : Conteneur des Smart Chips (suggestions permanentes) -->
		<div id="sydra-ai-suggestions" class="sydra-ai-suggestions-container"></div>
		
		<form id="sydra-ai-form" class="sydra-ai-form" novalidate>
			<div class="sydra-ai-input-wrapper">
				<textarea
					id="sydra-ai-input"
					class="sydra-ai-textarea"
					rows="1"
					placeholder="Écrivez votre message..."
					required
					aria-label="Message pour l'assistant IA"
				></textarea>
				<button
					type="submit"
					class="sydra-ai-send-btn"
					id="sydra-ai-send"
					aria-label="Envoyer le message"
					title="Envoyer (Entrée)"
				>
					<i class="fa-solid fa-paper-plane"></i>
				</button>
			</div>
			<div class="sydra-ai-form-hint">Entrée pour envoyer · Shift+Entrée pour un saut de ligne</div>
		</form>
	</div>
</div>

<style>
/* ============================================================
   WIDGET IA SYDRA — DESIGN SAAS PREMIUM (SPRINT 4.1)
   Inspiré de l'UI Intercom / ChatGPT
   ============================================================ */

/* --- Bouton flottant --- */
.sydra-ai-widget-btn {
	position: fixed;
	right: 20px;
	bottom: 22px;
	width: 60px;
	height: 60px;
	border-radius: 999px;
	border: 0;
	padding: 0;
	background: linear-gradient(135deg, #005bbb 0%, #3a86ff 100%);
	box-shadow: 0 8px 28px rgba(0, 91, 187, 0.45), 0 0 0 0 rgba(0, 91, 187, 0.30);
	z-index: 1090;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	transition: transform .2s ease, box-shadow .2s ease, opacity .2s ease;
	animation: sydraAiPulse 2.6s ease-in-out infinite;
	cursor: pointer;
}
.sydra-ai-widget-btn.is-hidden {
	opacity: 0;
	pointer-events: none;
	transform: scale(0.7);
}
.sydra-ai-widget-btn:hover,
.sydra-ai-widget-btn:focus-visible {
	transform: translateY(-3px) scale(1.08);
	box-shadow: 0 16px 40px rgba(0, 91, 187, 0.55);
	animation: none;
}
.sydra-ai-widget-icon {
	color: #ffffff;
	font-size: 1.5rem;
	display: inline-flex;
	align-items: center;
	justify-content: center;
}

/* --- Infobulle --- */
.sydra-ai-tooltip {
	position: fixed;
	right: 92px;
	bottom: 36px;
	background: #0f172a;
	color: #ffffff;
	padding: 8px 16px;
	border-radius: 10px;
	font-size: 0.82rem;
	font-weight: 600;
	box-shadow: 0 6px 20px rgba(0,0,0,0.20);
	opacity: 0;
	transform: translateX(12px);
	pointer-events: none;
	transition: opacity 0.3s ease, transform 0.3s ease;
	z-index: 1089;
	white-space: nowrap;
}
.sydra-ai-tooltip.show {
	opacity: 1;
	transform: translateX(0);
	animation: sydraAiBounce 2s infinite ease-in-out;
}
.sydra-ai-tooltip-arrow {
	position: absolute;
	right: -5px;
	top: 50%;
	transform: translateY(-50%);
	border-width: 6px 0 6px 6px;
	border-style: solid;
	border-color: transparent transparent transparent #0f172a;
}

/* ═══ PANEL OFFCANVAS — SAAS PREMIUM ═══ */
.sydra-ai-offcanvas {
	width: min(440px, 96vw) !important;
	border: none !important;
	box-shadow: 0 10px 40px rgba(0,0,0,0.15), -2px 0 12px rgba(0,0,0,0.06) !important;
	border-radius: 16px 0 0 16px !important;
	overflow: hidden;
}

/* ═══ HEADER PREMIUM ═══ */
.sydra-ai-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 14px 18px;
	background: linear-gradient(135deg, #005bbb 0%, #0074e4 60%, #3a86ff 100%);
	color: #ffffff;
	min-height: 64px;
}
.sydra-ai-header-left {
	display: flex;
	align-items: center;
	gap: 12px;
}
.sydra-ai-avatar-ring {
	width: 40px;
	height: 40px;
	border-radius: 50%;
	background: rgba(255,255,255,0.18);
	display: flex;
	align-items: center;
	justify-content: center;
	border: 2px solid rgba(255,255,255,0.30);
}
.sydra-ai-header-logo {
	width: 24px;
	height: 24px;
	object-fit: contain;
	filter: brightness(0) invert(1);
}
.sydra-ai-header-info {
	display: flex;
	flex-direction: column;
	line-height: 1.3;
}
.sydra-ai-header-info strong {
	font-size: 0.95rem;
	font-weight: 700;
	letter-spacing: 0.01em;
}
.sydra-ai-status {
	font-size: 0.72rem;
	opacity: 0.90;
	display: flex;
	align-items: center;
	gap: 5px;
}
.sydra-ai-status-dot {
	width: 7px;
	height: 7px;
	border-radius: 50%;
	background: #34d399;
	display: inline-block;
	box-shadow: 0 0 5px rgba(52, 211, 153, 0.60);
	animation: sydraStatusPulse 2s ease-in-out infinite;
}
.sydra-ai-close-btn {
	background: rgba(255,255,255,0.12);
	border: 1px solid rgba(255,255,255,0.18);
	border-radius: 10px;
	color: #ffffff;
	width: 34px;
	height: 34px;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
	font-size: 0.9rem;
	transition: background .15s ease;
}
.sydra-ai-close-btn:hover {
	background: rgba(255,255,255,0.22);
}

/* ═══ BODY CONTAINER ═══ */
.sydra-ai-body {
	display: flex;
	flex-direction: column;
	flex: 1;
	min-height: 0;
	background: #ffffff;
}

/* ═══ MODE BAR ═══ */
.sydra-ai-mode-bar {
	padding: 8px 16px;
	background: #f8fbff;
	border-bottom: 1px solid #e8edf5;
}
.sydra-ai-mode-badge {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	font-size: 0.72rem;
	font-weight: 600;
	color: #64748b;
	background: #eef2f7;
	padding: 4px 10px;
	border-radius: 6px;
}
.sydra-ai-mode-badge.mode-active {
	color: #166534;
	background: #dcfce7;
}

/* ═══ ZONE DE CHAT ═══ */
.sydra-ai-chat {
	flex: 1;
	overflow-y: auto;
	background: #fafbfd;
	padding: 18px 14px;
	display: flex;
	flex-direction: column;
	gap: 12px;
	scroll-behavior: smooth;
}

/* ═══ BULLES DE CHAT ═══ */
.sydra-ai-bubble {
	max-width: 85%;
	padding: 12px 16px;
	line-height: 1.55;
	font-size: .855rem;
	word-break: break-word;
	animation: sydraFadeIn .22s ease;
}
/* Texte pur = pre-wrap. HTML injecté = normal. */
.sydra-ai-bubble.text-only {
	white-space: pre-wrap;
}

/* Bulle utilisateur */
.sydra-ai-bubble.user {
	align-self: flex-end;
	background: linear-gradient(135deg, #005bbb 0%, #0074e4 100%);
	color: #ffffff;
	border-radius: 18px 18px 4px 18px;
	box-shadow: 0 3px 12px rgba(0, 91, 187, .20);
	white-space: pre-wrap;
}

/* Bulle IA */
.sydra-ai-bubble.assistant {
	align-self: flex-start;
	background: #ffffff;
	color: #1e293b;
	border-radius: 18px 18px 18px 4px;
	box-shadow: 0 1px 6px rgba(0,0,0,.06);
	border: 1px solid #e8edf5;
}

/* Amélioration du rendu HTML de l'IA (Mission 3) */
.sydra-ai-bubble.assistant p {
	margin-bottom: 0.8rem;
	line-height: 1.6;
	color: #333;
}
.sydra-ai-bubble.assistant p:last-child {
	margin-bottom: 0;
}

/* ═══ SMART CHIPS (Mission 2) ═══ */
.sydra-ai-smart-chips {
	display: flex;
	flex-wrap: wrap;
	gap: 8px;
	margin-top: 10px;
}
.sydra-ai-smart-chip {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	padding: 7px 14px;
	background: #ffffff;
	border: 1.5px solid #005BBB;
	color: #005BBB;
	border-radius: 50px;
	font-size: 0.78rem;
	font-weight: 600;
	cursor: pointer;
	text-decoration: none;
	transition: all 0.18s ease;
	box-shadow: 0 1px 4px rgba(0, 91, 187, 0.08);
}
.sydra-ai-smart-chip:hover {
	background: #005BBB;
	color: #ffffff;
	transform: translateY(-1px);
	box-shadow: 0 4px 14px rgba(0, 91, 187, 0.25);
}

/* ═══ SMART CHIPS PERMANENTS (Mission 4) ═══ */
.sydra-ai-suggestions-container {
	display: flex;
	overflow-x: auto;
	padding: 10px 14px 4px;
	background: #ffffff;
	border-top: 1px solid #eef1f6;
	scrollbar-width: none; /* Firefox */
}
.sydra-ai-suggestions-container::-webkit-scrollbar {
	display: none; /* Chrome/Safari */
}
.sydra-ai-suggestion-btn {
	white-space: nowrap;
	font-size: 0.75rem;
	font-weight: 500;
	cursor: pointer;
	transition: all 0.2s ease;
}
.sydra-ai-suggestion-btn:hover {
	background: #e2e8f0 !important;
}

/* ═══ ZONE DE SAISIE — PREMIUM ═══ */
.sydra-ai-form {
	padding: 8px 14px 12px;
	background: #ffffff;
}
.sydra-ai-input-wrapper {
	display: flex;
	align-items: flex-end;
	gap: 8px;
	background: #f4f6f9;
	border: 1.5px solid #e0e5ed;
	border-radius: 24px;
	padding: 6px 6px 6px 18px;
	transition: border-color .18s ease, box-shadow .18s ease;
}
.sydra-ai-input-wrapper:focus-within {
	border-color: #005BBB;
	box-shadow: 0 0 0 3px rgba(0, 91, 187, .10);
	background: #ffffff;
}
.sydra-ai-textarea {
	flex: 1;
	border: none !important;
	outline: none !important;
	box-shadow: none !important;
	background: transparent;
	resize: none;
	font-size: .855rem;
	line-height: 1.5;
	color: #1e293b;
	min-height: 22px;
	max-height: 110px;
	overflow-y: auto;
	padding: 6px 0;
}
.sydra-ai-textarea::placeholder {
	color: #94a3b8;
}
.sydra-ai-textarea:focus {
	outline: none !important;
	box-shadow: none !important;
}
.sydra-ai-form-hint {
	text-align: center;
	font-size: 0.68rem;
	color: #a0aec0;
	margin-top: 6px;
	letter-spacing: 0.01em;
}

/* Bouton circulaire d'envoi */
.sydra-ai-send-btn {
	flex-shrink: 0;
	width: 36px;
	height: 36px;
	border-radius: 50%;
	border: none;
	background: linear-gradient(135deg, #005bbb, #3a86ff);
	color: #ffffff;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	font-size: .85rem;
	transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
	box-shadow: 0 3px 12px rgba(0, 91, 187, .30);
	cursor: pointer;
	padding: 0;
}
.sydra-ai-send-btn:hover:not(:disabled) {
	transform: scale(1.1);
	box-shadow: 0 6px 18px rgba(0, 91, 187, .40);
}
.sydra-ai-send-btn:active:not(:disabled) {
	transform: scale(0.94);
}
.sydra-ai-send-btn:disabled {
	opacity: 0.5;
	cursor: not-allowed;
	box-shadow: none;
	transform: none;
}

/* ═══ INDICATEUR DE FRAPPE (Mission 3) ═══ */
.sydra-ai-typing {
	display: flex;
	align-items: center;
	gap: 5px;
	padding: 14px 18px;
	min-width: 60px;
}
.sydra-ai-typing span {
	width: 8px;
	height: 8px;
	border-radius: 50%;
	background: #94a3b8;
	animation: sydraTypingBounce 1.4s ease-in-out infinite;
}
.sydra-ai-typing span:nth-child(2) { animation-delay: .2s; }
.sydra-ai-typing span:nth-child(3) { animation-delay: .4s; }

/* ═══ ANIMATIONS ═══ */
@keyframes sydraAiPulse {
	0%   { transform: scale(1); box-shadow: 0 8px 28px rgba(0, 91, 187, 0.45), 0 0 0 0 rgba(0, 91, 187, 0.30); }
	50%  { transform: scale(1.05); box-shadow: 0 12px 36px rgba(0, 91, 187, 0.55), 0 0 0 8px rgba(0, 91, 187, 0); }
	100% { transform: scale(1); box-shadow: 0 8px 28px rgba(0, 91, 187, 0.45), 0 0 0 0 rgba(0, 91, 187, 0); }
}
@keyframes sydraAiBounce {
	0%, 100% { transform: translateX(0); }
	50% { transform: translateX(-5px); }
}
@keyframes sydraTypingBounce {
	0%, 80%, 100% { transform: translateY(0); opacity: .4; }
	40%           { transform: translateY(-6px); opacity: 1; }
}
@keyframes sydraFadeIn {
	from { opacity: 0; transform: translateY(8px); }
	to   { opacity: 1; transform: translateY(0); }
}
@keyframes sydraStatusPulse {
	0%, 100% { opacity: 1; }
	50% { opacity: 0.5; }
}
</style>
<?php endif; ?>
<?php $jsVersion = @filemtime(__DIR__ . '/../assets/js/app.js') ?: time(); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.net@2.0.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script src="assets/js/app.js?v=<?= (int) $jsVersion; ?>"></script>
<?php if (isset($_SESSION['auth_user_id']) && (int) ($_SESSION['auth_user_id'] ?? 0) > 0): ?>
<script src="assets/js/ai_chat.js?v=<?= (int) $aiJsVersion; ?>"></script>
<?php endif; ?>
</body>
</html>
