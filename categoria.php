<?php
require_once '/var/www/html/recetas/includes/conexion.php';

// Obtener la categoría de la URL
$categoria = isset($_GET['slug']) ? $_GET['slug'] : '';

// Obtener información de la categoría
$sql_categoria = "SELECT * FROM categorias WHERE slug = ?";
$stmt_categoria = $conexion->prepare($sql_categoria);
$stmt_categoria->bind_param("s", $categoria);
$stmt_categoria->execute();
$resultado_categoria = $stmt_categoria->get_result();
$info_categoria = $resultado_categoria->fetch_assoc();

// Si la categoría no existe, redirigir al inicio
if (!$info_categoria) {
    header("Location: /recetas");
    exit;
}

// Obtener las recetas de la categoría
$sql_recetas = "SELECT * FROM recetas WHERE categoria = ?";
$stmt_recetas = $conexion->prepare($sql_recetas);
$stmt_recetas->bind_param("s", $categoria);
$stmt_recetas->execute();
$resultado_recetas = $stmt_recetas->get_result();

// Obtener todas las categorías para el menú
$sql_todas_categorias = "SELECT * FROM categorias";
$resultado_todas_categorias = $conexion->query($sql_todas_categorias);

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
    <title><?php echo htmlspecialchars($info_categoria['nombre']); ?> - Recetas de Cocina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/recetas/assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar con buscador y menú de categorías -->
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
                            <?php while($cat = $resultado_todas_categorias->fetch_assoc()): ?>
                                <li>
                                    <a class="dropdown-item <?php echo ($cat['slug'] === $categoria) ? 'active' : ''; ?>" 
                                       href="categoria.php?slug=<?php echo urlencode($cat['slug']); ?>">
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
        <!-- Encabezado de la categoría -->
        <div class="text-center mb-5">
            <h1 class="section-title display-4">
                <?php echo $iconos[$categoria] ?? '📝'; ?> 
                <?php echo htmlspecialchars($info_categoria['nombre']); ?>
            </h1>
            <p class="lead text-muted">
                <?php echo htmlspecialchars($info_categoria['descripcion']); ?>
            </p>
        </div>

        <!-- Grid de recetas -->
        <div class="row">
            <?php if ($resultado_recetas->num_rows > 0): ?>
                <?php while($receta = $resultado_recetas->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card receta-card">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($receta['titulo']); ?></h5>
                                <p class="card-text">
                                    <?php echo htmlspecialchars(substr($receta['descripcion'], 0, 100)) . '...'; ?>
                                </p>
                                <div class="meta-info mb-3">
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($receta['dificultad']); ?></span>
                                    <span class="text-muted ms-2">⏱️ <?php echo htmlspecialchars($receta['tiempo_preparacion']); ?></span>
                                </div>
                                <a href="ver-receta.php?id=<?php echo $receta['id']; ?>" 
                                   class="btn btn-outline-primary w-100">Ver receta completa</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <p>Aún no hay recetas en esta categoría.</p>
                        <a href="/recetas" class="btn btn-primary mt-2">Volver al inicio</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$stmt_categoria->close();
$stmt_recetas->close();
$conexion->close();
?>