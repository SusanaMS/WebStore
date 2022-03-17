<?php
session_start();

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

// obtenemos la biblioteca de acceso a BD
require_once "BaseDatos.php";

// necesitamos determinar que ID corresponde al superadmin
$row = get_setup()->fetch();
$superUserId = $row[2];

// array para securizar las columnas de ordenación
$columns = array('UserID', 'FullName', 'Email', 'LastAccess', 'Enabled');

// obtenemos la columan de ordenación al igual que se ha explicado en el listado de art
$column = isset($_GET['column']) && in_array($_GET['column'], $columns) ? $_GET['column'] : $columns[0];

// orden ascendente o descendente
$sort_order = isset($_GET['order']) && strtolower($_GET['order']) == 'desc' ? 'DESC' : 'ASC';

// obtenemos los registros de usuarios
$rows = get_users(order_by: $column, order: $sort_order)->fetchAll();

$asc_or_desc = $sort_order == 'ASC' ? 'desc' : 'asc';
?>
<!DOCTYPE html>
<html lang="es-ES">

<head>
    <title>
        Usuarios
    </title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            max-width: 100%;
            margin-bottom: 20px;
            white-space: nowrap;

        }

        th {
            background-color: lightblue;
        }

        table, th, td {
            padding: 15px 0;
            border: 1px solid black;
        }

        td {
            text-align: center;
        }
    </style>
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
<br>
<a href='Index.php'><i>Index</i></a>
<br><br>
<a href='FormUsuario.php?action=creation'><i>Crear Usuario</i></a>

<h3>
    Usuarios
</h3>
<table>
    <thead>
    <tr>
        <th>
            <a href="ListaUsuario.php?column=UserId&order=<?php echo $asc_or_desc; ?>">ID</a></th>
        <th>
            <a href="ListaUsuario.php?column=FullName&order=<?php echo $asc_or_desc; ?>">Nombre</a></th>
        <th>
            <a href="ListaUsuario.php?column=Email&order=<?php echo $asc_or_desc; ?>">Email</a></th>
        <th><a href="ListaUsuario.php?column=LastAccess&order=<?php echo $asc_or_desc; ?>">Último acceso</a></th>
        <th>
            <a href="ListaUsuario.php?column=Enabled&order=<?php echo $asc_or_desc; ?>">Enabled</a></th>
        <th></th>
    </tr>
    </thead>
    <tbody>

    <?php

    foreach ($rows as $value) {
        // formateamos en función de lo requeridoene el ejercicio y resaltamos el usuario superadmin
        if ($superUserId == $value[0]) {
            echo "<tr style='background-color: lightcoral'>";
        } else {
            echo "<tr>";
        }
        echo "<td>{$value[0]}</td>";
        echo "<td style='text-align: justify'>{$value[8]}</td>";
        echo "<td style='text-align: justify'>{$value[2]}</td>";
        echo "<td>{$value[9]}</td>";
        echo "<td>{$value[10]}</td>";
        if ($superUserId == $value[0]) {
            // para superadmin no se mostraran los enlaces de modificación y eliminación
            echo "<td><strong>SUPERADMIN</strong></td>";
        } else {
            echo "<td><a href='FormUsuario.php?mode=modify&userID={$value[0]}'><i>Modificar</i></a>&NonBreakingSpace;&NonBreakingSpace;<a href='FormUsuario.php?mode=delete&userID={$value[0]}'><i>Eliminar</i></a></td>";
        }
        echo "</tr>";
    }
    ?>

    </tbody>
</table>
</body>

</html>