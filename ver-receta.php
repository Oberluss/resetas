<?php
require_once 'includes/conexion.php';

// Obtener el ID de la receta
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Obtener los detalles de la receta
$sql = "SELECT r.*, c.nombre as nombre_categoria 
        FROM recetas r 
        LEFT JOIN categorias c ON r.categoria = c.slug 
        WHERE r.id = ?";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$receta = $resultado->fetch_assoc();

// Si no existe la receta, redirigir al index
if (!$receta) {
    header("Location: index.php");
    exit;
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
    <title><?php echo htmlspecialchars($receta['titulo']); ?> - Recetas de Cocina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/recetas/assets/css/styles.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/recetas">🍳 Recetas de Cocina</a>
        </div>
    </nav>

    <div class="container my-4">
        <?php if(isset($_GET['created'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                ¡Receta creada con éxito!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <!-- Título de la receta -->
                        <h1 class="card-title text-center mb-4">
                            <?php echo $iconos[$receta['categoria']] ?? '📝'; ?> 
                            <?php echo htmlspecialchars($receta['titulo']); ?>
                        </h1>

                        <!-- Imagen de la receta -->
                        <?php if (!empty($receta['imagen'])): ?>
                            <div class="text-center mb-4">
                                <img src="assets/images/recetas/<?php echo htmlspecialchars($receta['imagen']); ?>" 
                                     class="img-fluid rounded receta-imagen-detalle" 
                                     alt="<?php echo htmlspecialchars($receta['titulo']); ?>">
                            </div>
                        <?php endif; ?>

                        <!-- Meta información -->
                        <div class="d-flex justify-content-center gap-3 mb-4">
                            <span class="badge bg-primary">
                                <?php echo htmlspecialchars($receta['dificultad']); ?>
                            </span>
                            <span class="badge bg-info">
                                ⏱️ <?php echo htmlspecialchars($receta['tiempo_preparacion']); ?>
                            </span>
                            <?php if (!empty($receta['porciones'])): ?>
                                <span class="badge bg-success">
                                    👥 <?php echo htmlspecialchars($receta['porciones']); ?> porciones
                                </span>
                            <?php endif; ?>
                            <span class="badge bg-secondary">
                                <?php echo htmlspecialchars($receta['nombre_categoria']); ?>
                            </span>
                        </div>

                        <!-- Descripción -->
                        <?php if (!empty($receta['descripcion'])): ?>
                            <div class="card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title">📝 Descripción</h5>
                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($receta['descripcion'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Ingredientes -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">🧺 Ingredientes</h5>
                                <ul class="list-unstyled mb-0">
                                    <?php 
                                    $ingredientes = explode("\n", $receta['ingredientes']);
                                    foreach ($ingredientes as $ingrediente) {
                                        if (trim($ingrediente) !== '') {
                                            echo "<li class='mb-2'>• " . htmlspecialchars(trim($ingrediente)) . "</li>";
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Instrucciones -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">📋 Instrucciones</h5>
                                <ol class="mb-0">
                                    <?php 
                                    $instrucciones = explode("\n", $receta['instrucciones']);
                                    foreach ($instrucciones as $paso) {
                                        if (trim($paso) !== '') {
                                            echo "<li class='mb-3'>" . htmlspecialchars(trim($paso)) . "</li>";
                                        }
                                    }
                                    ?>
                                </ol>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="text-muted text-center mb-4">
                            <small>Receta creada el <?php echo date('d/m/Y', strtotime($receta['fecha_creacion'])); ?></small>
                        </div>

                        <!-- Botones de acción -->
                        <div class="d-flex justify-content-center gap-2">
                            <a href="index.php" class="btn btn-outline-secondary">
                                ← Volver a las recetas
                            </a>
                            <a href="categoria.php?slug=<?php echo urlencode($receta['categoria']); ?>" 
                               class="btn btn-primary">
                                Ver más recetas de <?php echo htmlspecialchars($receta['nombre_categoria']); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$stmt->close();
$conexion->close();
?>