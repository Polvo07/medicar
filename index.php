<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'medico') {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Medicar: panel médico para coordinar tratamientos, terapias y evaluaciones de pacientes con ELA.">
    <link rel="icon" type="image/png" href="image/Logo.png">
    <title>Panel Médico · Medicar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="landing-page">
    <?php
    $activePage = 'inicio';
    $isPaciente = false;
    $homeHref = 'index.php';
    require 'includes/header.php';
    ?>
    <main class="landing-main">
        <section class="hero">
            <div class="hero-illustration">
                <img src="image/Paciente Doctor.avif" alt="Profesional médico revisando evaluaciones con paciente">
            </div>
            <div class="hero-content">
                <div class="brand-badge">Panel Médico</div>
                <h1>Cuide la salud de sus pacientes con <span>Medicar</span></h1>
                <p>Organice tratamientos, haga seguimiento a las terapias y registre evaluaciones clínicas con una experiencia centrada en el cuidado.</p>
                <div class="hero-actions">
                    <a href="medicamentos.php" class="btn btn-primary">Gestionar Medicación</a>
                    <a href="evaluaciones.php" class="btn btn-outline">Registrar Evaluación</a>
                </div>
                <div class="hero-footer-links"></div>
            </div>
        </section>
        <section class="quick-actions">
            <div class="section-heading">
                <h2>Accesos rápidos</h2>
                <p>Ingresa directamente a los módulos operativos que usas a diario para mantener el control del tratamiento de cada paciente.</p>
            </div>
            <div class="modules">
            <a href="medicamentos.php" class="module-link" data-module="medicamentos">
                <figure class="module-figure">
                    <img src="image/Medicamentos.jpg" alt="Gestión de medicamentos">
                </figure>
                <strong>Gestión de Medicamentos</strong>
                <span>Asigne, ajuste y supervise las prescripciones activas de sus pacientes.</span>
                <span class="btn btn-secondary">Ver módulo</span>
            </a>
            <a href="terapias.php" class="module-link" data-module="terapias">
                <figure class="module-figure">
                    <img src="image/Terapia.webp" alt="Agenda de terapias">
                </figure>
                <strong>Agenda de Terapias</strong>
                <span>Programe sesiones, registre notas y mantenga el seguimiento terapéutico.</span>
                <span class="btn btn-secondary">Gestionar citas</span>
            </a>
            <a href="evaluaciones.php" class="module-link" data-module="evaluaciones">
                <figure class="module-figure">
                    <img src="image/medical-evaluation.webp" alt="Evaluaciones diarias">
                </figure>
                <strong>Evaluaciones Diarias</strong>
                <span>Capture indicadores clínicos y revise la evolución de cada paciente.</span>
                <span class="btn btn-secondary">Registrar evaluación</span>
            </a>
        </div>
        </section>
    </main>
    <?php require 'includes/footer.php'; ?>
</body>
</html>