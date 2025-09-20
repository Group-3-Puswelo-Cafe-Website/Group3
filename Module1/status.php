<?php if (isset($_GET['status'])): ?>
<script>
  <?php if ($_GET['status'] === 'success'): ?>
    alert("Item saved successfully!");
  <?php elseif ($_GET['status'] === 'error'): ?>
    alert("Failed to save item. Please try again.");
  <?php endif; ?>
</script>
<?php endif; ?>