<?php
require_once '../config/database.php';
requireLogin();

if (!hasRole('admin')) {
    header('Location: ../auth/login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get system statistics
$stats = [];

// Total users by role
$query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$stmt = $db->prepare($query);
$stmt->execute();
$user_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($user_stats as $stat) {
    $stats[$stat['role']] = $stat['count'];
}

// Total courses
$query = "SELECT COUNT(*) as count FROM courses";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['courses'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Total enrollments
$query = "SELECT COUNT(*) as count FROM enrollments WHERE status = 'enrolled'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['enrollments'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Recent activities
$query = "SELECT 'user_registered' as type, CONCAT(first_name, ' ', last_name) as description, created_at as date
          FROM users 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          UNION ALL
          SELECT 'course_created' as type, title as description, created_at as date
          FROM courses 
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          UNION ALL
          SELECT 'enrollment' as type, CONCAT('Student enrolled in course') as description, enrollment_date as date
          FROM enrollments 
          WHERE enrollment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
          ORDER BY date DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Course enrollment data for chart
$query = "SELECT c.title, COUNT(e.id) as enrollments
          FROM courses c
          LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
          GROUP BY c.id, c.title
          ORDER BY enrollments DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$course_enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly user registrations
$query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
          FROM users
          WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
          ORDER BY month";
$stmt = $db->prepare($query);
$stmt->execute();
$monthly_registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - University LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-download"></i> Export Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Welcome Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-dark text-white">
                            <div class="card-body">
                                <h4><i class="fas fa-user-shield"></i> Welcome, Administrator!</h4>
                                <p class="mb-0">System overview and management tools for University LMS.</p>
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
                                            Total Students</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo isset($stats['student']) ? $stats['student'] : 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
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
                                            Total Instructors</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo isset($stats['instructor']) ? $stats['instructor'] : 0; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
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
                                            Total Courses</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['courses']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-book fa-2x text-gray-300"></i>
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
                                            Active Enrollments</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['enrollments']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Course Enrollments Chart -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-bar"></i> Course Enrollments
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="courseEnrollmentChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- User Registration Trend -->
                    <div class="col-xl-4 col-lg-5">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-chart-line"></i> Monthly Registrations
                                </h6>
                            </div>
                            <div class="card-body">
                                <canvas id="registrationChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Management Tools and Recent Activity -->
                <div class="row">
                    <!-- Quick Management Tools -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-tools"></i> Management Tools
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-primary text-white">
                                            <div class="card-body text-center">
                                                <i class="fas fa-users fa-2x mb-2"></i>
                                                <h6>User Management</h6>
                                                <a href="users.php" class="btn btn-light btn-sm">Manage</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <i class="fas fa-book fa-2x mb-2"></i>
                                                <h6>Course Management</h6>
                                                <a href="courses.php" class="btn btn-light btn-sm">Manage</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-info text-white">
                                            <div class="card-body text-center">
                                                <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                                <h6>Analytics</h6>
                                                <a href="analytics.php" class="btn btn-light btn-sm">View</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-warning text-white">
                                            <div class="card-body text-center">
                                                <i class="fas fa-cog fa-2x mb-2"></i>
                                                <h6>System Settings</h6>
                                                <a href="settings.php" class="btn btn-light btn-sm">Configure</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">
                                    <i class="fas fa-clock"></i> Recent Activity
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="timeline">
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-marker 
                                                <?php 
                                                switch($activity['type']) {
                                                    case 'user_registered': echo 'bg-success'; break;
                                                    case 'course_created': echo 'bg-primary'; break;
                                                    case 'enrollment': echo 'bg-info'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                                ?>"></div>
                                            <div class="timeline-content">
                                                <h6 class="mb-1">
                                                    <?php 
                                                    switch($activity['type']) {
                                                        case 'user_registered': echo 'New User Registration'; break;
                                                        case 'course_created': echo 'Course Created'; break;
                                                        case 'enrollment': echo 'New Enrollment'; break;
                                                        default: echo 'Activity';
                                                    }
                                                    ?>
                                                </h6>
                                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                <small class="text-muted"><?php echo formatDate($activity['date']); ?></small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Course Enrollment Chart
        const ctx1 = document.getElementById('courseEnrollmentChart').getContext('2d');
        const courseEnrollmentChart = new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($course_enrollments as $course): ?>
                        '<?php echo addslashes($course['title']); ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Enrollments',
                    data: [
                        <?php foreach ($course_enrollments as $course): ?>
                            <?php echo $course['enrollments']; ?>,
                        <?php endforeach; ?>
                    ],
                    backgroundColor: '#4e73df',
                    borderColor: '#4e73df',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Monthly Registration Chart
        const ctx2 = document.getElementById('registrationChart').getContext('2d');
        const registrationChart = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: [
                    <?php foreach ($monthly_registrations as $reg): ?>
                        '<?php echo $reg['month']; ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: 'Registrations',
                    data: [
                        <?php foreach ($monthly_registrations as $reg): ?>
                            <?php echo $reg['count']; ?>,
                        <?php endforeach; ?>
                    ],
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
