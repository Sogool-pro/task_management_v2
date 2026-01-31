<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id']) && $_SESSION['role'] == "employee") {
    include "DB_connection.php";
    include "app/Model/Task.php";
    include "app/Model/User.php";
    include "app/Model/Subtask.php";
    
    if (!isset($_GET['id'])) {
    	 header("Location: tasks.php");
    	 exit();
    }
    $id = $_GET['id'];
    $task = get_task_by_id($pdo, $id);

    if ($task == 0) {
    	 header("Location: tasks.php");
    	 exit();
    }
    
    // Check if the current user is assigned to this task
    $assignees = get_task_assignees($pdo, $id);
    $is_assigned = false;
    $is_leader = false;
    if ($assignees != 0) {
        foreach ($assignees as $assignee) {
            if ($assignee['user_id'] == $_SESSION['id']) {
                $is_assigned = true;
                if ($assignee['role'] == 'leader') {
                    $is_leader = true;
                }
                break;
            }
        }
    }
    
    if (!$is_assigned) {
        header("Location: my_task.php");
        exit();
    }
    
   $users = get_all_users($pdo);
 ?>
<!DOCTYPE html>
<html>
<head>
	<title>Edit Task</title>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="css/style.css">

</head>
<body>
	<input type="checkbox" id="checkbox">
	<?php include "inc/header.php" ?>
	<div class="body">
		<?php include "inc/nav.php" ?>
		<section class="section-1">
			<h4 class="title">Submit Task <a href="my_task.php">Tasks</a></h4>
			<form class="form-1"
			      method="POST"
			      enctype="multipart/form-data"
			      action="app/update-task-employee.php">
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
					<lable></lable>
					<p><b>Title: </b><?=$task['title']?></p>
				</div>
            <div class="input-holder">
					<lable></lable>
					<p><b>Description: </b><?=$task['description']?></p>
				</div><br>
            <?php if (!empty($task['template_file'])) { ?>
            <div class="input-holder">
					<lable><b>Template/Guide File:</b></lable>
					<p>
						<a href="<?=$task['template_file']?>" target="_blank" style="color: #007bff; text-decoration: none;">
							<i class="fa fa-download"></i> Download Template/Guide
						</a>
					</p>
				</div>
            <?php } ?>
            <div class="input-holder">
					<lable><b>Status:</b></lable>
					<p>
                        <?=$task['status']?>
                        <?php if ($task['status'] == 'revise') { ?>
                            <span style="color: red; font-weight: bold; margin-left: 10px;">(Needs Revision)</span>
                        <?php } ?>
                    </p>
				</div>
            <?php if (!empty($task['review_comment'])) { ?>
            <div class="input-holder">
					<lable><b>Admin Feedback:</b></lable>
					<p style="<?=$task['status'] == 'revise' ? 'background: #ffe6e6; padding: 10px; border-radius: 5px; color: #721c24;' : ''?>">
                        <?=$task['review_comment']?>
                    </p>
				</div>
            <?php } ?>
            
            <?php if ($task['status'] !== 'completed' && $is_leader) { ?>
            <div class="input-holder">
					<lable>Submit File <?=$task['status'] == 'revise' ? '(Re-submission)' : ''?></lable>
					<input type="file" name="submission_file" class="input-1" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip">
				</div>
				<input type="text" name="id" value="<?=$task['id']?>" hidden>

				<button class="edit-btn">Submit</button>
            <?php } elseif ($task['status'] !== 'completed' && !$is_leader) { ?>
            <div class="input-holder">
                <p><b>Note:</b> Only the task leader can submit this task.</p>
            </div>
            <?php } ?>
			</form>
			
			<?php 
            $subtasks = get_subtasks_by_task($pdo, $id);
            ?>
            
            <?php if ($is_leader) { ?>
            <div class="separator-line" style="margin: 30px 0; border-bottom: 2px solid #eee;"></div>
            
            <h4 class="title">Manage Subtasks</h4>
            
            <!-- Create Subtask Form -->
            <form class="form-1" method="POST" action="app/add-subtask.php" style="margin-bottom: 30px; background: #f9f9f9; padding: 20px; border-radius: 5px;">
                <h5>Create New Subtask</h5>
                <input type="hidden" name="task_id" value="<?=$id?>">
                <input type="hidden" name="parent_id" value="<?=$id?>">
                
                <div class="input-holder">
                    <label>Assign Member</label>
                    <select name="member_id" class="input-1" required>
                        <option value="">Select Member</option>
                        <?php 
                        if ($assignees != 0) {
                            foreach ($assignees as $assignee) {
                                // Exclude self if desired, but leader might assign to self too? Assuming leader assigns to members.
                                echo '<option value="'.$assignee['user_id'].'">'.$assignee['full_name'].' ('.$assignee['role'].')</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                
                <div class="input-holder">
                    <label>Description</label>
                    <textarea name="description" class="input-1" rows="2" required></textarea>
                </div>
                
                <div class="input-holder">
                    <label>Due Date</label>
                    <input type="date" name="due_date" class="input-1" required>
                </div>
                
                <button class="edit-btn">Add Subtask</button>
            </form>
            
            <!-- List Subtasks -->
            <?php if (!empty($subtasks)) { ?>
            <table class="main-table">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Description</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Submission</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subtasks as $sub) { ?>
                    <tr>
                        <td><?=$sub['member_name']?></td>
                        <td><?=$sub['description']?></td>
                        <td><?=$sub['due_date']?></td>
                        <td><?=$sub['status']?></td>
                        <td>
                            <?php if ($sub['submission_file']) { ?>
                                <a href="<?=$sub['submission_file']?>" target="_blank">View File</a>
                            <?php } else { echo "None"; } ?>
                        </td>
                        <td>
                            <?php if ($sub['status'] == 'submitted') { ?>
                                <button onclick="openReviewModal(<?=$sub['id']?>)" class="edit-btn" style="padding: 5px 10px; font-size: 12px;">Review</button>
                            <?php } else { echo "-"; } ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            
            <!-- Review Modal -->
            <div id="reviewModal" class="modal" style="display:none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4);">
                <div class="modal-content" style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 50%; border-radius: 8px;">
                    <span class="close" onclick="closeReviewModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                    <h3>Review Submission</h3>
                    <form method="POST" action="app/review-subtask.php">
                        <input type="hidden" name="subtask_id" id="reviewSubtaskId">
                        <input type="hidden" name="parent_id" value="<?=$id?>">
                        
                        <div class="input-holder">
                            <label>Feedback / Instruction</label>
                            <textarea name="feedback" class="input-1" required></textarea>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <button name="action" value="accept" class="edit-btn" style="background: #28a745;">Accept</button>
                            <button name="action" value="revise" class="edit-btn" style="background: #dc3545;">Request Revision</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
                function openReviewModal(id) {
                    document.getElementById('reviewSubtaskId').value = id;
                    document.getElementById('reviewModal').style.display = "block";
                }
                function closeReviewModal() {
                    document.getElementById('reviewModal').style.display = "none";
                }
                // Close modal if clicked outside
                window.onclick = function(event) {
                    if (event.target == document.getElementById('reviewModal')) {
                        closeReviewModal();
                    }
                }
            </script>
            <?php } ?>
            
            <?php } ?>
            
            <?php if (!$is_leader) { 
                $my_subtasks = [];
                // Filter subtasks for this member
                if (!empty($subtasks)) {
                    foreach ($subtasks as $sub) {
                        if ($sub['member_id'] == $_SESSION['id']) {
                            $my_subtasks[] = $sub;
                        }
                    }
                }
            ?>
            
            <?php if (!empty($my_subtasks)) { ?>
            <div class="separator-line" style="margin: 30px 0; border-bottom: 2px solid #eee;"></div>
            <h4 class="title">My Assignments (Subtasks)</h4>
            
            <table class="main-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Submission</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_subtasks as $sub) { ?>
                    <tr>
                        <td><?=$sub['description']?></td>
                        <td><?=$sub['due_date']?></td>
                        <td><?=$sub['status']?></td>
                        <td>
                            <?php if ($sub['submission_file']) { ?>
                                <a href="<?=$sub['submission_file']?>" target="_blank">View File</a>
                            <?php } else { echo "None"; } ?>
                        </td>
                        <td>
                            <a href="submit-subtask.php?id=<?=$sub['id']?>" class="edit-btn" style="padding: 5px 10px; font-size: 12px;">Submit/View</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            <?php } } ?>
			
		</section>
	</div>

<script type="text/javascript">
	var active = document.querySelector("#navList li:nth-child(2)");
	active.classList.add("active");
</script>
</body>
</html>
<?php }else{ 
   $em = "First login";
   header("Location: login.php?error=$em");
   exit();
}
 ?>