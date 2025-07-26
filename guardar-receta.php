<?php
require_once 'includes/conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Obtener y sanitizar los datos básicos
        $titulo = $conexion->real_escape_string($_POST['titulo']);
        $categoria = $conexion->real_escape_string($_POST['categoria']);
        $descripcion = $conexion->real_escape_string($_POST['descripcion']);
        $ingredientes = $conexion->real_escape_string($_POST['ingredientes']);
        $instrucciones = $conexion->real_escape_string($_POST['instrucciones']);
        $tiempo_preparacion = $conexion->real_escape_string($_POST['tiempo_preparacion']);
        $dificultad = $conexion->real_escape_string($_POST['dificultad']);
        $porciones = (int)$_POST['porciones'];

        // Manejar la imagen
        $imagen = '';
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['imagen']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $newname = uniqid() . '.' . $ext;
                $upload_dir = __DIR__ . '/assets/images/recetas/';
                
                // Crear directorio si no existe
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Subir la imagen
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $upload_dir . $newname)) {
                    $imagen = $newname;
                }
            }
        }

        // Preparar y ejecutar la consulta SQL
        $sql = "INSERT INTO recetas (titulo, categoria, descripcion, ingredientes, instrucciones, 
                tiempo_preparacion, dificultad, porciones, imagen) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sssssssss", 
            $titulo, $categoria, $descripcion, $ingredientes, 
            $instrucciones, $tiempo_preparacion, $dificultad, $porciones, $imagen
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al guardar la receta: " . $stmt->error);
        }

        $id_receta = $conexion->insert_id;
        
        // Redirigir a la página de la receta
        header("Location: ver-receta.php?id=" . $id_receta . "&created=1");
        exit;

    } catch (Exception $e) {
        // En caso de error, redirigir al formulario con mensaje de error
        header("Location: crear-receta.php?error=1&message=" . urlencode($e->getMessage()));
        exit;
    }
}
?>