# 📦 Desechables Punto Fijo - Sistema de E-Commerce

![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?style=flat&logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap)
![License](https://img.shields.io/badge/License-MIT-green.svg)

Sistema completo de comercio electrónico para la gestión y venta de productos desechables. Desarrollado con PHP nativo, MySQL y Bootstrap 5.

## 📋 Descripción

**Desechables Punto Fijo** es una plataforma web integral que permite la gestión completa de un negocio de productos desechables, ubicado en Barahoja, Aguachica - Cesar, Colombia. El sistema incluye un catálogo público de productos, carrito de compras, sistema de checkout y un robusto panel administrativo.

## ✨ Características Principales

### 👥 Sistema de Usuarios
- ✅ Registro e inicio de sesión seguro
- ✅ Autenticación con contraseñas encriptadas (bcrypt)
- ✅ Roles de usuario (Administrador y Cliente)
- ✅ Gestión de perfiles de usuario

### 🛍️ Catálogo de Productos
- ✅ Visualización de productos con filtros avanzados
- ✅ Búsqueda en tiempo real
- ✅ Filtrado por categorías
- ✅ Productos destacados
- ✅ Indicadores de stock bajo
- ✅ Diseño responsivo y moderno

### 🛒 Carrito de Compras
- ✅ Agregar/eliminar productos
- ✅ Actualizar cantidades
- ✅ Validación de stock en tiempo real
- ✅ Cálculo automático de totales
- ✅ Persistencia de carrito por usuario

### 💳 Sistema de Checkout
- ✅ Proceso de compra intuitivo en 3 pasos
- ✅ Múltiples métodos de pago (Efectivo, Transferencia, Nequi, Daviplata)
- ✅ Confirmación de pedido
- ✅ Gestión de direcciones de entrega
- ✅ Actualización automática de inventario

### 🎛️ Panel Administrativo
- ✅ Dashboard con estadísticas en tiempo real
- ✅ **Gestión de Productos**: CRUD completo con control de stock
- ✅ **Gestión de Categorías**: Organización del catálogo
- ✅ **Gestión de Ventas**: Seguimiento y cambio de estados
- ✅ **Gestión de Usuarios**: Control de accesos y roles
- ✅ Alertas de productos con stock bajo
- ✅ Visualización de últimas ventas
- ✅ Reportes y estadísticas

## 🛠️ Tecnologías Utilizadas

### Backend
- **PHP 7.4+**: Lenguaje de programación principal
- **MySQL 8.0+**: Sistema de gestión de base de datos
- **PDO/MySQLi**: Para conexiones seguras a la base de datos

### Frontend
- **HTML5 & CSS3**: Estructura y estilos
- **Bootstrap 5.3**: Framework CSS responsivo
- **Bootstrap Icons**: Iconografía
- **JavaScript**: Interactividad del cliente

### Seguridad
- Password hashing con `password_hash()`
- Sanitización de inputs
- Prepared statements para prevenir SQL injection
- Validación de sesiones
- Control de acceso basado en roles

## 📋 Requisitos Previos

- **XAMPP** / **WAMP** / **MAMP** o cualquier servidor local con:
  - PHP 7.4 o superior
  - MySQL 8.0 o superior
  - Apache Server
- Navegador web moderno (Chrome, Firefox, Edge, Safari)

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/desechables-punto-fijo.git
cd desechables-punto-fijo
```

### 2. Configurar la base de datos

1. Abre **phpMyAdmin** (usualmente en `http://localhost/phpmyadmin`)
2. Crea una nueva base de datos llamada `desechables_punto_fijo`
3. Importa el archivo SQL:
   - Selecciona la base de datos
   - Ve a la pestaña "Importar"
   - Selecciona el archivo `database.sql` (debes crearlo con la estructura)

### 3. Configurar la conexión

Edita el archivo `config.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Tu contraseña de MySQL
define('DB_NAME', 'desechables_punto_fijo');
```

### 4. Ejecutar el script de actualización de contraseñas

**⚠️ IMPORTANTE**: Ejecuta este archivo **UNA SOLA VEZ** para configurar las contraseñas iniciales:

1. Navega a: `http://localhost/desechables-punto-fijo/actualizar_passwords.php`
2. Sigue las instrucciones en pantalla
3. **ELIMINA** el archivo `actualizar_passwords.php` por seguridad

### 5. Acceder al sistema

- **URL Principal**: `http://localhost/desechables-punto-fijo/`
- Serás redirigido automáticamente al login

## 🔑 Credenciales de Prueba

### Administrador
- **Email**: `admin@puntofijo.com`
- **Contraseña**: `admin123`

### Cliente
- **Email**: `cliente@demo.com`
- **Contraseña**: `admin123`

## 📁 Estructura del Proyecto

```
desechables-punto-fijo/
├── 📄 config.php                    # Configuración de BD y funciones globales
├── 📄 login.php                     # Inicio de sesión
├── 📄 registro.php                  # Registro de usuarios
├── 📄 logout.php                    # Cerrar sesión
├── 📄 index.php                     # Catálogo público de productos
├── 📄 carrito.php                   # Carrito de compras
├── 📄 checkout.php                  # Proceso de checkout
├── 📄 admin.php                     # Dashboard administrativo
├── 📄 admin_productos.php           # Gestión de productos
├── 📄 admin_categorias.php          # Gestión de categorías
├── 📄 admin_ventas.php              # Gestión de ventas
├── 📄 admin_usuarios.php            # Gestión de usuarios
├── 📄 ajax_detalle_venta.php        # Detalle de ventas (AJAX)
├── 📄 actualizar_passwords.php      # Script inicial (eliminar después)
├── 📂 css/
│   ├── style.css                    # Estilos generales
│   ├── admin.css                    # Estilos del panel admin
│   ├── auth.css                     # Estilos de autenticación
│   └── carrito.css                  # Estilos de carrito/checkout
└── 📄 README.md                     # Este archivo
```

## 🎨 Características de Diseño

- **Diseño Responsivo**: Adaptable a dispositivos móviles, tablets y desktop
- **Gradientes Modernos**: Paleta de colores (#667eea - #764ba2)
- **Animaciones Sutiles**: Transiciones suaves en hover y clicks
- **UI/UX Intuitiva**: Navegación clara y fluida
- **Iconografía Consistente**: Bootstrap Icons en todo el sistema

## 📊 Funcionalidades del Panel Admin

### Dashboard
- Estadísticas en tiempo real
- Total de productos, ventas, clientes e ingresos
- Productos con stock bajo
- Últimas 5 ventas registradas

### Productos
- Crear, editar y eliminar productos
- Control de stock
- Productos destacados
- Códigos de producto
- Múltiples unidades de medida

### Categorías
- Organización del catálogo
- Estados activo/inactivo
- Conteo de productos por categoría

### Ventas
- Seguimiento de todas las ventas
- Cambio de estados (pendiente, procesando, completada, cancelada)
- Detalle completo de cada venta
- Información de cliente y productos

### Usuarios
- Gestión de clientes y administradores
- Cambio de roles
- Activar/desactivar usuarios
- Historial de compras

## 🔒 Seguridad Implementada

- ✅ Contraseñas hasheadas con `password_hash()` y `PASSWORD_DEFAULT`
- ✅ Prepared Statements para todas las consultas SQL
- ✅ Sanitización de inputs con funciones personalizadas
- ✅ Validación de sesiones en todas las páginas protegidas
- ✅ Control de acceso basado en roles
- ✅ Regeneración de ID de sesión al iniciar sesión
- ✅ Protección contra SQL injection y XSS

## 🐛 Solución de Problemas

### Error de conexión a la base de datos
```
Verifica que:
1. MySQL esté corriendo
2. Las credenciales en config.php sean correctas
3. La base de datos exista
```

### Las contraseñas no funcionan
```
Ejecuta nuevamente actualizar_passwords.php
Asegúrate de usar exactamente: admin123
```

### Los estilos no cargan
```
Verifica la ruta de los archivos CSS
Limpia el caché del navegador (Ctrl + F5)
```

## 📞 Información de Contacto

**Desechables Punto Fijo**
- 📍 Calle 4ta #6-51, Barrio Barahoja, Aguachica - Cesar
- 📱 317 726 8740 | 315 744 1535

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add: nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo `LICENSE` para más detalles.

## 🎯 Roadmap

- [ ] Sistema de notificaciones por email
- [ ] Reportes en PDF
- [ ] Pasarela de pago integrada
- [ ] Sistema de cupones y descuentos
- [ ] Historial de pedidos para clientes
- [ ] API REST
- [ ] Panel de métricas avanzadas

## 📸 Capturas de Pantalla

### Login
![Login](screenshots/login.png)

### Catálogo de Productos
![Catalogo](screenshots/catalogo.png)

### Panel Administrativo
![Admin](screenshots/admin-dashboard.png)

### Carrito de Compras
![Carrito](screenshots/carrito.png)

---

⭐ Si este proyecto te fue útil, considera darle una estrella en GitHub

💼 Desarrollado con ❤️ para Desechables Punto Fijo Barahoja