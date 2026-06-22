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

// Fetch farmacos
$stmt = $conn->query("SELECT id, nombre, presentacion FROM farmacos");
$farmacos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch prescripciones
$queryPresc = $isPaciente ? 
    "SELECT p.id, pa.nombre, pa.apellidos, f.nombre AS farmaco, p.dosis, p.frecuencia_horas, p.hora_inicio, p.fecha_inicio, p.duracion_dias, p.instrucciones 
     FROM prescripciones p 
     JOIN pacientes pa ON p.paciente_id = pa.id 
     JOIN farmacos f ON p.farmaco_id = f.id 
     WHERE p.paciente_id = :paciente_id AND p.activo = TRUE" :
    "SELECT p.id, pa.nombre, pa.apellidos, f.nombre AS farmaco, p.dosis, p.frecuencia_horas, p.hora_inicio, p.fecha_inicio, p.duracion_dias, p.instrucciones 
     FROM prescripciones p 
     JOIN pacientes pa ON p.paciente_id = pa.id 
     JOIN farmacos f ON p.farmaco_id = f.id 
     WHERE p.activo = TRUE";
$stmt = $conn->prepare($queryPresc);
if ($isPaciente) {
    $stmt->bindParam(':paciente_id', $pacienteId);
}
$stmt->execute();
$prescripciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPrescripciones = count($prescripciones);
$farmacosActivos = count(array_unique(array_map(function ($p) {
    return $p['farmaco'];
}, $prescripciones)));
$pacientesActivos = $isPaciente ? 1 : count($pacientes);

