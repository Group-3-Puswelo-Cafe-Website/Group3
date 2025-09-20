<?php if (isset($_GET['status'])): ?>
<script>
  <?php if ($_GET['status'] === 'success'): ?>
    alert("Item saved successfully!");
  <?php elseif ($_GET['status'] === 'error'): ?>
    alert("Failed to save item. Please try again.");
  <?php endif; ?>
</script>
<?php endif; ?>


<!-- Status for transfer actions -->
<?php if (isset($_GET['status'])): ?>
<script>
  <?php if ($_GET['status'] === 'transfer_success'): ?>
    alert("Transfer completed successfully.");
  <?php elseif ($_GET['status'] === 'transfer_error'): ?>
    alert("Transfer failed. Please check quantity or try again.");
  <?php elseif ($_GET['status'] === 'error_same_warehouse'): ?>
    alert("Cannot transfer to the same warehouse.");
  <?php endif; ?>
</script>
<?php endif; ?>