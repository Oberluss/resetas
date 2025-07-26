<?php
require_once "includes/auth.php";
requireAdmin();

$currentUser = getCurrentUser();

// Obtener estadísticas
$allUsers = $db->getAll("users");
$allRecipes = $db->getAll("recipes");
$categories = $db->getAll("categories");

// Calcular estadísticas
$stats = [
    "total_users" => count($allUsers),
    "active_users" => count(array_filter($allUsers, function($u) { return $u["status"] === "active"; })),
    "admin_users" => count(array_filter($allUsers, function($u) { return $u["role"] === "admin"; })),
    "total_recipes" => count($allRecipes),
    "recipes_today" => count(array_filter($allRecipes, function($r) { 
        return date("Y-m-d", strtotime($r["created_at"])) === date("Y-m-d"); 
    })),
    "recipes_week" => count(array_filter($allRecipes, function($r) { 
        return strtotime($r["created_at"]) >= strtotime("-7 days"); 
    }))
];

// Manejar acciones de usuario
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["update_user_status"])) {
        $userId = $_POST["user_id"];
        $status = $_POST["status"];
        
        $result = $db->update("users", $userId, ["status" => $status]);
        if ($result) {
            $message = "✅ Estado de usuario actualizado correctamente";
            $messageType = "success";
            // Recargar usuarios
            $allUsers = $db->getAll("users");
        } else {
            $message = "❌ Error al actualizar estado del usuario";
            $messageType = "error";
        }
    }
    
    if (isset($_POST["update_user_role"])) {
        $userId = $_POST["user_id"];
        $role = $_POST["role"];
        
        // No permitir quitar el último admin
        $adminCount = count(array_filter($allUsers, function($u) { return $u["role"] === "admin"; }));
        if ($adminCount <= 1 && $role === "user") {
            $targetUser = null;
            foreach ($allUsers as $u) {
                if ($u["id"] == $userId) {
                    $targetUser = $u;
                    break;
                }
            }
            if ($targetUser && $targetUser["role"] === "admin") {
                $message = "❌ No se puede quitar el último administrador";
                $messageType = "error";
            }
        } else {
            $result = $db->update("users", $userId, ["role" => $role]);
            if ($result) {
                $message = "✅ Rol de usuario actualizado correctamente";
                $messageType = "success";
                // Recargar usuarios
                $allUsers = $db->getAll("users");
            } else {
                $message = "❌ Error al actualizar rol del usuario";
                $messageType = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de Recetas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-content {
            max-width: 1400px;
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
            max-width: 1400px;
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
            border-top: 4px solid #dc3545;
        }
        .stat-card h3 {
            color: #dc3545;
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
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        .table tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-blocked { background: #f8d7da; color: #721c24; }
        .role-admin { background: #f8d7da; color: #721c24; }
        .role-user { background: #d1ecf1; color: #0c5460; }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .recipe-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .recipe-info h4 {
            color: #dc3545;
            margin-bottom: 5px;
        }
        .recipe-meta {
            color: #6c757d;
            font-size: 0.875rem;
        }
        @media (max-width: 768px) {
            .navbar-content {
                text-align: center;
            }
            .nav-links {
                margin-top: 10px;
                justify-content: center;
            }
            .table {
                font-size: 14px;
            }
            .table th, .table td {
                padding: 8px;
            }
            .btn-sm {
                padding: 2px 6px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <h1>⚙️ Panel de Administración</h1>
            <div class="nav-links">
                <a href="index.php">Inicio</a>
                <a href="crear-receta.php">➕ Nueva Receta</a>
                <a href="buscar.php">🔍 Buscar</a>
                <a href="admin.php">⚙️ Admin</a>
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
                <h3><?php echo $stats["total_users"]; ?></h3>
                <p>Total Usuarios</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats["active_users"]; ?></h3>
                <p>Usuarios Activos</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats["admin_users"]; ?></h3>
                <p>Administradores</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats["total_recipes"]; ?></h3>
                <p>Total Recetas</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats["recipes_today"]; ?></h3>
                <p>Recetas Hoy</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $stats["recipes_week"]; ?></h3>
                <p>Recetas Esta Semana</p>
            </div>
        </div>

        <!-- Gestión de Usuarios -->
        <div class="section">
            <h2>👥 Gestión de Usuarios</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Usuario</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Recetas</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allUsers as $user): ?>
                        <?php
                        $userRecipes = array_filter($allRecipes, function($r) use ($user) {
                            return $r["user_id"] == $user["id"];
                        });
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($user["username"]); ?></strong></td>
                            <td><?php echo htmlspecialchars($user["name"]); ?></td>
                            <td>
                                <span class="status-badge role-<?php echo $user["role"]; ?>">
                                    <?php echo ucfirst($user["role"]); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $user["status"]; ?>">
                                    <?php echo $user["status"] === "active" ? "Activo" : "Bloqueado"; ?>
                                </span>
                            </td>
                            <td><?php echo count($userRecipes); ?></td>
                            <td><?php echo date("d/m/Y", strtotime($user["created_at"])); ?></td>
                            <td>
                                <?php if ($user["id"] != $currentUser["id"]): ?>
                                    <!-- Cambiar estado -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user["id"]; ?>">
                                        <?php if ($user["status"] === "active"): ?>
                                            <input type="hidden" name="status" value="blocked">
                                            <button type="submit" name="update_user_status" class="btn btn-warning btn-sm">
                                                Bloquear
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" name="update_user_status" class="btn btn-success btn-sm">
                                                Activar
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                    
                                    <!-- Cambiar rol -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user["id"]; ?>">
                                        <?php if ($user["role"] === "user"): ?>
                                            <input type="hidden" name="role" value="admin">
                                            <button type="submit" name="update_user_role" class="btn btn-danger btn-sm">
                                                Hacer Admin
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="role" value="user">
                                            <button type="submit" name="update_user_role" class="btn btn-primary btn-sm">
                                                Hacer Usuario
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #6c757d; font-size: 12px;">Tu cuenta</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Últimas Recetas -->
        <div class="section">
            <h2>🕒 Últimas Recetas del Sistema</h2>
            <?php 
            $recentRecipes = array_slice($allRecipes, -10);
            $recentRecipes = array_reverse($recentRecipes);
            ?>
            
            <?php if (count($recentRecipes) > 0): ?>
                <?php foreach ($recentRecipes as $recipe): ?>
                    <?php
                    $author = null;
                    foreach ($allUsers as $user) {
                        if ($user["id"] == $recipe["user_id"]) {
                            $author = $user;
                            break;
                        }
                    }
                    
                    $category = null;
                    foreach ($categories as $cat) {
                        if ($cat["id"] == $recipe["category_id"]) {
                            $category = $cat;
                            break;
                        }
                    }
                    ?>
                    <div class="recipe-item">
                        <div class="recipe-info">
                            <h4><?php echo htmlspecialchars($recipe["title"]); ?></h4>
                            <p class="recipe-meta">
                                👤 <strong><?php echo $author ? htmlspecialchars($author["name"]) : "Usuario eliminado"; ?></strong> - 
                                📁 <?php echo $category ? htmlspecialchars($category["name"]) : "Sin categoría"; ?> - 
                                📅 <?php echo date("d/m/Y H:i", strtotime($recipe["created_at"])); ?>
                            </p>
                        </div>
                        <a href="ver-receta.php?id=<?php echo $recipe["id"]; ?>" class="btn btn-primary btn-sm">
                            Ver Receta
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #6c757d; padding: 40px;">
                    No hay recetas registradas en el sistema
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>