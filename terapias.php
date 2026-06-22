<?php
session_start();
if (!isset($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}
require_once 'config.php';
$database = new Database();
$conn = $database->getConnection();

$isPaciente = $_SESSION['role'] === 'paciente';
$pacienteId = $isPaciente ? $_SESSION['paciente_id'] : null;

// Fetch patients
$queryPacientes = $isPaciente ? "SELECT id, nombre, apellidos FROM pacientes WHERE id = :paciente_id" : "SELECT id, nombre, apellidos FROM pacientes";
$stmt = $conn->prepare($queryPacientes);
if ($isPaciente) {
    $stmt->bindParam(':paciente_id', $pacienteId);
}
$stmt->execute();
$pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch terapias
$queryTerapias = $isPaciente ? 
    "SELECT t.id, t.tipo, t.fecha, t.hora, t.notas, p.nombre, p.apellidos 
     FROM terapias t 
     JOIN pacientes p ON t.paciente_id = p.id 
     WHERE t.paciente_id = :paciente_id" :
    "SELECT t.id, t.tipo, t.fecha, t.hora, t.notas, p.nombre, p.apellidos 
     FROM terapias t 
     JOIN pacientes p ON t.paciente_id = p.id";
$stmt = $conn->prepare($queryTerapias);
if ($isPaciente) {
    $stmt->bindParam(':paciente_id', $pacienteId);
}
$stmt->execute();
$terapias = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalTerapias = count($terapias);
$tiposActivos = $totalTerapias > 0 ? count(array_unique(array_map(function ($t) {
    return strtolower($t['tipo']);
}, $terapias))) : 0;

$hoy = new DateTime('today');
$proximas = array_filter($terapias, function ($t) use ($hoy) {
    $fecha = new DateTime($t['fecha'] . ' ' . $t['hora']);
    return $fecha >= $hoy;
});
usort($proximas, function ($a, $b) {
    return strtotime($a['fecha'] . ' ' . $a['hora']) <=> strtotime($b['fecha'] . ' ' . $b['hora']);
});
$proximaTerapia = $proximas ? $proximas[0] : null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isPaciente) {
    $paciente_id = $_POST['paciente_id'];
    $tipo = $_POST['tipo'];
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $notas = trim($_POST['notas']);
    $stmt = $conn->prepare("INSERT INTO terapias (paciente_id, tipo, fecha, hora, notas) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$paciente_id, $tipo, $fecha, $hora, $notas]);
    header('Location: terapias.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Medicar: agenda y registra sesiones de terapia para pacientes con ELA.">
    <link rel="icon" type="image/png" href="image/Logo.png">
    <title>Terapias · Medicar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="therapy-page">
    <?php
    $activePage = 'terapias';
    $homeHref = $isPaciente ? 'dashboard_paciente.php' : 'index.php';
    $topbarExtra = !$isPaciente
        ? '<button class="btn btn-soft" onclick="mostrarModalTerapia()">+ Terapia</button>'
        : '';
    require 'includes/header.php';
    ?>

    <main class="therapy-main">
        <section class="therapy-hero">
            <div class="therapy-hero__content">
                <div class="brand-badge">Agenda de Terapias</div>
                <h1><?php echo $isPaciente ? 'Controla tus sesiones y notas de terapia' : 'Organiza la agenda terapéutica de tus pacientes'; ?></h1>
                <p>
                    <?php if ($isPaciente): ?>
                        Visualiza las sesiones programadas, tipos de terapia y comentarios del equipo clínico para preparar cada encuentro.
                    <?php else: ?>
                        Coordina y registra sesiones de fisioterapia, fonoaudiología u otras disciplinas, manteniendo informado al equipo de salud.
                    <?php endif; ?>
                </p>
                <div class="therapy-summary">
                    <div class="therapy-summary__card">
                        <span>Sesiones registradas</span>
                        <strong><?php echo $totalTerapias; ?></strong>
                    </div>
                    <div class="therapy-summary__card">
                        <span>Modalidades activas</span>
                        <strong><?php echo $tiposActivos; ?></strong>
                    </div>
                    <div class="therapy-summary__card">
                        <span>Próxima sesión</span>
                        <strong>
                            <?php
                            if ($proximaTerapia) {
                                echo formatearFecha($proximaTerapia['fecha']) . ' · ' . substr($proximaTerapia['hora'], 0, 5);
                            } else {
                                echo 'Sin programar';
                            }
                            ?>
                        </strong>
                    </div>
                </div>
            </div>
            <figure class="therapy-hero__figure">
                <img src="image/trata de personas.png" alt="Sesión de terapia">
            </figure>
        </section>

        <section class="therapy-section">
            <div class="section-heading">
                <h2>Sesiones programadas</h2>
                <p><?php echo $isPaciente ? 'Consulta tu agenda para esta semana y prepara tus próximas sesiones.' : 'Mantén un registro claro de las terapias pendientes y pasadas.'; ?></p>
            </div>
            <div class="table-wrapper therapy-table">
                <table>
                    <thead>
                        <tr>
                            <?php if (!$isPaciente): ?><th>Paciente</th><?php endif; ?>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($terapias as $terapia): ?>
                            <tr>
                                <?php if (!$isPaciente): ?>
                                    <td><?php echo htmlspecialchars($terapia['nombre'] . ' ' . $terapia['apellidos']); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars(ucfirst($terapia['tipo'])); ?></td>
                                <td><?php echo formatearFecha($terapia['fecha']); ?></td>
                                <td><?php echo $terapia['hora']; ?></td>
                                <td><?php echo htmlspecialchars($terapia['notas']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if (!$isPaciente): ?>
            <section class="therapy-section">
                <div class="therapy-grid">
                    <div class="info-card">
                        <h3>Programar terapia</h3>
                        <p>Define fecha, hora y notas personalizadas para mantener informados a pacientes y terapeutas.</p>
                        <button class="btn btn-secondary" onclick="mostrarModalTerapia()">Agendar sesión</button>
                    </div>
                    <div class="info-card">
                        <h3>Revisa tus pacientes</h3>
                        <p>Consulta las modalidades asignadas y evalúa la carga semanal de cada paciente.</p>
                        <a href="dashboard_paciente.php" class="btn btn-outline">Ver panel de pacientes</a>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <?php require 'includes/footer.php'; ?>

    <?php if (!$isPaciente): ?>
        <div id="modalTerapia" class="modal">
            <div class="modal-content">
                <span class="close" onclick="cerrarModalTerapia()">&times;</span>
                <h2>Agendar Terapia</h2>
                <form method="POST" class="stack">
                    <div class="form-group">
                        <label for="paciente_id">Paciente</label>
                        <select id="paciente_id" name="paciente_id" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($pacientes as $pac): ?>
                                <option value="<?php echo $pac['id']; ?>"><?php echo htmlspecialchars($pac['nombre'] . ' ' . $pac['apellidos']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tipo">Tipo de Terapia</label>
                        <select id="tipo" name="tipo" required>
                            <option value="fisioterapia">Fisioterapia</option>
                            <option value="ocupacional">Ocupacional</option>
                            <option value="fonoaudiologia">Fonoaudiología</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" required>
                    </div>
                    <div class="form-group">
                        <label for="hora">Hora</label>
                        <input type="time" id="hora" name="hora" required>
                    </div>
                    <div class="form-group">
                        <label for="notas">Notas</label>
                        <textarea id="notas" name="notas"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar sesión</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function mostrarModalTerapia() {
            document.getElementById('modalTerapia').style.display = 'block';
        }
        function cerrarModalTerapia() {
            document.getElementById('modalTerapia').style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                cerrarModalTerapia();
            }
        }
    </script>
</body>
</html>