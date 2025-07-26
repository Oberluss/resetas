<?php
include_once 'includes/conexion.php';

// Verificar la conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener todas las categorías
$sql_categorias = "SELECT * FROM categorias";
$resultado_categorias = $conexion->query($sql_categorias);

// Obtener todas las recetas con manejo de errores
$sql_recetas = "SELECT r.*, c.nombre as nombre_categoria 
                FROM recetas r 
                LEFT JOIN categorias c ON r.categoria = c.slug 
                ORDER BY r.fecha_creacion DESC";
$resultado_recetas = $conexion->query($sql_recetas);

if (!$resultado_recetas) {
    die("Error en la consulta: " . $conexion->error);
}

// Definir íconos para cada categoría
$iconos = [
    'carnes' => '🥩',
    'pescados' => '🐟',
    'postres' => '🧁',
    'guisos' => '🥘',
    'galletas' => '🍪'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recetas de Cocina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/recetas/assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/recetas">🍳 Recetas de Cocina</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <!-- Menú de categorías -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                            Categorías
                        </a>
                        <ul class="dropdown-menu">
                            <?php 
                            // Reiniciar el puntero del resultado de categorías
                            $resultado_categorias->data_seek(0);
                            while($cat = $resultado_categorias->fetch_assoc()): 
                            ?>
                                <li>
                                    <a class="dropdown-item" href="categoria.php?slug=<?php echo urlencode($cat['slug']); ?>">
                                        <?php echo $iconos[$cat['slug']] ?? '📝'; ?> 
                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </li>
                </ul>

                <!-- Buscador -->
                <form class="d-flex" action="buscar.php" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" placeholder="Buscar recetas..." 
                               style="border-radius: 20px 0 0 20px; min-width: 300px;">
                        <button class="btn btn-light" type="submit" style="border-radius: 0 20px 20px 0;">
                            🔍
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Botón Crear Nueva Receta -->
        <div class="text-end mb-4">
            <a href="crear-receta.php" class="btn btn-primary">
                ➕ Crear Nueva Receta
            </a>
        </div>

        <!-- Sección de Categorías -->
        <h2 class="section-title">Explora nuestras categorías</h2>
        <div class="row mb-5">
            <?php 
            // Reiniciar el puntero del resultado de categorías
            $resultado_categorias->data_seek(0);
            while($categoria = $resultado_categorias->fetch_assoc()): 
            ?>
                <div class="col-md-4 col-lg-3 mb-4">
                    <div class="card categoria-card">
                        <div class="card-body text-center">
                            <h3 class="card-title">
                                <?php echo $iconos[$categoria['slug']] ?? '📝'; ?> 
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </h3>
                            <p class="card-text"><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
                            <a href="categoria.php?slug=<?php echo urlencode($categoria['slug']); ?>" 
                               class="btn btn-primary">Explorar <?php echo htmlspecialchars($categoria['nombre']); ?></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Sección de Recetas -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="section-title mb-0">Últimas Recetas</h2>
        </div>

        <div class="row">
            <?php 
            if ($resultado_recetas && $resultado_recetas->num_rows > 0):
                while($receta = $resultado_recetas->fetch_assoc()): 
            ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card receta-card">
                        <?php if (!empty($receta['imagen'])): ?>
                            <img src="assets/images/recetas/<?php echo htmlspecialchars($receta['imagen']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($receta['titulo']); ?>">
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title">
                                <?php echo $iconos[$receta['categoria']] ?? '📝'; ?>
                                <?php echo htmlspecialchars($receta['titulo']); ?>
                            </h5>
                            <p class="card-text">
                                <?php 
                                if (!empty($receta['descripcion'])) {
                                    echo htmlspecialchars(substr($receta['descripcion'], 0, 100)) . '...';
                                }
                                ?>
                            </p>
                            <div class="meta-info mb-3">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($receta['dificultad']); ?></span>
                                <span class="text-muted ms-2">⏱️ <?php echo htmlspecialchars($receta['tiempo_preparacion']); ?></span>
                                <?php if (!empty($receta['porciones'])): ?>
                                    <span class="text-muted ms-2">👥 <?php echo htmlspecialchars($receta['porciones']); ?> porciones</span>
                                <?php endif; ?>
                            </div>
                            <a href="ver-receta.php?id=<?php echo $receta['id']; ?>" 
                               class="btn btn-outline-primary w-100">Ver receta completa</a>
                        </div>
                        <div class="card-footer text-muted">
                            <small>Categoría: <?php echo htmlspecialchars($receta['nombre_categoria']); ?></small>
                            <br>
                            <small>Creada: <?php echo date('d/m/Y', strtotime($receta['fecha_creacion'])); ?></small>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        No hay recetas disponibles en este momento.
                        <br>
                        <a href="crear-receta.php" class="btn btn-primary mt-2">
                            ¡Sé el primero en crear una receta!
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conexion->close();
?>