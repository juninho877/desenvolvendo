<?php
session_start();
if (!isset($_SESSION["usuario"]) || $_SESSION["role"] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'classes/User.php';

$user = new User();
$users = $user->getAllUsers();
$stats = $user->getUserStats();

// Processar ações AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'change_status':
            $result = $user->changeStatus($_POST['user_id'], $_POST['status']);
            echo json_encode($result);
            exit;
            
        case 'delete_user':
            $result = $user->deleteUser($_POST['user_id']);
            echo json_encode($result);
            exit;
    }
}

$pageTitle = "Gerenciamento de Usuários";
include "includes/header.php";
?>

<div class="page-header">
    <h1 class="page-title">
        <i class="fas fa-users text-primary-500 mr-3"></i>
        Gerenciamento de Usuários
    </h1>
    <p class="page-subtitle">Controle completo dos usuários do sistema</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Total de Usuários</p>
                    <p class="text-2xl font-bold text-primary"><?php echo $stats['total']; ?></p>
                </div>
                <div class="w-12 h-12 bg-primary-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-primary-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Usuários Ativos</p>
                    <p class="text-2xl font-bold text-success-500"><?php echo $stats['active']; ?></p>
                </div>
                <div class="w-12 h-12 bg-success-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-check text-success-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Usuários Inativos</p>
                    <p class="text-2xl font-bold text-danger-500"><?php echo $stats['inactive']; ?></p>
                </div>
                <div class="w-12 h-12 bg-danger-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-times text-danger-500"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-muted">Administradores</p>
                    <p class="text-2xl font-bold text-warning-500"><?php echo $stats['admins']; ?></p>
                </div>
                <div class="w-12 h-12 bg-warning-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-shield text-warning-500"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Actions Bar -->
