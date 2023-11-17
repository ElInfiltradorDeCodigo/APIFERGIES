<?php
    require_once 'headers.php';
    require_once 'auth.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'));

         if (empty($data->username) || empty($data->password)) {
            exit(json_encode(array('status' => 'error', 'message' => 'Todos los campos son obligatorios')));
        }

        if ($data->username === "admin" && $data->password === "12345") {
            $rol = "admin";
            $token = generarToken($data->username, $rol);

            exit(json_encode(array('status' => 'success', 'token' => $token)));
        } else {
            exit(json_encode(array('status' => 'error', 'message' => 'Credenciales incorrectas')));
        }
    }
?>
