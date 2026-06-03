</div>
<?php $jsVersion = @filemtime(__DIR__ . '/../assets/js/app.js') ?: time(); ?>
<script src="assets/js/app.js?v=<?= (int) $jsVersion; ?>"></script>
</body>
</html>