<div class="flex justify-between items-center mb-6">
    <div class="flex gap-3">
        <button id="refreshBtn" class="btn btn-secondary">
            <i class="fas fa-sync-alt"></i>
            Atualizar
        </button>
    </div>
    <a href="add_user.php" class="btn btn-primary">
        <i class="fas fa-plus"></i>
        Adicionar Usuário
    </a>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Lista de Usuários</h3>
        <p class="card-subtitle">Gerencie todos os usuários do sistema</p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Email</th>
                        <th>Função</th>
                        <th>Status</th>
                        <th>Expira em</th>
                        <th>Último Login</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $userData): ?>
                        <tr data-user-id="<?php echo $userData['id']; ?>">
                            <td><?php echo $userData['id']; ?></td>
                            <td>
                                <div class="user-info">
                                    <div class="user-avatar-small">
                                        <?php echo strtoupper(substr($userData['username'], 0, 2)); ?>
                                    </div>
                                    <span class="font-medium"><?php echo htmlspecialchars($userData['username']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($userData['email'] ?? '-'); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $userData['role']; ?>">
                                    <?php echo $userData['role'] === 'admin' ? 'Administrador' : 'Usuário'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $userData['status']; ?>">
                                    <?php echo $userData['status'] === 'active' ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($userData['expires_at']) {
                                    $expiresAt = new DateTime($userData['expires_at']);
                                    $now = new DateTime();
                                    $isExpired = $expiresAt < $now;
                                    echo '<span class="' . ($isExpired ? 'text-danger-500' : 'text-muted') . '">';
                                    echo $expiresAt->format('d/m/Y');
                                    echo '</span>';
                                } else {
                                    echo '<span class="text-muted">Nunca</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                if ($userData['last_login']) {
                                    $lastLogin = new DateTime($userData['last_login']);
                                    echo $lastLogin->format('d/m/Y H:i');
                                } else {
                                    echo '<span class="text-muted">Nunca</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_user.php?id=<?php echo $userData['id']; ?>" class="btn-action btn-edit" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if ($userData['status'] === 'active'): ?>
                                        <button class="btn-action btn-warning toggle-status" data-user-id="<?php echo $userData['id']; ?>" data-status="inactive" title="Desativar">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn-action btn-success toggle-status" data-user-id="<?php echo $userData['id']; ?>" data-status="active" title="Ativar">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($userData['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn-action btn-danger delete-user" data-user-id="<?php echo $userData['id']; ?>" data-username="<?php echo htmlspecialchars($userData['username']); ?>" title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .table-responsive {
        overflow-x: auto;
    }

    .users-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }

    .users-table th,
    .users-table td {
        padding: 1rem 0.75rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }

    .users-table th {
        background: var(--bg-secondary);
        font-weight: 600;
        color: var(--text-primary);
    }

    .users-table tbody tr:hover {
        background: var(--bg-secondary);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .user-avatar-small {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, var(--primary-500), var(--primary-600));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .role-badge,
    .status-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .role-admin {
        background: var(--warning-50);
        color: var(--warning-600);
    }

    .role-user {
        background: var(--primary-50);
        color: var(--primary-600);
    }

    .status-active {
        background: var(--success-50);
        color: var(--success-600);
    }

    .status-inactive {
        background: var(--danger-50);
        color: var(--danger-600);
    }

    .action-buttons {
        display: flex;
        gap: 0.5rem;
    }

    .btn-action {
        width: 32px;
        height: 32px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        text-decoration: none;
    }

    .btn-edit {
        background: var(--primary-50);
        color: var(--primary-600);
    }

    .btn-edit:hover {
        background: var(--primary-100);
    }

    .btn-success {
        background: var(--success-50);
        color: var(--success-600);
    }

    .btn-success:hover {
        background: var(--success-100);
    }

    .btn-warning {
        background: var(--warning-50);
        color: var(--warning-600);
    }

    .btn-warning:hover {
        background: var(--warning-100);
    }

    .btn-danger {
        background: var(--danger-50);
        color: var(--danger-600);
    }

    .btn-danger:hover {
        background: var(--danger-100);
    }

    /* Dark theme adjustments */
    [data-theme="dark"] .role-admin {
        background: rgba(245, 158, 11, 0.1);
        color: var(--warning-400);
    }

    [data-theme="dark"] .role-user {
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary-400);
    }

    [data-theme="dark"] .status-active {
        background: rgba(34, 197, 94, 0.1);
        color: var(--success-400);
    }

    [data-theme="dark"] .status-inactive {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger-400);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle Status
    document.querySelectorAll('.toggle-status').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const newStatus = this.getAttribute('data-status');
            const statusText = newStatus === 'active' ? 'ativar' : 'desativar';
            
            Swal.fire({
                title: 'Confirmar Ação',
                text: `Deseja ${statusText} este usuário?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sim, ' + statusText,
                cancelButtonText: 'Cancelar',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            }).then((result) => {
                if (result.isConfirmed) {
                    changeUserStatus(userId, newStatus);
                }
            });
        });
    });

    // Delete User
    document.querySelectorAll('.delete-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const username = this.getAttribute('data-username');
            
            Swal.fire({
                title: 'Excluir Usuário',
                text: `Tem certeza que deseja excluir o usuário "${username}"? Esta ação não pode ser desfeita.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, excluir',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#ef4444',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            }).then((result) => {
                if (result.isConfirmed) {
                    deleteUser(userId);
                }
            });
        });
    });

    // Refresh Button
    document.getElementById('refreshBtn').addEventListener('click', function() {
        location.reload();
    });

    function changeUserStatus(userId, status) {
        fetch('user_management.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=change_status&user_id=${userId}&status=${status}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Sucesso!',
                    text: data.message,
                    icon: 'success',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Erro!',
                    text: data.message,
                    icon: 'error',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Erro!',
                text: 'Erro de comunicação com o servidor',
                icon: 'error',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            });
        });
    }

    function deleteUser(userId) {
        fetch('user_management.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete_user&user_id=${userId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Sucesso!',
                    text: data.message,
                    icon: 'success',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Erro!',
                    text: data.message,
                    icon: 'error',
                    background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Erro!',
                text: 'Erro de comunicação com o servidor',
                icon: 'error',
                background: document.body.getAttribute('data-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: document.body.getAttribute('data-theme') === 'dark' ? '#f1f5f9' : '#1e293b'
            });
        });
    }
});
</script>

<?php include "includes/footer.php"; ?>