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
                if ($data['foto'] !== null) {
                    $data['foto'] = base64_encode($data['foto']);
                }
                sendJsonResponse('success', $data);
            } else {
                sendJsonResponse('error', null, 'No se encontró el empleado');
            }
        } else {
            $result = $conn->query("SELECT * FROM empleados");
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
        if (empty($_POST['contrasena'])) {
            $errores[] = 'El campo contraseña es obligatorio';
        }
        if (empty($_POST['telefono'])) {
            $errores[] = 'El campo teléfono es obligatorio';
        }
        // Agrega aquí otras validaciones necesarias para los campos de empleados

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

        if (isset($_POST['idEmpleado'])) {
            // Actualización del empleado
            $idEmpleado = $conn->real_escape_string($_POST['idEmpleado']);
            $sql = "UPDATE empleados SET nombre = ?, correo = ?, contrasena = ?, puesto = ?, telefono = ?, calle = ?, colonia = ?, codigo_postal = ?" . ($foto !== null ? ", foto = ?" : "") . " WHERE idEmpleado = ?";
            $stmt = $conn->prepare($sql);
            if ($foto !== null) {
                $stmt->bind_param("ssssssssbi", $_POST['nombre'], $_POST['correo'], $_POST['contrasena'], $_POST['puesto'], $_POST['telefono'], $_POST['calle'], $_POST['colonia'], $_POST['codigo_postal'], $foto, $idEmpleado);
                $stmt->send_long_data(8, $foto);
            } else {
                $stmt->bind_param("ssssssssi", $_POST['nombre'], $_POST['correo'], $_POST['contrasena'], $_POST['puesto'], $_POST['telefono'], $_POST['calle'], $_POST['colonia'], $_POST['codigo_postal'], $idEmpleado);
            }
        } else {
            // Inserción de un nuevo empleado
            $stmt = $conn->prepare("INSERT INTO empleados (nombre, correo, contrasena, puesto, telefono, foto, calle, colonia, codigo_postal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssbsss", $_POST['nombre'], $_POST['correo'], $_POST['contrasena'], $_POST['puesto'], $_POST['telefono'], $foto, $_POST['calle'], $_POST['colonia'], $_POST['codigo_postal']);
            if ($foto !== null) {
                $stmt->send_long_data(5, $foto);
            }
        }

        // Ejecución y respuesta
        if ($stmt->execute()) {
            $response = isset($_POST['idEmpleado']) ? 'Empleado actualizado con éxito' : 'Empleado agregado con éxito';
            $data = ['idEmpleado' => isset($idEmpleado) ? $idEmpleado : $stmt->insert_id];
            sendJsonResponse('success', $data, $response);
        } else {
            $error = isset($_POST['idEmpleado']) ? 'Error al actualizar el empleado' : 'Error al insertar el empleado';
            sendJsonResponse('error', null, $error);
        }
        $stmt->close();
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

