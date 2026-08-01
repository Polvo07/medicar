# Medicar

A PHP and MySQL web app for the clinical follow-up of ALS (ELA) patients: medication schedules, therapies, and daily assessments, with separate dashboards for the doctor and the patient. Academic practice project, not a production product.

## What it does

Medicar models the day-to-day tracking a care team does for an ALS patient. A doctor manages patients, prescriptions, therapy sessions, and clinical assessments. A patient signs in to their own dashboard to see their medication plan and record intakes. The medication module turns a prescription (dose, hours between intakes, start time) into a schedule of expected intakes and tracks which ones were actually taken. All data is simulated, there are no real patients.

## Stack

- PHP with PDO
- MySQL / MariaDB
- Vanilla HTML and CSS, responsive, no framework
- XAMPP (Apache + MySQL + PHP) for local hosting

## How it works

Authentication is role based. `login.php` looks the user up with a PDO prepared statement, verifies the password with `password_verify` (bcrypt hashes, not plaintext), and stores the role in the session. From there the redirect splits the two experiences: a `paciente` lands on `dashboard_paciente.php`, anyone else on the doctor panel `index.php`.

The schema in `ela_simple.sql` is the interesting part, because it separates the plan from what actually happened. `prescripciones` holds the plan (drug, dose, `frecuencia_horas`, `hora_inicio`, duration), and `registro_tomas` holds the reality, one row per scheduled intake with both the programmed time (`fecha_hora_programada`) and the real time it was taken (`fecha_hora_real`). Keeping those two apart is what lets the app show adherence instead of just a static list. Around that sit `pacientes`, `usuarios`, `farmacos`, `evaluaciones`, and `terapias`, all wired with foreign keys, plus an `als_data` table that `import_csv.php` seeds from a CSV for reference metrics.

## Running it locally

1. Install **XAMPP** (Apache + MySQL + PHP) or an equivalent stack.
2. Import the schema and sample data:
   ```sql
   SOURCE ela_simple.sql;
   ```
3. Copy the project into `htdocs/Medicar` and start Apache and MySQL.
4. Open `http://localhost/Medicar/login.php`.

Demo users (seeded by `ela_simple.sql`, for local testing only):

| Role | User | Password |
|---|---|---|
| Doctor | `medico1` | `Medicar2026!` |
| Patient | `11` (id number) | `Medicar2026!` |

By default `config.php` connects to `localhost` with user `root` and an empty password, the standard XAMPP setup. Change the demo credentials before using the project anywhere outside a local test environment.

## Screenshots

> Screenshots pending. TODO: AD to add. Suggested shots: the login screen, the doctor panel, and the patient dashboard with a medication schedule.

## Status

Academic simulation, not production. Runs locally on XAMPP with the bundled sample data. Known limitation: the demo credentials are seeded directly in `ela_simple.sql`, and all patient data is fictional.
