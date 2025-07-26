# 🍳 Sistema de Recetas con Base de Datos JSON

Un sistema web completo para gestionar recetas de cocina utilizando archivos JSON como base de datos. No requiere MySQL ni ningún otro sistema de base de datos.

## 📋 Características

### 🔐 Sistema de Autenticación
- Registro de nuevos usuarios
- Login seguro con contraseñas encriptadas
- Gestión de sesiones
- Roles de usuario (usuario normal y administrador)
- El primer usuario registrado se convierte automáticamente en administrador

### 📝 Gestión de Recetas
- **Crear**: Añade nuevas recetas con título, ingredientes, instrucciones y foto
- **Editar**: Modifica tus propias recetas
- **Eliminar**: Borra recetas con confirmación
- **Ver**: Visualiza recetas con formato atractivo
- **Categorizar**: Organiza recetas por categorías predefinidas

### 🔍 Búsqueda y Filtros
- Búsqueda por nombre de receta
- Búsqueda por ingredientes
- Filtrado por categorías
- Filtrado por usuario (solo administradores)
- Paginación automática de resultados

### 📷 Gestión de Imágenes
- Subida de fotos para cada receta
- Soporte para JPG, PNG, GIF y WEBP
- Vista previa antes de guardar
- Eliminación automática de imágenes al borrar recetas

### 👑 Panel de Administración
- Estadísticas del sistema
- Gestión de usuarios (activar/bloquear)
- Cambio de roles de usuario
- Vista de todas las recetas del sistema
- Información detallada de actividad

### 💾 Base de Datos JSON
- No requiere instalación de MySQL
- Datos almacenados en archivos JSON
- Backup fácil (solo copiar archivos)
- Portabilidad total

### 📱 Diseño Responsive
- Adaptado para móviles, tablets y desktop
- Interfaz moderna y atractiva
- Navegación intuitiva

## 🚀 Instalación

### Requisitos Previos
- PHP 7.0 o superior
- Servidor web (Apache, Nginx, etc.)
- Permisos de escritura en el directorio de instalación

### Opción 1: Instalador Automático (Recomendado)

1. **Descarga el instalador**
   ```
   wget https://raw.githubusercontent.com/Oberluss/resetas/main/instalador.php
   ```

2. **Súbelo a tu servidor web**
   - Por FTP o el método que prefieras

3. **Accede al instalador**
   ```
   http://tu-dominio.com/instalador.php
   ```

4. **Sigue las instrucciones en pantalla**
   - El instalador creará todos los archivos y carpetas necesarios
   - Descargará los archivos desde GitHub
   - Configurará los permisos automáticamente

5. **Elimina el instalador por seguridad**
   ```
   rm instalador.php
   ```

### Opción 2: Instalación Manual

1. **Clona el repositorio**
   ```bash
   git clone https://github.com/Oberluss/resetas.git
   cd resetas
   ```

2. **Crea las carpetas necesarias**
   ```bash
   mkdir -p data photos
   chmod 755 data photos
   ```

3. **Copia los archivos de ejemplo**
   ```bash
   cp -r data-example/* data/
   ```

4. **Configura permisos**
   ```bash
   chmod 644 data/*.json
   ```

5. **Accede al sistema**
   ```
   http://tu-dominio.com/login.php
   ```

## 📁 Estructura del Proyecto

```
resetas/
├── data/                    # Base de datos JSON (se crea automáticamente)
│   ├── users.json          # Usuarios del sistema
│   ├── recipes.json        # Recetas guardadas
│   └── categories.json     # Categorías de recetas
├── includes/               # Archivos del sistema
│   ├── db-json.php        # Manejador de base de datos JSON
│   └── auth.php           # Sistema de autenticación
├── photos/                 # Imágenes de recetas (se crea automáticamente)
├── data-example/           # Archivos JSON de ejemplo
├── index.php              # Página principal
├── login.php              # Página de login/registro
├── logout.php             # Cerrar sesión
├── buscar.php             # Búsqueda de recetas
├── categoria.php          # Ver recetas por categoría
├── crear-receta.php       # Crear nueva receta
├── editar-receta.php      # Editar receta existente
├── guardar-receta.php     # Procesar guardado de recetas
├── ver-receta.php         # Ver receta individual
├── admin.php              # Panel de administración
├── .htaccess              # Configuración de seguridad
└── README.md              # Este archivo
```

## 🔧 Configuración

### Categorías Predefinidas
El sistema viene con las siguientes categorías por defecto:
- 🥗 Entrantes
- 🍝 Platos principales
- 🍰 Postres
- 🥤 Bebidas
- 🥐 Desayunos
- 🥬 Vegetariano
- 🌱 Vegano
- 🌾 Sin gluten

Puedes modificarlas editando el archivo `data/categories.json`.

### Permisos de Usuario
- **Usuario Normal**: Solo puede ver y gestionar sus propias recetas
- **Administrador**: Puede ver todas las recetas y gestionar usuarios

## 📖 Uso del Sistema

### Primer Acceso
1. Accede a `login.php`
2. Haz clic en "Registrarse"
3. Completa el formulario (el primer usuario será administrador)
4. Inicia sesión con tus credenciales

### Crear una Receta
1. Haz clic en "➕ Nueva Receta" en el menú
2. Completa el formulario:
   - Título de la receta
   - Categoría
   - Tiempo de preparación
   - Porciones
   - Ingredientes (uno por línea)
   - Instrucciones paso a paso
   - Foto (opcional)
3. Haz clic en "Guardar Receta"

### Buscar Recetas
1. Usa la barra de búsqueda en la página principal
2. O haz clic en "🔍 Buscar" para búsqueda avanzada
3. Filtra por categoría o texto
4. Los administradores pueden filtrar por usuario

### Panel de Administración
Solo disponible para administradores:
1. Accede desde "⚙️ Admin" en el menú
2. Gestiona usuarios (activar/bloquear)
3. Cambia roles de usuario
4. Ve estadísticas del sistema
5. Accede a todas las recetas

## 🔒 Seguridad

- Contraseñas encriptadas con `password_hash()`
- Protección contra acceso no autorizado a archivos JSON
- Validación de tipos de archivo en uploads
- Sesiones seguras
- Escape de datos para prevenir XSS

## 🛠️ Solución de Problemas

### Error: "No se puede escribir en el directorio data"
```bash
chmod 755 data
chmod 644 data/*.json
```

### Error: "No se pueden subir imágenes"
```bash
chmod 755 photos
```

### Las imágenes no se muestran
- Verifica que la carpeta `photos/` tenga permisos de lectura
- Comprueba que el archivo `.htaccess` no esté bloqueando las imágenes

### Olvidé mi contraseña de administrador
1. Accede al servidor por FTP/SSH
2. Edita `data/users.json`
3. Borra el usuario o contacta con soporte

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:
1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 👥 Autor

- **Tu Nombre** - [Oberluss](https://github.com/Oberluss)

## 🙏 Agradecimientos

- Iconos de categorías por Emoji
- Diseño inspirado en Bootstrap
- Comunidad PHP por el soporte

## 📞 Soporte

Si encuentras algún problema o tienes sugerencias:
1. Abre un [Issue](https://github.com/Oberluss/resetas/issues)
2. Contacta por email: tu-email@ejemplo.com

---

**Nota**: Este sistema está diseñado para uso personal o pequeños grupos. Para aplicaciones con muchos usuarios concurrentes, considera migrar a una base de datos tradicional.
