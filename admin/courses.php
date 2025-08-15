<?php
require_once '../config/database.php';
requireLogin();

if (!hasRole('admin')) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle course deletion
if (isset($_POST['delete_course'])) {
    $course_id = (int)$_POST['course_id'];
    $query = "DELETE FROM courses WHERE id = ?";
    $stmt = $db->prepare($query);
    if ($stmt->execute([$course_id])) {
        $success = "Course deleted successfully!";
    } else {
        $error = "Error deleting course.";
    }
}

// Get all courses with instructor info
$query = "SELECT c.*, u.first_name, u.last_name, COUNT(e.id) as enrolled_students 
          FROM courses c 
          JOIN users u ON c.instructor_id = u.id 
          LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
          GROUP BY c.id 
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management - University LMS</title>
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
                        <i class="fas fa-book"></i> Course Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="course_create.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus"></i> Add Course
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">All Courses</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Code</th>
                                        <th>Instructor</th>
                                        <th>Students</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courses as $course): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($course['semester'] . ' ' . $course['year']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                            <td><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $course['enrolled_students']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $course['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($course['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatDate($course['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="course_view.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="course_edit.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteCourse(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['title']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

     Delete Confirmation Modal 
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete course <strong id="courseName"></strong>?</p>
                    <p class="text-danger">This action cannot be undone and will remove all associated data including enrollments, assignments, and grades.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="course_id" id="deleteCourseId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_course" class="btn btn-danger">Delete Course</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteCourse(courseId, courseName) {
            document.getElementById('deleteCourseId').value = courseId;
            document.getElementById('courseName').textContent = courseName;
            new bootstrap.Modal(document.getElementById('deleteModal')).show();
        }
    </script>
</body>
</html>
