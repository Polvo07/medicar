<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'ela_simple';
    private $username = 'root';
    private $password = '';
    private ?PDO $conn = null;

    public function getConnection(): ?PDO {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log('Database connection error: ' . $exception->getMessage());
            http_response_code(500);
            exit('No fue posible conectar con la base de datos. Intenta de nuevo más tarde.');
        }
        return $this->conn;
    }
}

function formatearFecha(string $fecha): string {
    return date('d/m/Y', strtotime($fecha));
}

function formatearFechaHora(string $fechaHora): string {
    return date('d/m/Y H:i', strtotime($fechaHora));
}
?>