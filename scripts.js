    //scripts.js
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

    // Fixed login function
    function login() {
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const errorDiv = document.getElementById('loginError');

        fetch('api/auth/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        })
        .then(response => {
            // Store response for later use
            const isOk = response.ok;
            return response.json().then(data => ({ data, isOk }));
        })
        .then(({ data, isOk }) => {
            if (isOk) {
                isAdmin = data.role === 'admin';
                bootstrap.Modal.getInstance(document.getElementById('loginModal')).hide();
                document.getElementById('loginForm').reset();
                errorDiv.style.display = 'none'; // Hide error on success
                fetchProjects();
            } else {
                errorDiv.textContent = data.message;
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Login error:', error);
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
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (!data.success) {
                    throw new Error(data.error || 'Failed to fetch projects');
                }
                
                projects = data.data.map(project => ({
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
            .catch(error => {
                console.error('Error fetching projects:', error);
                // Optionally show error to user
                alert('Failed to load projects. Please try again later.');
            });
    }

    // Add new project
    function addProject() {
        const form = document.getElementById('projectForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
    
        const project = {
            title: document.getElementById('projectTitle').value.trim(),
            department: document.getElementById('projectDepartment').value.trim(),
            status: document.getElementById('projectStatus').value.trim(),
            manager: document.getElementById('projectManager').value.trim(),
            startDate: document.getElementById('startDate').value,
            endDate: document.getElementById('endDate').value || null,
            description: document.getElementById('projectDescription').value.trim(),
            budget: parseFloat(document.getElementById('projectBudget').value) || 0
        };
    
        // Client-side validation
        if (!project.title || !project.department || !project.manager || !project.description) {
            alert('Please fill in all required fields');
            return;
        }
    
        // Disable submit button
        const submitBtn = document.querySelector('#addProjectModal .btn-primary');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Adding...';
    
        // Add credentials to include cookies
        fetch('api/projects/add_project.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json' // Explicitly ask for JSON
            },
            credentials: 'include', // Important for session cookies
            body: JSON.stringify(project)
        })
        .then(response => {
            console.log('Response status:', response.status); // Debug status
            console.log('Response headers:', [...response.headers.entries()]); // Debug headers
            
            // First get the response as text to inspect it
            return response.text().then(text => {
                console.log('Raw response text:', text); // Debug raw response
                
                try {
                    // Try to parse as JSON
                    const data = JSON.parse(text);
                    return { data, status: response.status };
                } catch (e) {
                    // If parsing fails, throw an error with the actual response
                    throw new Error(`Invalid JSON received (Status: ${response.status}). Response: ${text.substring(0, 100)}...`);
                }
            });
        })
        .then(({ data, status }) => {
            if (status >= 200 && status < 300) {
                form.reset();
                const modal = bootstrap.Modal.getInstance(document.getElementById('addProjectModal'));
                if (modal) modal.hide();
                fetchProjects();
                showNotification('Project added successfully!', 'success');
            } else {
                showNotification(data.message || `Failed to add project (Status: ${status})`, 'error');
            }
        })
        .catch(error => {
            console.error('Full error details:', error);
            showNotification(error.message || 'Network error. Please check console for details.', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
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
            title: document.getElementById('editProjectTitle').value.trim(),
            department: document.getElementById('editProjectDepartment').value.trim(),
            status: document.getElementById('editProjectStatus').value.trim(),
            manager: document.getElementById('editProjectManager').value.trim(),
            startDate: document.getElementById('editStartDate').value,
            endDate: document.getElementById('editEndDate').value || null,
            description: document.getElementById('editProjectDescription').value.trim(),
            budget: parseFloat(document.getElementById('editProjectBudget').value) || 0
        };
    
        // Client-side validation
        if (!project.title || !project.department || !project.manager || !project.description) {
            alert('Please fill in all required fields');
            return;
        }
    
        // Disable submit button
        const submitBtn = document.querySelector('#editProjectModal .btn-primary');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Updating...';
    
        fetch('api/projects/edit_project.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(project)
        })
        .then(response => {
            const isOk = response.ok;
            return response.json().then(data => ({ data, isOk }));
        })
        .then(({ data, isOk }) => {
            if (isOk) {
                form.reset();
                const modal = bootstrap.Modal.getInstance(document.getElementById('editProjectModal'));
                if (modal) modal.hide();
                fetchProjects();
                
                showNotification('Project updated successfully!', 'success');
            } else {
                showNotification(data.message || 'Failed to update project', 'error');
            }
        })
        .catch(error => {
            console.error('Error updating project:', error);
            showNotification('Network error. Please check your connection and try again.', 'error');
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
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
        console.log('Filtered department projects:', deptProjects); // Debug filtered projects
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

   

    function addAdmin() {
        const form = document.getElementById('addAdminForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
    
        const admin = {
            fullName: document.getElementById('adminFullName').value.trim(),
            department: document.getElementById('adminDepartment').value.trim(),
            email: document.getElementById('adminEmail').value.trim(),
            password: document.getElementById('adminPassword').value,
            confirmPassword: document.getElementById('adminConfirmPassword').value
        };
    
        // Client-side validation
        const errorDiv = document.getElementById('addAdminError');
        
        if (!admin.fullName || !admin.department || !admin.email || !admin.password) {
            errorDiv.textContent = 'Please fill in all required fields';
            errorDiv.style.display = 'block';
            return;
        }
    
        if (admin.password.length < 6) {
            errorDiv.textContent = 'Password must be at least 6 characters long';
            errorDiv.style.display = 'block';
            return;
        }
    
        if (admin.password !== admin.confirmPassword) {
            errorDiv.textContent = 'Passwords do not match';
            errorDiv.style.display = 'block';
            return;
        }
    
        // Email validation
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(admin.email)) {
            errorDiv.textContent = 'Please enter a valid email address';
            errorDiv.style.display = 'block';
            return;
        }
    
        // Hide error div and disable submit button
        errorDiv.style.display = 'none';
        const submitBtn = document.querySelector('#addAdminModal .btn-primary');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Adding...';
    
        fetch('api/auth/add_admin.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(admin)
        })
        .then(response => {
            // Store response status before parsing JSON
            const isOk = response.ok;
            return response.json().then(data => ({ data, isOk }));
        })
        .then(({ data, isOk }) => {
            if (isOk) {
                errorDiv.style.display = 'none';
                const modal = bootstrap.Modal.getInstance(document.getElementById('addAdminModal'));
                if (modal) modal.hide();
                form.reset();
                
                showNotification('Admin added successfully!', 'success');
            } else {
                errorDiv.textContent = data.message || 'Failed to add admin';
                errorDiv.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error adding admin:', error);
            errorDiv.textContent = 'Network error. Please check your connection and try again.';
            errorDiv.style.display = 'block';
        })
        .finally(() => {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
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
        fullName: document.getElementById('fullName').value.trim(),
        department: document.getElementById('department').value.trim(),
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value.trim(),
        confirmPassword: document.getElementById('confirmPassword').value.trim()
    };

    // Additional validation
    if (user.password.length < 6) { // Minimum length for security
        document.getElementById('registerError').textContent = 'Password must be at least 6 characters.';
        document.getElementById('registerError').style.display = 'block';
        return;
    }
    if (user.password !== user.confirmPassword) {
        document.getElementById('registerError').textContent = 'Passwords do not match.';
        document.getElementById('registerError').style.display = 'block';
        return;
    }

    console.log('Sending user data:', JSON.stringify(user)); // Debug log

    fetch('api/auth/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(user)
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug status
        return response.json().then(data => ({ data, isOk: response.ok }));
    })
    .then(({ data, isOk }) => {
        const errorDiv = document.getElementById('registerError');
        if (isOk) {
            errorDiv.style.display = 'none';
            bootstrap.Modal.getInstance(document.getElementById('registerModal')).hide();
            form.reset();
            alert('User registered successfully');
        } else {
            errorDiv.textContent = data.message;
            errorDiv.style.display = 'block';
            console.log('Error data:', data); // Debug error
        }
    })
    .catch(error => {
        document.getElementById('registerError').textContent = 'Registration failed. Please try again.';
        document.getElementById('registerError').style.display = 'block';
        console.error('Fetch error:', error); // Debug catch
    });
}

function showNotification(message, type = 'info') {
    // Create notification element if it doesn't exist
    let notificationContainer = document.getElementById('notificationContainer');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'notificationContainer';
        notificationContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 300px;
        `;
        document.body.appendChild(notificationContainer);
    }

    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';
    
    notification.className = `alert ${bgColor} text-white alert-dismissible fade show`;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    `;

    notificationContainer.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}
