<?php
session_start();

// mysql.server start
// obtenenmos la biblioteca de funciones de aaceso a BD
require_once "BaseDatos.php";

// en primer lugar determinamos si la autenticación está activada
// en base al valor de la tabla setup.
$row = get_setup()->fetch();
$_SESSION["must_be_auth"] = $row[1];

if (isset($_SESSION["must_be_auth"])) {
    // la variable de session loggedin no esta activada cuando debería estarlo nos redirige al formulario de login
    if ((isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] !== true) && $_SESSION["must_be_auth"] === 1) {
        header("location: Validacion.php");
        exit;
    }
} else {
    $_SESSION["must_be_auth"] = 0;
}
?>

<!DOCTYPE html>
<html lang="es-ES">

<head>
    <title>
        WebStore
    </title>
</head>

<body style="text-align:center;">

<h1 style="color:blue;">
    WestStore
</h1>

<?php
// mostramos un mensaje para aclarar si el usuario esta logado o no
if (isset($_SESSION["name_loggedin"])) {
    echo "<h4>Bienvenido/a {$_SESSION["name_loggedin"]}</h4>";
} else {
    echo "<h4>No autenticado/a</h4>";
}
?>

<h3>
    Seleccione opción
</h3>

<?php
// variable en la que almacenaremos posibles errores de acceso a BD
$db_errors = "";
// detectamos el boton que se ha clicado y dirigimos a la url correspondinte
if (array_key_exists('articulos', $_POST)) {
    header("Location: ListaArticulo.php");
    exit;
} elseif (array_key_exists('usuarios', $_POST)) {
    header("Location: ListaUsuario.php");
} elseif (array_key_exists('login_on', $_POST)) {
    // en caso de que activemos la autenticación lo reflejaremos en la variable de sesión correspondiente
    // y actualizaremos la tabla setup
    $_SESSION["must_be_auth"] = 1;
    try {
        setup_autentication($_SESSION["must_be_auth"]);
    } catch (Exception $e) {
        $db_errors = $e->getMessage();
    }
    if (!isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] !== true) {
        header("location: Validacion.php");
        exit;
    }
} elseif (array_key_exists('login_off', $_POST)) {
    $_SESSION["must_be_auth"] = 0;
    try {
        setup_autentication($_SESSION["must_be_auth"]);
    } catch (Exception $e) {
        $db_errors = $e->getMessage();
    }
    $_SESSION["loggedin"] = false;
    // al desactivar la autenticación, hacemos un logout
    unset($_SESSION["name_loggedin"]);
    // refrescamos la pagina actual para reflejar los cambios
    // https://stackoverflow.com/questions/12383371/refresh-a-page-using-php
    header("Refresh:0");
}

?>

<form method="post">
    <input type="submit" name="articulos" class="button" value="Articulos"/>
    <input type="submit" name="usuarios" class="button" value="Usuarios"/>
    <br><br><br>
    <hr>
    <input type="submit" name="login_off" class="button" value="Autenticación OFF"/>
    <input type="submit" name="login_on" class="button" value="Autenticación ON"/>
</form>
<?php
// reflejamos los posibles errores de acceso a BD
if (!empty($db_errors)) {
    echo "<h4 style='color: #FF0000'>$db_errors</h4>";
}
?>
</body>

</html>