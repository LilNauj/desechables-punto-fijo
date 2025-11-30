<?php
/**
 * SCRIPT PARA ACTUALIZAR CONTRASEÑAS
 * Ejecutar UNA SOLA VEZ y luego BORRAR este archivo
 */

require_once 'config/config.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Actualizar Contraseñas</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container mt-5'>
    <div class='card'>
        <div class='card-header bg-primary text-white'>
            <h4 class='mb-0'>🔧 Actualización de Contraseñas</h4>
        </div>
        <div class='card-body'>";

$db = getDB();

// Contraseña que queremos usar
$nueva_password = 'admin123';
$password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);

echo "<div class='alert alert-info'>
        <strong>Contraseña a establecer:</strong> admin123<br>
        <strong>Hash generado:</strong> " . htmlspecialchars($password_hash) . "
      </div>";

// Actualizar admin
$stmt1 = $db->prepare("UPDATE usuarios SET password = ? WHERE email = 'admin@puntofijo.com'");
$stmt1->bind_param("s", $password_hash);

if ($stmt1->execute()) {
    echo "<div class='alert alert-success'>
            ✅ <strong>Admin actualizado correctamente</strong><br>
            Email: admin@puntofijo.com<br>
            Password: admin123
          </div>";
} else {
    echo "<div class='alert alert-danger'>
            ❌ Error al actualizar admin: " . $stmt1->error . "
          </div>";
}

// Actualizar cliente demo
$stmt2 = $db->prepare("UPDATE usuarios SET password = ? WHERE email = 'cliente@demo.com'");
$stmt2->bind_param("s", $password_hash);

if ($stmt2->execute()) {
    echo "<div class='alert alert-success'>
            ✅ <strong>Cliente demo actualizado correctamente</strong><br>
            Email: cliente@demo.com<br>
            Password: admin123
          </div>";
} else {
    echo "<div class='alert alert-danger'>
            ❌ Error al actualizar cliente: " . $stmt2->error . "
          </div>";
}

$stmt1->close();
$stmt2->close();

echo "<hr>
      <div class='alert alert-warning'>
        <strong>⚠️ IMPORTANTE:</strong> 
        <ol>
            <li>Ahora puedes iniciar sesión con las credenciales actualizadas</li>
            <li><strong>BORRA ESTE ARCHIVO (actualizar_passwords.php) por seguridad</strong></li>
        </ol>
      </div>
      
      <div class='d-grid gap-2'>
        <a href='login.php' class='btn btn-primary btn-lg'>
            🔐 Ir al Login
        </a>
        <a href='index.php' class='btn btn-secondary'>
            🏠 Ir al Inicio
        </a>
      </div>
      
      </div>
    </div>
</div>
</body>
</html>";
?>