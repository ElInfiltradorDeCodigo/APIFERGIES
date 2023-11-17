<?php
require_once 'headers.php';
require_once 'auth.php';

$conn = new mysqli('localhost', 'root', '', 'escuela');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        $id = $conn->real_escape_string($_GET['id']);
        $sql = $conn->query("SELECT * FROM alumnos WHERE id = '$id'");
        $data = $sql->fetch_assoc();
    }
    else {
        $data = array();
        $sql = $conn->query("SELECT * FROM alumnos");
        while ($d = $sql->fetch_assoc()) {
            $data[] = $d;
        }
    }
    exit(json_encode($data));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = verificarToken();
    $data = json_decode(file_get_contents('php://input'));

    // Validación de token
    if (!$token || $token->rol !== 'admin') {
        exit(json_encode(array('status' => 'error', 'message' => 'No autorizado para agregar alumnos')));
    }

      // Validación de datos
    if (empty($data->nombre) || empty($data->correo) || empty($data->telefono)) {
        exit(json_encode(array('status' => 'error', 'message' => 'Todos los campos son obligatorios')));
    }

    // Validación de correo electrónico
    if (!filter_var($data->correo, FILTER_VALIDATE_EMAIL)) {
        exit(json_encode(array('status' => 'error', 'message' => 'El correo electrónico no es válido')));
    }

    // Validación de número de teléfono (puedes adaptar esto según tus requisitos)
    if (!preg_match('/^\d{10}$/', $data->telefono)) {
        exit(json_encode(array('status' => 'error', 'message' => 'El número de teléfono no es válido')));
    }

    $sql = $conn->query("INSERT INTO alumnos(nombre, correo, telefono) VALUES('".$data->nombre."', '".$data->correo."', '".$data->telefono."')");
    if ($sql) {
        $data->id = $conn->insert_id;
        exit(json_encode($data));
    }
    else {
        exit(json_encode(array('status' => 'error')));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (isset($_GET['id'])) {
        $id = $conn->real_escape_string($_GET['id']);
        $data = json_decode(file_get_contents('php://input'));

         // Validación de datos
         if (empty($data->nombre) || empty($data->correo) || empty($data->telefono)) {
            exit(json_encode(array('status' => 'error', 'message' => 'Todos los campos son obligatorios')));
        }

        // Validación de correo electrónico
        if (!filter_var($data->correo, FILTER_VALIDATE_EMAIL)) {
            exit(json_encode(array('status' => 'error', 'message' => 'El correo electrónico no es válido')));
        }

        // Validación de número de teléfono (puedes adaptar esto según tus requisitos)
        if (!preg_match('/^\d{10}$/', $data->telefono)) {
            exit(json_encode(array('status' => 'error', 'message' => 'El número de teléfono no es válido')));
        }

        $sql = $conn->query("UPDATE alumnos SET nombre = '".$data->nombre."', correo = '".$data->correo."', telefono = '".$data->telefono."' WHERE id = '$id'");
        if ($sql) {
            exit(json_encode(array('status' => 'success')));
        }
        else {
            exit(json_encode(array('status' => 'error')));
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (isset($_GET['id'])) {
        $id = $conn->real_escape_string($_GET['id']);
        $sql = $conn->query("DELETE FROM alumnos WHERE id = '$id'");

        if ($sql) {
            exit(json_encode(array('status' => 'success')));
        }
        else {
            exit(json_encode(array('status' => 'error')));
        }
    }
}
?>