<?php
// Clave secreta para firmar el token (debe ser segura)
$claveSecreta = 'clave_secreta';

function generarToken($usuario, $rol) {
    global $claveSecreta;

    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode(['usuario' => $usuario, 'rol' => $rol, 'exp' => time() + 3600]); // Caduca en 1 hora

    $headerBase64 = base64_encode($header);
    $payloadBase64 = base64_encode($payload);

    $signature = hash_hmac('sha256', "$headerBase64.$payloadBase64", $claveSecreta, true);
    $signatureBase64 = base64_encode($signature);

    return "$headerBase64.$payloadBase64.$signatureBase64";
}

function verificarToken() {
    global $claveSecreta;

    $headers = apache_request_headers();

    if (isset($headers['Authorization']) && preg_match('/Bearer (.+)/', $headers['Authorization'], $matches)) {
        $token = $matches[1];

        list($headerBase64, $payloadBase64, $signature) = explode('.', $token);

        $data = "$headerBase64.$payloadBase64";
        $signatureExpected = base64_encode(hash_hmac('sha256', $data, $claveSecreta, true));

        if ($signature === $signatureExpected) {
            $payload = json_decode(base64_decode($payloadBase64));
            return $payload;
        }
    }

    return null;
}
?>
