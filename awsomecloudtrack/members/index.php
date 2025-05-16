<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Initialize messages
$success_message = '';
$error_message = '';

// Handle delete POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && is_numeric($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);

    $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
    if (!$stmt) {
        $error_message = "Prepare failed: " . $conn->error;
    } else {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            // Redirect to avoid form resubmission
            header("Location: index.php?deleted=1");
            exit();
        } else {
            $error_message = "Error deleting member: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Show success message after redirect
if (isset($_GET['deleted']) && $_GET['deleted'] == 1) {
    $success_message = "Member deleted successfully.";
}

// Fetch members
$result = $conn->query("SELECT * FROM members ORDER BY name ASC");
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>Member Management</h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="add-member.php" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Member
        </a>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="membersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Join Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($member = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $member['id']; ?></td>
                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                        <td><?php echo htmlspecialchars($member['phone']); ?></td>
                        <td><?php echo htmlspecialchars($member['role']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($member['join_date'])); ?></td>
                        <td>
                            <?php if ($member['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo url('members/edit-member.php?id=' . $member['id']); ?>" class="btn btn-sm btn-primary" title="Edit Member">
                                <i class="bi bi-pencil"></i>
                            </a>

                            <!-- Delete Button triggers modal -->
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $member['id']; ?>" title="Delete Member">
                                <i class="bi bi-trash"></i>
                            </button>

                            <!-- Delete Confirmation Modal -->
                            <div class="modal fade" id="deleteModal<?php echo $member['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $member['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <form method="POST" action="index.php">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $member['id']; ?>">Confirm Delete</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to delete <strong><?php echo htmlspecialchars($member['name']); ?></strong>?
                                            </div>
                                            <div class="modal-footer">
                                                <input type="hidden" name="delete_id" value="<?php echo $member['id']; ?>">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- End Modal -->

                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No members found.</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Bootstrap JS bundle (ensure you have this included in your header or before this script) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<?php include '../includes/footer.php'; ?>
