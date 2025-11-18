<?php
require_once 'config/database.php';

class PasswordReset {
    private $conn;
    private $table_name = "password_resets";

    public $id;
    public $user_id;
    public $token_hash;
    public $expires_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createResetToken($user_id) {
        // Generate secure token
        $token = bin2hex(random_bytes(32));
        $this->token_hash = hash('sha256', $token);
        $this->user_id = $user_id;
        $this->expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // Delete any existing tokens for this user
        $this->deleteUserTokens($user_id);

        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, token_hash=:token_hash, expires_at=:expires_at";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":token_hash", $this->token_hash);
        $stmt->bindParam(":expires_at", $this->expires_at);

        if ($stmt->execute()) {
            return $token; // Return the plain token for email
        }
        return false;
    }

    public function verifyToken($token) {
        $token_hash = hash('sha256', $token);
        
        $query = "SELECT pr.*, u.email, u.username 
                  FROM " . $this->table_name . " pr
                  JOIN users u ON pr.user_id = u.id
                  WHERE pr.token_hash = :token_hash 
                  AND pr.expires_at > NOW()";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token_hash", $token_hash);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->user_id = $row['user_id'];
            return $row;
        }
        return false;
    }

    public function deleteUserTokens($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        return $stmt->execute();
    }

    public function cleanExpiredTokens() {
        $query = "DELETE FROM " . $this->table_name . " WHERE expires_at < NOW()";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute();
    }
}
?>
