<?php
require_once '../config/database.php';
requireLogin();

if (!hasRole('admin')) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get all instructors
$query = "SELECT id, first_name, last_name FROM users WHERE role = 'instructor' ORDER BY first_name, last_name";
$stmt = $db->prepare($query);
$stmt->execute();
$instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_POST) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $instructor_id = (int)$_POST['instructor_id'];
    $course_code = sanitizeInput($_POST['course_code']);
    $credits = (int)$_POST['credits'];
    $semester = sanitizeInput($_POST['semester']);
    $year = (int)$_POST['year'];
    $max_students = (int)$_POST['max_students'];
    $status = sanitizeInput($_POST['status']);

    // Check if course code already exists
    $query = "SELECT id FROM courses WHERE course_code = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$course_code]);
    
    if ($stmt->rowCount() > 0) {
        $error = "Course code already exists!";
    } else {
        $query = "INSERT INTO courses (title, description, instructor_id, course_code, credits, semester, year, max_students, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$title, $description, $instructor_id, $course_code, $credits, $semester, $year, $max_students, $status])) {
            header('Location: courses.php?success=Course created successfully');
            exit();
        } else {
            $error = "Error creating course!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course - University LMS</title>
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
                        <i class="fas fa-plus"></i> Create New Course
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="courses.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Courses
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
                                <h6 class="m-0 font-weight-bold text-primary">Course Information</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Course Title</label>
                                        <input type="text" class="form-control" id="title" name="title" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="course_code" class="form-label">Course Code</label>
                                                <input type="text" class="form-control" id="course_code" name="course_code" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="instructor_id" class="form-label">Instructor</label>
                                                <select class="form-control" id="instructor_id" name="instructor_id" required>
                                                    <option value="">Select Instructor</option>
                                                    <?php foreach ($instructors as $instructor): ?>
                                                        <option value="<?php echo $instructor['id']; ?>">
                                                            <?php echo htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="credits" class="form-label">Credits</label>
                                                <input type="number" class="form-control" id="credits" name="credits" value="3" min="1" max="6" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="semester" class="form-label">Semester</label>
                                                <select class="form-control" id="semester" name="semester" required>
                                                    <option value="">Select Semester</option>
                                                    <option value="Spring">Spring</option>
                                                    <option value="Summer">Summer</option>
                                                    <option value="Fall">Fall</option>
                                                    <option value="Winter">Winter</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label for="year" class="form-label">Year</label>
                                                <input type="number" class="form-control" id="year" name="year" value="<?php echo date('Y'); ?>" min="2020" max="2030" required>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="max_students" class="form-label">Maximum Students</label>
                                                <input type="number" class="form-control" id="max_students" name="max_students" value="50" min="1" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Status</label>
                                                <select class="form-control" id="status" name="status" required>
                                                    <option value="active">Active</option>
                                                    <option value="inactive">Inactive</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="courses.php" class="btn btn-secondary me-md-2">Cancel</a>
                                        <button type="submit" class="btn btn-primary">Create Course</button>
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
