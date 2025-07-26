<?php
require_once "includes/auth.php";
requireLogin();

$currentUser = getCurrentUser();

// Obtener ID de la categoría
$categoryId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($categoryId == 0) {
    header("Location: index.php");
    exit;
}

// Obtener información de la categoría
$categories = $db->getAll("categories");
$selectedCategory = null;

foreach ($categories as $cat) {
    if ($cat["id"] == $categoryId) {
        $selectedCategory = $cat;
        break;
    }
}

if (!$selectedCategory) {
    header("Location: index.php?error=category_not_found");
    exit;
}

// Obtener todas las recetas
$allRecipes = $db->getAll("recipes");

// Filtrar recetas por categoría y permisos
$categoryRecipes = [];

foreach ($allRecipes as $recipe) {
    // Solo mostrar recetas de la categoría seleccionada
    if ($recipe["category_id"] != $categoryId) {
        continue;
    }
    
    // Si no es admin, mostrar solo las propias recetas
    if (!isAdmin() && $recipe["user_id"] != $currentUser["id"]) {
        continue;
    }
    
    $categoryRecipes[] = $recipe;
}

// Ordenar por fecha de creación (más recientes primero)
usort($categoryRecipes, function($a, $b) {
    return strtotime($b["created_at"]) - strtotime($a["created_at"]);
});

// Paginación
$recipesPerPage = 12;
$totalRecipes = count($categoryRecipes);
$totalPages = ceil($totalRecipes / $recipesPerPage);
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $recipesPerPage;

// Obtener recetas de la página actual
$recipesOnPage = array_slice($categoryRecipes, $offset, $recipesPerPage);

// Obtener información de usuarios si es admin
$users = [];
if (isAdmin()) {
    $users = $db->getAll("users");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($selectedCategory["name"]); ?> - Sistema de Recetas</title>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .category-header {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .category-icon {
            font-size: 4rem;
            margin-bottom: 10px;
        }
        .category-title {
            font-size: 2rem;
            color: #333;
            margin-bottom: 10px;
        }
        .category-stats {
            color: #6c757d;
            margin-bottom: 20px;
        }
        .other-categories {
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .category-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            background: #f8f9fa;
            border-radius: 20px;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .category-link:hover {
            border-color: #28a745;
            background: white;
            transform: translateY(-2px);
        }
        .category-link.active {
            background: #28a745;
            color: white;
        }
        .recipes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .recipe-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            text-decoration: none;
            color: inherit;
        }
        .recipe-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .recipe-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #e9ecef;
        }
        .recipe-content {
            padding: 20px;
        }
        .recipe-content h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .recipe-meta {
            color: #6c757d;
            font-size: 0.875rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 10px;
        }
        .recipe-author {
            background: #f8f9fa;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 0.75rem;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #218838;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            color: #495057;
            transition: all 0.3s;
        }
        .pagination a:hover {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        .pagination .active {
            background: #28a745;
            color: white;
            border-color: #28a745;
        }
        @media (max-width: 768px) {
            .navbar-content {
                text-align: center;
            }
            .nav-links {
                margin-top: 10px;
                justify-content: center;
            }
            .category-title {
                font-size: 1.5rem;
            }
            .category-icon {
                font-size: 3rem;
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
        <div class="category-header">
            <div class="category-icon"><?php echo $selectedCategory["icon"] ?? "📁"; ?></div>
            <h2 class="category-title"><?php echo htmlspecialchars($selectedCategory["name"]); ?></h2>
            <div class="category-stats">
                <?php echo $totalRecipes; ?> receta<?php echo $totalRecipes != 1 ? 's' : ''; ?> en esta categoría
            </div>
            
            <div class="other-categories">
                <span style="color: #6c757d; margin-right: 10px;">Otras categorías:</span>
                <?php foreach ($categories as $cat): ?>
                    <?php if ($cat["id"] != $categoryId): ?>
                        <a href="categoria.php?id=<?php echo $cat["id"]; ?>" class="category-link">
                            <?php echo $cat["icon"] ?? "📁"; ?>
                            <?php echo htmlspecialchars($cat["name"]); ?>
                        </a>
                    <?php else: ?>
                        <span class="category-link active">
                            <?php echo $cat["icon"] ?? "📁"; ?>
                            <?php echo htmlspecialchars($cat["name"]); ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (count($recipesOnPage) > 0): ?>
            <div class="recipes-grid">
                <?php foreach ($recipesOnPage as $recipe): ?>
                    <?php
                    $authorName = "Usuario";
                    if (isAdmin()) {
                        foreach ($users as $user) {
                            if ($user["id"] == $recipe["user_id"]) {
                                $authorName = $user["name"];
                                break;
                            }
                        }
                    }
                    ?>
                    <a href="ver-receta.php?id=<?php echo $recipe["id"]; ?>" class="recipe-card">
                        <?php if (!empty($recipe["photo"]) && file_exists($recipe["photo"])): ?>
                            <img src="<?php echo htmlspecialchars($recipe["photo"]); ?>" 
                                 alt="<?php echo htmlspecialchars($recipe["title"]); ?>" class="recipe-image">
                        <?php else: ?>
                            <div class="recipe-image" style="display: flex; align-items: center; justify-content: center; font-size: 4rem; color: #e9ecef;">
                                🍽️
                            </div>
                        <?php endif; ?>
                        <div class="recipe-content">
                            <h3><?php echo htmlspecialchars($recipe["title"]); ?></h3>
                            <div class="recipe-meta">
                                <span>📅 <?php echo date("d/m/Y", strtotime($recipe["created_at"])); ?></span>
                                <?php if ($recipe["prep_time"] > 0): ?>
                                    <span>⏱️ <?php echo $recipe["prep_time"]; ?> min</span>
                                <?php endif; ?>
                            </div>
                            <?php if (isAdmin()): ?>
                                <div class="recipe-meta" style="margin-top: 5px;">
                                    <span class="recipe-author">👤 <?php echo htmlspecialchars($authorName); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="?id=<?php echo $categoryId; ?>&page=1">Primera</a>
                        <a href="?id=<?php echo $categoryId; ?>&page=<?php echo $currentPage - 1; ?>">←</a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $currentPage - 2);
                    $end = min($totalPages, $currentPage + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <?php if ($i == $currentPage): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?id=<?php echo $categoryId; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?id=<?php echo $categoryId; ?>&page=<?php echo $currentPage + 1; ?>">→</a>
                        <a href="?id=<?php echo $categoryId; ?>&page=<?php echo $totalPages; ?>">Última</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-state">
                <h3>📭 No hay recetas en esta categoría</h3>
                <p>¡Sé el primero en agregar una receta de <?php echo htmlspecialchars($selectedCategory["name"]); ?>!</p>
                <a href="crear-receta.php" class="btn" style="margin-top: 20px;">➕ Crear Nueva Receta</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
