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
$pacienteId = $isPaciente ? $_SESSION['paciente_id'] : ($_GET['selected_paciente'] ?? null);

// Fetch patients (for doctors only)
$pacientes = [];
if (!$isPaciente) {
    $stmt = $conn->query("SELECT id, nombre, apellidos, cedula FROM pacientes");
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch evaluaciones
$queryEval = $isPaciente ? 
    "SELECT e.id, e.paciente_id, e.fecha, e.fuerza_muscular, e.capacidad_caminar, e.capacidad_hablar, e.notas 
     FROM evaluaciones e WHERE e.paciente_id = :paciente_id ORDER BY e.fecha DESC" :
    "SELECT e.id, e.paciente_id, e.fecha, e.fuerza_muscular, e.capacidad_caminar, e.capacidad_hablar, e.notas, p.nombre, p.apellidos, p.cedula 
     FROM evaluaciones e JOIN pacientes p ON e.paciente_id = p.id " . ($pacienteId ? "WHERE e.paciente_id = :paciente_id" : "") . " ORDER BY e.fecha DESC";
$stmt = $conn->prepare($queryEval);
if ($pacienteId) {
    $stmt->bindParam(':paciente_id', $pacienteId);
}
$stmt->execute();
$evaluaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalEvaluaciones = count($evaluaciones);
$promedioFuerza = $totalEvaluaciones ? round(array_sum(array_column($evaluaciones, 'fuerza_muscular')) / $totalEvaluaciones, 1) : 0;
$promedioCaminar = $totalEvaluaciones ? round(array_sum(array_column($evaluaciones, 'capacidad_caminar')) / $totalEvaluaciones, 1) : 0;
$promedioHablar = $totalEvaluaciones ? round(array_sum(array_column($evaluaciones, 'capacidad_hablar')) / $totalEvaluaciones, 1) : 0;
$ultimaEvaluacion = $totalEvaluaciones ? $evaluaciones[0] : null;
$pacientesEvaluados = !$isPaciente ? count(array_unique(array_column($evaluaciones, 'paciente_id'))) : 1;

// Fetch ALS data for current patient (for doctors)
$alsData = null;
if (!$isPaciente && $pacienteId) {
    $stmt = $conn->prepare("SELECT * FROM als_data WHERE ID = (SELECT cedula FROM pacientes WHERE id = :paciente_id)");
    $stmt->bindParam(':paciente_id', $pacienteId);
    $stmt->execute();
    $alsData = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paciente_id = $isPaciente ? $pacienteId : $_POST['paciente_id'];
    $fecha = $_POST['fecha'];
    $fuerza_muscular = (int)$_POST['fuerza_muscular'];
    $capacidad_caminar = (int)$_POST['capacidad_caminar'];
    $capacidad_hablar = (int)$_POST['capacidad_hablar'];
    $notas = trim($_POST['notas']);

    $stmt = $conn->prepare("SELECT id FROM evaluaciones WHERE paciente_id = :paciente_id AND fecha = :fecha");
    $stmt->bindParam(':paciente_id', $paciente_id);
    $stmt->bindParam(':fecha', $fecha);
    $stmt->execute();
    if ($stmt->rowCount() === 1) {
        $existingId = $stmt->fetchColumn();
        $stmt = $conn->prepare("UPDATE evaluaciones SET fuerza_muscular = ?, capacidad_caminar = ?, capacidad_hablar = ?, notas = ? WHERE id = ?");
        $stmt->execute([$fuerza_muscular, $capacidad_caminar, $capacidad_hablar, $notas, $existingId]);
        $message = 'Evaluación actualizada exitosamente';
    } else {
        $stmt = $conn->prepare("INSERT INTO evaluaciones (paciente_id, fecha, fuerza_muscular, capacidad_caminar, capacidad_hablar, notas) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$paciente_id, $fecha, $fuerza_muscular, $capacidad_caminar, $capacidad_hablar, $notas]);
        $message = 'Evaluación guardada exitosamente';
    }
    header('Location: evaluaciones.php?msg=' . urlencode($message) . ($pacienteId ? '&selected_paciente=' . $pacienteId : ''));
    exit;
}

// Get message
$message = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Medicar: registra y analiza evaluaciones clínicas diarias de pacientes con ELA.">
    <link rel="icon" type="image/png" href="image/Logo.png">
    <title>Evaluaciones · Medicar</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="eval-page">
    <?php
    $activePage = 'evaluaciones';
    $homeHref = $isPaciente ? 'dashboard_paciente.php' : 'index.php';
    $topbarExtra = !$isPaciente
        ? '<button class="btn btn-soft" onclick="mostrarModalEvaluacion()">+ Evaluación</button>'
        : '';
    require 'includes/header.php';
    ?>

    <main class="eval-main">
        <section class="eval-hero">
            <div class="eval-hero__content">
                <div class="brand-badge">Evaluaciones Clínicas</div>
                <h1><?php echo $isPaciente ? 'Monitorea tu progreso diario con Medicar' : 'Analiza y registra evaluaciones de tus pacientes'; ?></h1>
                <p>
                    <?php if ($isPaciente): ?>
                        Registra cómo te sientes cada día y lleva un seguimiento continuo de tu fuerza, movilidad y habla para compartir con tu equipo médico.
                    <?php else: ?>
                        Visualiza tendencias, registra nuevas evaluaciones y apóyate en métricas objetivas para tomar decisiones clínicas oportunas.
                    <?php endif; ?>
                </p>
                <?php if (!$isPaciente): ?>
                    <form method="GET" class="eval-filter">
                        <label for="selected_paciente">Filtrar por paciente</label>
                        <select id="selected_paciente" name="selected_paciente" onchange="this.form.submit()">
                            <option value="">Todos los pacientes</option>
                            <?php foreach ($pacientes as $pac): ?>
                                <option value="<?php echo $pac['id']; ?>" <?php if ($pacienteId == $pac['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($pac['nombre'] . ' ' . $pac['apellidos']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <div class="eval-hero__actions">
                        <button class="btn btn-primary" onclick="mostrarModalEvaluacion()">Registrar evaluación</button>
                    </div>
                <?php endif; ?>
                <?php if ($message): ?>
                    <div class="mensaje-estado success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <div class="eval-summary">
                    <div class="eval-summary__card">
                        <span>Total de evaluaciones</span>
                        <strong><?php echo $totalEvaluaciones; ?></strong>
                    </div>
                    <div class="eval-summary__card">
                        <span>Promedio fuerza muscular</span>
                        <strong><?php echo $promedioFuerza; ?>/10</strong>
                    </div>
                    <div class="eval-summary__card">
                        <span>Promedio movilidad</span>
                        <strong><?php echo $promedioCaminar; ?>/10</strong>
                    </div>
                    <div class="eval-summary__card">
                        <span>Promedio habla</span>
                        <strong><?php echo $promedioHablar; ?>/10</strong>
                    </div>
                    <?php if (!$isPaciente): ?>
                        <div class="eval-summary__card">
                            <span>Pacientes evaluados</span>
                            <strong><?php echo $pacientesEvaluados; ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($ultimaEvaluacion): ?>
                    <div class="eval-latest">
                        <div class="eval-latest__header">
                            <h3>Última evaluación</h3>
                            <span class="pill"><?php echo formatearFecha($ultimaEvaluacion['fecha']); ?></span>
                        </div>
                        <div class="eval-latest__grid">
                            <div>
                                <span>Fuerza muscular</span>
                                <strong><?php echo $ultimaEvaluacion['fuerza_muscular']; ?>/10</strong>
                            </div>
                            <div>
                                <span>Capacidad para caminar</span>
                                <strong><?php echo $ultimaEvaluacion['capacidad_caminar']; ?>/10</strong>
                            </div>
                            <div>
                                <span>Capacidad para hablar</span>
                                <strong><?php echo $ultimaEvaluacion['capacidad_hablar']; ?>/10</strong>
                            </div>
                        </div>
                        <?php if (!$isPaciente && isset($ultimaEvaluacion['nombre'])): ?>
                            <p class="footnote">Paciente: <?php echo htmlspecialchars($ultimaEvaluacion['nombre'] . ' ' . $ultimaEvaluacion['apellidos']); ?></p>
                        <?php elseif (!empty($ultimaEvaluacion['notas'])): ?>
                            <p class="footnote">Notas: <?php echo htmlspecialchars($ultimaEvaluacion['notas']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <figure class="eval-hero__figure">
                <img src="image/metricas img.jpg" alt="Panel de métricas clínicas">
            </figure>
        </section>

        <?php if ($isPaciente): ?>
        <section class="eval-section">
            <div class="eval-form-card">
                <h2>Registrar nueva evaluación</h2>
                <p>Actualiza tus indicadores diarios para compartir con tu equipo médico.</p>
                <form method="POST" class="stack">
                    <div class="form-group">
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="fuerza_muscular">Fuerza Muscular (1-10)</label>
                        <div class="scale-container">
                            <input type="range" id="fuerza_muscular" name="fuerza_muscular" min="1" max="10" value="5" oninput="updateScaleValue('fuerza_muscular')">
                            <span class="scale-value" id="fuerza_muscular_value">5</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="capacidad_caminar">Capacidad para Caminar (1-10)</label>
                        <div class="scale-container">
                            <input type="range" id="capacidad_caminar" name="capacidad_caminar" min="1" max="10" value="5" oninput="updateScaleValue('capacidad_caminar')">
                            <span class="scale-value" id="capacidad_caminar_value">5</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="capacidad_hablar">Capacidad para Hablar (1-10)</label>
                        <div class="scale-container">
                            <input type="range" id="capacidad_hablar" name="capacidad_hablar" min="1" max="10" value="5" oninput="updateScaleValue('capacidad_hablar')">
                            <span class="scale-value" id="capacidad_hablar_value">5</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notas">Notas</label>
                        <textarea id="notas" name="notas"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar evaluación</button>
                </form>
            </div>
        </section>
        <?php endif; ?>

        <section class="eval-section">
            <div class="section-heading">
                <h2><?php echo $isPaciente ? 'Historial de evaluaciones' : 'Listado de evaluaciones'; ?></h2>
                <p><?php echo $isPaciente ? 'Consulta tus resultados anteriores y revisa las observaciones registradas.' : 'Visualiza las evaluaciones registradas y monitorea la evolución de cada paciente.'; ?></p>
            </div>
            <div class="table-wrapper eval-table">
                <table>
                    <thead>
                        <tr>
                            <?php if (!$isPaciente): ?><th>Paciente</th><?php endif; ?>
                            <th>Fecha</th>
                            <th>Fuerza muscular</th>
                            <th>Capacidad caminar</th>
                            <th>Capacidad hablar</th>
                            <th>Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($evaluaciones as $eval): ?>
                            <tr>
                                <?php if (!$isPaciente): ?>
                                    <td><?php echo htmlspecialchars($eval['nombre'] . ' ' . $eval['apellidos']); ?></td>
                                <?php endif; ?>
                                <td><?php echo formatearFecha($eval['fecha']); ?></td>
                                <td><?php echo $eval['fuerza_muscular']; ?>/10</td>
                                <td><?php echo $eval['capacidad_caminar']; ?>/10</td>
                                <td><?php echo $eval['capacidad_hablar']; ?>/10</td>
                                <td><?php echo htmlspecialchars($eval['notas']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if (!$isPaciente): ?>
            <section class="eval-section">
                <?php if ($pacienteId && $alsData): ?>
                    <div class="eval-als-card">
                        <h2>Métricas de voz (ALS Dataset)</h2>
                        <p>Indicadores acústicos asociados a la progresión de la enfermedad para el paciente seleccionado.</p>
                        <div class="eval-als-grid">
                            <div><span>HNR_a</span><strong><?php echo round($alsData['HNR_a'], 3); ?></strong></div>
                            <div><span>Jitter (J1_a)</span><strong><?php echo round($alsData['J1_a'], 3); ?></strong></div>
                            <div><span>Shimmer (S1_a)</span><strong><?php echo round($alsData['S1_a'], 3); ?></strong></div>
                            <div><span>GNEa_mu</span><strong><?php echo round($alsData['GNEa_mu'], 3); ?></strong></div>
                        </div>
                    </div>
                <?php elseif (!$pacienteId): ?>
                    <div class="mensaje-estado">Selecciona un paciente para visualizar métricas de voz derivadas del dataset ALS.</div>
                <?php else: ?>
                    <div class="mensaje-estado">No se encontraron métricas de voz registradas para este paciente.</div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php require 'includes/footer.php'; ?>

    <?php if (!$isPaciente): ?>
        <div id="modalEvaluacion" class="modal">
            <div class="modal-content">
                <span class="close" onclick="cerrarModalEvaluacion()">&times;</span>
                <h2>Nueva Evaluación</h2>
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
                        <label for="fecha">Fecha</label>
                        <input type="date" id="fecha" name="fecha" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="fuerza_muscular">Fuerza Muscular (1-10)</label>
                        <div class="scale-container">
                            <input type="range" id="fuerza_muscular" name="fuerza_muscular" min="1" max="10" value="5" oninput="updateScaleValue('fuerza_muscular')">
                            <span class="scale-value" id="fuerza_muscular_value">5</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="capacidad_caminar">Capacidad para Caminar (1-10)</label>
                        <div class="scale-container">
                            <input type="range" id="capacidad_caminar" name="capacidad_caminar" min="1" max="10" value="5" oninput="updateScaleValue('capacidad_caminar')">
                            <span class="scale-value" id="capacidad_caminar_value">5</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="capacidad_hablar">Capacidad para Hablar (1-10)</label>
                        <div class="scale-container">
                            <input type="range" id="capacidad_hablar" name="capacidad_hablar" min="1" max="10" value="5" oninput="updateScaleValue('capacidad_hablar')">
                            <span class="scale-value" id="capacidad_hablar_value">5</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notas">Notas</label>
                        <textarea id="notas" name="notas"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar evaluación</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    <script>
        function updateScaleValue(scaleId) {
            const scale = document.getElementById(scaleId);
            const valueDisplay = document.getElementById(scaleId + '_value');
            if (scale && valueDisplay) {
                valueDisplay.textContent = scale.value;
            }
        }
        <?php if (!$isPaciente): ?>
        function mostrarModalEvaluacion() {
            document.getElementById('modalEvaluacion').style.display = 'block';
        }
        function cerrarModalEvaluacion() {
            document.getElementById('modalEvaluacion').style.display = 'none';
        }
        window.onclick = function(event) {
            const modal = document.getElementById('modalEvaluacion');
            if (event.target === modal) {
                cerrarModalEvaluacion();
            }
        }
        <?php endif; ?>
        ['fuerza_muscular', 'capacidad_caminar', 'capacidad_hablar'].forEach(id => updateScaleValue(id));
    </script>
</body>
</html>
