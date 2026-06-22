# Medicar

Aplicación web de ejemplo (PHP + MySQL) para el seguimiento clínico de pacientes con ELA: medicación, terapias y evaluaciones diarias, con paneles separados para médico y paciente.

> Proyecto académico / de práctica, no es un producto en producción.

## Stack

- PHP (PDO + MySQL)
- HTML/CSS responsive (sin frameworks)
- MySQL/MariaDB

## Requisitos

- XAMPP (Apache + MySQL + PHP) u otro entorno equivalente

## Puesta en marcha

1. Importa el esquema y datos de ejemplo:
   ```sql
   SOURCE ela_simple.sql;
   ```
2. Copia el proyecto a `htdocs/Medicar` (XAMPP) y arranca Apache + MySQL.
3. Abre `http://localhost/Medicar/login.php`.

### Usuarios de demo

| Rol | Usuario | Contraseña |
|---|---|---|
| Médico | `medico1` | `Medicar2026!` |
| Paciente | `11` (cédula) | `Medicar2026!` |

Cambia estas credenciales antes de usar el proyecto fuera de un entorno local de pruebas.

## Estructura

- `config.php` — conexión a base de datos y helpers de formato.
- `login.php` / `logout.php` — autenticación por rol.
- `index.php` — panel médico.
- `dashboard_paciente.php` — panel paciente.
- `medicamentos.php`, `terapias.php`, `evaluaciones.php` — módulos clínicos.
- `includes/` — cabecera y pie de página compartidos.
- `import_csv.php` — script de importación inicial (solo CLI).
