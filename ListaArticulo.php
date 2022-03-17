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

// obtenemos el numero de productos uq ehay en la tabla ya que lo necesitaremos para construir la paginación correctamente
$products_count = get_products_count()->fetch()[0];

// el numero total de paginás sera el resultado de dividir las filas que tenemos entre 10
$total_pages = ceil($products_count / 10);

// creamois un array con las columnas ordenables como metodo de seguridad a la hora de determinar la ordenación
$columns = array('ID', 'Categoria', 'Nombre', 'Coste', 'Precio');

// obtenemos la columna de ordenacion a partir de GET de la propia página , usaremos por defecto la primera columna del array anterior
$column = isset($_GET['column']) && in_array($_GET['column'], $columns) ? $_GET['column'] : $columns[0];

// de la misma forma obtenemos si el orden debe ser ascendente (por defecto) o descendente
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) == 'desc' ? 'DESC' : 'ASC';

// y la pagina a partir de la cual mostremos los resultados
$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? $_GET['page'] : 1;

// calculamos el num de registros a mostrar a partir de valor anterior y lo multiplicamos por 10
$paginationStart = ($page - 1) * 10;

// obtenemos las filas correspondientes
$rows = get_products(order_by: $column, order: $sort_order, page: $paginationStart)->fetchAll();

// necesitaremos alternar el modo de ordenación entre ascendente y descentente
$asc_or_desc = $sort_order == 'ASC' ? 'desc' : 'asc';

// estas variables nos serviran para los botones de paginacion
$prev = $page - 1;
$next = $page + 1;
?>

<!DOCTYPE html>
<html lang="es-ES">

<head>
    <title>
        Artículos
    </title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            max-width: 100%;
            margin-bottom: 20px;
            white-space: nowrap;
            table-layout: fixed;
        }

        th {
            background-color: lightgreen;
        }

        table, th, td {
            padding: 15px 0;
            border: 1px solid black;
        }

        td {
            text-align: center;
        }

        ul {
            list-style: none;
        }

        li {
            display: inline;
            padding: 4px;
        }
    </style>
</head>

<body style="text-align:center;">

<h1 style="color:blue;">
    WestStore
</h1>
<a href='Index.php'><i>Index</i></a>
<br><br>
<!-- mostramos el link de creación -->
<a href='FormArticulo.php?mode=creation'><i>Crear Artículo</i></a>
<?php
// mostramos un mensaje para aclarar si el usuario esta logado o no
if (isset($_SESSION["name_loggedin"])) {
    echo "<h4>Bienvenido/a {$_SESSION["name_loggedin"]}</h4>";
} else {
    echo "<h4>No autenticado/a</h4>";
}
?>
<h3>
    Artículos
</h3>
<table>
    <thead>
    <tr>
        <!-- Los href de la ordenacion en cabeceras los componemos segun la columna y alternamos el tipo de ordenacion -->
        <th><a href="ListaArticulo.php?column=ID&order=<?php echo $asc_or_desc; ?>">ID</a></th>
        <th><a href="ListaArticulo.php?column=Categoria&order=<?php echo $asc_or_desc; ?>">Categoria</a></th>
        <th><a href="ListaArticulo.php?column=Nombre&order=<?php echo $asc_or_desc; ?>">Nombre</a></th>
        <th><a href="ListaArticulo.php?column=Coste&order=<?php echo $asc_or_desc; ?>">Coste</a></th>
        <th><a href="ListaArticulo.php?column=Precio&order=<?php echo $asc_or_desc; ?>">Precio</a></th>
        <th></th>
    </tr>
    </thead>
    <tbody>

    <?php

    foreach ($rows as $value) {
        // mostramos los campos alineados en funcion de lo requerido en el ejercicio
        echo "<td>{$value[0]}</td>";
        echo "<td style='text-align: left'>{$value[1]}</td>";
        echo "<td style='text-align: left'>{$value[2]}</td>";
        echo "<td style='text-align: right'>{$value[3]} €</td>";
        echo "<td style='text-align: right'>{$value[4]} €</td>";
        // mostramos los links a formulario de modificación al que le pasaremos el ID de articulo
        echo "<th><a href='FormArticulo.php?mode=modify&articleID={$value[0]}'><i>Modificar</i></a>&NonBreakingSpace;&NonBreakingSpace;<a href='FormArticulo.php?mode=delete&articleID={$value[0]}'><i>Eliminar</i></a></th>";
        echo "</tr>";
    }
    ?>
    </tbody>
</table>

<ul>
    <!-- la paginación la mostramos como una lista , en los extremos estarán los botones previo y
     siguiente -->
    <li class="page-item <?php if ($page <= 1) {
        echo 'disabled';
    } ?>">
        <a class="page-link"
           href="<?php if ($page <= 1) {
               echo '#';
           } else {
               echo "?page=" . $prev;
           } ?>">Previo</a>
    </li>

    <!-- por cada pagina que resulte de vividir el total de elementos mostramos un boton con su correspondiente link -->
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?php if ($page == $i) {
            echo 'active';
        } ?>">
            <a class="page-link" href="ListaArticulo.php?page=<?= $i; ?>"> <?= $i; ?> </a>
        </li>
    <?php endfor; ?>

    <li class="page-item <?php if ($page >= $total_pages) {
        echo 'disabled';
    } ?>">
        <a class="page-link"
           href="<?php if ($page >= $total_pages) {
               echo '#';
           } else {
               echo "?page=" . $next;
           } ?>">Siguiente</a>
    </li>
</ul>

</body>

</html>