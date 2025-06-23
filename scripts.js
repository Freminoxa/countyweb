// Global variables
let projects = [];
let currentDepartment = null;
let isAdmin = false;

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    fetchProjects();
    setDateConstraints();
});

// Check authentication status
function checkAuth() {
    fetch('api/auth/check_auth.php')
        .then(response => response.json())
        .then(data => {
            if (data.isLoggedIn) {
                isAdmin = data.role === 'admin';
                renderProjects();
                renderDepartmentCards();
                new bootstrap.Tab(document.getElementById('all-tab')).show();
            } else {
                showLoginModal();
            }
        })
        .catch(error => console.error('Error checking auth:', error));
}

// Login function
function login() {
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const errorDiv = document.getElementById('loginError');

    fetch('api/auth/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
    })
        .then(response => response.json())
        .then(data => {
            if (response.ok) {
                isAdmin = data.role === 'admin';
                bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
                document.getElementById('loginForm').reset();
                fetchProjects();
            } else {
                errorDiv.textContent = data.message;
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            errorDiv.textContent = 'Login failed. Please try again.';
            errorDiv.style.display = 'block';
        });
}

// Logout function
function logout() {
    fetch('api/auth/logout.php')
        .then(() => location.reload())
        .catch(error => console.error('Error logging out:', error));
}

// Fetch projects from backend
function fetchProjects() {
    fetch('api/projects/get_projects.php')
        .then(response => response.json())
        .then(data => {
            projects = data.map(project => ({
                id: project.id,
                title: project.title,
                department: project.department,
                status: project.status,
                manager: project.manager,
                startDate: project.start_date,
                endDate: project.end_date,
                description: project.description,
                budget: parseFloat(project.budget) || 0
            }));
            renderProjects();
            renderDepartmentCards();
        })
        .catch(error => console.error('Error fetching projects:', error));
}

// Add new project
function addProject() {
    const form = document.getElementById('projectForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const project = {
        title: document.getElementById('projectTitle').value,
        department: document.getElementById('projectDepartment').value,
        status: document.getElementById('projectStatus').value,
        manager: document.getElementById('projectManager').value,
        startDate: document.getElementById('startDate').value,
        endDate: document.getElementById('endDate').value,
        description: document.getElementById('projectDescription').value,
        budget: parseFloat(document.getElementById('projectBudget').value) || 0
    };

    fetch('api/projects/add_project.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(project)
    })
        .then(response => response.json())
        .then(data => {
            if (response.ok) {
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('addProjectModal')).hide();
                fetchProjects();
            } else {
                alert(data.message);
            }
        })
        .catch(error => alert('Error adding project: ' + error.message));
}

// Edit project
function editProject(id) {
    const project = projects.find(p => p.id == id);
    if (!project) return;

    document.getElementById('editProjectId').value = project.id;
    document.getElementById('editProjectTitle').value = project.title;
    document.getElementById('editProjectDepartment').value = project.department;
    document.getElementById('editProjectStatus').value = project.status;
    document.getElementById('editProjectManager').value = project.manager;
    document.getElementById('editStartDate').value = project.startDate;
    document.getElementById('editEndDate').value = project.endDate || '';
    document.getElementById('editProjectDescription').value = project.description;
    document.getElementById('editProjectBudget').value = project.budget;

    bootstrap.Modal.getInstance(document.getElementById('editProjectModal')).show();
}

// Update project
function updateProject() {
    const form = document.getElementById('editProjectForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const project = {
        id: document.getElementById('editProjectId').value,
        title: document.getElementById('editProjectTitle').value,
        department: document.getElementById('editProjectDepartment').value,
        status: document.getElementById('editProjectStatus').value,
        manager: document.getElementById('editProjectManager').value,
        startDate: document.getElementById('editStartDate').value,
        endDate: document.getElementById('editEndDate').value,
        description: document.getElementById('editProjectDescription').value,
        budget: parseFloat(document.getElementById('editProjectBudget').value) || 0
    };

    fetch('api/projects/edit_project.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(project)
    })
        .then(response => response.json())
        .then(data => {
            if (response.ok) {
                form.reset();
                bootstrap.Modal.getInstance(document.getElementById('editProjectModal')).hide();
                fetchProjects();
            } else {
                alert(data.message);
            }
        })
        .catch(error => alert('Error updating project: ' + error.message));
}

