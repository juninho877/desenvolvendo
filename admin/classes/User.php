<?php
require_once 'config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Autenticar usuário
    public function authenticate($username, $password) {
        $stmt = $this->db->prepare("
            SELECT id, username, password, role, status, expires_at 
            FROM usuarios 
            WHERE username = ? AND status = 'active'
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Verificar se a conta não expirou
            if ($user['expires_at'] && $user['expires_at'] < date('Y-m-d')) {
                return ['success' => false, 'message' => 'Conta expirada'];
            }
            
            // Atualizar último login
            $this->updateLastLogin($user['id']);
            
            return [
                'success' => true, 
                'user' => $user
            ];
        }
        
        return ['success' => false, 'message' => 'Credenciais inválidas'];
    }
    
    // Atualizar último login
    private function updateLastLogin($userId) {
        $stmt = $this->db->prepare("UPDATE usuarios SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    // Listar todos os usuários
    public function getAllUsers() {
        $stmt = $this->db->prepare("
            SELECT id, username, email, role, status, expires_at, created_at, last_login 
            FROM usuarios 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    // Buscar usuário por ID
    public function getUserById($id) {
        $stmt = $this->db->prepare("
            SELECT id, username, email, role, status, expires_at, created_at, last_login 
            FROM usuarios 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Criar novo usuário
    public function createUser($data) {
        try {
            // Verificar se username já existe
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$data['username']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Nome de usuário já existe'];
            }
            
            // Verificar se email já existe (se fornecido)
            if (!empty($data['email'])) {
                $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$data['email']]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Email já está em uso'];
                }
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO usuarios (username, password, email, role, status, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $expiresAt = !empty($data['expires_at']) ? $data['expires_at'] : null;
            
            $stmt->execute([
                $data['username'],
                $hashedPassword,
                $data['email'] ?? null,
                $data['role'] ?? 'user',
                $data['status'] ?? 'active',
                $expiresAt
            ]);
            
            return ['success' => true, 'message' => 'Usuário criado com sucesso'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao criar usuário: ' . $e->getMessage()];
        }
    }
    
    // Atualizar usuário
    public function updateUser($id, $data) {
        try {
            // Verificar se username já existe (exceto para o próprio usuário)
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
            $stmt->execute([$data['username'], $id]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Nome de usuário já existe'];
            }
            
            // Verificar se email já existe (se fornecido e exceto para o próprio usuário)
            if (!empty($data['email'])) {
                $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
                $stmt->execute([$data['email'], $id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Email já está em uso'];
                }
            }
            
            $sql = "UPDATE usuarios SET username = ?, email = ?, role = ?, status = ?, expires_at = ?";
            $params = [
                $data['username'],
                $data['email'] ?? null,
                $data['role'],
                $data['status'],
                !empty($data['expires_at']) ? $data['expires_at'] : null
            ];
            
            // Se uma nova senha foi fornecida
            if (!empty($data['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return ['success' => true, 'message' => 'Usuário atualizado com sucesso'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao atualizar usuário: ' . $e->getMessage()];
        }
    }
    
    // Alterar status do usuário
    public function changeStatus($id, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE usuarios SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            
            $statusText = $status === 'active' ? 'ativado' : 'desativado';
            return ['success' => true, 'message' => "Usuário {$statusText} com sucesso"];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao alterar status: ' . $e->getMessage()];
        }
    }
    
    // Excluir usuário
    public function deleteUser($id) {
        try {
            // Não permitir excluir o próprio usuário
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
                return ['success' => false, 'message' => 'Você não pode excluir sua própria conta'];
            }
            
            $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            
            return ['success' => true, 'message' => 'Usuário excluído com sucesso'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Erro ao excluir usuário: ' . $e->getMessage()];
        }
    }
    
    // Contar usuários por status
    public function getUserStats() {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins
            FROM usuarios
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>