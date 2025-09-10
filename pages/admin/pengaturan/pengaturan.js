// Pengaturan JavaScript
class PengaturanComponent {
    constructor() {
        this.init();
    }
    
    init() {
        console.log('🔧 Initializing Pengaturan Component');
        this.bindEvents();
    }
    
    bindEvents() {
        // Change password form
        const changePasswordForm = document.getElementById('changePasswordForm');
        if (changePasswordForm) {
            changePasswordForm.addEventListener('submit', this.handleChangePassword.bind(this));
        }
        
        // Create user form
        const createUserForm = document.getElementById('createUserForm');
        if (createUserForm) {
            createUserForm.addEventListener('submit', this.handleCreateUser.bind(this));
        }
    }
    
    async handleChangePassword(e) {
        e.preventDefault();
        
        const oldPassword = document.getElementById('oldPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (newPassword !== confirmPassword) {
            alert('Password baru dan konfirmasi password tidak sama!');
            return;
        }
        
        if (newPassword.length < 6) {
            alert('Password baru minimal 6 karakter!');
            return;
        }
        
        try {
            const response = await fetch(window.BASE_URL + 'pages/admin/pengaturan/change-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    old_password: oldPassword,
                    new_password: newPassword
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Password berhasil diubah!');
                document.getElementById('changePasswordForm').reset();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            alert('Terjadi kesalahan: ' + error.message);
        }
    }
    
    async handleCreateUser(e) {
        e.preventDefault();
        // Handled by createUser() function
    }
}

// Global functions
function showTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Add active to clicked button
    event.target.classList.add('active');
}

function toggleFaq(element) {
    const answer = element.nextElementSibling;
    const icon = element.querySelector('i');
    
    if (answer.classList.contains('show')) {
        answer.classList.remove('show');
        element.classList.remove('active');
    } else {
        // Close all other FAQs
        document.querySelectorAll('.faq-answer').forEach(ans => {
            ans.classList.remove('show');
        });
        document.querySelectorAll('.faq-question').forEach(q => {
            q.classList.remove('active');
        });
        
        // Open clicked FAQ
        answer.classList.add('show');
        element.classList.add('active');
    }
}

function showCreateUserModal() {
    document.getElementById('createUserModal').classList.add('show');
}

function closeCreateUserModal() {
    document.getElementById('createUserModal').classList.remove('show');
    document.getElementById('createUserForm').reset();
}

async function createUser() {
    const namaLengkap = document.getElementById('createNamaLengkap').value;
    const username = document.getElementById('createUsername').value;
    const email = document.getElementById('createEmail').value;
    const password = document.getElementById('createPassword').value;
    const role = document.getElementById('createRole').value;
    const idDesa = document.getElementById('createDesa').value;
    
    if (!namaLengkap || !username || !email || !password || !role) {
        alert('Semua field wajib diisi!');
        return;
    }
    
    if (role === 'operator_desa' && !idDesa) {
        alert('Pilih desa untuk operator desa!');
        return;
    }
    
    try {
        const response = await fetch(window.BASE_URL + 'pages/admin/pengaturan/create-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                nama_lengkap: namaLengkap,
                username: username,
                email: email,
                password: password,
                role: role,
                id_desa: idDesa || null
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('User berhasil dibuat!');
            closeCreateUserModal();
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    }
}

async function deleteUser(userId, userName) {
    if (!confirm(`Yakin ingin menghapus user "${userName}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(window.BASE_URL + 'pages/admin/pengaturan/delete-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: userId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('User berhasil dihapus!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Terjadi kesalahan: ' + error.message);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.pengaturan-container')) {
        window.pengaturanComponent = new PengaturanComponent();
    }
});

// Also initialize if DOM is already loaded
if (document.readyState !== 'loading' && document.querySelector('.pengaturan-container')) {
    window.pengaturanComponent = new PengaturanComponent();
}