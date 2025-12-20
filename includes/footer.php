</div>

<?php
// Determine path prefix based on current directory (same logic as header.php)
$path_prefix = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : '';
?>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo $path_prefix; ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $path_prefix; ?>assets/js/script.js"></script>

<script>
// Improve scroll performance: prevent mousewheel from changing date/number inputs
// (common cause of "scroll lamban" when cursor is over an input)
document.addEventListener('wheel', function(e) {
	const target = e.target;
	if (!target || target.tagName !== 'INPUT') return;

	const type = (target.getAttribute('type') || '').toLowerCase();
	if (type !== 'date' && type !== 'number') return;

	// If the wheel is over an input, browsers may consume the scroll to change the value.
	// We disable that behavior and keep the page scrolling.
	e.preventDefault();
	try { target.blur(); } catch (err) {}
	window.scrollBy({ top: e.deltaY, left: 0, behavior: 'auto' });
}, { passive: false });

// Make date inputs easier to use: clicking anywhere on the input opens the date picker
// (supported by Chromium via HTMLInputElement.showPicker).
document.addEventListener('click', function(e) {
	const input = e.target && e.target.closest ? e.target.closest('input[type="date"]') : null;
	if (!input) return;
	if (input.disabled || input.readOnly) return;
	if (typeof input.showPicker === 'function') {
		try { input.showPicker(); } catch (err) {}
	}
}, true);

// Auto-mark notifications as read when dropdown opens
(function() {
	const notifDropdown = document.getElementById('notifBellDropdown');
	if (!notifDropdown) return;

	let marked = false;
	notifDropdown.addEventListener('show.bs.dropdown', function() {
		if (marked) return;
		marked = true;

		// Remove badge immediately for instant feedback
		const badge = document.getElementById('notifBadge');
		if (badge) badge.remove();

		// Mark all unread as read via AJAX
		fetch('<?php echo $path_prefix; ?>process/mark_notifications_read.php', {
			method: 'POST',
			credentials: 'same-origin'
		}).catch(function(err) {
			console.warn('Failed to mark notifications as read:', err);
		});
	});
})();
</script>

<footer class="mt-5 py-4 border-top" style="width:100%;">
	<div class="container text-center small text-muted">
		&copy; <?php echo date('Y'); ?> RMS - Rekomendasi Makanan Sehat
	</div>
</footer>

</body>
</html>
