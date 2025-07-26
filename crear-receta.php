<?php
require_once 'includes/conexion.php';

// Obtener las categorías para el select
$sql_categorias = "SELECT * FROM categorias";
$resultado_categorias = $conexion->query($sql_categorias);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Nueva Receta</title>
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
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">Crear Nueva Receta</h2>

                        <?php if(isset($_GET['error'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($_GET['message'] ?? 'Error al guardar la receta.'); ?>
                            </div>
                        <?php endif; ?>

                        <form action="guardar-receta.php" method="POST" enctype="multipart/form-data">
                            <!-- Título -->
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título de la Receta</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required>
                            </div>

                            <!-- Categoría -->
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría</label>
                                <select class="form-select" id="categoria" name="categoria" required>
                                    <option value="">Selecciona una categoría</option>
                                    <?php while($categoria = $resultado_categorias->fetch_assoc()): ?>
                                        <option value="<?php echo $categoria['slug']; ?>">
                                            <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Descripción -->
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción Breve</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" 
                                          rows="3" required></textarea>
                            </div>

                            <!-- Ingredientes -->
                            <div class="mb-3">
                                <label for="ingredientes" class="form-label">Ingredientes</label>
                                <textarea class="form-control" id="ingredientes" name="ingredientes" 
                                          rows="5" placeholder="Escribe cada ingrediente en una línea nueva" required></textarea>
                            </div>

                            <!-- Instrucciones -->
                            <div class="mb-3">
                                <label for="instrucciones" class="form-label">Instrucciones</label>
                                <textarea class="form-control" id="instrucciones" name="instrucciones" 
                                          rows="5" placeholder="Escribe cada paso en una línea nueva" required></textarea>
                            </div>

                            <!-- Tiempo de Preparación -->
                            <div class="mb-3">
                                <label for="tiempo_preparacion" class="form-label">Tiempo de Preparación</label>
                                <input type="text" class="form-control" id="tiempo_preparacion" 
                                       name="tiempo_preparacion" placeholder="Ej: 30 minutos" required>
                            </div>

                            <!-- Dificultad -->
                            <div class="mb-3">
                                <label for="dificultad" class="form-label">Dificultad</label>
                                <select class="form-select" id="dificultad" name="dificultad" required>
                                    <option value="Fácil">Fácil</option>
                                    <option value="Media">Media</option>
                                    <option value="Difícil">Difícil</option>
                                </select>
                            </div>

                            <!-- Porciones -->
                            <div class="mb-3">
                                <label for="porciones" class="form-label">Número de Porciones</label>
                                <input type="number" class="form-control" id="porciones" name="porciones" 
                                       min="1" max="20" required>
                            </div>

                            <!-- Imagen -->
                            <div class="mb-3">
                                <label for="imagen" class="form-label">Imagen de la Receta</label>
                                <input type="file" class="form-control" id="imagen" name="imagen" 
                                       accept="image/jpeg,image/png,image/gif">
                                <div class="form-text">
                                    Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 2MB
                                </div>
                                <div id="imagen-preview" class="mt-2"></div>
                            </div>

                            <!-- Botones -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Guardar Receta</button>
                                <a href="/recetas" class="btn btn-outline-secondary">Cancelar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Script para previsualizar la imagen -->
    <script>
        document.getElementById('imagen').onchange = function(e) {
            const preview = document.getElementById('imagen-preview');
            preview.innerHTML = '';
            
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-fluid imagen-preview';
                    img.style.maxHeight = '200px';
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(e.target.files[0]);
            }
        };
    </script>
</body>
</html>
<?php $conexion->close(); ?>