<?php
require_once 'headers.php';
require_once 'auth.php';
require_once 'db_connect.php'; // Asegúrate de crear este archivo para la conexión a la base de datos.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'));

    if (empty($data->correo) || empty($data->contrasena)) {
        exit(json_encode(array('status' => 'error', 'message' => 'Todos los campos son obligatorios')));
    }

    // Conectar a la base de datos
    $conn = connectDB(); // Esta función debe estar definida en db_connect.php

    // Preparar la consulta SQL para evitar inyecciones SQL
    $stmt = $conn->prepare("SELECT correo, contrasena FROM empleados WHERE correo = ?");
    $stmt->bind_param("s", $data->correo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($data->contrasena == $row['contrasena']) { // Comparación directa de la contraseña
            $rol = "admin"; // Define el rol según tu lógica de negocio
            $token = generarToken($data->correo, $rol);
            exit(json_encode(array('status' => 'success', 'token' => $token)));
        } else {
            exit(json_encode(array('status' => 'error', 'message' => 'Credenciales incorrectas')));
        }
    } else {
        exit(json_encode(array('status' => 'error', 'message' => 'Usuario no encontrado')));
    }

    $stmt->close();
    $conn->close();
}
?>


