<?php
session_start();
require_once 'config.php'; // Arquivo com configurações do banco de dados

class Auth {
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Registra um novo usuário no sistema
     * @param string $username Nome de usuário
     * @param string $email Email do usuário
     * @param string $password Senha do usuário
     * @return array Resultado da operação
     */
    public function register($username, $email, $password) {
        // Verificar se o usuário já existe
        $stmt = $this->conn->prepare("SELECT id FROM usuarios WHERE email = ? OR username = ? LIMIT 1");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return ["success" => false, "message" => "Email ou nome de usuário já está em uso."];
        }
        
        // Hash da senha
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Inserir o novo usuário
        $stmt = $this->conn->prepare("INSERT INTO usuarios (username, email, password, data_criacao) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $username, $email, $hashed_password);
        
        if ($stmt->execute()) {
            return ["success" => true, "message" => "Usuário registrado com sucesso!"];
        } else {
            return ["success" => false, "message" => "Erro ao registrar usuário: " . $stmt->error];
        }
    }
    
    /**
     * Realiza o login do usuário
     * @param string $email Email do usuário
     * @param string $password Senha do usuário
     * @return array Resultado da operação
     */
    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT id, username, password, nivel_acesso FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Login bem-sucedido - criar sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
                $_SESSION['logado'] = true;
                
                return ["success" => true, "message" => "Login realizado com sucesso!", "user" => $user];
            } else {
                return ["success" => false, "message" => "Senha incorreta!"];
            }
        } else {
            return ["success" => false, "message" => "Usuário não encontrado!"];
        }
    }
    
    /**
     * Verifica se o usuário está logado
     * @return bool true se estiver logado, false caso contrário
     */
    public function estaLogado() {
        return isset($_SESSION['logado']) && $_SESSION['logado'] === true;
    }
    
    /**
     * Verifica se o usuário tem o nível de acesso necessário
     * @param int $nivel Nível mínimo de acesso necessário
     * @return bool true se tiver acesso, false caso contrário
     */
    public function temAcesso($nivel) {
        if (!$this->estaLogado()) {
            return false;
        }
        
        return isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] >= $nivel;
    }
    
    /**
     * Realiza o logout do usuário
     */
    public function logout() {
        // Destruir todas as variáveis da sessão
        $_SESSION = array();
        
        // Destruir a sessão
        session_destroy();
        
        return ["success" => true, "message" => "Logout realizado com sucesso!"];
    }
    
    /**
     * Recupera informações do usuário logado
     * @return array|null Dados do usuário ou null se não estiver logado
     */
    public function getUsuarioAtual() {
        if (!$this->estaLogado()) {
            return null;
        }
        
        $userId = $_SESSION['user_id'];
        $stmt = $this->conn->prepare("SELECT id, username, email, nivel_acesso, data_criacao FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Atualiza a senha do usuário
     * @param int $userId ID do usuário
     * @param string $senhaAtual Senha atual
     * @param string $novaSenha Nova senha
     * @return array Resultado da operação
     */
    public function atualizarSenha($userId, $senhaAtual, $novaSenha) {
        // Verificar senha atual
        $stmt = $this->conn->prepare("SELECT password FROM usuarios WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows !== 1) {
            return ["success" => false, "message" => "Usuário não encontrado!"];
        }
        
        $user = $result->fetch_assoc();
        
        if (!password_verify($senhaAtual, $user['password'])) {
            return ["success" => false, "message" => "Senha atual incorreta!"];
        }
        
        // Atualizar senha
        $hash_nova_senha = password_hash($novaSenha, PASSWORD_DEFAULT);
        $stmt = $this->conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hash_nova_senha, $userId);
        
        if ($stmt->execute()) {
            return ["success" => true, "message" => "Senha atualizada com sucesso!"];
        } else {
            return ["success" => false, "message" => "Erro ao atualizar senha: " . $stmt->error];
        }
    }
}

// Exemplo de como usar a classe Auth
/*
// Incluir o arquivo config.php que tem a conexão com banco de dados
// require_once 'config.php';

// Criar instância da classe Auth
$auth = new Auth($conn);

// Exemplo de registro
// $resultado = $auth->register('usuario_teste', 'teste@email.com', 'senha123');

// Exemplo de login
// $resultado = $auth->login('teste@email.com', 'senha123');

// Verificar se está logado
// if ($auth->estaLogado()) {
//     echo "Usuário está logado!";
// }

// Verificar nível de acesso
// if ($auth->temAcesso(2)) {
//     echo "Usuário tem acesso de administrador!";
// }

// Fazer logout
// $auth->logout();
*/
?>