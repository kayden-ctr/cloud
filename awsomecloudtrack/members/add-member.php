<?php
session_start();
include '../includes/db_connect.php';
include '../includes/functions.php'; // Make sure this includes the `url()` function if needed

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
        // Insert new member
        $query = "INSERT INTO members (name, email, phone, role, join_date, status, notes) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssssss", $name, $email, $phone, $role, $join_date, $status, $notes);
        
        if($stmt->execute()) {
            header("Location: /awsomecloudtrack/members/index.php?success=1"); // Hardcoded to avoid dependency
            exit();
        } else {
            $error_message = "Error adding member: " . $conn->error;
        }
    }
}

// Get current date for default join date
$current_date = date('Y-m-d');
?>

<?php include '../includes/header.php'; ?>
<!-- HTML OUTPUT STARTS HERE -->

<div class="row mb-4">
    <div class="col-md-6">
        <h1><i class="bi bi-person-plus me-2"></i>Add New Member</h1>
        <p class="text-muted">Register a new organization member</p>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo url('members/index.php'); ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Back to Members
        </a>
    </div>
</div>

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
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Please enter the member's name.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="role" name="role" list="roleList" required>
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
                            <input type="date" class="form-control" id="join_date" name="join_date" value="<?php echo $current_date; ?>" required>
                            <div class="invalid-feedback">Please select a join date.</div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <div class="invalid-feedback">Please select a status.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-person-plus me-1"></i> Add Member
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
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
        
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Collect accurate contact information
                    </li>
                    <li class="list-group-item px-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Assign appropriate roles based on responsibilities
                    </li>
                    <li class="list-group-item px-0">
                        <i class="bi bi-info-circle text-primary me-2"></i>
                        Use notes to add important details about the member
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
