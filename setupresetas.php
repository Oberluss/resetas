<?php
/**
 * INSTALADOR AUTOMÁTICO - Sistema de Recetas
 * Este archivo descarga e instala todo el sistema completo
 */

// Verificar si ya está instalado
if (file_exists('.installed') && !isset($_POST['force_install'])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema ya instalado</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f8f9fa; padding: 20px; text-align: center; }
            .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .btn { padding: 15px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 10px; display: inline-block; }
            .btn-danger { background: #dc3545; border: none; cursor: pointer; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>✅ Sistema ya instalado</h2>
            <p>El sistema de recetas ya está instalado en este directorio.</p>
            <a href="index.php" class="btn">Ir al Sistema</a>
            <form method="POST" style="display: inline;">
                <button type="submit" name="force_install" class="btn btn-danger">Reinstalar</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Función para descargar archivos de GitHub
function downloadFromGitHub($file, $localPath) {
    $githubUrl = "https://raw.githubusercontent.com/Oberluss/resetas/main/" . $file;
    $content = @file_get_contents($githubUrl);
    
    if ($content === false) {
        return false;
    }
    
    $dir = dirname($localPath);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    return file_put_contents($localPath, $content);
}

// Función para crear el archivo de configuración JSON
function createJsonConfig() {
    return '<?php
class JsonDatabase {
    private $dataPath;
    private $files = [
        "users" => "users.json",
        "recipes" => "recipes.json",
        "categories" => "categories.json"
    ];
    
    public function __construct() {
        $this->dataPath = dirname(__DIR__) . "/data/";
        $this->ensureDataDirectory();
    }
    
    private function ensureDataDirectory() {
        if (!file_exists($this->dataPath)) {
            mkdir($this->dataPath, 0777, true);
        }
    }
    
    private function getFilePath($collection) {
        return $this->dataPath . $this->files[$collection];
    }
    
    private function readData($collection) {
        $filePath = $this->getFilePath($collection);
        
        if (!file_exists($filePath)) {
            return ["next_id" => 1, $collection => []];
        }
        
        $json = file_get_contents($filePath);
        return json_decode($json, true);
    }
    
    private function writeData($collection, $data) {
        $filePath = $this->getFilePath($collection);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($filePath, $json);
    }
    
    public function getAll($collection) {
        $data = $this->readData($collection);
        return $data[$collection] ?? [];
    }
    
    public function getById($collection, $id) {
        $items = $this->getAll($collection);
        foreach ($items as $item) {
            if ($item["id"] == $id) {
                return $item;
            }
        }
        return null;
    }
    
    public function findBy($collection, $field, $value) {
        $items = $this->getAll($collection);
        $results = [];
        foreach ($items as $item) {
            if (isset($item[$field]) && $item[$field] == $value) {
                $results[] = $item;
            }
        }
        return $results;
    }
    
    public function findOneBy($collection, $field, $value) {
        $results = $this->findBy($collection, $field, $value);
        return !empty($results) ? $results[0] : null;
    }
    
    public function insert($collection, $item) {
        $data = $this->readData($collection);
        $item["id"] = $data["next_id"];
        $item["created_at"] = date("Y-m-d H:i:s");
        $data[$collection][] = $item;
        $data["next_id"]++;
        $this->writeData($collection, $data);
        return $item;
    }
    
    public function update($collection, $id, $updates) {
        $data = $this->readData($collection);
        $items = &$data[$collection];
        
        foreach ($items as &$item) {
            if ($item["id"] == $id) {
                $item = array_merge($item, $updates);
                $item["updated_at"] = date("Y-m-d H:i:s");
                $this->writeData($collection, $data);
                return $item;
            }
        }
        return null;
    }
    
    public function delete($collection, $id) {
        $data = $this->readData($collection);
        $items = &$data[$collection];
        
        foreach ($items as $key => $item) {
            if ($item["id"] == $id) {
                unset($items[$key]);
                $data[$collection] = array_values($items);
                $this->writeData($collection, $data);
                return true;
            }
        }
        return false;
    }
    
    public function searchRecipes($query) {
        $recipes = $this->getAll("recipes");
        $results = [];
        $searchTerm = strtolower($query);
        
        foreach ($recipes as $recipe) {
            $inTitle = stripos($recipe["title"], $searchTerm) !== false;
            $inIngredients = stripos($recipe["ingredients"], $searchTerm) !== false;
            $inInstructions = stripos($recipe["instructions"], $searchTerm) !== false;
            
            if ($inTitle || $inIngredients || $inInstructions) {
                $results[] = $recipe;
            }
        }
        
        return $results;
    }
}

$db = new JsonDatabase();
?>';
}

// Función para crear el archivo de autenticación
function createAuthFile() {
    return '<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once "includes/db-json.php";

function isLoggedIn() { 
    return isset($_SESSION["user_id"]); 
}

function isAdmin() { 
    return isset($_SESSION["role"]) && $_SESSION["role"] === "admin"; 
}

function requireLogin() {
    if (!isLoggedIn()) { 
        header("Location: login.php"); 
        exit; 
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) { 
        header("Location: index.php"); 
        exit; 
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        "id" => $_SESSION["user_id"],
        "username" => $_SESSION["username"], 
        "name" => $_SESSION["name"],
        "role" => $_SESSION["role"]
    ];
}

function getUserStats($userId = null) {
    global $db;
    
    if ($userId === null && isset($_SESSION["user_id"])) {
        $userId = $_SESSION["user_id"];
    }
    
    $recipes = $db->findBy("recipes", "user_id", $userId);
    $today = date("Y-m-d");
    
    $stats = [
        "total" => count($recipes), 
        "today" => 0,
        "this_week" => 0,
        "with_photos" => 0
    ];
    
    foreach ($recipes as $recipe) {
        $recipeDate = date("Y-m-d", strtotime($recipe["created_at"]));
        if ($recipeDate === $today) $stats["today"]++;
        
        $weekAgo = date("Y-m-d", strtotime("-7 days"));
        if (strtotime($recipe["created_at"]) >= strtotime($weekAgo)) {
            $stats["this_week"]++;
        }
        
        if (!empty($recipe["photo"]) && file_exists($recipe["photo"])) {
            $stats["with_photos"]++;
        }
    }
    
    return $stats;
}
?>';
}

// Función para crear el login.php
function createLoginFile() {
    return '<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

require_once "includes/db-json.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["login"])) {
    $username = trim($_POST["username"]);
    $password = $_POST["password"];
    
    if (empty($username) || empty($password)) {
        $error = "Por favor, completa todos los campos";
    } else {
        $user = $db->findOneBy("users", "username", $username);
        
        if ($user && password_verify($password, $user["password"])) {
            if ($user["status"] === "blocked") {
                $error = "Tu cuenta está bloqueada. Contacta al administrador.";
            } else {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["role"] = $user["role"];
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["register"])) {
    $username = trim($_POST["reg_username"]);
    $name = trim($_POST["reg_name"]);
    $password = $_POST["reg_password"];
    $confirmPassword = $_POST["reg_confirm_password"];
    
    if (empty($username) || empty($name) || empty($password) || empty($confirmPassword)) {
        $error = "Por favor, completa todos los campos";
    } elseif (strlen($password) < 6) {
        $error = "La contraseña debe tener al menos 6 caracteres";
    } elseif ($password !== $confirmPassword) {
        $error = "Las contraseñas no coinciden";
    } else {
        $existingUser = $db->findOneBy("users", "username", $username);
        
        if ($existingUser) {
            $error = "Este nombre de usuario ya existe";
        } else {
            $users = $db->getAll("users");
            $isFirstUser = count($users) === 0;
            
            $newUser = [
                "username" => $username,
                "name" => $name,
                "password" => password_hash($password, PASSWORD_DEFAULT),
                "role" => $isFirstUser ? "admin" : "user",
                "status" => "active"
            ];
            
            $db->insert("users", $newUser);
            $success = "Usuario registrado correctamente. Ahora puedes iniciar sesión.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema de Recetas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 400px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .body { padding: 30px; }
        .form-toggle {
            display: flex;
            margin-bottom: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 5px;
        }
        .toggle-btn {
            flex: 1;
            padding: 10px;
            background: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .toggle-btn.active {
            background: #28a745;
            color: white;
        }
        .form-group { margin-bottom: 20px; }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input:focus {
            border-color: #28a745;
            outline: none;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .btn:hover { background: #218838; }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-container { display: none; }
        .form-container.active { display: block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍳 Sistema de Recetas</h1>
            <p>Bienvenido a tu recetario digital</p>
        </div>
        
        <div class="body">
            <div class="form-toggle">
                <button class="toggle-btn active" onclick="showLogin()">Iniciar Sesión</button>
                <button class="toggle-btn" onclick="showRegister()">Registrarse</button>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <div id="login-form" class="form-container active">
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Usuario:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login" class="btn">Iniciar Sesión</button>
                </form>
            </div>
            
            <div id="register-form" class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label for="reg_username">Usuario:</label>
                        <input type="text" id="reg_username" name="reg_username" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_name">Nombre completo:</label>
                        <input type="text" id="reg_name" name="reg_name" required>
                    </div>
                    <div class="form-group">
                        <label for="reg_password">Contraseña:</label>
                        <input type="password" id="reg_password" name="reg_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="reg_confirm_password">Confirmar contraseña:</label>
                        <input type="password" id="reg_confirm_password" name="reg_confirm_password" required minlength="6">
                    </div>
                    <button type="submit" name="register" class="btn">Registrarse</button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showLogin() {
            document.getElementById("login-form").classList.add("active");
            document.getElementById("register-form").classList.remove("active");
            document.querySelectorAll(".toggle-btn")[0].classList.add("active");
            document.querySelectorAll(".toggle-btn")[1].classList.remove("active");
        }
        
        function showRegister() {
            document.getElementById("register-form").classList.add("active");
            document.getElementById("login-form").classList.remove("active");
            document.querySelectorAll(".toggle-btn")[1].classList.add("active");
            document.querySelectorAll(".toggle-btn")[0].classList.remove("active");
        }
    </script>
</body>
</html>';
}

if (isset($_POST['install']) || isset($_POST['force_install'])) {
    $results = [];
    $errors = [];
    
    // Crear directorios
    $dirs = ['data', 'includes', 'photos', 'css', 'js'];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                $results[] = "✅ Directorio '$dir' creado";
            } else {
                $errors[] = "❌ Error al crear directorio '$dir'";
            }
        } else {
            $results[] = "✅ Directorio '$dir' ya existe";
        }
    }
    
    // Archivos a descargar de GitHub
    $githubFiles = [
        'index.php' => 'index.php',
        'buscar.php' => 'buscar.php',
        'categoria.php' => 'categoria.php',
        'crear-receta.php' => 'crear-receta.php',
        'guardar-receta.php' => 'guardar-receta.php',
        'ver-receta.php' => 'ver-receta.php',
        'test.php' => 'test.php'
    ];
    
    // Descargar archivos desde GitHub
    foreach ($githubFiles as $remote => $local) {
        if (downloadFromGitHub($remote, $local)) {
            $results[] = "✅ Archivo '$local' descargado de GitHub";
        } else {
            $errors[] = "❌ Error al descargar '$local' de GitHub";
        }
    }
    
    // Crear archivos JSON
    $jsonFiles = [
        'data/users.json' => json_encode(["next_id" => 1, "users" => []], JSON_PRETTY_PRINT),
        'data/recipes.json' => json_encode(["next_id" => 1, "recipes" => []], JSON_PRETTY_PRINT),
        'data/categories.json' => json_encode([
            "categories" => [
                ["id" => 1, "name" => "Entrantes", "icon" => "🥗"],
                ["id" => 2, "name" => "Platos principales", "icon" => "🍝"],
                ["id" => 3, "name" => "Postres", "icon" => "🍰"],
                ["id" => 4, "name" => "Bebidas", "icon" => "🥤"],
                ["id" => 5, "name" => "Desayunos", "icon" => "🥐"],
                ["id" => 6, "name" => "Vegetariano", "icon" => "🥬"],
                ["id" => 7, "name" => "Vegano", "icon" => "🌱"],
                ["id" => 8, "name" => "Sin gluten", "icon" => "🌾"]
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    ];
    
    foreach ($jsonFiles as $file => $content) {
        if (file_put_contents($file, $content) !== false) {
            $results[] = "✅ Archivo '$file' creado";
        } else {
            $errors[] = "❌ Error al crear '$file'";
        }
    }
    
    // Crear archivos PHP del sistema
    $systemFiles = [
        'includes/db-json.php' => createJsonConfig(),
        'includes/auth.php' => createAuthFile(),
        'login.php' => createLoginFile(),
        'logout.php' => '<?php
session_start();
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), "", time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();
header("Location: login.php");
exit;
?>',
        '.htaccess' => 'DirectoryIndex index.php
<FilesMatch "\.(json)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
Options -Indexes
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^receta/([0-9]+)/?$ ver-receta.php?id=$1 [L,QSA]'
    ];
    
    foreach ($systemFiles as $file => $content) {
        if (file_put_contents($file, $content) !== false) {
            $results[] = "✅ Archivo '$file' creado";
        } else {
            $errors[] = "❌ Error al crear '$file'";
        }
    }
    
    // Marcar como instalado
    if (count($errors) == 0) {
        file_put_contents('.installed', date('Y-m-d H:i:s'));
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador Automático - Sistema de Recetas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .body { padding: 40px; }
        .btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            margin: 20px 0;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #218838; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0056b3; }
        .result {
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .file-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }
        .file-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
            font-size: 14px;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .feature {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #28a745;
        }
        .highlight {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #333;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍳 Instalador Automático del Sistema de Recetas</h1>
            <p>¡Instalación completa con descarga desde GitHub!</p>
        </div>

        <div class="body">
            <?php if (!isset($_POST['install']) && !isset($_POST['force_install'])): ?>
                
                <div class="highlight">
                    🎉 ¡INSTALADOR AUTOMÁTICO COMPLETO!<br>
                    Descarga los archivos desde GitHub y configura todo automáticamente
                </div>
                
                <h2>Sistema de Recetas con Base de Datos JSON</h2>
                <p>Este instalador descargará y configurará automáticamente todo el sistema de recetas, migrando de SQL a JSON.</p>
                
                <div class="info">
                    <h3>📋 Se instalarán automáticamente:</h3>
                    <div class="file-list">
                        <div class="file-item">📄 login.php - Sistema de autenticación</div>
                        <div class="file-item">📄 index.php - Página principal</div>
                        <div class="file-item">📄 buscar.php - Búsqueda de recetas</div>
                        <div class="file-item">📄 categoria.php - Gestión de categorías</div>
                        <div class="file-item">📄 crear-receta.php - Crear recetas</div>
                        <div class="file-item">📄 ver-receta.php - Ver recetas</div>
                        <div class="file-item">📄 guardar-receta.php - Guardar recetas</div>
                        <div class="file-item">📄 includes/db-json.php - Base de datos JSON</div>
                        <div class="file-item">📄 includes/auth.php - Autenticación</div>
                        <div class="file-item">📄 .htaccess - Configuración</div>
                        <div class="file-item">📁 data/ - Base de datos JSON</div>
                        <div class="file-item">📁 photos/ - Imágenes de recetas</div>
                    </div>
                </div>
                
                <div class="info">
                    <h3>🌟 Características del Sistema:</h3>
                    <div class="features">
                        <div class="feature">
                            <h4>🔐 Sistema de Usuarios</h4>
                            <p>Login, registro, roles y permisos</p>
                        </div>
                        <div class="feature">
                            <h4>📝 Gestión de Recetas</h4>
                            <p>Crear, editar, buscar y categorizar</p>
                        </div>
                        <div class="feature">
                            <h4>🗂️ Base de Datos JSON</h4>
                            <p>Sin necesidad de MySQL</p>
                        </div>
                        <div class="feature">
                            <h4>📷 Gestión de Imágenes</h4>
                            <p>Subida de fotos para cada receta</p>
                        </div>
                        <div class="feature">
                            <h4>🔍 Búsqueda Avanzada</h4>
                            <p>Por título, ingredientes o categoría</p>
                        </div>
                        <div class="feature">
                            <h4>📱 Diseño Responsive</h4>
                            <p>Optimizado para todos los dispositivos</p>
                        </div>
                    </div>
                </div>
                
                <div class="info">
                    <h3>⚠️ Requisitos:</h3>
                    <ul style="margin-left: 20px;">
                        <li>PHP 7.0 o superior</li>
                        <li>Permisos de escritura en el directorio</li>
                        <li>Conexión a Internet para descargar archivos de GitHub</li>
                    </ul>
                </div>
                
                <div style="text-align: center;">
                    <form method="POST">
                        <button type="submit" name="install" class="btn">
                            🚀 Instalar Sistema de Recetas Ahora
                        </button>
                    </form>
                    <p style="margin-top: 15px; color: #6c757d;">
                        <small>La instalación descargará los archivos desde GitHub</small>
                    </p>
                </div>
                
            <?php else: ?>
                <h2>Resultados de la Instalación</h2>
                
                <?php foreach ($results as $result): ?>
                    <div class="result success"><?php echo $result; ?></div>
                <?php endforeach; ?>
                
                <?php foreach ($errors as $error): ?>
                    <div class="result error"><?php echo $error; ?></div>
                <?php endforeach; ?>
                
                <?php if (count($errors) == 0): ?>
                    <div class="info">
                        <h3>🎉 ¡Instalación Completa Exitosa!</h3>
                        <p>El sistema de recetas se ha instalado correctamente con todas las funcionalidades.</p>
                        
                        <h4>✅ Sistema 100% Funcional:</h4>
                        <ul style="margin: 15px 0; padding-left: 20px;">
                            <li>✅ Sistema de autenticación completo</li>
                            <li>✅ Base de datos JSON configurada</li>
                            <li>✅ Archivos descargados desde GitHub</li>
                            <li>✅ Gestión completa de recetas</li>
                            <li>✅ Sistema de categorías</li>
                            <li>✅ Búsqueda avanzada</li>
                            <li>✅ Subida de imágenes</li>
                        </ul>
                        
                        <h4>🎯 Siguientes pasos:</h4>
                        <ol style="margin: 15px 0; padding-left: 20px;">
                            <li><strong>Accede al sistema</strong> haciendo clic en el botón de abajo</li>
                            <li><strong>Regístrate</strong> - El primer usuario será automáticamente administrador</li>
                            <li><strong>¡Comienza a crear recetas!</strong></li>
                        </ol>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="login.php" class="btn btn-primary">
                            🍳 Acceder al Sistema de Recetas
                        </a>
                        
                        <p style="margin-top: 20px; color: #6c757d;">
                            <small>🗑️ Puedes eliminar este archivo instalador después de acceder al sistema</small>
                        </p>
                    </div>
                    
                <?php else: ?>
                    <div class="info">
                        <h3>⚠️ Instalación Incompleta</h3>
                        <p>Se produjeron algunos errores. Verifica:</p>
                        <ul style="margin-left: 20px;">
                            <li>Permisos de escritura en el directorio</li>
                            <li>Conexión a Internet para descargar de GitHub</li>
                            <li>Versión de PHP (requiere 7.0+)</li>
                        </ul>
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <form method="POST">
                                <button type="submit" name="install" class="btn">
                                    🔄 Reintentar Instalación
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
