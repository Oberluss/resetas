<?php
require_once "includes/auth.php";
requireLogin();

$currentUser = getCurrentUser();

// Obtener ID de la receta a editar
$recipeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($recipeId == 0) {
    header("Location: index.php");
    exit;
}

// Obtener la receta
$recipe = $db->getById("recipes", $recipeId);

if (!$recipe) {
    header("Location: index.php?error=recipe_not_found");
    exit;
}

// Verificar permisos
if ($recipe["user_id"] != $currentUser["id"] && !isAdmin()) {
    header("Location: index.php?error=permission_denied");
    exit;
}

// Obtener categorías
$categories = $db->getAll("categories");

// Manejar errores de sesión si vienen de guardar-receta.php
session_start();
$errors = $_SESSION['form_errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);

// Si hay datos del formulario en sesión, usarlos; si no, usar los de la receta
$title = $formData['title'] ?? $recipe['title'];
$ingredients = $formData['ingredients'] ?? $recipe['ingredients'];
$instructions = $formData['instructions'] ?? $recipe['instructions'];
$prep_time = $formData['prep_time'] ?? $recipe['prep_time'];
$servings = $formData['servings'] ?? $recipe['servings'];
$category_id = $formData['category_id'] ?? $recipe['category_id'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Receta - Sistema de Recetas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .navbar h1 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }
        .form-header h2 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .form-header p {
            color: #6c757d;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 500;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #28a745;
            outline: none;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .btn {
            padding: 12px 24px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #218838;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 30px;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .error ul {
            margin: 10px 0 0 20px;
        }
        .help-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
        }
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        .file-input-label {
            display: inline-block;
            padding: 12px 24px;
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .file-input-label:hover {
            background: #e9ecef;
            border-color: #28a745;
        }
        .current-image {
            margin-top: 10px;
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
        }
        .preview-image {
            margin-top: 10px;
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            display: none;
        }
        .image-info {
            background: #e9ecef;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 0.875rem;
            color: #495057;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .navbar-content {
                text-align: center;
            }
            .nav-links {
                margin-top: 10px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>🍳 Sistema de Recetas</h1>
            <div class="nav-links">
                <a href="index.php">Inicio</a>
                <a href="crear-receta.php">➕ Nueva Receta</a>
                <a href="buscar.php">🔍 Buscar</a>
                <?php if (isAdmin()): ?>
                    <a href="admin.php">⚙️ Admin</a>
                <?php endif; ?>
                <a href="logout.php">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <div class="form-header">
                <h2>✏️ Editar Receta</h2>
                <p>Modifica los datos de tu receta</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <strong>❌ Por favor corrige los siguientes errores:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="guardar-receta.php" enctype="multipart/form-data">
                <input type="hidden" name="recipe_id" value="<?php echo $recipeId; ?>">
                
                <div class="form-group">
                    <label for="title">Título de la Receta *</label>
                    <input type="text" id="title" name="title" required 
                           value="<?php echo htmlspecialchars($title); ?>"
                           placeholder="Ej: Pasta a la Carbonara">
                </div>

                <div class="form-group">
                    <label for="category_id">Categoría *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Selecciona una categoría</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"
                                    <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo $category['icon'] ?? ''; ?> <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="prep_time">Tiempo de Preparación (minutos)</label>
                        <input type="number" id="prep_time" name="prep_time" min="0" 
                               value="<?php echo htmlspecialchars($prep_time); ?>"
                               placeholder="Ej: 30">
                    </div>

                    <div class="form-group">
                        <label for="servings">Porciones</label>
                        <input type="number" id="servings" name="servings" min="1" 
                               value="<?php echo htmlspecialchars($servings); ?>"
                               placeholder="Ej: 4">
                    </div>
                </div>

                <div class="form-group">
                    <label for="ingredients">Ingredientes *</label>
                    <textarea id="ingredients" name="ingredients" required 
                              placeholder="Escribe cada ingrediente en una línea nueva"><?php echo htmlspecialchars($ingredients); ?></textarea>
                    <p class="help-text">Tip: Escribe cada ingrediente en una línea nueva para mejor legibilidad</p>
                </div>

                <div class="form-group">
                    <label for="instructions">Instrucciones *</label>
                    <textarea id="instructions" name="instructions" required 
                              placeholder="Describe paso a paso cómo preparar la receta"><?php echo htmlspecialchars($instructions); ?></textarea>
                    <p class="help-text">Tip: Numera cada paso para que sea más fácil de seguir</p>
                </div>

                <div class="form-group">
                    <label for="photo">Foto de la Receta</label>
                    
                    <?php if (!empty($recipe["photo"]) && file_exists($recipe["photo"])): ?>
                        <div class="image-info">
                            📷 Imagen actual:
                            <img src="<?php echo htmlspecialchars($recipe["photo"]); ?>" 
                                 alt="Imagen actual" class="current-image" id="currentImage">
                        </div>
                    <?php endif; ?>
                    
                    <div class="file-input-wrapper">
                        <label for="photo" class="file-input-label">
                            📷 <?php echo !empty($recipe["photo"]) ? 'Cambiar Imagen' : 'Seleccionar Imagen'; ?>
                        </label>
                        <input type="file" id="photo" name="photo" accept="image/*" onchange="previewImage(this)">
                    </div>
                    <img id="preview" class="preview-image" alt="Vista previa">
                    <p class="help-text">Formatos permitidos: JPG, PNG, GIF, WEBP (máx. 5MB)</p>
                    <?php if (!empty($recipe["photo"])): ?>
                        <p class="help-text">Deja este campo vacío si no quieres cambiar la imagen actual</p>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">
                        💾 Guardar Cambios
                    </button>
                    <a href="ver-receta.php?id=<?php echo $recipeId; ?>" class="btn btn-secondary">
                        ❌ Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const currentImage = document.getElementById('currentImage');
            const label = document.querySelector('.file-input-label');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    if (currentImage) {
                        currentImage.style.display = 'none';
                    }
                    label.textContent = '📷 Cambiar Imagen';
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
                if (currentImage) {
                    currentImage.style.display = 'block';
                }
                label.textContent = '📷 Seleccionar Imagen';
            }
        }
    </script>
</body>
</html>