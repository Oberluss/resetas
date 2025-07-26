<?php
require_once '../includes/conexion.php';

// Obtener todas las categorías
$sql_categorias = "SELECT * FROM categorias";
$resultado_categorias = $conexion->query($sql_categorias);

// Obtener las recetas
$sql_recetas = "SELECT * FROM recetas";
if(isset($_GET['categoria'])) {
    $categoria = $conexion->real_escape_string($_GET['categoria']);
    $sql_recetas .= " WHERE categoria = '$categoria'";
}
$resultado_recetas = $conexion->query($sql_recetas);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recetas de Cocina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .categoria-card {
            transition: transform 0.3s;
        }
        .categoria-card:hover {
            transform: translateY(-5px);
        }
        .receta-card {
            height: 100%;
            transition: box-shadow 0.3s;
        }
        .receta-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Recetas de Cocina</a>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Sección de Categorías -->
        <h2 class="mb-4">Categorías</h2>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-4 mb-5">
            <?php while($categoria = $resultado_categorias->fetch_assoc()): ?>
                <div class="col">
                    <a href="?categoria=<?php echo $categoria['slug']; ?>" class="text-decoration-none">
                        <div class="card categoria-card">
                            <div class="card-body text-center">
                                <h5 class="card-title"><?php echo $categoria['nombre']; ?></h5>
                                <p class="card-text small text-muted"><?php echo $categoria['descripcion']; ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Sección de Recetas -->
        <h2 class="mb-4">Recetas</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php while($receta = $resultado_recetas->fetch_assoc()): ?>
                <div class="col">
                    <div class="card receta-card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $receta['titulo']; ?></h5>
                            <p class="card-text"><?php echo substr($receta['descripcion'], 0, 100) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-primary"><?php echo $receta['dificultad']; ?></span>
                                <small class="text-muted"><?php echo $receta['tiempo_preparacion']; ?></small>
                            </div>
                            <a href="ver-receta.php?id=<?php echo $receta['id']; ?>" class="btn btn-outline-primary mt-3">Ver receta</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>