// Handle form submissions (doctors only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isPaciente) {
    if (isset($_POST['add_farmaco'])) {
        $nombre = trim($_POST['nombre']);
        $presentacion = trim($_POST['presentacion']);
        $descripcion = trim($_POST['descripcion']);
        $stmt = $conn->prepare("INSERT INTO farmacos (nombre, presentacion, descripcion) VALUES (?, ?, ?)");
        $stmt->execute([$nombre, $presentacion, $descripcion]);
        header('Location: medicamentos.php');
        exit;
    } elseif (isset($_POST['add_prescripcion'])) {
        $paciente_id = $_POST['paciente_id'];
        $farmaco_id = $_POST['farmaco_id'];
        $dosis = trim($_POST['dosis']);
        $frecuencia_horas = (int)$_POST['frecuencia_horas'];
        $hora_inicio = $_POST['hora_inicio'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $duracion_dias = !empty($_POST['duracion_dias']) ? (int)$_POST['duracion_dias'] : null;
        $instrucciones = trim($_POST['instrucciones']);
        $stmt = $conn->prepare("INSERT INTO prescripciones (paciente_id, farmaco_id, dosis, frecuencia_horas, hora_inicio, fecha_inicio, duracion_dias, instrucciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$paciente_id, $farmaco_id, $dosis, $frecuencia_horas, $hora_inicio, $fecha_inicio, $duracion_dias, $instrucciones]);
        header('Location: medicamentos.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Medicar: gestiona fármacos y prescripciones de tus pacientes con ELA.">
    <link rel="icon" type="image/png" href="image/Logo.png">
    <title>Medicamentos · Medicar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="meds-page">
    <?php
    $activePage = 'medicamentos';
    $homeHref = $isPaciente ? 'dashboard_paciente.php' : 'index.php';
    $topbarExtra = !$isPaciente
        ? '<button class="btn btn-soft" onclick="mostrarModalFarmaco()">+ Fármaco</button>'
        . '<button class="btn btn-soft" onclick="mostrarModalPrescripcion()">+ Prescripción</button>'
        : '';
    require 'includes/header.php';
    ?>
    <main class="meds-main">
        <section class="meds-hero">
            <div class="meds-hero__content">
                <div class="brand-badge">Gestión de Medicamentos</div>
                <h1><?php echo $isPaciente ? 'Tus recordatorios y tratamientos activos' : 'Coordina tratamientos y prescripciones de tus pacientes'; ?></h1>
                <p>
                    <?php if ($isPaciente): ?>
                        Consulta tus dosis, horarios y notas personalizadas. Mantén el control de tu tratamiento diario con recordatorios claros.
                    <?php else: ?>
                        Visualiza rápidamente las prescripciones activas, registra nuevas indicaciones y mantiene sincronizado al equipo clínico.
                    <?php endif; ?>
                </p>
                <?php if (!$isPaciente): ?>
                    <div class="meds-hero__actions">
                        <button class="btn btn-primary" onclick="mostrarModalPrescripcion()">Registrar prescripción</button>
                        <button class="btn btn-outline" onclick="mostrarModalFarmaco()">Añadir fármaco</button>
                    </div>
                <?php endif; ?>
                <div class="meds-summary">
                    <div class="meds-summary__card">
                        <span>Total de prescripciones</span>
                        <strong><?php echo $totalPrescripciones; ?></strong>
                    </div>
                    <div class="meds-summary__card">
                        <span>Fármacos diferentes</span>
                        <strong><?php echo $farmacosActivos; ?></strong>
                    </div>
                    <div class="meds-summary__card">
                        <span><?php echo $isPaciente ? 'Tratamientos activos' : 'Pacientes activos'; ?></span>
                        <strong><?php echo $pacientesActivos; ?></strong>
                    </div>
                </div>
            </div>
            <figure class="meds-hero__figure">
                <img src="image/Toma de medicamentos.png" alt="Control de medicación">
            </figure>
        </section>

        <section class="meds-section">
            <div class="section-heading">
                <h2>Prescripciones activas</h2>
                <p><?php echo $isPaciente ? 'Revisa tus tratamientos vigentes y sus instrucciones.' : 'Lista de prescripciones en curso para tus pacientes.'; ?></p>
            </div>
            <div class="table-wrapper meds-table">
                <table>
                    <thead>
                        <tr>
                            <?php if (!$isPaciente): ?><th>Paciente</th><?php endif; ?>
                            <th>Fármaco</th>
                            <th>Dosis</th>
                            <th>Frecuencia</th>
                            <th>Hora inicio</th>
                            <th>Fecha inicio</th>
                            <th>Duración</th>
                            <th>Instrucciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prescripciones as $presc): ?>
                            <tr>
                                <?php if (!$isPaciente): ?>
                                    <td><?php echo htmlspecialchars($presc['nombre'] . ' ' . $presc['apellidos']); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($presc['farmaco']); ?></td>
                                <td><?php echo htmlspecialchars($presc['dosis']); ?></td>
                                <td><?php echo $presc['frecuencia_horas']; ?> h</td>
                                <td><?php echo $presc['hora_inicio']; ?></td>
                                <td><?php echo formatearFecha($presc['fecha_inicio']); ?></td>
                                <td><?php echo $presc['duracion_dias'] ?: 'Indefinido'; ?></td>
                                <td><?php echo htmlspecialchars($presc['instrucciones']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if (!$isPaciente): ?>
            <section class="meds-section">
                <div class="meds-grid">
                    <div class="info-card">
                        <h3>Registrar un nuevo fármaco</h3>
                        <p>Actualiza el catálogo con presentaciones y dosis disponibles para tus pacientes.</p>
                        <button class="btn btn-secondary" onclick="mostrarModalFarmaco()">Nuevo fármaco</button>
                    </div>
                    <div class="info-card">
                        <h3>Generar prescripción</h3>
                        <p>Configura recordatorios automáticos con horarios, duración y observaciones específicas.</p>
                        <button class="btn btn-secondary" onclick="mostrarModalPrescripcion()">Nueva prescripción</button>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <?php require 'includes/footer.php'; ?>

    <?php if (!$isPaciente): ?>
        <!-- Modal Nuevo Fármaco -->
        <div id="modalFarmaco" class="modal">
            <div class="modal-content">
                <span class="close" onclick="cerrarModalFarmaco()">&times;</span>
                <h2>Nuevo Fármaco</h2>
                <form method="POST" class="stack">
                    <input type="hidden" name="add_farmaco" value="1">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="presentacion">Presentación</label>
                        <input type="text" id="presentacion" name="presentacion" required>
                    </div>
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar fármaco</button>
                </form>
            </div>
        </div>
        <!-- Modal Nueva Prescripción -->
        <div id="modalPrescripcion" class="modal">
            <div class="modal-content">
                <span class="close" onclick="cerrarModalPrescripcion()">&times;</span>
                <h2>Nueva Prescripción</h2>
                <form method="POST" class="stack">
                    <input type="hidden" name="add_prescripcion" value="1">
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
                        <label for="farmaco_id">Fármaco</label>
                        <select id="farmaco_id" name="farmaco_id" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($farmacos as $farmaco): ?>
                                <option value="<?php echo $farmaco['id']; ?>"><?php echo htmlspecialchars($farmaco['nombre'] . ' (' . $farmaco['presentacion'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="dosis">Dosis</label>
                        <input type="text" id="dosis" name="dosis" required>
                    </div>
                    <div class="form-group">
                        <label for="frecuencia_horas">Frecuencia (horas)</label>
                        <input type="number" id="frecuencia_horas" name="frecuencia_horas" required>
                    </div>
                    <div class="form-group">
                        <label for="hora_inicio">Hora inicio</label>
                        <input type="time" id="hora_inicio" name="hora_inicio" required>
                    </div>
                    <div class="form-group">
                        <label for="fecha_inicio">Fecha inicio</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" required>
                    </div>
                    <div class="form-group">
                        <label for="duracion_dias">Duración (días, opcional)</label>
                        <input type="number" id="duracion_dias" name="duracion_dias">
                    </div>
                    <div class="form-group">
                        <label for="instrucciones">Instrucciones</label>
                        <textarea id="instrucciones" name="instrucciones"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar prescripción</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function mostrarModalFarmaco() {
            document.getElementById('modalFarmaco').style.display = 'block';
        }
        function cerrarModalFarmaco() {
            document.getElementById('modalFarmaco').style.display = 'none';
        }
        function mostrarModalPrescripcion() {
            document.getElementById('modalPrescripcion').style.display = 'block';
        }
        function cerrarModalPrescripcion() {
            document.getElementById('modalPrescripcion').style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                cerrarModalFarmaco();
                cerrarModalPrescripcion();
            }
        }
    </script>
</body>
</html>
