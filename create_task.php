<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id']) && $_SESSION['role'] == "admin") {
    include "DB_connection.php";
    include "app/Model/User.php";

    $users = get_all_users($pdo);

 ?>
<!DOCTYPE html>
<html>
<head>
	<title>Create Task</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="css/style.css">

</head>
<body>
	<input type="checkbox" id="checkbox">
	<?php include "inc/header.php" ?>
	<div class="body">
		<?php include "inc/nav.php" ?>
		<section class="section-1">
			<h4 class="title">Create Task </h4>
		   <form class="form-1"
			      method="POST"
			      enctype="multipart/form-data"
			      action="app/add-task.php">
			      <?php if (isset($_GET['error'])) {?>
      	  	<div class="danger" role="alert">
			  <?php echo stripcslashes($_GET['error']); ?>
			</div>
      	  <?php } ?>

      	  <?php if (isset($_GET['success'])) {?>
      	  	<div class="success" role="alert">
			  <?php echo stripcslashes($_GET['success']); ?>
			</div>
      	  <?php } ?>
				<div class="input-holder">
					<lable>Title</lable>
					<input type="text" name="title" class="input-1" placeholder="Title"><br>
				</div>
				<div class="input-holder">
					<lable>Description</lable>
					<textarea type="text" name="description" class="input-1" placeholder="Description"></textarea><br>
				</div>
				<div class="input-holder">
					<lable>Due Date</lable>
					<input type="date" name="due_date" class="input-1" placeholder="Due Date"><br>
				</div>
				<div class="input-holder">
					<lable>Leader</lable>
					<select name="leader_id" class="input-1" required>
						<option value="0">Select leader</option>
						<?php if ($users !=0) { 
							foreach ($users as $user) {
						?>
                  <option value="<?=$user['id']?>"><?=$user['full_name']?></option>
						<?php } } ?>
					</select><br>
				</div>
				<div class="input-holder">
					<lable>Members</lable>
					<div style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px;">
						<select id="memberSelect" class="input-1" style="flex: 1;">
							<option value="0">Select member</option>
							<?php if ($users !=0) { 
								foreach ($users as $user) {
							?>
								<option value="<?=$user['id']?>" data-name="<?=htmlspecialchars($user['full_name'])?>"><?=$user['full_name']?></option>
							<?php } } ?>
						</select>
						<button type="button" id="addMemberBtn" class="edit-btn" style="padding: 8px 20px; white-space: nowrap;">
							<i class="fa fa-plus"></i> Add
						</button>
					</div>
					<div id="membersList" style="min-height: 40px; margin-top: 10px;">
						<!-- Added members will appear here -->
					</div>
					<small style="color: #666; font-size: 12px;">Click the plus button to add members (optional)</small>
					<!-- Hidden inputs for form submission -->
					<div id="memberInputs"></div>
				</div>
				<div class="input-holder">
					<lable>Template/Guide File (Optional)</lable>
					<input type="file" name="template_file" class="input-1" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip,.txt">
					<small style="color: #666; font-size: 12px;">Upload templates, guides, or reference materials for the team</small>
				</div>
				<button class="edit-btn">Create Task</button>
			</form>
			
		</section>
	</div>

<script type="text/javascript">
	var active = document.querySelector("#navList li:nth-child(3)");
	active.classList.add("active");
	
	// Members management
	var selectedMembers = {}; // Object to store selected members {id: name}
	var selectedLeaderId = null;
	
	// Update selected leader when leader dropdown changes
	document.querySelector('select[name="leader_id"]').addEventListener('change', function() {
		selectedLeaderId = this.value;
		updateMemberDropdown();
	});
	
	// Update member dropdown to exclude leader and already selected members
	function updateMemberDropdown() {
		var memberSelect = document.getElementById('memberSelect');
		var currentValue = memberSelect.value;
		var options = memberSelect.querySelectorAll('option');
		
		options.forEach(function(option) {
			if (option.value == '0') return; // Keep the "Select member" option
			
			var memberId = option.value;
			var isSelected = selectedMembers.hasOwnProperty(memberId);
			var isLeader = memberId == selectedLeaderId;
			
			option.style.display = (isSelected || isLeader) ? 'none' : '';
		});
	}
	
	// Add member button click handler
	document.getElementById('addMemberBtn').addEventListener('click', function() {
		var memberSelect = document.getElementById('memberSelect');
		var selectedOption = memberSelect.options[memberSelect.selectedIndex];
		
		if (selectedOption.value == '0') {
			alert('Please select a member first');
			return;
		}
		
		var memberId = selectedOption.value;
		var memberName = selectedOption.getAttribute('data-name');
		
		// Add to selected members
		selectedMembers[memberId] = memberName;
		
		// Add hidden input for form submission
		var hiddenInput = document.createElement('input');
		hiddenInput.type = 'hidden';
		hiddenInput.name = 'member_ids[]';
		hiddenInput.value = memberId;
		hiddenInput.id = 'member_' + memberId;
		document.getElementById('memberInputs').appendChild(hiddenInput);
		
		// Add to members list display
		var memberItem = document.createElement('div');
		memberItem.className = 'member-item';
		memberItem.id = 'member_item_' + memberId;
		memberItem.style.cssText = 'display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f0f0f0; border-radius: 4px; margin-bottom: 8px;';
		memberItem.innerHTML = '<span>' + memberName + '</span><button type="button" class="remove-member-btn" data-id="' + memberId + '" style="background: #ef4444; color: white; border: none; border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 12px;"><i class="fa fa-times"></i> Remove</button>';
		document.getElementById('membersList').appendChild(memberItem);
		
		// Reset dropdown
		memberSelect.value = '0';
		updateMemberDropdown();
	});
	
	// Remove member button click handler (event delegation)
	document.getElementById('membersList').addEventListener('click', function(e) {
		if (e.target.closest('.remove-member-btn')) {
			var btn = e.target.closest('.remove-member-btn');
			var memberId = btn.getAttribute('data-id');
			
			// Remove from selected members
			delete selectedMembers[memberId];
			
			// Remove hidden input
			var hiddenInput = document.getElementById('member_' + memberId);
			if (hiddenInput) hiddenInput.remove();
			
			// Remove from display
			var memberItem = document.getElementById('member_item_' + memberId);
			if (memberItem) memberItem.remove();
			
			updateMemberDropdown();
		}
	});
	
	// Initial update of member dropdown
	updateMemberDropdown();
</script>
</body>
</html>
<?php }else{ 
   $em = "First login";
   header("Location: login.php?error=$em");
   exit();
}
 ?>