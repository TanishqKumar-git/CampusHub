    </main>
</div>

<footer class="footer">
    <p>CampusHub &copy; <?= date('Y') ?> &middot; A student community &amp; resource-sharing platform</p>
</footer>

<script>
    // Data the JavaScript file needs
    window.CAMPUSHUB = {
        baseUrl: "<?= BASE_URL ?>",
        csrf: "<?= e(csrf_token()) ?>",
        loggedIn: <?= is_logged_in() ? 'true' : 'false' ?>
    };
</script>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
