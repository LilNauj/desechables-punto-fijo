<?php
// Mostrar errores temporalmente para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '6Xx3tLr93N57Cfh8');
define('DB_NAME', 'desechables_punto_fijo');

// Configuración de la aplicación
define('SITE_URL', 'http://localhost/desechables_punto_fijo');
define('SITE_NAME', 'Desechables Punto Fijo');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Conexión a la base de datos
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Error de conexión: " . $this->conn->connect_error);
            }
            
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die("Error al conectar con la base de datos: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    private function __clone() {}
}

function getDB() {
    return Database::getInstance()->getConnection();
}

function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function estaLogueado() {
    return isset($_SESSION['usuario_id']);
}

function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function requerirLogin() {
    if (!estaLogueado()) {
        redirect('login.php');
    }
}

function requerirAdmin() {
    if (!esAdmin()) {
        redirect('index.php');
    }
}
?>