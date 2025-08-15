<?php
require_once '../config/database.php';
requireLogin();

if (!hasRole('student')) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get enrolled courses
$query = "SELECT c.*, u.first_name, u.last_name, e.enrollment_date, e.final_grade 
          FROM courses c 
          JOIN enrollments e ON c.id = e.course_id 
          JOIN users u ON c.instructor_id = u.id 
          WHERE e.student_id = ? AND e.status = 'enrolled'";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$enrolled_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent assignments
$query = "SELECT a.*, c.title as course_title, c.course_code,
          CASE WHEN s.id IS NOT NULL THEN 'submitted' ELSE 'pending' END as status,
          s.grade, s.submitted_at
          FROM assignments a
          JOIN courses c ON a.course_id = c.id
          JOIN enrollments e ON c.id = e.course_id
          LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
          WHERE e.student_id = ? AND e.status = 'enrolled'
          ORDER BY a.due_date ASC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$recent_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent quiz attempts
$query = "SELECT q.title, c.title as course_title, qa.score, qa.total_points, qa.completed_at
          FROM quiz_attempts qa
          JOIN quizzes q ON qa.quiz_id = q.id
          JOIN courses c ON q.course_id = c.id
          WHERE qa.student_id = ? AND qa.completed_at IS NOT NULL
          ORDER BY qa.completed_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$recent_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - University LMS</title>
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
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-calendar"></i> This week
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h4><i class="fas fa-user-graduate"></i> Welcome back, <?php echo $_SESSION['first_name']; ?>!</h4>
                                <p class="mb-0">You have <?php echo count($enrolled_courses); ?> active courses and <?php echo count(array_filter($recent_assignments, function($a) { return $a['status'] == 'pending'; })); ?> pending assignments.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Enrolled Courses</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($enrolled_courses); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Completed Assignments</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count(array_filter($recent_assignments, function($a) { return $a['status'] == 'submitted'; })); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Quiz Attempts</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo count($recent_quizzes); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-question-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Tasks</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo count(array_filter($recent_assignments, function($a) { return $a['status'] == 'pending'; })); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="row">
                    <!-- Enrolled Courses -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-book"></i> My Courses
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($enrolled_courses)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-book fa-3x text-gray-300 mb-3"></i>
                                        <p class="text-muted">You are not enrolled in any courses yet.</p>
                                        <a href="courses.php" class="btn btn-primary">Browse Courses</a>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($enrolled_courses as $course): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card border-left-primary">
                                                    <div class="card-body">
                                                        <h6 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h6>
                                                        <p class="card-text text-muted small">
                                                            <?php echo htmlspecialchars($course['course_code']); ?> - 
                                                            <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?>
                                                        </p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small class="text-muted">
                                                                Enrolled: <?php echo formatDate($course['enrollment_date']); ?>
                                                            </small>
                                                            <a href="course_view.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-primary">
                                                                View Course
                                                            </a>
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

                    <!-- Recent Activity -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-clock"></i> Recent Activity
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php foreach ($recent_assignments as $assignment): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker <?php echo $assignment['status'] == 'submitted' ? 'bg-success' : 'bg-warning'; ?>"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($assignment['course_title']); ?></p>
                                                <small class="text-muted">
                                                    Due: <?php echo formatDate($assignment['due_date']); ?>
                                                    <?php if ($assignment['status'] == 'submitted'): ?>
                                                        <span class="badge bg-success ms-2">Submitted</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning ms-2">Pending</span>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Quiz Results -->
                <?php if (!empty($recent_quizzes)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-line"></i> Recent Quiz Results
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Quiz</th>
                                                <th>Course</th>
                                                <th>Score</th>
                                                <th>Percentage</th>
                                                <th>Completed</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_quizzes as $quiz): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($quiz['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($quiz['course_title']); ?></td>
                                                    <td><?php echo $quiz['score']; ?>/<?php echo $quiz['total_points']; ?></td>
                                                    <td>
                                                        <?php 
                                                        $percentage = ($quiz['score'] / $quiz['total_points']) * 100;
                                                        $badge_class = $percentage >= 90 ? 'bg-success' : ($percentage >= 70 ? 'bg-warning' : 'bg-danger');
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?>">
                                                            <?php echo number_format($percentage, 1); ?>%
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatDate($quiz['completed_at']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
