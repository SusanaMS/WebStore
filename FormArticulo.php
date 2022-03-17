<?php
// inicializamos sesion
session_start();


// si esta activada la autenticación y el usuario no está logado obligamos a pasar antes por el login
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

// obtenemos la libreia de acceso a BD
require_once "BaseDatos.php";

// obetenmos las categorias ya que necesitaremos los nombres asociados a las ids para mostrar un formulario legible
$categorias = get_categories()->fetchAll();

// inicializamos las variables
$error_action = "";

// array de control de acciones
$allowed_modes = array('creation', 'modify', 'delete');

// obtemos el modo de accion que nos llega desde el listado
if (!isset($_SESSION['article_mode'])) {
    if (!isset($mode)) {
        $mode = isset($_GET['mode']) && in_array($_GET['mode'], $allowed_modes) ? $_GET['mode'] : $allowed_modes[0];
    }
}

// determinamos el ID que vamos a tratar, si es una creación será nulo
$articleID = $_GET['articleID'] ?? null;

// informamos la varible de sesión correspondiente
if (isset($mode)) {
    $_SESSION['article_mode'] = $mode;
}

// declaramos e inicializamos variables de error
$idErr = $categoriaErr = $nombreErr = $costeErr = $precioErr = "";

if ($_SESSION['article_mode'] == "creation") {
    // cuando el modo es creacion vaciamos las variables correspondientes
    $id = $nombre = $coste = $precio = "";
    $categoria = 1;
} else {
    // en caso contrario cuando dispongamos de ID nos interesa que el formulario se rellene con los datos de BD
    if ($articleID != null) {
        $product = get_product($articleID)->fetch();
        list($id, $categoria, $nombre, $coste, $precio) = $product;
    }
}

// tratamos los datos que nos lleguen del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['submit'] == "Cancelar") {
        // si cancelamos  eliminamos la variable del modo de acción y redirigimos a la web de listado
        unset($_SESSION['article_mode']);
        header("Location: ListaArticulo.php");
        exit;
    }
    // validamos los datos
    if (empty($_POST["id"])) {
        $idErr = "Se require ID";
    } else {
        $id = test_input($_POST["id"]);
    }

    if (empty($_POST["categoria"])) {
        $categoriaErr = "Se require Categoria";
    } else {
        $categoria = test_input($_POST["categoria"]);
    }

    if (empty($_POST["nombre"])) {
        $nombreErr = "Se require Nombre";
    } else {
        $nombre = test_input($_POST["nombre"]);
    }

    if (empty($_POST["coste"])) {
        $costeErr = "Se require Coste";
    } else {
        $coste = test_input($_POST["coste"]);
    }

    if (empty($_POST["precio"])) {
        $precioErr = "Se require Precio";
    } else {
        $precio = test_input($_POST["precio"]);
    }

    // en caso de que guardemos
    if ($_POST['submit'] == "Guardar") {
        $error_action = "";
        try {
            // segun sea la accion de modificación o creación usuaremos la función correspondiente
            // para atacar la BD
            if ($_SESSION['article_mode'] == "modify") {
                $count = update_product($id, $categoria, $nombre, $coste, $precio);
            } else {
                $count = create_product($id, $categoria, $nombre, $coste, $precio);
            }
            // si el count es positivo la acción habrá sido correcta
            if ($count > 0) {
                $action_ok = true;
            } else {
                $action_ok = false;
                $error_action = "Sin cambios o el registro no existe";
            }
        } catch (Exception $e) {
            echo "entro error";
            $error_action = $e->getMessage();
            $action_ok = false;
        }
    } elseif ($_POST['submit'] == "Eliminar") {
        // cuando la acción eliminar usamos la id para hacer el delete
        try {
            $count = delete_product($id);
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
    <title>Gestión de Artículo</title>
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
    Gestión de Artículo
</h3>

<p><span class="error">* campo requerido</span></p>
<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <!-- si el modo no es creación no permitimos la modificación de la ID -->
    <label>ID:
        <input type="number" name="id" value="<?php echo $id; ?>" <?php if ($_SESSION['article_mode'] != 'creation') {
            echo "readonly";
        } ?>>
    </label>
    <span class="error">* <?php echo $idErr; ?></span>
    <br><br>
    <label>Categoria:
        <select name="categoria" required>
            <?php foreach ($categorias as $option) : ?>
                <option value="<?php echo $option['categoryID']; ?>"
                    <?php
                    // mostramos la categorias po su nombre y en caso de que estemos ante un borrado
                    // no permitimos seleccionar otra
                    if ($option['categoryID'] == $categoria) {
                        echo "selected";
                    } elseif ($_SESSION['article_mode'] == "delete") {
                        echo "disabled";
                    }
                    ?> >
                    <?php echo $option['name']; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <br><br>
    <!-- cuando la acción es dekete nos campos será readonly -->
    <label>Nombre:
        <input type="text" name="nombre"
               value="<?php echo $nombre; ?>" <?php if ($_SESSION['article_mode'] == 'delete') {
            echo "readonly";
        } ?>>
    </label>
    <span class="error">* <?php echo $nombreErr; ?></span>
    <br><br>
    <label>
        Coste:
        <input type="number" name="coste"
               value="<?php echo $coste; ?>" <?php if ($_SESSION['article_mode'] == 'delete') {
            echo "readonly";
        } ?>>
    </label>
    <span class="error">* <?php echo $costeErr; ?></span>
    <br><br>
    <label>
        Precio:
        <input type="number" name="precio"
               value="<?php echo $precio; ?>" <?php if ($_SESSION['article_mode'] == 'delete') {
            echo "readonly";
        } ?>>
    </label>
    <span class="error">* <?php echo $precioErr; ?></span>
    <br><br>
    <?php if ($_SESSION['article_mode'] != "delete") { ?>
        <input type="submit" name="submit" value="Guardar">
    <?php } else { ?>
        <input type="submit" name="submit"
               value="Eliminar" <?php echo isset($_POST['submit']) ? "disabled" : "enabled"; ?> >
    <?php } ?>
    <input type="submit" name="submit" value="Cancelar">
</form>

<?php
// mostramos los mensajes en funcion de la acción realizada
if (isset($action_ok)) {
    echo "<br>";
    if ($action_ok) {
        if ($_SESSION['article_mode'] == "creation") {
            echo "<h4 style='color: green'>Producto creado</h4>";
        } elseif ($_SESSION['article_mode'] == "modify") {
            echo "<h4 style='color: green'>Producto modificado</h4>";
        } else {
            echo "<h4 style='color: green'>Producto eliminado</h4>";
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