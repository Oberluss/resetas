<?php
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
</html>