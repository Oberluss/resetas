<?php
require_once "includes/auth.php";
requireLogin();

$currentUser = getCurrentUser();

// Verificar si es una petición POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

// Determinar si es edición o creación
$isEdit = isset($_POST["recipe_id"]) && !empty($_POST["recipe_id"]);
$recipeId = $isEdit ? intval($_POST["recipe_id"]) : null;

// Si es edición, verificar permisos
if ($isEdit) {
    $existingRecipe = $db->getById("recipes", $recipeId);
    
    if (!$existingRecipe) {
        header("Location: index.php?error=recipe_not_found");
        exit;
    }
    
    // Solo el propietario o admin puede editar
    if ($existingRecipe["user_id"] != $currentUser["id"] && !isAdmin()) {
        header("Location: index.php?error=permission_denied");
        exit;
    }
}

// Obtener datos del formulario
$title = trim($_POST["title"] ?? "");
$ingredients = trim($_POST["ingredients"] ?? "");
$instructions = trim($_POST["instructions"] ?? "");
$prep_time = intval($_POST["prep_time"] ?? 0);
$servings = intval($_POST["servings"] ?? 0);
$category_id = intval($_POST["category_id"] ?? 0);

// Validaciones
$errors = [];

if (empty($title)) {
    $errors[] = "El título es obligatorio";
}

if (empty($ingredients)) {
    $errors[] = "Los ingredientes son obligatorios";
}

if (empty($instructions)) {
    $errors[] = "Las instrucciones son obligatorias";
}

if ($category_id == 0) {
    $errors[] = "Debes seleccionar una categoría";
}

// Si hay errores, regresar
if (!empty($errors)) {
    session_start();
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    
    if ($isEdit) {
        header("Location: editar-receta.php?id=" . $recipeId);
    } else {
        header("Location: crear-receta.php");
    }
    exit;
}

// Procesar imagen si se subió
$photoPath = $isEdit ? $existingRecipe["photo"] : null;

if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
    $uploadedFile = $_FILES["photo"];
    $allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];
    
    // Verificar tipo de archivo
    $fileType = mime_content_type($uploadedFile["tmp_name"]);
    
    if (!in_array($fileType, $allowedTypes)) {
        session_start();
        $_SESSION['form_errors'] = ["Tipo de archivo no permitido. Solo se permiten imágenes JPG, PNG, GIF o WEBP"];
        $_SESSION['form_data'] = $_POST;
        
        if ($isEdit) {
            header("Location: editar-receta.php?id=" . $recipeId);
        } else {
            header("Location: crear-receta.php");
        }
        exit;
    }
    
    // Verificar tamaño (5MB máximo)
    if ($uploadedFile["size"] > 5 * 1024 * 1024) {
        session_start();
        $_SESSION['form_errors'] = ["La imagen no debe superar los 5MB"];
        $_SESSION['form_data'] = $_POST;
        
        if ($isEdit) {
            header("Location: editar-receta.php?id=" . $recipeId);
        } else {
            header("Location: crear-receta.php");
        }
        exit;
    }
    
    // Generar nombre único para la imagen
    $extension = pathinfo($uploadedFile["name"], PATHINFO_EXTENSION);
    $photoName = uniqid("recipe_") . "_" . time() . "." . $extension;
    $newPhotoPath = "photos/" . $photoName;
    
    // Crear directorio si no existe
    if (!file_exists("photos")) {
        mkdir("photos", 0755, true);
    }
    
    // Mover archivo
    if (move_uploaded_file($uploadedFile["tmp_name"], $newPhotoPath)) {
        // Si es edición y había una foto anterior, eliminarla
        if ($isEdit && !empty($existingRecipe["photo"]) && file_exists($existingRecipe["photo"])) {
            unlink($existingRecipe["photo"]);
        }
        
        $photoPath = $newPhotoPath;
    } else {
        session_start();
        $_SESSION['form_errors'] = ["Error al subir la imagen"];
        $_SESSION['form_data'] = $_POST;
        
        if ($isEdit) {
            header("Location: editar-receta.php?id=" . $recipeId);
        } else {
            header("Location: crear-receta.php");
        }
        exit;
    }
}

// Preparar datos de la receta
$recipeData = [
    "title" => $title,
    "ingredients" => $ingredients,
    "instructions" => $instructions,
    "prep_time" => $prep_time,
    "servings" => $servings,
    "category_id" => $category_id,
    "photo" => $photoPath
];

// Guardar o actualizar
if ($isEdit) {
    // Actualizar receta existente
    $result = $db->update("recipes", $recipeId, $recipeData);
    
    if ($result) {
        header("Location: ver-receta.php?id=" . $recipeId . "&updated=true");
    } else {
        session_start();
        $_SESSION['form_errors'] = ["Error al actualizar la receta"];
        $_SESSION['form_data'] = $_POST;
        header("Location: editar-receta.php?id=" . $recipeId);
    }
} else {
    // Crear nueva receta
    $recipeData["user_id"] = $currentUser["id"];
    
    $result = $db->insert("recipes", $recipeData);
    
    if ($result) {
        header("Location: index.php?saved=true");
    } else {
        session_start();
        $_SESSION['form_errors'] = ["Error al guardar la receta"];
        $_SESSION['form_data'] = $_POST;
        header("Location: crear-receta.php");
    }
}
exit;
?>

<?php
// Si llegamos aquí, algo salió mal, mostrar página de error
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Sistema de Recetas</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
        }
        .error-container h2 {
            color: #dc3545;
            margin-bottom: 20px;
        }
        .error-container p {
            color: #6c757d;
            margin-bottom: 30px;
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
    </style>
</head>
<body>
    <div class="error-container">
        <h2>❌ Error al procesar la receta</h2>
        <p>Ocurrió un error inesperado al procesar tu solicitud.</p>
        <a href="index.php" class="btn">Volver al Inicio</a>
    </div>
</body>
</html>
