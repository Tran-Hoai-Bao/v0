<?php
require_once '../config/database.php';
requireLogin();

if (!hasRole('student')) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if student is enrolled in this course
$query = "SELECT c.*, u.first_name, u.last_name, e.enrollment_date 
          FROM courses c 
          JOIN users u ON c.instructor_id = u.id 
          JOIN enrollments e ON c.id = e.course_id 
          WHERE c.id = ? AND e.student_id = ? AND e.status = 'enrolled'";
$stmt = $db->prepare($query);
$stmt->execute([$course_id, $_SESSION['user_id']]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: courses.php?error=Course not found or you are not enrolled');
    exit();
}

// Get course materials
$query = "SELECT * FROM course_materials WHERE course_id = ? ORDER BY upload_date DESC";
$stmt = $db->prepare($query);
$stmt->execute([$course_id]);
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get assignments
$query = "SELECT a.*, 
          CASE WHEN s.id IS NOT NULL THEN 'submitted' ELSE 'pending' END as status,
          s.grade, s.submitted_at
          FROM assignments a
          LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
          WHERE a.course_id = ?
          ORDER BY a.due_date ASC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id'], $course_id]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get quizzes
$query = "SELECT q.*, 
          COUNT(qa.id) as attempts,
          MAX(qa.score) as best_score,
          MAX(qa.total_points) as total_points
          FROM quizzes q
          LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.student_id = ?
          WHERE q.course_id = ?
          GROUP BY q.id
          ORDER BY q.due_date ASC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id'], $course_id]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - University LMS</title>
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
                        <i class="fas fa-book"></i> <?php echo htmlspecialchars($course['title']); ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="courses.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Courses
                            </a>
                        </div>
                    </div>
                </div>

                 Course Info 
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4><?php echo htmlspecialchars($course['title']); ?></h4>
                                        <p class="mb-2"><?php echo htmlspecialchars($course['description']); ?></p>
                                        <p class="mb-0">
                                            <strong>Instructor:</strong> <?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?> |
                                            <strong>Course Code:</strong> <?php echo htmlspecialchars($course['course_code']); ?> |
                                            <strong>Credits:</strong> <?php echo $course['credits']; ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <h6>Enrolled Since</h6>
                                        <p><?php echo formatDate($course['enrollment_date']); ?></p>
                                        <span class="badge bg-success fs-6">
                                            <?php echo htmlspecialchars($course['semester'] . ' ' . $course['year']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                 Course Content Tabs 
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs" id="courseTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="materials-tab" data-bs-toggle="tab" data-bs-target="#materials" type="button" role="tab">
                                            <i class="fas fa-file-alt"></i> Materials
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab">
                                            <i class="fas fa-tasks"></i> Assignments
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="quizzes-tab" data-bs-toggle="tab" data-bs-target="#quizzes" type="button" role="tab">
                                            <i class="fas fa-question-circle"></i> Quizzes
                                        </button>
                                    </li>                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="forum-tab" data-bs-toggle="tab" data-bs-target="#forum" type="button" role="tab">
                                            <i class="fas fa-comments"></i> Forum
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="courseTabContent">
                                     Materials Tab 
                                    <div class="tab-pane fade show active" id="materials" role="tabpanel">
                                        <?php if (empty($materials)): ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-file-alt fa-3x text-gray-300 mb-3"></i>
                                                <p class="text-muted">No course materials available yet.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="list-group">
                                                <?php foreach ($materials as $material): ?>
                                                    <div class="list-group-item">
                                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                                            <div>
                                                                <h6 class="mb-1">
                                                                    <i class="fas fa-file"></i> <?php echo htmlspecialchars($material['title']); ?>
                                                                </h6>
                                                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($material['description']); ?></p>
                                                                <small class="text-muted">Uploaded: <?php echo formatDate($material['upload_date']); ?></small>
                                                            </div>
                                                            <div>
                                                                <?php if ($material['file_path']): ?>
                                                                    <a href="../uploads/<?php echo htmlspecialchars($material['file_path']); ?>" class="btn btn-sm btn-primary" target="_blank">
                                                                        <i class="fas fa-download"></i> Download
                                                                    </a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                     Assignments Tab 
                                    <div class="tab-pane fade" id="assignments" role="tabpanel">
                                        <?php if (empty($assignments)): ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-tasks fa-3x text-gray-300 mb-3"></i>
                                                <p class="text-muted">No assignments available yet.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach ($assignments as $assignment): ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card border-left-<?php echo $assignment['status'] == 'submitted' ? 'success' : 'warning'; ?>">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <h6 class="card-title"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                                                    <span class="badge bg-<?php echo $assignment['status'] == 'submitted' ? 'success' : 'warning'; ?>">
                                                                        <?php echo ucfirst($assignment['status']); ?>
                                                                    </span>
                                                                </div>
                                                                <p class="card-text small"><?php echo htmlspecialchars(substr($assignment['description'], 0, 100)) . '...'; ?></p>
                                                                <div class="row text-center mb-2">
                                                                    <div class="col-6">
                                                                        <small class="text-muted">Due Date</small>
                                                                        <div class="font-weight-bold small"><?php echo formatDate($assignment['due_date']); ?></div>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <small class="text-muted">Points</small>
                                                                        <div class="font-weight-bold"><?php echo $assignment['max_points']; ?></div>
                                                                    </div>
                                                                </div>
                                                                <?php if ($assignment['status'] == 'submitted'): ?>
                                                                    <div class="alert alert-success py-2 mb-2">
                                                                        <small>
                                                                            <i class="fas fa-check"></i> Submitted: <?php echo formatDate($assignment['submitted_at']); ?>
                                                                            <?php if ($assignment['grade']): ?>
                                                                                <br>Grade: <?php echo $assignment['grade']; ?>/<?php echo $assignment['max_points']; ?>
                                                                            <?php endif; ?>
                                                                        </small>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div class="d-grid">
                                                                    <a href="assignment_view.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-primary">
                                                                        <i class="fas fa-eye"></i> View Assignment
                                                                    </a>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                     Quizzes Tab 
                                    <div class="tab-pane fade" id="quizzes" role="tabpanel">
                                        <?php if (empty($quizzes)): ?>
                                            <div class="text-center py-4">
                                                <i class="fas fa-question-circle fa-3x text-gray-300 mb-3"></i>
                                                <p class="text-muted">No quizzes available yet.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach ($quizzes as $quiz): ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card border-left-info">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <h6 class="card-title"><?php echo htmlspecialchars($quiz['title']); ?></h6>
                                                                    <?php if ($quiz['attempts'] > 0): ?>
                                                                        <span class="badge bg-success">Completed</span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-info">Available</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <p class="card-text small"><?php echo htmlspecialchars(substr($quiz['description'], 0, 100)) . '...'; ?></p>
                                                                <div class="row text-center mb-2">
                                                                    <div class="col-4">
                                                                        <small class="text-muted">Due Date</small>
                                                                        <div class="font-weight-bold small"><?php echo formatDate($quiz['due_date']); ?></div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <small class="text-muted">Attempts</small>
                                                                        <div class="font-weight-bold"><?php echo $quiz['attempts']; ?>/<?php echo $quiz['max_attempts']; ?></div>
                                                                    </div>
                                                                    <div class="col-4">
                                                                        <small class="text-muted">Best Score</small>
                                                                        <div class="font-weight-bold">
                                                                            <?php if ($quiz['best_score']): ?>
                                                                                <?php echo $quiz['best_score']; ?>/<?php echo $quiz['total_points']; ?>
                                                                            <?php else: ?>
                                                                                -
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="d-grid">
                                                                    <?php if ($quiz['attempts'] >= $quiz['max_attempts']): ?>
                                                                        <button class="btn btn-sm btn-secondary" disabled>
                                                                            <i class="fas fa-ban"></i> No More Attempts
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <a href="quiz_take.php?id=<?php echo $quiz['id']; ?>" class="btn btn-sm btn-info">
                                                                            <i class="fas fa-play"></i> Take Quiz
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                     Forum Tab 
                                    <div class="tab-pane fade" id="forum" role="tabpanel">
                                        <div class="text-center py-4">
                                            <i class="fas fa-comments fa-3x text-primary mb-3"></i>
                                            <h5>Course Discussion Forum</h5>
                                            <p class="text-muted">Connect with your classmates and instructor</p>
                                            <a href="../forum/index.php?course_id=<?php echo $course_id; ?>" class="btn btn-primary">
                                                <i class="fas fa-external-link-alt"></i> Go to Forum
                                            </a>
                                        </div>
                                    </div>
                                </div>
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
