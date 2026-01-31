<!-- Error Modal -->
<?php if (isset($_GET['error'])) {?>
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="errorModalLabel">
          <i class="bi bi-exclamation-triangle-fill me-2"></i>Error
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php echo htmlspecialchars(stripcslashes($_GET['error'])); ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php } ?>

<!-- Success Modal -->
<?php if (isset($_GET['success'])) {?>
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="successModalLabel">
          <i class="bi bi-check-circle-fill me-2"></i>Success
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php echo htmlspecialchars(stripcslashes($_GET['success'])); ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php } ?>

<!-- Bootstrap Icons (if not already included) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<!-- Auto-show modal script -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    <?php if (isset($_GET['error'])) {?>
      var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
      errorModal.show();
    <?php } ?>
    
    <?php if (isset($_GET['success'])) {?>
      var successModal = new bootstrap.Modal(document.getElementById('successModal'));
      successModal.show();
    <?php } ?>
  });
</script>

