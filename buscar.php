<?php
require_once '/var/www/html/recetas/includes/conexion.php';

// Obtener el término de búsqueda y sanitizarlo
$busqueda = isset($_GET['q']) ? trim($_GET['q']) : '';
$busqueda = $conexion->real_escape_string($busqueda);

// Preparar la consulta SQL
$sql = "SELECT * FROM recetas WHERE 
        titulo LIKE ? OR 
        descripcion LIKE ? OR 
        ingredientes LIKE ?";

$termino_busqueda = "%{$busqueda}%";

// Preparar y ejecutar la consulta
$stmt = $conexion->prepare($sql);
$stmt->bind_param("sss", $termino_busqueda, $termino_busqueda, $termino_busqueda);
$stmt->execute();
$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Búsqueda de Recetas - <?php echo htmlspecialchars($busqueda); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/recetas/assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar con buscador -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/recetas">🍳 Recetas de Cocina</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSearch" aria-controls="navbarSearch" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSearch">
                <form class="d-flex ms-auto" action="buscar.php" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" 
                               value="<?php echo htmlspecialchars($busqueda); ?>"
                               placeholder="Buscar recetas..." aria-label="Buscar"
                               style="border-radius: 20px 0 0 20px; min-width: 300px;">
                        <button class="btn btn-light" type="submit" style="border-radius: 0 20px 20px 0;">
                            🔍
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Resultados de la búsqueda -->
        <div class="my-4">
            <h2 class="section-title">
                <?php
                if (!empty($busqueda)) {
                    echo "Resultados para: " . htmlspecialchars($busqueda);
                } else {
                    echo "Todas las recetas";
                }
                ?>
            </h2>

            <?php if ($resultado->num_rows == 0): ?>
                <div class="alert alert-info">
                    No se encontraron recetas que coincidan con tu búsqueda. 
                    <a href="/recetas" class="alert-link">Volver al inicio</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php while($receta = $resultado->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card receta-card">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($receta['titulo']); ?></h5>
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
                                    </div>
                                    <a href="ver-receta.php?id=<?php echo $receta['id']; ?>" 
                                       class="btn btn-outline-primary w-100">Ver receta completa</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$stmt->close();
$conexion->close();
?>