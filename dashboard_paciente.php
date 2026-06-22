<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'paciente') {
    header('Location: login.php');
    exit;
}
require_once 'config.php';

$database = new Database();
$conn = $database->getConnection();
$pacienteId = $_SESSION['paciente_id'];

$stmt = $conn->prepare("SELECT nombre, apellidos FROM pacientes WHERE id = :id");
$stmt->bindParam(':id', $pacienteId);
$stmt->execute();
$paciente = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Medicar: panel del paciente para seguir tratamientos, terapias y evaluaciones diarias.">
    <link rel="icon" type="image/png" href="image/Logo.png">
    <title>Panel Paciente · Medicar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-landing">
    <?php
    $activePage = 'inicio';
    $isPaciente = true;
    $homeHref = 'dashboard_paciente.php';
    require 'includes/header.php';
    ?>
    <main class="dashboard-main">
        <section class="hero">
            <div class="hero-illustration">
                <img src="image/Paciente Doctor.avif" alt="Paciente y doctor revisando la salud">
            </div>
            <div class="hero-content">
                <div class="brand-badge">Panel del Paciente</div>
                <h1>Hola <?php echo htmlspecialchars($paciente['nombre']); ?>, cuida tu salud con <span>Medicar</span></h1>
                <p>Consulta tus recordatorios de medicación, mantén al día tus terapias y registra síntomas para compartir con tu equipo médico.</p>
                <div class="hero-actions">
                    <a href="medicamentos.php" class="btn btn-primary">Ver mis medicamentos</a>
                    <a href="evaluaciones.php" class="btn btn-outline">Registrar evaluación</a>
                </div>
                <div class="hero-footer-links"></div>
            </div>
        </section>
        <section class="quick-actions">
            <div class="section-heading">
                <h2>Tus accesos principales</h2>
                <p>Accede rápidamente a las herramientas que te ayudan a seguir tu tratamiento y mantener informado a tu médico.</p>
            </div>
            <div class="modules">
                <a href="medicamentos.php" class="module-link" data-module="medicamentos">
                    <figure class="module-figure">
                        <img src="image/Medicamentos.jpg" alt="Mis medicamentos">
                    </figure>
                    <strong>Mis Medicamentos</strong>
                    <span>Revisa tus dosis, recordatorios y recomendaciones médicas.</span>
                    <span class="btn btn-secondary">Ver tratamientos</span>
                </a>
                <a href="terapias.php" class="module-link" data-module="terapias">
                    <figure class="module-figure">
                        <img src="image/Terapia.webp" alt="Mis terapias">
                    </figure>
                    <strong>Mis Terapias</strong>
                    <span>Consulta próximas sesiones y notas del equipo terapéutico.</span>
                    <span class="btn btn-secondary">Ver terapias</span>
                </a>
                <a href="evaluaciones.php" class="module-link" data-module="evaluaciones">
                    <figure class="module-figure">
                        <img src="image/medical-evaluation.webp" alt="Mis evaluaciones">
                    </figure>
                    <strong>Mis Evaluaciones</strong>
                    <span>Registra tus síntomas y monitorea tu evolución diaria.</span>
                    <span class="btn btn-secondary">Registrar ahora</span>
                </a>
            </div>
        </section>
    </main>
    <?php require 'includes/footer.php'; ?>
</body>
</html>