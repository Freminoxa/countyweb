<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = $isLoggedIn && $_SESSION['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meru County Government Project Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <div class="header">
                <div class="header-content">
                    <div class="logo-section">
                        <div class="logo-placeholder">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    <div class="title-section">
                        <h1>Meru County Government</h1>
                        <p class="subtitle mb-0">Project Management System</p>
                    </div>
                    <div class="ms-auto">
                        <?php if ($isLoggedIn): ?>
                            <span class="text-white me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo $_SESSION['role']; ?>)</span>
                            <button class="btn btn-danger" onclick="logout()">Logout</button>
                        <?php else: ?>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">Login</button>
                        <?php endif; ?>
                        <?php if ($isLoggedIn && $isAdmin): ?>
                            <button class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#registerModal">Register User</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Department Cards -->
            <div class="row mb-4" id="departmentCards"></div>

            <!-- Add Project Button (Admin Only) -->
            <?php if ($isAdmin): ?>
                <div class="text-center mb-4">
                    <button class="btn btn-primary add-project-btn" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                        <i class="fas fa-plus me-2"></i>Add New Project
                    </button>
                </div>
            <?php endif; ?>

            <!-- Status Tabs -->
            <ul class="nav nav-tabs status-tabs" id="statusTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab">
                        <i class="fas fa-list me-2"></i>All Projects
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="finished-tab" data-bs-toggle="tab" data-bs-target="#finished" type="button" role="tab">
                        <i class="fas fa-check-circle me-2"></i>Finished
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="proposed-tab" data-bs-toggle="tab" data-bs-target="#proposed" type="button" role="tab">
                        <i class="fas fa-lightbulb me-2"></i>Proposed
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="unfinished-tab" data-bs-toggle="tab" data-bs-target="#unfinished" type="button" role="tab">
                        <i class="fas fa-clock me-2"></i>Unfinished
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="statusTabsContent">
                <div class="tab-pane fade show active" id="all" role="tabpanel">
                    <div id="allProjects"></div>
                </div>
                <div class="tab-pane fade" id="finished" role="tabpanel">
                    <div id="finishedProjects"></div>
                </div>
                <div class="tab-pane fade" id="proposed" role="tabpanel">
                    <div id="proposedProjects"></div>
                </div>
                <div class="tab-pane fade" id="unfinished" role="tabpanel">
                    <div id="unfinishedProjects"></div>
                </div>
            </div>

            <!-- Department View (hidden by default) -->
            <div id="departmentView" style="display: none;"></div>
        </div>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-sign-in-alt me-2"></i>Login</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" required>
                        </div>
                        <div class="mb-2">
                            <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot Password?</a>
                        </div>
                        <div id="loginError" class="text-danger" style="display: none;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="login()">Login</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Project Modal (Admin Only) -->
    <div class="modal fade" id="addProjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Project</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="projectForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="projectTitle" class="form-label">Project Title</label>
                                <input type="text" class="form-control" id="projectTitle" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="projectDepartment" class="form-label">Department</label>
                                <select class="form-select" id="projectDepartment" required>
                                    <option value="">Select Department</option>
                                    <option value="Health Services">Health Services</option>
                                    <option value="Education">Education</option>
                                    <option value="Agriculture">Agriculture</option>
                                    <option value="Infrastructure">Infrastructure</option>
                                    <option value="Water & Sanitation">Water & Sanitation</option>
                                    <option value="Trade & Industry">Trade & Industry</option>
                                    <option value="Youth & Sports">Youth & Sports</option>
                                    <option value="Environment">Environment</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Administration">Administration</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="projectStatus" class="form-label">Status</label>
                                <select class="form-select" id="projectStatus" required>
                                    <option value="">Select Status</option>
                                    <option value="finished">Finished</option>
                                    <option value="proposed">Proposed</option>
                                    <option value="unfinished">Unfinished</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="projectManager" class="form-label">Project Manager</label>
                                <input type="text" class="form-control" id="projectManager" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="startDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="endDate">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="projectDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="projectDescription" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="projectBudget" class="form-label">Budget (KSh)</label>
                            <input type="number" class="form-control" id="projectBudget" min="0" step="0.01">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addProject()">Add Project</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Project Modal (Admin Only) -->
    <div class="modal fade" id="editProjectModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Project</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editProjectForm">
                        <input type="hidden" id="editProjectId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editProjectTitle" class="form-label">Project Title</label>
                                <input type="text" class="form-control" id="editProjectTitle" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editProjectDepartment" class="form-label">Department</label>
                                <select class="form-select" id="editProjectDepartment" required>
                                    <option value="">Select Department</option>
                                    <option value="Health Services">Health Services</option>
                                    <option value="Education">Education</option>
                                    <option value="Agriculture">Agriculture</option>
                                    <option value="Infrastructure">Infrastructure</option>
                                    <option value="Water & Sanitation">Water & Sanitation</option>
                                    <option value="Trade & Industry">Trade & Industry</option>
                                    <option value="Youth & Sports">Youth & Sports</option>
                                    <option value="Environment">Environment</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Administration">Administration</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editProjectStatus" class="form-label">Status</label>
                                <select class="form-select" id="editProjectStatus" required>
                                    <option value="">Select Status</option>
                                    <option value="finished">Finished</option>
                                    <option value="proposed">Proposed</option>
                                    <option value="unfinished">Unfinished</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editProjectManager" class="form-label">Project Manager</label>
                                <input type="text" class="form-control" id="editProjectManager" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editStartDate" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="editStartDate" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editEndDate" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="editEndDate">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editProjectDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editProjectDescription" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editProjectBudget" class="form-label">Budget (KSh)</label>
                            <input type="number" class="form-control" id="editProjectBudget" min="0" step="0.01">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateProject()">Update Project</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal (Admin Only) -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Register New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="fullName" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="fullName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="department" class="form-label">Department</label>
                                <select class="form-select" id="department" required>
                                    <option value="">Select Department</option>
                                    <option value="Health Services">Health Services</option>
                                    <option value="Education">Education</option>
                                    <option value="Agriculture">Agriculture</option>
                                    <option value="Infrastructure">Infrastructure</option>
                                    <option value="Water & Sanitation">Water & Sanitation</option>
                                    <option value="Trade & Industry">Trade & Industry</option>
                                    <option value="Youth & Sports">Youth & Sports</option>
                                    <option value="Environment">Environment</option>
                                    <option value="Finance">Finance</option>
                                    <option value="Administration">Administration</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirmPassword" required>
                            </div>
                        </div>
                        <div id="registerError" class="text-danger" style="display: none;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="registerUser()">Register</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-key me-2"></i>Forgot Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="forgotPasswordForm">
                        <div class="mb-3">
                            <label for="forgotEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="forgotEmail" required>
                        </div>
                        <div id="forgotError" class="text-danger" style="display: none;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="resetPassword()">Reset Password</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="scripts.js"></script>
</body>
</html>