<?php
require_once '../config/database.php';
requireLogin();

if (!hasRole('student')) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Handle course enrollment
if (isset($_POST['enroll_course'])) {
    $course_id = (int)$_POST['course_id'];
    
    // Check if already enrolled
    $query = "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user_id'], $course_id]);
    
    if ($stmt->rowCount() > 0) {
        $error = "You are already enrolled in this course!";
    } else {
        // Check course capacity
        $query = "SELECT c.max_students, COUNT(e.id) as current_enrollments 
                  FROM courses c 
                  LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
                  WHERE c.id = ? 
                  GROUP BY c.id";
        $stmt = $db->prepare($query);
        $stmt->execute([$course_id]);
        $course_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($course_info && $course_info['current_enrollments'] >= $course_info['max_students']) {
            $error = "Course is full! Cannot enroll.";
        } else {
            $query = "INSERT INTO enrollments (student_id, course_id, status) VALUES (?, ?, 'enrolled')";
            $stmt = $db->prepare($query);
            
            if ($stmt->execute([$_SESSION['user_id'], $course_id])) {
                $success = "Successfully enrolled in the course!";
            } else {
                $error = "Error enrolling in course!";
            }
        }
    }
}

// Get enrolled courses
$query = "SELECT c.*, u.first_name, u.last_name, e.enrollment_date, e.final_grade 
          FROM courses c 
          JOIN enrollments e ON c.id = e.course_id 
          JOIN users u ON c.instructor_id = u.id 
          WHERE e.student_id = ? AND e.status = 'enrolled'
          ORDER BY e.enrollment_date DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available courses (not enrolled)
$query = "SELECT c.*, u.first_name, u.last_name, COUNT(e.id) as enrolled_students
          FROM courses c 
          JOIN users u ON c.instructor_id = u.id 
          LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
          WHERE c.status = 'active' 
          AND c.id NOT IN (
              SELECT course_id FROM enrollments 
              WHERE student_id = ? AND status = 'enrolled'
          )
          GROUP BY c.id
          ORDER BY c.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$available_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - University LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/student_navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/student_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-book"></i> My Courses
                    </h1>
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

                 Enrolled Courses 
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-graduation-cap"></i> Enrolled Courses (<?php echo count($enrolled_courses); ?>)
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($enrolled_courses)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-book fa-3x text-gray-300 mb-3"></i>
                                        <p class="text-muted">You are not enrolled in any courses yet.</p>
                                        <p class="text-muted">Browse available courses below to get started!</p>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($enrolled_courses as $course): ?>
                                            <div class="col-lg-6 mb-4">
                                                <div class="card border-left-primary h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h5 class="card-title text-primary"><?php echo htmlspecialchars($course['title']); ?></h5>
                                                            <span class="badge bg-success">Enrolled</span>
                                                        </div>
                                                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($course['course_code']); ?></h6>
                                                        <p class="card-text"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . (strlen($course['description']) > 100 ? '...' : ''); ?></p>
                                                        <div class="row text-center mb-3">
                                                            <div class="col-4">
                                                                <small class="text-muted">Instructor</small>
                                                                <div class="font-weight-bold"><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></div>
                                                            </div>
                                                            <div class="col-4">
                                                                <small class="text-muted">Credits</small>
                                                                <div class="font-weight-bold"><?php echo $course['credits']; ?></div>
                                                            </div>
                                                            <div class="col-4">
                                                                <small class="text-muted">Semester</small>
                                                                <div class="font-weight-bold"><?php echo htmlspecialchars($course['semester'] . ' ' . $course['year']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="d-grid">
                                                            <a href="course_view.php?id=<?php echo $course['id']; ?>" class="btn btn-primary">
                                                                <i class="fas fa-eye"></i> View Course
                                                            </a>
                                                        </div>
                                                        <small class="text-muted">Enrolled: <?php echo formatDate($course['enrollment_date']); ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                 Available Courses 
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-success">
                                    <i class="fas fa-plus-circle"></i> Available Courses (<?php echo count($available_courses); ?>)
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($available_courses)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <p class="text-muted">No more courses available for enrollment.</p>
                                        <p class="text-muted">You have enrolled in all available courses!</p>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($available_courses as $course): ?>
                                            <div class="col-lg-6 mb-4">
                                                <div class="card border-left-success h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h5 class="card-title text-success"><?php echo htmlspecialchars($course['title']); ?></h5>
                                                            <span class="badge bg-info">FREE</span>
                                                        </div>
                                                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($course['course_code']); ?></h6>
                                                        <p class="card-text"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . (strlen($course['description']) > 100 ? '...' : ''); ?></p>
                                                        <div class="row text-center mb-3">
                                                            <div class="col-3">
                                                                <small class="text-muted">Instructor</small>
                                                                <div class="font-weight-bold small"><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></div>
                                                            </div>
                                                            <div class="col-3">
                                                                <small class="text-muted">Credits</small>
                                                                <div class="font-weight-bold"><?php echo $course['credits']; ?></div>
                                                            </div>
                                                            <div class="col-3">
                                                                <small class="text-muted">Students</small>
                                                                <div class="font-weight-bold"><?php echo $course['enrolled_students']; ?>/<?php echo $course['max_students']; ?></div>
                                                            </div>
                                                            <div class="col-3">
                                                                <small class="text-muted">Semester</small>
                                                                <div class="font-weight-bold small"><?php echo htmlspecialchars($course['semester'] . ' ' . $course['year']); ?></div>
                                                            </div>
                                                        </div>
                                                        <div class="d-grid">
                                                            <?php if ($course['enrolled_students'] >= $course['max_students']): ?>
                                                                <button class="btn btn-secondary" disabled>
                                                                    <i class="fas fa-users"></i> Course Full
                                                                </button>
                                                            <?php else: ?>
                                                                <form method="POST" class="d-inline">
                                                                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                                                    <button type="submit" name="enroll_course" class="btn btn-success w-100" onclick="return confirm('Are you sure you want to enroll in this course?')">
                                                                        <i class="fas fa-plus"></i> Enroll Now (FREE)
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
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
