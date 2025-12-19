</div>

<?php
// Determine path prefix based on current directory (same logic as header.php)
$path_prefix = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) ? '../' : '';
?>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo $path_prefix; ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $path_prefix; ?>assets/js/script.js"></script>

</body>
</html>
