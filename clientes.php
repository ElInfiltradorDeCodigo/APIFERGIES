<?php

require_once 'headers.php';

$conn = new mysqli('localhost', 'root', '', 'fergies');

if ($conn->connect_error) {
    sendJsonResponse('error', null, "Conexión fallida: " . $conn->connect_error);
    exit;
}

function sendJsonResponse($status, $data, $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'data' => $data, 'message' => $message]);
    exit;
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function handleGetRequest($conn) {
    if (isset($_GET['idCliente'])) {
        $idCliente = $conn->real_escape_string($_GET['idCliente']);
        $stmt = $conn->prepare("SELECT * FROM clientes WHERE idCliente = ?");
        $stmt->bind_param("i", $idCliente);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($data = $result->fetch_assoc()) {
            sendJsonResponse('success', $data);
        } else {
            sendJsonResponse('error', null, 'Cliente no encontrado');
        }
    } else {
        $result = $conn->query("SELECT * FROM clientes");
        $clientes = [];
        while ($row = $result->fetch_assoc()) {
            $clientes[] = $row;
        }
        sendJsonResponse('success', $clientes);
    }
}

function handlePostRequest($conn) {
    if (empty($_POST['nombre']) || empty($_POST['correo']) || empty($_POST['telefono'])) {
        sendJsonResponse('error', null, 'Todos los campos son obligatorios');
    } elseif (!validateEmail($_POST['correo'])) {
        sendJsonResponse('error', null, 'El correo electrónico no es válido');
    } else {
        $foto = null;
        if (!empty($_FILES['foto']['tmp_name'])) {
            $foto = file_get_contents($_FILES['foto']['tmp_name']);
        }

        $stmt = $conn->prepare("INSERT INTO clientes (nombre, correo, telefono, calle, colonia, codigo_postal, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $_POST['nombre'], $_POST['correo'], $_POST['telefono'], $_POST['calle'], $_POST['colonia'], $_POST['codigo_postal'], $foto);

        if ($stmt->execute()) {
            $data = ['idCliente' => $stmt->insert_id];
            sendJsonResponse('success', $data);
        } else {
            sendJsonResponse('error', null, 'Error al insertar el cliente');
        }
    }
}

function handlePutRequest($conn) {
    parse_str(file_get_contents("php://input"), $_PUT);
    if (isset($_GET['idCliente'])) {
        $idCliente = $conn->real_escape_string($_GET['idCliente']);

        if (empty($_PUT['nombre']) || empty($_PUT['correo']) || empty($_PUT['telefono'])) {
            sendJsonResponse('error', null, 'Todos los campos son obligatorios');
        } elseif (!validateEmail($_PUT['correo'])) {
            sendJsonResponse('error', null, 'El correo electrónico no es válido');
        } else {
            $foto = null;
            if (!empty($_FILES['foto']['tmp_name'])) {
                $foto = file_get_contents($_FILES['foto']['tmp_name']);
            }

            $stmt = $conn->prepare("UPDATE clientes SET nombre = ?, correo = ?, telefono = ?, calle = ?, colonia = ?, codigo_postal = ?, foto = ? WHERE idCliente = ?");
            $stmt->bind_param("ssssssbi", $_PUT['nombre'], $_PUT['correo'], $_PUT['telefono'], $_PUT['calle'], $_PUT['colonia'], $_PUT['codigo_postal'], $foto, $idCliente);

            if ($stmt->execute()) {
                sendJsonResponse('success', null, 'Cliente actualizado con éxito');
            } else {
                sendJsonResponse('error', null, 'Error al actualizar el cliente');
            }
        }
    } else {
        sendJsonResponse('error', null, 'ID de cliente no proporcionado');
    }
}

function handleDeleteRequest($conn) {
    if (isset($_GET['idCliente'])) {
        $idCliente = $conn->real_escape_string($_GET['idCliente']);
        $stmt = $conn->prepare("DELETE FROM clientes WHERE idCliente = ?");
        $stmt->bind_param("i", $idCliente);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            sendJsonResponse('success', null, 'Cliente eliminado con éxito');
        } else {
            sendJsonResponse('error', null, 'Error al eliminar el cliente o cliente no encontrado');
        }
    } else {
        sendJsonResponse('error', null, 'ID de cliente no proporcionado');
    }
}

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetRequest($conn);
        break;

    case 'POST':
        handlePostRequest($conn);
        break;

    case 'PUT':
        handlePutRequest($conn);
        break;

    case 'DELETE':
        handleDeleteRequest($conn);
        break;
}

$conn->close();
?>



