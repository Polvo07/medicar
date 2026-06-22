<?php
/**
 * Cabecera compartida.
 * Variables esperadas antes del include:
 * $activePage  string  'inicio' | 'medicamentos' | 'terapias' | 'evaluaciones'
 * $isPaciente  bool
 * $homeHref    string  destino del enlace "Inicio"
 * $topbarExtra string  HTML opcional para botones extra (definido por la propia página, no por el usuario)
 */
?>
<header class="topbar">
    <div class="topbar__brand">
        <img src="image/Logo.png" alt="Medicar">
        <div>Medicar · <?php echo $isPaciente ? 'Panel Paciente' : 'Panel Médico'; ?></div>
    </div>
    <button type="button" class="topbar__toggle" id="navToggle" aria-label="Abrir menú" aria-expanded="false" aria-controls="topbarNav">
        <span></span><span></span><span></span>
    </button>
    <nav class="topbar__nav" id="topbarNav">
        <a href="<?php echo htmlspecialchars($homeHref); ?>" <?php echo $activePage === 'inicio' ? 'class="is-active"' : ''; ?>>Inicio</a>
        <a href="medicamentos.php" <?php echo $activePage === 'medicamentos' ? 'class="is-active"' : ''; ?>>Medicamentos</a>
        <a href="terapias.php" <?php echo $activePage === 'terapias' ? 'class="is-active"' : ''; ?>>Terapias</a>
        <a href="evaluaciones.php" <?php echo $activePage === 'evaluaciones' ? 'class="is-active"' : ''; ?>>Evaluaciones</a>
    </nav>
    <div class="topbar__actions">
        <?php if (!empty($topbarExtra)) { echo $topbarExtra; } ?>
        <a href="logout.php" class="btn btn-ghost">Cerrar Sesión</a>
    </div>
</header>
<script>
    (function () {
        var toggle = document.getElementById('navToggle');
        var nav = document.getElementById('topbarNav');
        if (!toggle || !nav) return;
        toggle.addEventListener('click', function () {
            var isOpen = nav.classList.toggle('is-open');
            toggle.classList.toggle('is-open', isOpen);
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                nav.classList.remove('is-open');
                toggle.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    })();
</script>
