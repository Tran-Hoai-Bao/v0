<?php
require_once '../config/database.php';
requireLogin();

if (!hasRole('admin')) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: users.php?error=User not found');
    exit();
}

if ($_POST) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $role = sanitizeInput($_POST['role']);

    // Check if username or email already exists (excluding current user)
    $query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$username, $email, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        $error = "Username or email already exists!";
    } else {
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $query = "UPDATE users SET username = ?, email = ?, password = ?, first_name = ?, last_name = ?, role = ? WHERE id = ?";
            $params = [$username, $email, $password, $first_name, $last_name, $role, $user_id];
        } else {
            $query = "UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ? WHERE id = ?";
            $params = [$username, $email, $first_name, $last_name, $role, $user_id];
        }
        
        $stmt = $db->prepare($query);
        
        if ($stmt->execute($params)) {
            header('Location: users.php?success=User updated successfully');
            exit();
        } else {
            $error = "Error updating user!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - University LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-user-edit"></i> Edit User
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="users.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Users
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">User Information</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="first_name" class="form-label">First Name</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="last_name" class="form-label">Last Name</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="username" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="email" class="form-label">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                                                <input type="password" class="form-control" id="password" name="password">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="role" class="form-label">Role</label>
                                                <select class="form-control" id="role" name="role" required>
                                                    <option value="student" <?php echo $user['role'] == 'student' ? 'selected' : ''; ?>>Student</option>
                                                    <option value="instructor" <?php echo $user['role'] == 'instructor' ? 'selected' : ''; ?>>Instructor</option>
                                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="users.php" class="btn btn-secondary me-md-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary">Update User</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
