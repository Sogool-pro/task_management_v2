<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['id']) && $_SESSION['role'] == "admin") {
    include "DB_connection.php";
    include "app/Model/Task.php";
    include "app/Model/User.php";
    include "app/Model/Subtask.php"; // Need this for getting subtasks
    
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
    
    $subtasks = get_subtasks_by_task($pdo, $id);
    $users = get_all_users($pdo);
    
    // Check if task is submitted (status=completed but maybe we check review_comment or if it's new submission)
    // Actually, per previous step, Leader submits -> status='completed', submission_note set.
    // If status='completed' and review_comment is NULL (or rated=undef), it's "Awaiting Review".
    // If rated > 0 or has review_comment, it's "Reviewed".
    
    $isSubmitted = ($task['status'] == 'completed' && !empty($task['submission_note']));
    $isReviewed = (!empty($task['reviewed_by'])); 
    
    // Refine Logic: "Submitted for Admin Review" Banner should show if:
    // 1. Task is Completed.
    // 2. Admin hasn't reviewed/rated it yet (or we want to allow re-review).
    // Let's assume if it is 'completed' and 'rating' is 0 or NULL, it is awaiting rating.
    
    $isAwaitingReview = ($task['status'] == 'completed' && ($task['rating'] == 0 || $task['rating'] == NULL));

 ?>
<!DOCTYPE html>
<html>
<head>
	<title>Review Task | TaskFlow</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/task_redesign.css"> <!-- Reusing styles -->
    <style>
        /* Extra styles for Admin Review specific elements */
        .admin-review-section {
            background: #EFF6FF; /* Blue-50 */
            border: 1px solid #BFDBFE; /* Blue-200 */
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .admin-review-header {
            display: flex;
            gap: 12px;
            margin-bottom: 8px;
        }
        
        .admin-review-icon {
            color: #2563EB; /* Blue-600 */
        }
        
        .admin-review-title {
            font-weight: 600;
            color: #1E40AF; /* Blue-800 */
            font-size: 15px;
        }
        
        .admin-review-text {
            font-size: 14px;
            color: #1E3A8A; /* Blue-900 */
            margin-left: 28px; /* Indent to align with text start */
            line-height: 1.5;
        }

        .awaiting-review-section {
            background: #FAF5FF; /* Purple-50 */
            border: 1px solid #E9D5FF; /* Purple-200 */
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .awaiting-review-title {
            color: #6B21A8; /* Purple-800 */
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .leader-notes-box {
            background: #fff;
            border: 1px solid #F3E8FF;
            border-radius: 6px;
            padding: 12px;
            font-size: 14px;
            color: #4B5563;
        }

        .rating-input i {
            font-size: 24px;
            color: #D1D5DB;
            cursor: pointer;
            transition: color 0.2s;
        }
        
        .rating-input i.active {
            color: #F59E0B;
        }
    </style>
</head>
<body>
    
    <!-- Sidebar -->
    <?php include "inc/new_sidebar.php"; ?>

     <!-- Main Content -->
    <div class="dash-main">
        
         <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <a href="tasks.php" style="color: #6B7280; font-size: 18px;"><i class="fa fa-arrow-left"></i></a>
                <h2 style="font-size: 24px; font-weight: 700; color: var(--text-dark); margin: 0;">Task Details</h2>
                <span class="badge-v2 <?=$task['status']?>"><?=str_replace('_',' ', $task['status'])?></span>
                <?php if($task['status'] == 'completed' && $task['rating'] > 0) { ?>
                     <span class="subtask-rating"><i class="fa fa-star"></i> <?=$task['rating']?>/5</span>
                <?php } ?>
            </div>
        </div>

        <?php if (isset($_GET['success'])) {?>
            <div style="background: #D1FAE5; color: #065F46; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
              <?= stripcslashes($_GET['success']); ?>
            </div>
        <?php } ?>

        <div style="background: white; border-radius: 12px; border: 1px solid #E5E7EB; padding: 24px; max-width: 900px;">
            
            <!-- Task Header Info -->
            <div style="margin-bottom: 24px;">
                <h1 style="font-size: 20px; font-weight: 600; margin-bottom: 8px;"><?=$task['title']?></h1>
                <div style="color: #6B7280; font-size: 14px; line-height: 1.6;">
                    <?= nl2br(htmlspecialchars($task['description'])) ?>
                </div>
                <div style="margin-top: 16px; display: flex; gap: 24px; font-size: 13px; color: #6B7280;">
                     <div>
                        <i class="fa fa-users" style="width: 16px;"></i> 
                        Team: 
                        <?php 
                            $assignees = get_task_assignees($pdo, $task['id']);
                            if($assignees != 0){
                                $names = array_map(function($a){ return $a['full_name']; }, $assignees);
                                echo implode(", ", $names);
                            } else {
                                echo "Unknown User";
                            }
                        ?>
                    </div>
                    <div>
                        <i class="fa fa-calendar" style="width: 16px;"></i> 
                        Due: <?= empty($task['due_date']) ? 'No Date' : date("F j, Y", strtotime($task['due_date'])) ?>
                    </div>
                </div>
            </div>

            <!-- Admin Review Section (If Submitted) -->
            <?php if (!empty($task['submission_note'])) { ?>
                <div class="admin-review-section">
                    <div class="admin-review-header">
                        <i class="fa fa-paper-plane admin-review-icon"></i>
                        <span class="admin-review-title">Submitted for Admin Review</span>
                    </div>
                    <div class="admin-review-text">
                        <?=$task['submission_note']?>
                        <div style="margin-top: 6px; font-size: 12px; color: #60A5FA;">
                            Submitted: <?= $task['reviewed_at'] ? date("F j, Y, g:i A", strtotime($task['reviewed_at'])) : 'Recently' ?> 
                            <!-- Note: reviewed_at is updated on submission in submit-task-review.php -->
                        </div>
                    </div>
                </div>
            <?php } ?>
            
            <?php if ($isAwaitingReview) { ?>
                <div class="awaiting-review-section">
                    <div class="awaiting-review-title">
                        <i class="fa fa-exclamation-circle"></i> Awaiting Admin Review
                    </div>
                    <div class="leader-notes-box">
                        <strong>Leader's Notes:</strong><br>
                        <?= !empty($task['submission_note']) ? htmlspecialchars($task['submission_note']) : "No notes provided." ?>
                    </div>
                </div>
            <?php } ?>

            <!-- Subtasks Accordion/List -->
            <div class="subtasks-section">
                 <div class="subtasks-header" onclick="$('#subtaskList').slideToggle();" style="cursor: pointer;">
                    <button class="btn-v2 btn-white" style="width: 100%; justify-content: space-between;">
                        <span><i class="fa fa-chevron-down"></i> View Subtasks (<?= !empty($subtasks) ? count($subtasks) : 0 ?>)</span>
                    </button>
                </div>
                
                <div id="subtaskList" style="<?= $isAwaitingReview ? 'display: none;' : 'display: block;' ?>">
                     <?php if (!empty($subtasks)) { 
                            foreach($subtasks as $sub) { 
                                $subStatusClass = "pending";
                                if ($sub['status'] == 'in_progress') $subStatusClass = "in_progress";
                                if ($sub['status'] == 'completed') $subStatusClass = "completed";
                                if ($sub['status'] == 'submitted') $subStatusClass = "submitted";
                                if ($sub['status'] == 'revise') $subStatusClass = "revision_needed";
                                if ($sub['status'] == 'rejected') $subStatusClass = "rejected";
                        ?>
                        <div class="subtask-card" style="background: #F9FAFB;">
                            <div class="subtask-header">
                                <div>
                                    <div class="subtask-title-text"><?= htmlspecialchars(mb_strimwidth($sub['description'], 0, 50, "...")) ?></div>
                                    <div class="subtask-meta">
                                        <i class="fa fa-user"></i> <?= htmlspecialchars($sub['member_name']) ?>
                                    </div>
                                </div>
                                <span class="badge-v2 <?=$subStatusClass?>"><?= str_replace('_',' ', $sub['status']) ?></span>
                            </div>
                            <!-- Show Submission Link if exists -->
                             <?php if(!empty($sub['submission_file'])) { ?>
                                <div style="font-size: 13px; margin-top: 5px;">
                                    <a href="<?=$sub['submission_file']?>" target="_blank" style="color: #4F46E5;">View File</a>
                                    <?php if(!empty($sub['submission_note'])) echo ' &bull; "'.htmlspecialchars($sub['submission_note']).'"'; ?>
                                </div>
                             <?php } ?>
                        </div>
                        <?php } } else { ?>
                            <div style="padding: 10px; color: #9CA3AF;">No subtasks.</div>
                        <?php } ?>
                </div>
            </div>

            <!-- Action Buttons for Admin -->
            <?php if ($isAwaitingReview || ($task['status'] == 'completed')) { ?>
                <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 30px; border-top: 1px solid #E5E7EB; padding-top: 20px;">
                     <button class="btn-v2 btn-yellow" onclick="openRevisionModal()">
                        <i class="fa fa-refresh"></i> Request Revision
                    </button>
                    <button class="btn-v2 btn-green" onclick="openAcceptModal()">
                        <i class="fa fa-check"></i> Accept & Rate
                    </button>
                </div>
            <?php } ?>

        </div>
    </div>

    <!-- Accept & Rate Modal -->
    <div id="acceptModal" class="modal-overlay" style="display: none;">
        <div class="modal-box">
            <h3 style="margin-top: 0; font-size: 18px; color: #111827;">Accept & Rate Task</h3>
            
            <div style="background: #F3F4F6; padding: 10px; border-radius: 6px; margin: 15px 0; font-size: 14px; font-weight: 500;">
                <?=$task['title']?>
                <div style="font-size: 12px; color: #6B7280; font-weight: 400; margin-top: 4px;">
                     <?= !empty($subtasks) ? count($subtasks) : 0 ?> completed subtasks
                </div>
            </div>

            <form action="app/admin-review-task.php" method="POST">
                <input type="hidden" name="task_id" value="<?=$task['id']?>">
                <input type="hidden" name="action" value="accept">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 5px;">Performance Rating</label>
                    <div class="rating-input" id="ratingStars">
                        <i class="fa fa-star" data-value="1"></i>
                        <i class="fa fa-star" data-value="2"></i>
                        <i class="fa fa-star" data-value="3"></i>
                        <i class="fa fa-star" data-value="4"></i>
                        <i class="fa fa-star" data-value="5"></i>
                    </div>
                    <input type="hidden" name="rating" id="ratingValue" value="0">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 5px;">Feedback (Optional)</label>
                    <textarea name="feedback" class="form-input-v2" rows="3" placeholder="Add your feedback about the completed task..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-v2 btn-white" onclick="closeModal('acceptModal')">Cancel</button>
                    <button type="submit" class="btn-v2 btn-green">Accept & Rate</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Request Revision Modal -->
    <div id="revisionModal" class="modal-overlay" style="display: none;">
         <div class="modal-box">
            <h3 style="margin-top: 0; font-size: 18px; color: #111827;">Request Revision</h3>
            
            <div style="background: #F3F4F6; padding: 10px; border-radius: 6px; margin: 15px 0; font-size: 14px; font-weight: 500;">
                <?=$task['title']?>
                <div style="font-size: 12px; color: #6B7280; font-weight: 400; margin-top: 4px;">
                     <?= !empty($subtasks) ? count($subtasks) : 0 ?> completed subtasks
                </div>
            </div>

            <form action="app/admin-review-task.php" method="POST">
                <input type="hidden" name="task_id" value="<?=$task['id']?>">
                <input type="hidden" name="action" value="revise">
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 5px;">Revision Notes</label>
                    <textarea name="feedback" class="form-input-v2" rows="3" placeholder="Explain what needs to be revised..." required></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn-v2 btn-white" onclick="closeModal('revisionModal')">Cancel</button>
                    <button type="submit" class="btn-v2 btn-yellow">Request Revision</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function openAcceptModal() {
            $("#acceptModal").fadeIn(200);
        }

        function openRevisionModal() {
            $("#revisionModal").fadeIn(200);
        }

        function closeModal(id) {
            $("#" + id).fadeOut(200);
        }

        // Rating Star Logic
        $(".rating-input i").hover(function() {
            let val = $(this).data('value');
            highlightStars(val);
        }, function() {
            let current = $("#ratingValue").val();
            highlightStars(current);
        });

        $(".rating-input i").click(function() {
            let val = $(this).data('value');
            $("#ratingValue").val(val);
            highlightStars(val);
        });

        function highlightStars(val) {
            $(".rating-input i").each(function() {
                if ($(this).data('value') <= val) {
                    $(this).addClass('active');
                    $(this).css('color', '#F59E0B');
                } else {
                    $(this).removeClass('active');
                    $(this).css('color', '#D1D5DB');
                }
            });
        }
    </script>
</body>
</html>
<?php }else{ 
   $em = "First login";
   header("Location: login.php?error=$em");
   exit();
}
?>