// Delete project
function deleteProject(id) {
    if (!confirm('Are you sure you want to delete this project?')) return;

    fetch('api/projects/delete_project.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
        .then(response => response.json())
        .then(data => {
            if (response.ok) {
                fetchProjects();
            } else {
                alert(data.message);
            }
        })
        .catch(error => alert('Error deleting project: ' + error.message));
}

// Create project card HTML
function createProjectCard(project) {
    const statusClass = project.status === 'finished' ? 'finished-card' : 
                       project.status === 'proposed' ? 'proposed-card' : 'unfinished-card';
    const statusBadgeClass = project.status === 'finished' ? 'badge-finished' : 
                           project.status === 'proposed' ? 'badge-proposed' : 'badge-unfinished';
    const endDateDisplay = project.endDate ? new Date(project.endDate).toLocaleDateString() : 'TBD';
    const budgetDisplay = project.budget ? `KSh ${project.budget.toLocaleString()}` : 'Not specified';

    return `
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card project-card ${statusClass}" style="position: relative;">
                ${isAdmin ? `<button class="delete-btn" onclick="deleteProject(${project.id})" title="Delete Project">
                    <i class="fas fa-trash"></i>
                </button>
                <button class="edit-btn" style="position: absolute; top: 15px; right: 50px; width: 30px; height: 30px; border-radius: 50%; background: rgba(52, 152, 219, 0.1); border: none; color: #3498db; display: flex; align-items: center; justify-content: center;" onclick="editProject(${project.id})" title="Edit Project">
                    <i class="fas fa-edit"></i>
                </button>` : ''}
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">${project.title}</h6>
                    <span class="status-badge ${statusBadgeClass}">${project.status}</span>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="department-badge">${project.department}</span>
                    </div>
                    <p class="card-text mb-2">${project.description}</p>
                    <div class="small text-muted">
                        <div class="mb-1"><i class="fas fa-user me-2"></i><strong>Manager:</strong> ${project.manager}</div>
                        <div class="mb-1"><i class="fas fa-calendar me-2"></i><strong>Start:</strong> ${new Date(project.startDate).toLocaleDateString()}</div>
                        <div class="mb-1"><i class="fas fa-calendar-check me-2"></i><strong>End:</strong> ${endDateDisplay}</div>
                        <div><i class="fas fa-money-bill me-2"></i><strong>Budget:</strong> ${budgetDisplay}</div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Render projects based on status
function renderProjects() {
    const containers = {
        all: document.getElementById('allProjects'),
        finished: document.getElementById('finishedProjects'),
        proposed: document.getElementById('proposedProjects'),
        unfinished: document.getElementById('unfinishedProjects')
    };

    Object.values(containers).forEach(container => container.innerHTML = '');

    if (projects.length === 0) {
        containers.all.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-folder-open"></i>
                <h4>No Projects Found</h4>
                <p class="text-muted">Start by adding your first project to the system.</p>
            </div>
        `;
        return;
    }

    const sortedProjects = [...projects].sort((a, b) => {
        const statusOrder = { 'unfinished': 0, 'proposed': 1, 'finished': 2 };
        if (statusOrder[a.status] !== statusOrder[b.status]) {
            return statusOrder[a.status] - statusOrder[b.status];
        }
        return new Date(b.startDate) - new Date(a.startDate);
    });

    containers.all.innerHTML = '<div class="row">' + sortedProjects.map(createProjectCard).join('') + '</div>';

    const statusTypes = { finished: 'finished', proposed: 'proposed', unfinished: 'unfinished' };
    Object.entries(statusTypes).forEach(([key, status]) => {
        const filteredProjects = projects.filter(p => p.status === status);
        containers[key].innerHTML = filteredProjects.length === 0
            ? `<div class="empty-state"><i class="fas fa-info-circle"></i><h5>No ${status} projects yet</h5></div>`
            : '<div class="row">' + filteredProjects.map(createProjectCard).join('') + '</div>';
    });
}

// Get unique departments
function getUniqueDepartments() {
    return [...new Set(projects.map(project => project.department))];
}

// Get department statistics
function getDepartmentStats(department) {
    const deptProjects = projects.filter(p => p.department === department);
    return {
        total: deptProjects.length,
        finished: deptProjects.filter(p => p.status === 'finished').length,
        proposed: deptProjects.filter(p => p.status === 'proposed').length,
        unfinished: deptProjects.filter(p => p.status === 'unfinished').length,
        budget: deptProjects.reduce((sum, project) => sum + (project.budget || 0), 0)
    };
}

