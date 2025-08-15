<?php
require_once '../config/database.php';
requireLogin();

if (!hasRole('student')) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get all assignments for enrolled courses
$query = "SELECT a.*, c.title as course_title, c.course_code,
          CASE WHEN s.id IS NOT NULL THEN 'submitted' ELSE 'pending' END as status,
          s.grade, s.submitted_at, s.feedback
          FROM assignments a
          JOIN courses c ON a.course_id = c.id
          JOIN enrollments e ON c.id = e.course_id
          LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = ?
          WHERE e.student_id = ? AND e.status = 'enrolled'
          ORDER BY a.due_date ASC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Separate assignments by status
$pending_assignments = array_filter($assignments, function($a) { return $a['status'] == 'pending'; });
$submitted_assignments = array_filter($assignments, function($a) { return $a['status'] == 'submitted'; });
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - University LMS</title>
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
                        <i class="fas fa-tasks"></i> My Assignments
                    </h1>
                </div>

                 Pending Assignments 
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-warning">
                                    <i class="fas fa-clock"></i> Pending Assignments (<?php echo count($pending_assignments); ?>)
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pending_assignments)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                        <p class="text-muted">Great! You have no pending assignments.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($pending_assignments as $assignment): ?>
                                            <div class="col-lg-6 mb-3">
                                                <div class="card border-left-warning">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                                            <h6 class="card-title"><?php echo htmlspecialchars($assignment['title']); ?></h6>
                                                            <span class="badge bg-warning">Pending</span>
                                                        </div>
                                                        <p class="card-text">
                                                            <strong>Course:</strong> <?php echo htmlspecialchars($assignment['course_title']); ?> (<?php echo htmlspecialchars($assignment['course_code']); ?>)
                                                        </p>
                                                        <p class="card-text small"><?php echo htmlspecialchars(substr($assignment['description'], 0, 100)) . '...'; ?></p>
                                                        <div class="row text-center mb-3">
                                                            <div class="col-6">
                                                                <small class="text-muted">Due Date</small>
                                                                <div class="font-weight-bold <?php echo strtotime($assignment['due_date']) < time() ? 'text-danger' : ''; ?>">
                                                                    <?php echo formatDate($assignment['due_date']); ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-6">
                                                                <small class="text-muted">Max Points</small>
                                                                <div class="font-weight-bold"><?php echo $assignment['max_points']; ?></div>
                                                            </div>
                                                        </div>
                                                        <?php if (strtotime($assignment['due_date']) < time()): ?>
                                                            <div class="alert alert-danger py-2 mb-2">
                                                                <small><i class="fas fa-exclamation-triangle"></i> Overdue!</small>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="d-grid">
                                                            <a href="assignment_view.php?id=<?php echo $assignment['id']; ?>" class="btn btn-warning">
                                                                <i class="fas fa-edit"></i> Work on Assignment
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
                </div>

                 Submitted Assignments 
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-success">
                                    <i class="fas fa-check"></i> Submitted Assignments (<?php echo count($submitted_assignments); ?>)
                                </h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($submitted_assignments)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-clipboard-list fa-3x text-gray-300 mb-3"></i>
                                        <p class="text-muted">No submitted assignments yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Assignment</th>
                                                    <th>Course</th>
                                                    <th>Submitted</th>
                                                    <th>Grade</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($submitted_assignments as $assignment): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo htmlspecialchars($assignment['title']); ?></strong>
                                                            <br>
                                                            <small class="text-muted">Due: <?php echo formatDate($assignment['due_date']); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php echo htmlspecialchars($assignment['course_title']); ?>
                                                            <br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($assignment['course_code']); ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success">Submitted</span>
                                                            <br>
                                                            <small class="text-muted"><?php echo formatDate($assignment['submitted_at']); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php if ($assignment['grade']): ?>
                                                                <strong class="text-primary"><?php echo $assignment['grade']; ?>/<?php echo $assignment['max_points']; ?></strong>
                                                                <br>
                                                                <small class="text-muted">
                                                                    <?php 
                                                                    $percentage = ($assignment['grade'] / $assignment['max_points']) * 100;
                                                                    echo number_format($percentage, 1) . '%';
                                                                    ?>
                                                                </small>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Pending Grade</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <a href="assignment_view.php?id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-eye"></i> View
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
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
