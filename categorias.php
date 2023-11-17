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
        if (isset($_GET['idCategoria'])) {
            $idCategoria = $conn->real_escape_string($_GET['idCategoria']);
            $result = $conn->query("SELECT * FROM categorias WHERE idCategoria = '$idCategoria'");
            $data = $result->fetch_assoc();
            if ($data) {
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'No se encontró la categoría');
            }
        } else {
            $result = $conn->query("SELECT * FROM categorias");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            sendJsonResponse('success', $data);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'));
        if (empty($data->nombre)) {
            sendJsonResponse('error', null, 'El campo nombre es obligatorio');
        } else {
            $stmt = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
            $stmt->bind_param("s", $data->nombre);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $data->idCategoria = $stmt->insert_id;
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'Error al insertar la categoría');
            }
        }
        break;

    case 'PUT':
        if (isset($_GET['idCategoria'])) {
            $idCategoria = $conn->real_escape_string($_GET['idCategoria']);
            $data = json_decode(file_get_contents('php://input'));
            if (empty($data->nombre)) {
                sendJsonResponse('error', null, 'El campo nombre es obligatorio');
            } else {
                $stmt = $conn->prepare("UPDATE categorias SET nombre = ? WHERE idCategoria = ?");
                $stmt->bind_param("si", $data->nombre, $idCategoria);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    sendJsonResponse('success', null);
                } else {
                    sendJsonResponse('error', null, 'Error al actualizar la categoría');
                }
            }
        }
        break;

    case 'DELETE':
        if (isset($_GET['idCategoria'])) {
            $idCategoria = $conn->real_escape_string($_GET['idCategoria']);
            $stmt = $conn->prepare("DELETE FROM categorias WHERE idCategoria = ?");
            $stmt->bind_param("i", $idCategoria);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                sendJsonResponse('success', null);
            } else {
                sendJsonResponse('error', null, 'Error al eliminar la categoría');
            }
        }
        break;
}

$conn->close();
?>


