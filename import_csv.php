<?php
// Script de importación inicial: solo debe ejecutarse desde la línea de comandos
// (php import_csv.php), nunca expuesto como endpoint web.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso denegado: este script solo puede ejecutarse desde la línea de comandos.');
}

require_once 'config.php';

$database = new Database();
$conn = $database->getConnection();

$csvFile = 'JPS2024_ALS_dataset.csv';

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $first = true;
    $columns = [];

    while ($data = fgetcsv($handle, 10000, ",")) {
        if ($first) {
            $columns = $data;
            foreach ($columns as &$col) {
                $col = str_replace(['{\mu}', '{\sigma}', '{', '}', '(', ')'], ['_mu', '_sigma', '', '', '_', ''], $col);
            }
            $first = false;
            continue;
        }

        $sql = "INSERT IGNORE INTO als_data (`" . implode("`, `", $columns) . "`) VALUES (" . implode(', ', array_fill(0, count($data), '?')) . ")";
        $stmt = $conn->prepare($sql);
        foreach ($data as $key => $value) {
            $data[$key] = $value === '' ? null : $value;
        }
        $stmt->execute($data);
    }
    fclose($handle);

    // Contraseña de demo para todos los usuarios importados: Medicar2026! (cámbiala antes de usar en producción)
    $fixedHash = '$2y$10$pOG8zEWAaSt6Boitt48l3ev/A5TgPpXx0oi4LZfZFCPmsjwwvoKAi';
    $stmt = $conn->query("SELECT ID, Sex, Age FROM als_data");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cedula = $row['ID'];
        $nombre = 'Paciente ' . $cedula;
        $edad = $row['Age'];
        $fechaNacimiento = date('Y-m-d', strtotime("-$edad years"));

        $insertPaciente = $conn->prepare("INSERT IGNORE INTO pacientes (cedula, nombre, fecha_nacimiento) VALUES (?, ?, ?)");
        $insertPaciente->execute([$cedula, $nombre, $fechaNacimiento]);
        $pacienteId = $conn->lastInsertId();

        if ($pacienteId) {
            $insertUser = $conn->prepare("INSERT IGNORE INTO usuarios (username, contrasena, role, paciente_id) VALUES (?, ?, 'paciente', ?)");
            $insertUser->execute([$cedula, $fixedHash, $pacienteId]);
        }
    }
    echo "Importación y generación de usuarios completada. Todos los usuarios del CSV usan la contraseña 'Medicar2026!'.";
} else {
    echo "Error al abrir el CSV.";
}
?>
