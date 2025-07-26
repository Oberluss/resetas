<?php
require_once "includes/auth.php";
requireLogin();

$currentUser = getCurrentUser();

// Obtener ID de la receta
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

// Verificar permisos de visualización
if (!isAdmin() && $recipe["user_id"] != $currentUser["id"]) {
    header("Location: index.php?error=permission_denied");
    exit;
}

// Obtener categoría
$categories = $db->getAll("categories");
$category = null;
foreach ($categories as $cat) {
    if ($cat["id"] == $recipe["category_id"]) {
        $category = $cat;
        break;
    }
}

// Obtener información del autor
$users = $db->getAll("users");
$author = null;
foreach ($users as $user) {
    if ($user["id"] == $recipe["user_id"]) {
        $author = $user;
        break;
    }
}

// Procesar eliminación si se solicitó
if (isset($_POST['delete']) && $_POST['delete'] == 'true') {
    // Verificar permisos para eliminar
    if ($recipe["user_id"] == $currentUser["id"] || isAdmin()) {
        // Eliminar imagen si existe
        if (!empty($recipe["photo"]) && file_exists($recipe["photo"])) {
            unlink($recipe["photo"]);
        }
        
        // Eliminar receta
        $db->delete("recipes", $recipeId);
        
        header("Location: index.php?deleted=true");
        exit;
    }
}

$message = "";
if (isset($_GET["updated"]) && $_GET["updated"] === "true") {
    $message = "✅ Receta actualizada correctamente";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($recipe["title"]); ?> - Sistema de Recetas</title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        .recipe-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .recipe-header {
            padding: 30px;
            border-bottom: 1px solid #e9ecef;
        }
        .recipe-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 20px;
        }
        .recipe-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            color: #6c757d;
            font-size: 0.9rem;
        }
        .recipe-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .recipe-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            display: block;
        }
        .recipe-content {
            padding: 30px;
        }
        .recipe-section {
            margin-bottom: 30px;
        }
        .recipe-section h3 {
            color: #28a745;
            font-size: 1.3rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .ingredients-list {
            list-style: none;
            padding: 0;
        }
        .ingredients-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .ingredients-list li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 8px;
        }
        .instructions {
            line-height: 1.8;
            white-space: pre-wrap;
        }
        .recipe-actions {
            padding: 20px 30px;
            background: #f8f9fa;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 16px;
        }
        .btn:hover {
            background: #218838;
        }
        .btn-primary {
            background: #007bff;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .author-info {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            display: inline-block;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            text-align: center;
        }
        .modal h3 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .navbar-content {
                text-align: center;
            }
            .nav-links {
                margin-top: 10px;
                justify-content: center;
            }
            .recipe-title {
                font-size: 1.5rem;
            }
            .recipe-meta {
                font-size: 0.8rem;
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
        <?php if ($message): ?>
            <div class="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="recipe-container">
            <?php if (!empty($recipe["photo"]) && file_exists($recipe["photo"])): ?>
                <img src="<?php echo htmlspecialchars($recipe["photo"]); ?>" 
                     alt="<?php echo htmlspecialchars($recipe["title"]); ?>" 
                     class="recipe-image">
            <?php endif; ?>

            <div class="recipe-header">
                <h2 class="recipe-title"><?php echo htmlspecialchars($recipe["title"]); ?></h2>
                <div class="recipe-meta">
                    <div class="recipe-meta-item">
                        <span>📁</span>
                        <span><?php echo $category ? htmlspecialchars($category["name"]) : "Sin categoría"; ?></span>
                    </div>
                    <?php if ($recipe["prep_time"] > 0): ?>
                    <div class="recipe-meta-item">
                        <span>⏱️</span>
                        <span><?php echo $recipe["prep_time"]; ?> minutos</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($recipe["servings"] > 0): ?>
                    <div class="recipe-meta-item">
                        <span>🍽️</span>
                        <span><?php echo $recipe["servings"]; ?> porciones</span>
                    </div>
                    <?php endif; ?>
                    <div class="recipe-meta-item">
                        <span>📅</span>
                        <span><?php echo date("d/m/Y", strtotime($recipe["created_at"])); ?></span>
                    </div>
                    <?php if ($author): ?>
                    <div class="recipe-meta-item author-info">
                        <span>👤</span>
                        <span><?php echo htmlspecialchars($author["name"]); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="recipe-content">
                <div class="recipe-section">
                    <h3>🛒 Ingredientes</h3>
                    <ul class="ingredients-list">
                        <?php 
                        $ingredients = array_filter(array_map('trim', explode("\n", $recipe["ingredients"])));
                        foreach ($ingredients as $ingredient): 
                            if (!empty($ingredient)):
                        ?>
                            <li><?php echo htmlspecialchars($ingredient); ?></li>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </ul>
                </div>

                <div class="recipe-section">
                    <h3>👨‍🍳 Instrucciones</h3>
                    <div class="instructions"><?php echo nl2br(htmlspecialchars($recipe["instructions"])); ?></div>
                </div>
            </div>

            <div class="recipe-actions">
                <a href="buscar.php" class="btn btn-secondary">← Volver</a>
                
                <?php if ($recipe["user_id"] == $currentUser["id"] || isAdmin()): ?>
                    <a href="editar-receta.php?id=<?php echo $recipeId; ?>" class="btn btn-primary">✏️ Editar</a>
                    <button onclick="confirmDelete()" class="btn btn-danger">🗑️ Eliminar</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>⚠️ ¿Eliminar receta?</h3>
            <p>¿Estás seguro de que quieres eliminar esta receta?</p>
            <p><strong>Esta acción no se puede deshacer.</strong></p>
            <div class="modal-actions">
                <button onclick="closeModal()" class="btn btn-secondary">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="delete" value="true">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
