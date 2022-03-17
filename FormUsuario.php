<?php
// iniciamos sesión
session_start();

// determinamos si el usuario debe estar logeado
if (isset($_SESSION["must_be_auth"])) {
    if (isset($_SESSION["loggedin"])) {
        if ($_SESSION["loggedin"] !== true && $_SESSION["must_be_auth"] === 1) {
            header("location: Validacion.php");
            exit;
        }
    } elseif ($_SESSION["must_be_auth"] === 1) {
        header("location: Validacion.php");
        exit;
    }
}

// biblioteca de acceso a BD
require_once "BaseDatos.php";

$error_action = "";
$allowed_modes = array('creation', 'modify', 'delete');

// determinamos la acción a llevar cabo, debe estar permitida en el array anterior
if (!isset($_SESSION['action_mode'])) {
    if (!isset($mode)) {
        $mode = isset($_GET['mode']) && in_array($_GET['mode'], $allowed_modes) ? $_GET['mode'] : $allowed_modes[0];
    }
}

// debemos obtener el ID a tratar
$userID = $_GET['userID'] ?? null;

if (isset($mode)) {
    $_SESSION['action_mode'] = $mode;
}

$idErr = $nombreErr = $emailErr = $passwordErr = "";
if ($_SESSION['action_mode'] == "creation") {
    $nombre = $email = $acceso = $habilitado = $password = "";
} else {
    // si no es una creacion obtenemos las variables para rellenar los campos
    if ($userID != null) {
        $usuario = get_user($userID)->fetch();
        list($email, $nombre, $acceso, $habilitado) = $usuario;
    }
}

// tratamos los datos enviados desde el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['submit'] == "Cancelar") {
        // nos redirige a la pagina de usuario
        unset($_SESSION['action_mode']);
        header("Location: ListaUsuario.php");
        exit;
    }

    // validamos los campos del POST
    if (empty($_POST["userID"])) {
        $idErr = "Se require ID";
    } else {
        $userID = test_input($_POST["userID"]);
    }

    if (empty($_POST["nombre"])) {
        $nombreErr = "Se require Nombre";
    } else {
        $nombre = test_input($_POST["nombre"]);
    }
    if (empty($_POST["email"])) {
        $costeErr = "Se require email";
    } else {
        $email = test_input($_POST["email"]);
    }

    // leemos el radio button
    $habilitado = $_POST["habilitado"] ?? 0;

    if ($_POST['submit'] == "Guardar") {
        $error_action = "";
        try {
            if ($_SESSION['action_mode'] == "modify") {
                // actualizamos el usuario si es modificación
                $count = update_user($userID, $nombre, $email, $habilitado);
            } else {
                if (empty($_POST["password"])) {
                    $passwordErr = "Se require password";
                } else {
                    $password = test_input($_POST["password"]);
                }
                // creamos el usuario si los datos son correctos
                $count = create_user($userID, $nombre, $email, $habilitado, $password);
            }
            // el rowcount debe ser positivo si se producen modificaciones
            if ($count > 0) {
                $action_ok = true;
            } else {
                $action_ok = false;
                $error_action = "Sin cambios o el registro no existe";
            }
        } catch (Exception $e) {
            $error_action = $e->getMessage();
            $action_ok = false;
        }
    } elseif ($_POST['submit'] == "Eliminar") {
        try {
            // hacemos el delete en base a la ID
            $count = delete_user($userID);
            if ($count > 0) {
                $action_ok = true;
            } else {
                $action_ok = false;
                $error_action = "El registro no existe";
            }
        } catch (Exception $e) {
            $error_action = $e->getMessage();
            $action_ok = false;
        }
    }

}

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data);
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
</head>
<body style="text-align:center;">

<h1 style="color:blue;">
    WestStore
</h1>
<?php
if (isset($_SESSION["name_loggedin"])) {
    echo "<h4>Bienvenido/a {$_SESSION["name_loggedin"]}</h4>";
} else {
    echo "<h4>No autenticado/a</h4>";
}
?>
<h3>
    Gestión de Usuarios
</h3>

<p><span class="error">* campo requerido</span></p>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <!-- Deshabilitaremos la modificación e campos cuando la acción lo requiera -->
    ID: <input type="number" name="userID"
               value="<?php echo $userID; ?>" <?php if ($_SESSION['action_mode'] != 'creation') {
        echo "readonly";
    } ?>>
    <span class="error">* <?php echo $idErr; ?></span>
    <br><br>
    Nombre: <input type="text" name="nombre"
                   value="<?php echo $nombre; ?>" <?php if ($_SESSION['action_mode'] == 'delete') {
        echo "readonly";
    } ?>>
    <span class="error">* <?php echo $nombreErr; ?></span>
    <br><br>
    Email: <input type="email" name="email"
                  value="<?php echo $email; ?>" <?php if ($_SESSION['action_mode'] == 'delete') {
        echo "readonly";
    } ?>>
    <span class="error">* <?php echo $emailErr; ?></span>
    <br><br>
    <?php if ($_SESSION['action_mode'] == "creation") { ?>
        Password: <input type="password" name="password" value="<?php echo $password; ?>">
        <span class="error">* <?php echo $passwordErr; ?></span>
        <br><br>
    <?php } ?>

    <input type="radio" id="enabled" name="habilitado"
           value="1" <?php echo ($habilitado) ? "checked" : "" ?> <?php if ($_SESSION['action_mode'] == 'delete') {
        echo "disabled";
    } ?> >
    <label for="enabled">Enabled</label><br>
    <input type="radio" id="disabled" name="habilitado"
           value="0" <?php echo (!$habilitado) ? "checked" : "" ?> <?php if ($_SESSION['action_mode'] == 'delete') {
        echo "disabled";
    } ?> >
    <label for="disabled">Disabled</label><br>
    <br><br>
    <?php if ($_SESSION['action_mode'] != "delete") { ?>
        <input type="submit" name="submit" value="Guardar">
    <?php } else { ?>
        <input type="submit" name="submit"
               value="Eliminar" <?php echo isset($_POST['submit']) ? "disabled" : "enabled"; ?> >
    <?php } ?>
    <input type="submit" name="submit" value="Cancelar">
</form>

<?php
// mostramos el feedback a usuario
if (isset($action_ok)) {
    echo "<br>";
    if ($action_ok) {
        if ($_SESSION['action_mode'] == "creation") {
            echo "<h4 style='color: green'>Usuario creado</h4>";
        } elseif ($_SESSION['action_mode'] == "modify") {
            echo "<h4 style='color: green'>Usuario modificado</h4>";
        } else {
            echo "<h4 style='color: green'>Usuario eliminado</h4>";
        }
    } else {
        echo "<h4 style='color: #FF0000'>$error_action</h4>";
    }
    unset($action_ok);
    $error_action = "";
}
?>

</body>
</html>