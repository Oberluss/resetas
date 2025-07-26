
<?php
/**
 * INSTALADOR AUTOMÁTICO - Sistema de Recetas JSON
 * Versión actualizada para repositorio con archivos ya modificados
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

if (isset($_POST['install']) || isset($_POST['force_install'])) {
    $results = [];
    $errors = [];
    
    // Crear directorios necesarios
    $dirs = ['data', 'photos', 'includes'];
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
    
    // Lista de archivos a descargar desde GitHub
    $githubFiles = [
        // Archivos principales
        'index.php' => 'index.php',
        'buscar.php' => 'buscar.php',
        'categoria.php' => 'categoria.php',
        'crear-receta.php' => 'crear-receta.php',
        'editar-receta.php' => 'editar-receta.php',
        'guardar-receta.php' => 'guardar-receta.php',
        'ver-receta.php' => 'ver-receta.php',
        'login.php' => 'login.php',
        'logout.php' => 'logout.php',
        'admin.php' => 'admin.php',
        '.htaccess' => '.htaccess',
        
        // Archivos includes
        'includes/db-json.php' => 'includes/db-json.php',
        'includes/auth.php' => 'includes/auth.php'
    ];
    
    // Descargar archivos desde GitHub
    foreach ($githubFiles as $remote => $local) {
        if (downloadFromGitHub($remote, $local)) {
            $results[] = "✅ Archivo '$local' descargado correctamente";
        } else {
            $errors[] = "❌ Error al descargar '$local' - Verifica que el archivo exista en GitHub";
        }
    }
    
    // Crear archivos JSON iniciales solo si no existen
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
        if (!file_exists($file)) {
            if (file_put_contents($file, $content) !== false) {
                $results[] = "✅ Base de datos '$file' creada";
            } else {
                $errors[] = "❌ Error al crear '$file'";
            }
        } else {
            $results[] = "⚠️ Base de datos '$file' ya existe (no se sobrescribió)";
        }
    }
    
    // Verificar permisos de escritura
    if (is_writable('data') && is_writable('photos')) {
        $results[] = "✅ Permisos de escritura verificados";
    } else {
        $errors[] = "❌ Error de permisos - Las carpetas 'data' y 'photos' deben tener permisos de escritura";
    }
    
    // Marcar como instalado si no hay errores críticos
    if (count($errors) == 0 || (count($errors) < 3 && count($results) > 10)) {
        file_put_contents('.installed', date('Y-m-d H:i:s'));
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - Sistema de Recetas JSON</title>
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
        .warning { background: #fff3cd; color: #856404; }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
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
        .requirements {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .requirements ul {
            margin-left: 20px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🍳 Instalador del Sistema de Recetas</h1>
            <p>Instalación automática desde GitHub</p>
        </div>

        <div class="body">
            <?php if (!isset($_POST['install']) && !isset($_POST['force_install'])): ?>
                
                <div class="highlight">
                    🎉 Sistema de Recetas con Base de Datos JSON<br>
                    ¡Sin necesidad de MySQL!
                </div>
                
                <h2>Bienvenido al Instalador</h2>
                <p>Este instalador descargará y configurará automáticamente el sistema de recetas desde GitHub.</p>
                
                <div class="requirements">
                    <h3>⚠️ Requisitos del Sistema:</h3>
                    <ul>
                        <li>PHP 7.0 o superior</li>
                        <li>Función <code>file_get_contents()</code> habilitada</li>
                        <li>Permisos de escritura en el directorio actual</li>
                        <li>Conexión a Internet para descargar archivos</li>
                    </ul>
                </div>
                
                <div class="info">
                    <h3>📋 ¿Qué hará el instalador?</h3>
                    <div class="file-list">
                        <div class="file-item">✅ Crear directorios necesarios</div>
                        <div class="file-item">✅ Descargar archivos desde GitHub</div>
                        <div class="file-item">✅ Crear base de datos JSON</div>
                        <div class="file-item">✅ Configurar permisos</div>
                        <div class="file-item">✅ Verificar la instalación</div>
                    </div>
                </div>
                
                <div class="info">
                    <h3>🌟 Características del Sistema:</h3>
                    <div class="features">
                        <div class="feature">
                            <h4>🔐 Sistema de Usuarios</h4>
                            <p>Login, registro y roles</p>
                        </div>
                        <div class="feature">
                            <h4>📝 Gestión de Recetas</h4>
                            <p>Crear, editar y eliminar</p>
                        </div>
                        <div class="feature">
                            <h4>🔍 Búsqueda Avanzada</h4>
                            <p>Por nombre e ingredientes</p>
                        </div>
                        <div class="feature">
                            <h4>📷 Subida de Imágenes</h4>
                            <p>Fotos para cada receta</p>
                        </div>
                        <div class="feature">
                            <h4>📱 Diseño Responsive</h4>
                            <p>Adaptado a todos los dispositivos</p>
                        </div>
                        <div class="feature">
                            <h4>👑 Panel Admin</h4>
                            <p>Control total del sistema</p>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <form method="POST">
                        <button type="submit" name="install" class="btn">
                            🚀 Instalar Sistema de Recetas
                        </button>
                    </form>
                    <p style="margin-top: 15px; color: #6c757d;">
                        <small>La instalación descargará los archivos desde GitHub</small>
                    </p>
                </div>
                
            <?php else: ?>
                <h2>Proceso de Instalación</h2>
                
                <?php foreach ($results as $result): ?>
                    <div class="result success"><?php echo $result; ?></div>
                <?php endforeach; ?>
                
                <?php foreach ($errors as $error): ?>
                    <div class="result error"><?php echo $error; ?></div>
                <?php endforeach; ?>
                
                <?php if (count($errors) == 0): ?>
                    <div class="info">
                        <h3>🎉 ¡Instalación Completada con Éxito!</h3>
                        <p>El sistema de recetas se ha instalado correctamente.</p>
                        
                        <h4>✅ Componentes instalados:</h4>
                        <ul style="margin: 15px 0; padding-left: 20px;">
                            <li>Sistema de autenticación</li>
                            <li>Base de datos JSON</li>
                            <li>Gestión de recetas</li>
                            <li>Sistema de categorías</li>
                            <li>Búsqueda avanzada</li>
                            <li>Panel de administración</li>
                        </ul>
                        
                        <h4>🎯 Siguientes pasos:</h4>
                        <ol style="margin: 15px 0; padding-left: 20px;">
                            <li>Haz clic en el botón de abajo para acceder al sistema</li>
                            <li>Regístrate como primer usuario (serás administrador)</li>
                            <li>¡Comienza a crear tus recetas!</li>
                        </ol>
                    </div>
                    
                    <div style="text-align: center; margin-top: 30px;">
                        <a href="login.php" class="btn btn-primary">
                            🍳 Acceder al Sistema de Recetas
                        </a>
                        
                        <p style="margin-top: 20px; color: #dc3545;">
                            <strong>⚠️ Importante:</strong> Por seguridad, elimina este archivo instalador
                        </p>
                    </div>
                    
                <?php elseif (count($errors) < 3 && count($results) > 10): ?>
                    <div class="info">
                        <h3>⚠️ Instalación Completada con Advertencias</h3>
                        <p>El sistema se instaló pero algunos componentes opcionales no se pudieron configurar.</p>
                        <p>El sistema debería funcionar correctamente.</p>
                        
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="login.php" class="btn btn-primary">
                                🍳 Acceder al Sistema de Recetas
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="info">
                        <h3>❌ Instalación Fallida</h3>
                        <p>Se produjeron errores críticos durante la instalación.</p>
                        
                        <h4>Posibles soluciones:</h4>
                        <ul style="margin-left: 20px;">
                            <li>Verifica que tienes permisos de escritura en el directorio</li>
                            <li>Asegúrate de que PHP puede acceder a URLs externas</li>
                            <li>Comprueba que el repositorio de GitHub esté accesible</li>
                            <li>Verifica que todos los archivos estén en el repositorio</li>
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
