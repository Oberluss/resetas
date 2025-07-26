<?php
require_once "includes/auth.php";
requireLogin();

$currentUser = getCurrentUser();

// Obtener parámetros de búsqueda
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;
$userId = isset($_GET['user']) ? intval($_GET['user']) : 0;

// Obtener todas las recetas
$allRecipes = $db->getAll("recipes");
$categories = $db->getAll("categories");

// Filtrar recetas
$filteredRecipes = [];

foreach ($allRecipes as $recipe) {
    // Si se especifica un usuario, mostrar solo sus recetas
    if ($userId > 0 && $recipe["user_id"] != $userId) {
        continue;
    }
    
    // Si no es admin, mostrar solo las propias recetas
    if (!isAdmin() && $userId == 0 && $recipe["user_id"] != $currentUser["id"]) {
        continue;
    }
    
    // Filtrar por categoría si se especifica
    if ($categoryId > 0 && $recipe["category_id"] != $categoryId) {
        continue;
    }
    
    // Filtrar por búsqueda de texto
    if (!empty($searchQuery)) {
        $searchLower = strtolower($searchQuery);
        $titleMatch = stripos($recipe["title"], $searchLower) !== false;
        $ingredientsMatch = stripos($recipe["ingredients"], $searchLower) !== false;
        $instructionsMatch = stripos($recipe["instructions"], $searchLower) !== false;
        
        if (!$titleMatch && !$ingredientsMatch && !$instructionsMatch) {
            continue;
        }
    }
    
    $filteredRecipes[] = $recipe;
}

// Ordenar por fecha de creación (más recientes primero)
usort($filteredRecipes, function($a, $b) {
    return strtotime($b["created_at"]) - strtotime($a["created_at"]);
});

// Paginación
$recipesPerPage = 12;
$totalRecipes = count($filteredRecipes);
$totalPages = ceil($totalRecipes / $recipesPerPage);
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $recipesPerPage;

// Obtener recetas de la página actual
$recipesOnPage = array_slice($filteredRecipes, $offset, $recipesPerPage);

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
    <title>Buscar Recetas - Sistema de Recetas</title>
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
        .search-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .search-form {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 15px;
            align-items: end;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 5px;
            color: #495057;
            font-weight: 500;
        }
        .form-group input, .form-group select {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #28a745;
            outline: none;
        }
        .btn {
            padding: 10px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            transition: background 0.3s;
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
        .results-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
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
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .search-form {
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
        <div class="search-section">
            <h2>🔍 Buscar Recetas</h2>
            <form method="GET" action="buscar.php">
                <div class="search-form">
                    <div class="form-group">
                        <label for="q">Buscar por nombre o ingredientes:</label>
                        <input type="text" id="q" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                               placeholder="Ej: pasta, tomate, pollo...">
                    </div>
                    <div class="form-group">
                        <label for="category">Categoría:</label>
                        <select id="category" name="category">
                            <option value="0">Todas las categorías</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category["id"]; ?>" 
                                        <?php echo $categoryId == $category["id"] ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($category["name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (isAdmin()): ?>
                    <div class="form-group">
                        <label for="user">Usuario:</label>
                        <select id="user" name="user">
                            <option value="0">Todos los usuarios</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user["id"]; ?>" 
                                        <?php echo $userId == $user["id"] ? "selected" : ""; ?>>
                                    <?php echo htmlspecialchars($user["name"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn">Buscar</button>
                    <a href="buscar.php" class="btn btn-secondary">Limpiar</a>
                </div>
            </form>
        </div>

        <?php if ($searchQuery || $categoryId || $userId): ?>
            <div class="results-info">
                <span>
                    Se encontraron <strong><?php echo $totalRecipes; ?></strong> recetas
                    <?php if ($searchQuery): ?>
                        para "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
                    <?php endif; ?>
                </span>
                <span>
                    Página <?php echo $currentPage; ?> de <?php echo max(1, $totalPages); ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if (count($recipesOnPage) > 0): ?>
            <div class="recipes-grid">
                <?php foreach ($recipesOnPage as $recipe): ?>
                    <?php
                    $categoryName = "Sin categoría";
                    $categoryIcon = "📁";
                    foreach ($categories as $cat) {
                        if ($cat["id"] == $recipe["category_id"]) {
                            $categoryName = $cat["name"];
                            $categoryIcon = $cat["icon"] ?? "📁";
                            break;
                        }
                    }
                    
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
                                <span><?php echo $categoryIcon; ?> <?php echo htmlspecialchars($categoryName); ?></span>
                                <span>📅 <?php echo date("d/m/Y", strtotime($recipe["created_at"])); ?></span>
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
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">Primera</a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>">←</a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $currentPage - 2);
                    $end = min($totalPages, $currentPage + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <?php if ($i == $currentPage): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>">→</a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $totalPages])); ?>">Última</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="empty-state">
                <h3>😕 No se encontraron recetas</h3>
                <?php if ($searchQuery || $categoryId): ?>
                    <p>Intenta ajustar los filtros de búsqueda</p>
                    <a href="buscar.php" class="btn" style="margin-top: 20px;">Ver Todas las Recetas</a>
                <?php else: ?>
                    <p>¡Sé el primero en crear una receta!</p>
                    <a href="crear-receta.php" class="btn" style="margin-top: 20px;">➕ Crear Primera Receta</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
