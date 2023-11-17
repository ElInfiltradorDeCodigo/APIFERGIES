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

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        if (isset($_GET['idEmpleado'])) {
            $idEmpleado = $conn->real_escape_string($_GET['idEmpleado']);
            $result = $conn->query("SELECT * FROM empleados WHERE idEmpleado = '$idEmpleado'");
            $data = $result->fetch_assoc();
            if ($data) {
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'No se encontró el empleado');
            }
        } else {
            $result = $conn->query("SELECT * FROM empleados");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            sendJsonResponse('success', $data);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents('php://input'));
        if (empty($data->nombre) || empty($data->correo) || empty($data->contraseña) || empty($data->puesto)) {
            sendJsonResponse('error', null, 'Todos los campos son obligatorios');
        } elseif (!validateEmail($data->correo)) {
            sendJsonResponse('error', null, 'El correo electrónico no es válido');
        } else {
            $stmt = $conn->prepare("INSERT INTO empleados (nombre, correo, contraseña, puesto, dirección, teléfono) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $data->nombre, $data->correo, $data->contraseña, $data->puesto, $data->dirección, $data->teléfono);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $data->idEmpleado = $stmt->insert_id;
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'Error al insertar el empleado');
            }
        }
        break;

    case 'PUT':
        if (isset($_GET['idEmpleado'])) {
            $idEmpleado = $conn->real_escape_string($_GET['idEmpleado']);
            $data = json_decode(file_get_contents('php://input'));
            if (empty($data->nombre) || empty($data->correo) || empty($data->contraseña) || empty($data->puesto)) {
                sendJsonResponse('error', null, 'Todos los campos son obligatorios');
            } elseif (!validateEmail($data->correo)) {
                sendJsonResponse('error', null, 'El correo electrónico no es válido');
            } else {
                $stmt = $conn->prepare("UPDATE empleados SET nombre = ?, correo = ?, contraseña = ?, puesto = ?, dirección = ?, teléfono = ? WHERE idEmpleado = ?");
                $stmt->bind_param("ssssssi", $data->nombre, $data->correo, $data->contraseña, $data->puesto, $data->dirección, $data->teléfono, $idEmpleado);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    sendJsonResponse('success', null);
                } else {
                    sendJsonResponse('error', null, 'Error al actualizar el empleado');
                }
            }
        }
        break;

    case 'DELETE':
        if (isset($_GET['idEmpleado'])) {
            $idEmpleado = $conn->real_escape_string($_GET['idEmpleado']);
            $stmt = $conn->prepare("DELETE FROM empleados WHERE idEmpleado = ?");
            $stmt->bind_param("i", $idEmpleado);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                sendJsonResponse('success', null);
            } else {
                sendJsonResponse('error', null, 'Error al eliminar el empleado');
            }
        }
        break;
}

$conn->close();
?>
