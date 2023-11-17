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
        if (isset($_GET['idDistribuidor'])) {
            $idDistribuidor = $conn->real_escape_string($_GET['idDistribuidor']);
            $result = $conn->query("SELECT * FROM distribuidores WHERE idDistribuidor = '$idDistribuidor'");
            $data = $result->fetch_assoc();
            if ($data) {
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'No se encontró el distribuidor');
            }
        } else {
            $result = $conn->query("SELECT * FROM distribuidores");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            sendJsonResponse('success', $data);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'));
        if (empty($data->nombre) || empty($data->contacto) || empty($data->calle) || empty($data->colonia) || empty($data->numero) || empty($data->codigoPostal)) {
            sendJsonResponse('error', null, 'Todos los campos son obligatorios');
        } else {
            $stmt = $conn->prepare("INSERT INTO distribuidores (nombre, contacto, calle, colonia, numero, codigoPostal) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $data->nombre, $data->contacto, $data->calle, $data->colonia, $data->numero, $data->codigoPostal);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $data->idDistribuidor = $stmt->insert_id;
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'Error al insertar el distribuidor');
            }
        }
        break;

    case 'PUT':
        if (isset($_GET['idDistribuidor'])) {
            $idDistribuidor = $conn->real_escape_string($_GET['idDistribuidor']);
            $data = json_decode(file_get_contents('php://input'));
            if (empty($data->nombre) || empty($data->contacto) || empty($data->calle) || empty($data->colonia) || empty($data->numero) || empty($data->codigoPostal)) {
                sendJsonResponse('error', null, 'Todos los campos son obligatorios');
            } else {
                $stmt = $conn->prepare("UPDATE distribuidores SET nombre = ?, contacto = ?, calle = ?, colonia = ?, numero = ?, codigoPostal = ? WHERE idDistribuidor = ?");
                $stmt->bind_param("ssssssi", $data->nombre, $data->contacto, $data->calle, $data->colonia, $data->numero, $data->codigoPostal, $idDistribuidor);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    sendJsonResponse('success', null);
                } else {
                    sendJsonResponse('error', null, 'Error al actualizar el distribuidor');
                }
            }
        }
        break;

    case 'DELETE':
        if (isset($_GET['idDistribuidor'])) {
            $idDistribuidor = $conn->real_escape_string($_GET['idDistribuidor']);
            $stmt = $conn->prepare("DELETE FROM distribuidores WHERE idDistribuidor = ?");
            $stmt->bind_param("i", $idDistribuidor);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                sendJsonResponse('success', null);
            } else {
                sendJsonResponse('error', null, 'Error al eliminar el distribuidor');
            }
        }
        break;
}

$conn->close();
?>


?>

