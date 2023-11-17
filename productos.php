<?php

require_once 'headers.php';

$conn = new mysqli('localhost', 'root', '', 'fergies');

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

function sendJsonResponse($status, $data, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'data' => $data, 'message' => $message]);
    exit;
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['idProducto'])) {
            $idProducto = $conn->real_escape_string($_GET['idProducto']);
            $result = $conn->query("SELECT nombre, idDistribuidor, idCategoria, precio, stock, columna_imagen FROM productos WHERE idProducto = '$idProducto'");
            $data = $result->fetch_assoc();
            if ($data) {
                if ($data['columna_imagen'] !== null) {
                    $data['columna_imagen'] = base64_encode($data['columna_imagen']);
                }
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'No se encontró el producto');
            }
        } else {
            $result = $conn->query("SELECT idProducto, nombre, idDistribuidor, idCategoria, precio, stock, columna_imagen FROM productos");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                if ($row['columna_imagen'] !== null) {
                    $row['columna_imagen'] = base64_encode($row['columna_imagen']);
                }
                $data[] = $row;
            }
            sendJsonResponse('success', $data);
        }
        break;

    case 'POST':
        if (empty($_POST['nombre']) || empty($_POST['idDistribuidor']) || empty($_POST['idCategoria']) || empty($_POST['precio']) || empty($_POST['stock']) || !isset($_FILES['imagen'])) {
            sendJsonResponse('error', null, 'Todos los campos son obligatorios');
        } else {
            $imagen = file_get_contents($_FILES['imagen']['tmp_name']);
            $stmt = $conn->prepare("INSERT INTO productos (nombre, idDistribuidor, idCategoria, precio, stock, columna_imagen) VALUES (?, ?, ?, ?, ?, ?)");
            $null = NULL;
            $stmt->bind_param("siidib", $_POST['nombre'], $_POST['idDistribuidor'], $_POST['idCategoria'], $_POST['precio'], $_POST['stock'], $null);
            $stmt->send_long_data(5, $imagen);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $data = [
                    'idProducto' => $stmt->insert_id,
                    'nombre' => $_POST['nombre'],
                    'idDistribuidor' => $_POST['idDistribuidor'],
                    'idCategoria' => $_POST['idCategoria'],
                    'precio' => $_POST['precio'],
                    'stock' => $_POST['stock']
                ];
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'Error al insertar el producto');
            }
        }
        break;

    case 'PUT':
        if (isset($_GET['idProducto'])) {
            parse_str(file_get_contents("php://input"), $postData);
            $idProducto = $conn->real_escape_string($_GET['idProducto']);

            if (empty($postData['nombre']) || empty($postData['idDistribuidor']) || empty($postData['idCategoria']) || empty($postData['precio']) || empty($postData['stock'])) {
                sendJsonResponse('error', null, 'Todos los campos son obligatorios');
                break;
            }

            $sql = "UPDATE productos SET nombre = ?, idDistribuidor = ?, idCategoria = ?, precio = ?, stock = ?";
            if (isset($_FILES['imagen'])) {
                $imagen = file_get_contents($_FILES['imagen']['tmp_name']);
                $sql .= ", columna_imagen = ?";
                $stmt = $conn->prepare($sql);
                $null = NULL;
                $stmt->bind_param("siidib", $postData['nombre'], $postData['idDistribuidor'], $postData['idCategoria'], $postData['precio'], $postData['stock'], $null);
                $stmt->send_long_data(5, $imagen);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("siidi", $postData['nombre'], $postData['idDistribuidor'], $postData['idCategoria'], $postData['precio'], $postData['stock']);
            }

            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                sendJsonResponse('success', null);
            } else {
                sendJsonResponse('error', null, 'Error al actualizar el producto');
            }
        }
        break;

    case 'DELETE':
        if (isset($_GET['idProducto'])) {
            $idProducto = $conn->real_escape_string($_GET['idProducto']);
            $stmt = $conn->prepare("DELETE FROM productos WHERE idProducto = ?");
            $stmt->bind_param("i", $idProducto);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                sendJsonResponse('success', null);
            } else {
                sendJsonResponse('error', null, 'Error al eliminar el producto');
            }
        }
        break;
}

$conn->close();
?>




