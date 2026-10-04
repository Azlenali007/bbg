    </main>
</div>

<?php if ($flashSuccess = flash('success')): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        ApexToast.success(<?= json_encode($flashSuccess) ?>);
    });
</script>
<?php endif; ?>

<?php if ($flashError = flash('error')): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        ApexToast.error(<?= json_encode($flashError) ?>);
    });
</script>
<?php endif; ?>

</body>
</html>
