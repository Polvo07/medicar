<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (empty($username) || empty($password) || empty($role)) {
        $error = 'Todos los campos son requeridos.';
    } else {
        $database = new Database();
        $conn = $database->getConnection();

        $query = "SELECT id, username, contrasena, role, paciente_id FROM usuarios WHERE username = :username AND role = :role";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':role', $role);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $user['contrasena'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                if ($user['role'] === 'paciente') {
                    $_SESSION['paciente_id'] = $user['paciente_id'];
                    header('Location: dashboard_paciente.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Contraseña incorrecta.';
            }
        } else {
            $error = 'Usuario no encontrado o rol incorrecto.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Inicia sesión en Medicar, la plataforma clínica para el seguimiento de pacientes con ELA.">
    <link rel="icon" type="image/png" href="image/Logo.png">
    <title>Iniciar sesión · Medicar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-layout">
    <div class="auth-wrapper--login">
        <div class="auth-brand">
            <div class="auth-logo">MEDICAR<span>+</span></div>
            <p>Plataforma clínica para coordinar tratamientos, terapias y evaluaciones entre médicos y pacientes.</p>
            <ul class="auth-brand-list">
                <li>Recordatorios y planes personalizados</li>
                <li>Seguimiento diario de síntomas</li>
                <li>Alertas de medicación y citas</li>
            </ul>
        </div>
        <div class="auth-card auth-card--login">
            <header>
                <h1>Inicia sesión</h1>
                <p>Accede con tu usuario asignado para continuar con el seguimiento.</p>
            </header>
            <?php if ($error): ?>
                <div class="mensaje-estado error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Usuario (Cédula para pacientes)</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="role">Rol</label>
                    <select id="role" name="role" required>
                        <option value="">Seleccionar</option>
                        <option value="paciente">Paciente</option>
                        <option value="medico">Médico</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Ingresar</button>
            </form>
            <p class="footnote">Al continuar aceptas los Términos y Condiciones de uso de la plataforma. <br> ¿Necesitas ayuda? Contacta al administrador del sistema.</p>
        </div>
    </div>
</body>
</html>
