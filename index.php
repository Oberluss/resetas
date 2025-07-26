<?php
require_once "includes/auth.php";
requireLogin();

$currentUser = getCurrentUser();
$stats = getUserStats();

// Obtener recetas recientes del usuario
$allRecipes = $db->getAll("recipes");
$userRecipes = array_filter($allRecipes, function($recipe) use ($currentUser) {
    return $recipe["user_id"] == $currentUser["id"];
});

// Ordenar por fecha de creación (más recientes primero)
usort($userRecipes, function($a, $b) {
    return strtotime($b["created_at"]) - strtotime($a["created_at"]);
});

// Tomar solo las 6 más recientes
$recentRecipes = array_slice($userRecipes, 0, 6);

// Obtener categorías
$categories = $db->getAll("categories");

$message = "";
$messageType = "";

if (isset($_GET["saved"]) && $_GET["saved"] === "true") {
    $message = "✅ Receta guardada correctamente";
    $messageType = "success";
} elseif (isset($_GET["error"])) {
    $message = "❌ Error al procesar la solicitud";
    $messageType = "error";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Recetas - <?php echo htmlspecialchars($currentUser["name"]); ?></title>
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
        .user-info {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 5px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            border-top: 4px solid #28a745;
        }
        .stat-card h3 {
            color: #28a745;
            font-size: 2rem;
            margin-bottom: 5px;
        }
        .stat-card p {
            color: #6c757d;
        }
        .section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .category-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .category-card:hover {
            border-color: #28a745;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .category-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .recipes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
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
        .btn-primary {
            background: #007bff;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .empty-state h3 {
            margin-bottom: 10px;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
        }
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 16px;
        }
        .search-box input:focus {
            border-color: #28a745;
            outline: none;
        }
        @media (max-width: 768px) {
            .navbar-content {
                text-align: center;
            }
            .nav-links {
                margin-top: 10px;
                justify-content: center;
            }
            .categories-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>🍳 Sistema de Recetas</h1>
            <div class="nav-links">
                <span class="user-info">
                    👤 <?php echo htmlspecialchars($currentUser["name"]); ?>
                    <?php if (isAdmin()): ?>(Admin)<?php endif; ?>
                </span>
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
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $stats["total"]; ?></h3>
                <p>Total Recetas</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats["today"]; ?></h3>
                <p>Hoy</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats["this_week"]; ?></h3>
                <p>Esta Semana</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats["with_photos"]; ?></h3>
                <p>Con Fotos</p>
            </div>
        </div>

        <!-- Búsqueda Rápida -->
        <div class="section">
            <h2>🔍 Búsqueda Rápida</h2>
            <form action="buscar.php" method="GET">
                <div class="search-box">
                    <input type="text" name="q" placeholder="Buscar recetas por nombre, ingredientes...">
                    <button type="submit" class="btn">Buscar</button>
                </div>
            </form>
        </div>

        <!-- Categorías -->
        <div class="section">
            <h2>📂 Categorías</h2>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <a href="categoria.php?id=<?php echo $category["id"]; ?>" class="category-card">
                        <div class="category-icon"><?php echo $category["icon"]; ?></div>
                        <div><?php echo htmlspecialchars($category["name"]); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="crear-receta.php" class="btn">➕ Crear Nueva Receta</a>
            </div>
        </div>

        <!-- Recetas Recientes -->
        <div class="section">
            <h2>🕒 Mis Recetas Recientes</h2>
            <?php if (count($recentRecipes) > 0): ?>
                <div class="recipes-grid">
                    <?php foreach ($recentRecipes as $recipe): ?>
                        <a href="ver-receta.php?id=<?php echo $recipe["id"]; ?>" class="recipe-card">
                            <?php if (!empty($recipe["photo"]) && file_exists($recipe["photo"])): ?>
                                <img src="<?php echo htmlspecialchars($recipe["photo"]); ?>" alt="<?php echo htmlspecialchars($recipe["title"]); ?>" class="recipe-image">
                            <?php else: ?>
                                <div class="recipe-image" style="display: flex; align-items: center; justify-content: center; font-size: 4rem; color: #e9ecef;">
                                    🍽️
                                </div>
                            <?php endif; ?>
                            <div class="recipe-content">
                                <h3><?php echo htmlspecialchars($recipe["title"]); ?></h3>
                                <?php 
                                $categoryName = "Sin categoría";
                                foreach ($categories as $cat) {
                                    if ($cat["id"] == $recipe["category_id"]) {
                                        $categoryName = $cat["name"];
                                        break;
                                    }
                                }
                                ?>
                                <div class="recipe-meta">
                                    <span>📁 <?php echo htmlspecialchars($categoryName); ?></span>
                                    <span>📅 <?php echo date("d/m/Y", strtotime($recipe["created_at"])); ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <?php if (count($userRecipes) > 6): ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="buscar.php?user=<?php echo $currentUser["id"]; ?>" class="btn btn-primary">
                            Ver Todas Mis Recetas (<?php echo count($userRecipes); ?>)
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>🍳 No tienes recetas guardadas</h3>
                    <p>¡Comienza creando tu primera receta!</p>
                    <a href="crear-receta.php" class="btn" style="margin-top: 20px;">➕ Crear Primera Receta</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
