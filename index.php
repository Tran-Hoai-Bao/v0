<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get featured courses
$query = "SELECT c.*, u.first_name, u.last_name, COUNT(e.id) as enrolled_students
          FROM courses c 
          JOIN users u ON c.instructor_id = u.id 
          LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'enrolled'
          WHERE c.status = 'active'
          GROUP BY c.id
          ORDER BY enrolled_students DESC
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$query = "SELECT 
    (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
    (SELECT COUNT(*) FROM users WHERE role = 'instructor') as total_instructors,
    (SELECT COUNT(*) FROM courses WHERE status = 'active') as total_courses,
    (SELECT COUNT(*) FROM enrollments WHERE status = 'enrolled') as total_enrollments";
$stmt = $db->prepare($query);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University LMS - Online Learning Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .stats-section {
            background: #f8f9fa;
            padding: 60px 0;
        }
        .course-card {
            transition: transform 0.3s;
            height: 100%;
        }
        .course-card:hover {
            transform: translateY(-5px);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
     Navigation 
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-graduation-cap"></i> University LMS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#courses">Courses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['first_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo $_SESSION['role']; ?>/dashboard.php">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

     Hero Section 
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">Learn Without Limits</h1>
                    <p class="lead mb-4">Join thousands of students in our comprehensive online learning platform. Access high-quality courses, connect with expert instructors, and advance your career from anywhere.</p>
                    <div class="d-flex gap-3">
                        <?php if (!isLoggedIn()): ?>
                            <a href="auth/login.php" class="btn btn-light btn-lg">
                                <i class="fas fa-play"></i> Get Started
                            </a>
                        <?php else: ?>
                            <a href="<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-light btn-lg">
                                <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                            </a>
                        <?php endif; ?>
                        <a href="#courses" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-book"></i> Browse Courses
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-laptop-code" style="font-size: 15rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

     Statistics Section 
    <section class="stats-section">
        <div class="container">
            <div class="row text-center">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <i class="fas fa-user-graduate fa-3x text-primary mb-3"></i>
                            <h3 class="fw-bold"><?php echo number_format($stats['total_students']); ?>+</h3>
                            <p class="text-muted">Active Students</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <i class="fas fa-chalkboard-teacher fa-3x text-success mb-3"></i>
                            <h3 class="fw-bold"><?php echo number_format($stats['total_instructors']); ?>+</h3>
                            <p class="text-muted">Expert Instructors</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <i class="fas fa-book fa-3x text-info mb-3"></i>
                            <h3 class="fw-bold"><?php echo number_format($stats['total_courses']); ?>+</h3>
                            <p class="text-muted">Available Courses</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <i class="fas fa-certificate fa-3x text-warning mb-3"></i>
                            <h3 class="fw-bold"><?php echo number_format($stats['total_enrollments']); ?>+</h3>
                            <p class="text-muted">Course Enrollments</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

     Featured Courses 
    <section id="courses" class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Featured Courses</h2>
                    <p class="lead text-muted">Discover our most popular courses and start learning today</p>
                </div>
            </div>
            <div class="row">
                <?php foreach ($featured_courses as $course): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card course-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <span class="badge bg-success">FREE</span>
                                </div>
                                <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($course['course_code']); ?></h6>
                                <p class="card-text"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <small class="text-muted">Instructor</small>
                                        <div class="fw-bold small"><?php echo htmlspecialchars($course['first_name'] . ' ' . $course['last_name']); ?></div>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Students</small>
                                        <div class="fw-bold"><?php echo $course['enrolled_students']; ?></div>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Credits</small>
                                        <div class="fw-bold"><?php echo $course['credits']; ?></div>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <?php if (isLoggedIn() && hasRole('student')): ?>
                                        <a href="student/courses.php" class="btn btn-primary">
                                            <i class="fas fa-plus"></i> Enroll Now
                                        </a>
                                    <?php else: ?>
                                        <a href="auth/login.php" class="btn btn-primary">
                                            <i class="fas fa-sign-in-alt"></i> Login to Enroll
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-4">
                <?php if (isLoggedIn() && hasRole('student')): ?>
                    <a href="student/courses.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-book"></i> View All Courses
                    </a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Login to View All Courses
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

     Features Section 
    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Why Choose Our Platform?</h2>
                    <p class="lead text-muted">Everything you need for successful online learning</p>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-video fa-3x text-primary mb-3"></i>
                            <h5>Interactive Learning</h5>
                            <p class="text-muted">Engage with multimedia content, quizzes, and interactive assignments designed to enhance your learning experience.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x text-success mb-3"></i>
                            <h5>Expert Instructors</h5>
                            <p class="text-muted">Learn from industry professionals and experienced educators who are passionate about sharing their knowledge.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-3x text-info mb-3"></i>
                            <h5>Flexible Schedule</h5>
                            <p class="text-muted">Study at your own pace with 24/7 access to course materials and resources from anywhere in the world.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-comments fa-3x text-warning mb-3"></i>
                            <h5>Discussion Forums</h5>
                            <p class="text-muted">Connect with fellow students and instructors through course-specific discussion forums and collaborative learning.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line fa-3x text-danger mb-3"></i>
                            <h5>Progress Tracking</h5>
                            <p class="text-muted">Monitor your learning progress with detailed analytics, grades, and performance insights.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card feature-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-mobile-alt fa-3x text-secondary mb-3"></i>
                            <h5>Mobile Friendly</h5>
                            <p class="text-muted">Access your courses on any device - desktop, tablet, or smartphone with our responsive design.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

     About Section 
    <section id="about" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-4">About University LMS</h2>
                    <p class="lead mb-4">We are dedicated to providing high-quality online education that is accessible, affordable, and effective. Our platform combines cutting-edge technology with proven pedagogical methods to create an engaging learning environment.</p>
                    <div class="row">
                        <div class="col-6">
                            <h4 class="text-primary">100%</h4>
                            <p class="text-muted">Free Courses</p>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success">24/7</h4>
                            <p class="text-muted">Support Available</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-university" style="font-size: 12rem; color: #e9ecef;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

     Footer 
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-graduation-cap"></i> University LMS</h5>
                    <p class="text-muted">Empowering learners worldwide with quality online education.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted">&copy; 2024 University LMS. All rights reserved.</p>
                    <div class="social-links">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-linkedin"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
