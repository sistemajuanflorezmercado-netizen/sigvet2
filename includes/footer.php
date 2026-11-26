    </div><!-- .page-content -->
</div><!-- #main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-dismiss alerts after 4s
document.querySelectorAll('.alert').forEach(function(el) {
    setTimeout(function() {
        var bsAlert = bootstrap.Alert.getOrCreateInstance(el);
        bsAlert.close();
    }, 4000);
});
// Confirm delete
document.querySelectorAll('[data-confirm]').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm || '¿Está seguro?')) e.preventDefault();
    });
});
</script>
</body>
</html>
