<?php
// Inicializamos la sesión
session_start();

// Si el usuario ya estuviera logado lo dirigimos al Index
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header('Location: Index.php');
    exit;
}

// Incluimos la libreria de acceso a BD
require_once "BaseDatos.php";



// Decalarmos e inicializamos las variables
$email = $password = $usuario = "";
$email_err = $password_err = $login_err = $db_errors = "";

// obtenemos el ID que corresponde al superadmin para evitar bloquearlo en caso de que
// introduzca la contraseña erroneamente
$row = get_setup()->fetch();
$superUserId = $row[2];

// En este bloque procesamos el contenido del formulario cuando se realice el POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Detectamos si se ha dejado en blanco
    if (empty(trim($_POST["email"]))) {
        $email_err = "Por favor introduzca su email";
    } else {
        $email = trim($_POST["email"]);
    }

    // Detectamos si se ha dejado en blanco
    if (empty(trim($_POST["password"]))) {
        $password_err = "Por favor introduzca su password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Llevamos a cabo la validación de los dos valores en caso de que no se hayan presentado errores
    // en los controles anteriores
    if (empty($email_err) && empty($password_err)) {
        // Obtenemos el registro de usuario para el mail posteado
        try {
            $usuario = validate_user($email);
        } catch (Exception $e) {
            $db_errors = $e->getMessage();
        }

        // en caso de que hayamos obtenido un registro, significará que el usuario está registrado
        if (is_array($usuario)) {
            // extraemos el nombre y el id
            $fullName = $usuario[0];
            $userID = $usuario[1];

            // extraemos la password de usuario
            try {
                $password_ok = validate_password($email, $password);
            } catch (Exception $e) {
                $db_errors = $e->getMessage();
            }

            // utilizaremos una varia de sesion para controlar el numero de intentos de acceso
            // IMPORTANTE: esta variable sera única por user
            if (!isset($_SESSION["password_tries${email}"])) {
                $_SESSION["password_tries${email}"] = 0;
            }

            // si el arry tiene datos la consulta arroja datos
            if (is_array($password_ok)) {
                // Si el count no devuelve cero la paswword es correcta
                if ($password_ok[0]) {
                    // inicializamos intentos y seteamos la variables de sesion de logeo correspondientes
                    $_SESSION["password_tries${email}"] = 0;
                    $_SESSION["loggedin"] = true;
                    $_SESSION["name_loggedin"] = $fullName;
                    // dirigimos al index
                    header('Location: Index.php');
                    exit;
                } else {
                    // cuando se introduce una password erronea incrementamos el numero de intentos
                    $_SESSION["password_tries${email}"] += 1;
                    // personalizamos el mensaje de error en función de que el intento sea llevado a cabo por
                    // un suario normal o un superusuario
                    if ($userID !== $superUserId) {
                        $login_err = "Password invalida (intento {$_SESSION["password_tries${email}"]} / 3)";
                    } else {
                        $login_err = "Password invalida (SUPERADMIN intento {$_SESSION["password_tries${email}"]})";
                    }
                }
            } else {
                // si no se han devuleto datos la pass es erronea (no debe entrar nunca por esta parte del flujo
                // pero lo implementamos por claridad / seguridad)
                $_SESSION["password_tries${email}"] += 1;
                if ($userID !== $superUserId) {
                    $login_err = "Password invalida (intento {$_SESSION["password_tries${email}"]} / 3)";
                } else {
                    $login_err = "Password invalida (SUPERADMIN intento {$_SESSION["password_tries${email}"]})";
                }
            }
            // si sobrepasamos el numero de inetentos para un usuario que no sea superadmin lo bloqueamos
            if ($_SESSION["password_tries${email}"] > 2 && $userID !== $superUserId) {
                try {
                    lock_user($email);
                } catch (Exception $e) {
                    $db_errors = $e->getMessage();
                }
                $login_err = "Max. número intentos alcanzado. Usuario bloqueado";
            }
        } else {
            // si no obtenemos datos de usario, o bien no existe o bien está bloqueado
            $login_err = "El usuario no existe o está bloqueado";
        }
    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Autenticación</title>
</head>
<body style="text-align:center;">

<h1 style="color:blue;">
    WestStore
</h1>
<h3>
    Autenticación
</h3>
<br>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <label>Mail</label>
    <input type="email" name="email" value="<?php echo $email; ?>" autofocus>
    <br><br>
    <label>Pass</label>
    <input type="password" name="password">
    <br><br>
    <input type="submit" class="button" value="Acceder">
</form>
<br>
<?php
// mostramos los posibles errores
if (!empty($login_err)) {
    echo "<h4 style='color: #FF0000'>$login_err</h4>";
}
if (!empty($db_errors)) {
    echo "<h4 style='color: #FF0000'>$db_errors</h4>";
}
?>

</body>
</html>