		</div>
	</main>
</div>
<?php
$aiLogoPath = 'assets/img/sydra-logo/BLEU-PRIMARY-SYDRA-LOGO.png';
$aiCsrfToken = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
$aiJsVersion = @filemtime(__DIR__ . '/../assets/js/ai_chat.js') ?: time();
?>
<?php if (isset($_SESSION['auth_user_id']) && (int) ($_SESSION['auth_user_id'] ?? 0) > 0): ?>
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

<div class="offcanvas offcanvas-end sydra-ai-offcanvas" tabindex="-1" id="sydraAiOffcanvas" aria-labelledby="sydraAiOffcanvasLabel">
	<div class="offcanvas-header border-bottom">
		<h2 class="offcanvas-title h6 mb-0 d-flex align-items-center gap-2" id="sydraAiOffcanvasLabel">
			<img src="<?= htmlspecialchars($aiLogoPath, ENT_QUOTES, 'UTF-8'); ?>" alt="SyDRA" class="sydra-ai-header-logo">
			Assistant IA Sécurisé
		</h2>
		<button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fermer"></button>
	</div>
	<div class="offcanvas-body d-flex flex-column p-0"
		 id="sydra-ai-root"
		 data-csrf="<?= $aiCsrfToken; ?>"
		 data-ai-endpoint="api/ai_handler.php">
		<div class="px-3 pt-3 pb-2 border-bottom bg-light-subtle">
			<span id="sydra-ai-mode-badge" class="badge text-bg-secondary">Mode Aide Générale (Aucune donnée partagée)</span>
		</div>
		<div id="sydra-ai-chat" class="sydra-ai-chat"></div>
		<form id="sydra-ai-form" class="sydra-ai-form js-ai-chat-form" novalidate>
			<textarea id="sydra-ai-input" class="form-control" rows="2" placeholder="Posez votre question..." required></textarea>
			<button type="submit" class="btn btn-primary" id="sydra-ai-send" aria-label="Envoyer">
				<i class="fa-solid fa-paper-plane"></i>
			</button>
		</form>
	</div>
</div>

<style>
.sydra-ai-widget-btn {
	position: fixed;
	right: 18px;
	bottom: 20px;
	width: 58px;
	height: 58px;
	border-radius: 999px;
	border: 0;
	padding: 0;
	background: linear-gradient(135deg, #005bbb 0%, #3a86ff 100%);
	box-shadow: 0 10px 24px rgba(0, 91, 187, 0.38);
	z-index: 1090;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
	animation: sydraAiPulse 2.2s ease-in-out infinite;
}

.sydra-ai-widget-btn.is-hidden {
	opacity: 0;
	pointer-events: none;
	transform: scale(0.8);
}

.sydra-ai-widget-btn:hover,
.sydra-ai-widget-btn:focus {
	transform: translateY(-3px) scale(1.05);
	box-shadow: 0 18px 34px rgba(0, 91, 187, 0.46);
	animation: none;
}

.sydra-ai-widget-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	color: #ffffff;
	font-size: 1.45rem;
}

.sydra-ai-offcanvas {
	width: min(460px, 96vw);
}

.sydra-ai-header-logo {
	width: 24px;
	height: 24px;
	object-fit: contain;
}

.sydra-ai-chat {
	flex: 1;
	overflow-y: auto;
	background: #f8fafc;
	padding: 12px;
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.sydra-ai-bubble {
	max-width: 88%;
	border-radius: 14px;
	padding: 10px 12px;
	line-height: 1.42;
	white-space: pre-wrap;
}

.sydra-ai-bubble.user {
	align-self: flex-end;
	background: #005bbb;
	color: #ffffff;
	border-bottom-right-radius: 8px;
}

.sydra-ai-bubble.assistant {
	align-self: flex-start;
	background: #e2e8f0;
	color: #0f172a;
	border-bottom-left-radius: 8px;
}

.sydra-ai-form {
	display: flex;
	gap: 8px;
	padding: 10px;
	background: #ffffff;
	border-top: 1px solid #e2e8f0;
}

.sydra-ai-form textarea {
	resize: none;
}

@keyframes sydraAiPulse {
	0% { transform: scale(1); }
	50% { transform: scale(1.05); box-shadow: 0 14px 32px rgba(0, 91, 187, 0.52); }
	100% { transform: scale(1); }
}

/* Indicateur de frappe "..." */
.sydra-ai-typing {
	display: flex;
	align-items: center;
	gap: 5px;
	padding: 10px 14px;
	min-width: 52px;
}

.sydra-ai-typing span {
	width: 7px;
	height: 7px;
	border-radius: 50%;
	background: #94a3b8;
	animation: sydraTypingDot 1.2s ease-in-out infinite;
}

.sydra-ai-typing span:nth-child(2) { animation-delay: .2s; }
.sydra-ai-typing span:nth-child(3) { animation-delay: .4s; }

@keyframes sydraTypingDot {
	0%, 80%, 100% { transform: scale(0.7); opacity: .5; }
	40% { transform: scale(1.1); opacity: 1; }
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
