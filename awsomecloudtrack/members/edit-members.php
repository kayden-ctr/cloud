<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Check if ID is provided
if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: " . url("members/index.php"));
    exit();
}

$id = $_GET['id'];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $join_date = $_POST['join_date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    
    // Basic validation
    if (empty($name) || empty($email) || empty($role) || empty($join_date)) {
        $error_message = "Please fill all required fields";
    } else {
        // Update member
        $query = "UPDATE members SET 
                  name = ?, 
                  email = ?, 
                  phone = ?, 
                  role = ?, 
                  join_date = ?, 
                  status = ?, 
                  notes = ?,
                  updated_at = NOW()
                  WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssssi", $name, $email, $phone, $role, $join_date, $status, $notes, $id);
        
        if($stmt->execute()) {
            $success_message = "Member updated successfully";
        } else {
            $error_message = "Error updating member: " . $conn->error;
        }
    }
}

// Get member data
$query = "SELECT * FROM members WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("Location: " . url("members/index.php"));
    exit();
}

$member = $result->fetch_assoc();
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-pencil-square me-2"></i>Edit Member</h1>
        <p class="text-muted">Update member information</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo url('members/index.php'); ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Members
        </a>
    </div>
</div>

<?php if(isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if(isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Member Information</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?id=" . $id; ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $member['name']; ?>" required>
                            <div class="invalid-feedback">Please enter the member's name.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $member['email']; ?>" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $member['phone']; ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="role" name="role" list="roleList" value="<?php echo $member['role']; ?>" required>
                            <datalist id="roleList">
                                <option value="President">
                                <option value="Vice President">
                                <option value="Secretary">
                                <option value="Treasurer">
                                <option value="Member">
                            </datalist>
                            <div class="invalid-feedback">Please specify the member's role.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="join_date" class="form-label">Join Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="join_date" name="join_date" value="<?php echo $member['join_date']; ?>" required>
                            <div class="invalid-feedback">Please select a join date.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" <?php echo $member['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $member['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <div class="invalid-feedback">Please select a status.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $member['notes']; ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="<?php echo url('members/index.php'); ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Update Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Member Details</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Member ID:</span>
                        <span class="fw-semibold">#<?php echo $member['id']; ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Created:</span>
                        <span><?php echo date('M d, Y', strtotime($member['created_at'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Last Updated:</span>
                        <span><?php echo date('M d, Y', strtotime($member['updated_at'])); ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <span class="text-muted">Status:</span>
                        <span>
                            <?php if($member['status'] == 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Member Roles</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0">
                        <strong>President</strong>
                        <p class="mb-0 text-muted small">Leads the organization and oversees all activities</p>
                    </li>
                    <li class="list-group-item px-0">
                        <strong>Vice President</strong>
                        <p class="mb-0 text-muted small">Assists the president and acts in their absence</p>
                    </li>
                    <li class="list-group-item px-0">
                        <strong>Secretary</strong>
                        <p class="mb-0 text-muted small">Maintains records and handles correspondence</p>
                    </li>
                    <li class="list-group-item px-0">
                        <strong>Treasurer</strong>
                        <p class="mb-0 text-muted small">Manages finances and financial records</p>
                    </li>
                    <li class="list-group-item px-0">
                        <strong>Member</strong>
                        <p class="mb-0 text-muted small">Regular organization member</p>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Form validation
(function() {
    'use strict';
    
    // Fetch all forms we want to apply validation styles to
    var forms = document.querySelectorAll('.needs-validation');
    
    // Loop over them and prevent submission
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>

<?php include '../includes/footer.php'; ?>
