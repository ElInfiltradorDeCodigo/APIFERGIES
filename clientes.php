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
        if (isset($_GET['idCliente'])) {
            $idCliente = $conn->real_escape_string($_GET['idCliente']);
            $result = $conn->query("SELECT * FROM clientes WHERE idCliente = '$idCliente'");
            $data = $result->fetch_assoc();
            if ($data) {
                if ($data['foto'] !== null) {
                    $data['foto'] = base64_encode($data['foto']);
                }
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'No se encontró el cliente');
            }
        } else {
            $result = $conn->query("SELECT * FROM clientes");
            $data = [];
            while ($row = $result->fetch_assoc()) {
                if ($row['foto'] !== null) {
                    $row['foto'] = base64_encode($row['foto']);
                }
                $data[] = $row;
            }
            sendJsonResponse('success', $data);
        }
        break;

    case 'POST':
        // Validaciones de campos
        $errores = [];
        if (empty($_POST['nombre'])) {
            $errores[] = 'El campo nombre es obligatorio';
        }
        if (empty($_POST['correo']) || !filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'Se requiere un correo electrónico válido';
        }
        if (empty($_POST['teléfono'])) {
            $errores[] = 'El campo teléfono es obligatorio';
        }
        if (strlen($_POST['calle']) > 80) {
            $errores[] = 'El campo calle no debe exceder los 80 caracteres';
        }
        if (strlen($_POST['colonia']) > 80) {
            $errores[] = 'El campo colonia no debe exceder los 80 caracteres';
        }
        if (isset($_POST['codigo_postal']) && strlen($_POST['codigo_postal']) != 5) {
            $errores[] = 'El código postal debe tener 5 caracteres';
        }

        // Si hay errores, enviar respuesta y salir
        if (count($errores) > 0) {
            sendJsonResponse('error', null, implode(', ', $errores));
            break;
        }

        // Manejo de la foto
        $foto = null;
        if (isset($_FILES['foto']) && $_FILES['foto']['tmp_name'] != '') {
            $foto = file_get_contents($_FILES['foto']['tmp_name']);
        }

        // Preparación de la consulta
        $stmt = $conn->prepare("INSERT INTO clientes (nombre, correo, teléfono, foto, calle, colonia, codigo_postal) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssbsss", $_POST['nombre'], $_POST['correo'], $_POST['teléfono'], $foto, $_POST['calle'], $_POST['colonia'], $_POST['codigo_postal']);

        if ($foto !== null) {
            $stmt->send_long_data(3, $foto);
        }

        // Ejecución y respuesta
        if ($stmt->execute()) {
            $data = [
                'idCliente' => $stmt->insert_id,
                'nombre' => $_POST['nombre'],
                'correo' => $_POST['correo'],
                'teléfono' => $_POST['teléfono'],
                'calle' => $_POST['calle'],
                'colonia' => $_POST['colonia'],
                'codigo_postal' => $_POST['codigo_postal']
                // Puedes añadir aquí otros campos si los agregas en el futuro
            ];
            sendJsonResponse('success', $data, 'Cliente agregado con éxito');
        } else {
            sendJsonResponse('error', null, 'Error al insertar el cliente');
        }
        $stmt->close();
        break;


    case 'PUT':
        if (isset($_GET['idCliente'])) {
            parse_str(file_get_contents("php://input"), $postData);
            $idCliente = $conn->real_escape_string($_GET['idCliente']);

            if (empty($postData['nombre']) || empty($postData['correo']) || empty($postData['telefono'])) {
                sendJsonResponse('error', null, 'Todos los campos son obligatorios');
                break;
            }

            $sql = "UPDATE clientes SET nombre = ?, correo = ?, telefono = ?, calle = ?, colonia = ?, codigo_postal = ?";
            if (isset($_FILES['foto'])) {
                $foto = file_get_contents($_FILES['foto']['tmp_name']);
                $sql .= ", foto = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssb", $postData['nombre'], $postData['correo'], $postData['telefono'], $postData['calle'], $postData['colonia'], $postData['codigo_postal'], $foto);
            } else {
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $postData['nombre'], $postData['correo'], $postData['telefono'], $postData['calle'], $postData['colonia'], $postData['codigo_postal']);
            }

            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                sendJsonResponse('success', null);
            } else {
                sendJsonResponse('error', null, 'Error al actualizar el cliente');
            }
        }
        break;

    case 'DELETE':
        if (isset($_GET['idCliente'])) {
            $idCliente = $conn->real_escape_string($_GET['idCliente']);
            $stmt = $conn->prepare("DELETE FROM clientes WHERE idCliente = ?");
            $stmt->bind_param("i", $idCliente);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                sendJsonResponse('success', null);
            } else {
                sendJsonResponse('error', null, 'Error al eliminar el cliente');
            }
        }
        break;
}

$conn->close();
?>




