<?php

session_start();
require_once 'db.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "toys";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener datos del formulario
$accion = $_POST['accion'] ?? '';
$id_producto = isset($_POST['id']) ? intval($_POST['id']) : 0;
$cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 1;

// Validar cantidad mínima
if ($cantidad < 1) $cantidad = 1;

// Función para obtener datos del juguete
function obtenerJuguete($conn, $id) {
    $stmt = $conn->prepare("SELECT nombre, precio, cantidad_en_inventario FROM juguetes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $juguete = $resultado->fetch_assoc();
    $stmt->close();
    return $juguete;
}

switch ($accion) {
    case 'add':
        if ($id_producto > 0) {
            $juguete = obtenerJuguete($conn, $id_producto);
            if ($juguete) {
                // Verificar stock
                if ($cantidad <= $juguete['cantidad_en_inventario']) {
                    if (isset($_SESSION['carrito'][$id_producto])) {
                        // Sumar cantidad
                        $nueva_cantidad = $_SESSION['carrito'][$id_producto]['cantidad'] + $cantidad;
                        if ($nueva_cantidad > $juguete['cantidad_en_inventario']) {
                            $nueva_cantidad = $juguete['cantidad_en_inventario'];
                        }
                        $_SESSION['carrito'][$id_producto]['cantidad'] = $nueva_cantidad;
                    } else {
                        $_SESSION['carrito'][$id_producto] = [
                            'nombre' => $juguete['nombre'],
                            'precio' => $juguete['precio'],
                            'cantidad' => $cantidad
                        ];
                    }
                } else {
                    $_SESSION['mensaje_error'] = "Cantidad solicitada supera el stock disponible.";
                }
            }
        }
        break;

    case 'update':
        if ($id_producto > 0 && isset($_SESSION['carrito'][$id_producto])) {
            $juguete = obtenerJuguete($conn, $id_producto);
            if ($juguete) {
                if ($cantidad <= $juguete['cantidad_en_inventario']) {
                    $_SESSION['carrito'][$id_producto]['cantidad'] = $cantidad;
                } else {
                    $_SESSION['mensaje_error'] = "Cantidad solicitada supera el stock disponible.";
                }
            }
        }
        break;

    case 'delete':
        if ($id_producto > 0 && isset($_SESSION['carrito'][$id_producto])) {
            unset($_SESSION['carrito'][$id_producto]);
        }
        break;

    default:
        // Acción no válida
        break;
}

$conn->close();

// Redirigir a la página del carrito
header("Location: ver_carrito.php");
exit();