// Render department cards
function renderDepartmentCards() {
    const departmentCards = document.getElementById('departmentCards');
    const departments = getUniqueDepartments();

    if (departments.length === 0) {
        departmentCards.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-sitemap"></i>
                <h4>No Departments Found</h4>
                <p class="text-muted">Add projects to see departments.</p>
            </div>
        `;
        return;
    }

    departmentCards.innerHTML = departments.map(department => {
        const stats = getDepartmentStats(department);
        return `
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stats-card" onclick="viewDepartment('${department}')">
                    <div class="stats-number">${stats.total}</div>
                    <div class="text-muted">${department}</div>
                    <div class="mt-2 small">
                        <span class="badge bg-success">${stats.finished} Finished</span>
                        <span class="badge bg-warning">${stats.proposed} Proposed</span>
                        <span class="badge bg-danger">${stats.unfinished} Unfinished</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// View department projects
function viewDepartment(department) {
    currentDepartment = department;
    document.getElementById('statusTabs').style.display = 'none';
    document.getElementById('statusTabsContent').style.display = 'none';
    document.getElementById('departmentView').style.display = 'block';
    renderDepartmentProjects(department);
}

// Render projects for a specific department
function renderDepartmentProjects(department) {
    const deptProjects = projects.filter(p => p.department === department);
    const sections = {
        finished: deptProjects.filter(p => p.status === 'finished'),
        proposed: deptProjects.filter(p => p.status === 'proposed'),
        unfinished: deptProjects.filter(p => p.status === 'unfinished')
    };

    let departmentHTML = `
        <div class="department-view">
            <div class="department-view-header">
                <h3><i class="fas fa-sitemap me-2"></i>${department} Department</h3>
                <button class="back-to-home" onclick="backToHome()">
                    <i class="fas fa-arrow-left me-1"></i> Back to Home
                </button>
            </div>
    `;

    const renderSection = (status, projects, title) => {
        return projects.length === 0
            ? `
                <div class="status-section">
                    <div class="status-header">
                        <div class="status-count">0</div>
                        <h4 class="status-title">${title}</h4>
                    </div>
                    <div class="alert alert-info">
                        No ${status} projects for this department
                    </div>
                </div>
            `
            : `
                <div class="status-section">
                    <div class="status-header">
                        <div class="status-count">${projects.length}</div>
                        <h4 class="status-title">${title}</h4>
                    </div>
                    <div class="department-projects-container">
                        ${projects.map(createProjectCard).join('')}
                    </div>
                </div>
            `;
    };

    departmentHTML += renderSection('finished', sections.finished, 'Finished Projects');
    departmentHTML += renderSection('proposed', sections.proposed, 'Proposed Projects');
    departmentHTML += renderSection('unfinished', sections.unfinished, 'Unfinished Projects');
    departmentHTML += '</div>';

    document.getElementById('departmentView').innerHTML = departmentHTML;
}

// Go back to home view
function backToHome() {
    currentDepartment = null;
    document.getElementById('statusTabs').style.display = 'flex';
    document.getElementById('statusTabsContent').style.display = 'block';
    document.getElementById('departmentView').style.display = 'none';
    new bootstrap.Tab(document.getElementById('all-tab')).show();
}

// Set date constraints
function setDateConstraints() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('startDate').min = today;
    document.getElementById('endDate').min = today;
    document.getElementById('editStartDate').min = today;
    document.getElementById('editEndDate').min = today;

    document.getElementById('startDate').addEventListener('change', function() {
        document.getElementById('endDate').min = this.value;
    });
    document.getElementById('editStartDate').addEventListener('change', function() {
        document.getElementById('editEndDate').min = this.value;
    });
}

// Show login modal if not logged in
function showLoginModal() {
    bootstrap.Modal.getInstance(document.getElementById('loginModal')).show();
}

// Reset Password
function resetPassword() {
    const email = document.getElementById('forgotEmail').value;
    if (!email) {
        document.getElementById('forgotError').textContent = 'Email is required';
        document.getElementById('forgotError').style.display = 'block';
        return;
    }

    fetch('api/auth/reset_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        const errorDiv = document.getElementById('forgotError');
        if (response.ok) {
            errorDiv.style.display = 'none';
            alert(data.message);
            bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal')).hide();
        } else {
            errorDiv.textContent = data.message;
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        document.getElementById('forgotError').textContent = 'Reset failed. Please try again.';
        document.getElementById('forgotError').style.display = 'block';
    });
}
// Register new user
function registerUser() {
    const form = document.getElementById('registerForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const user = {
        fullName: document.getElementById('fullName').value,
        department: document.getElementById('department').value,
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
        confirmPassword: document.getElementById('confirmPassword').value
    };

    fetch('api/auth/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(user)
    })
    .then(response => response.json())
    .then(data => {
        const errorDiv = document.getElementById('registerError');
        if (response.ok) {
            errorDiv.style.display = 'none';
            bootstrap.Modal.getInstance(document.getElementById('registerModal')).hide();
            form.reset();
            alert('User registered successfully');
        } else {
            errorDiv.textContent = data.message;
            errorDiv.style.display = 'block';
        }
    })
    .catch(error => {
        document.getElementById('registerError').textContent = 'Registration failed. Please try again.';
        document.getElementById('registerError').style.display = 'block';
    });
}