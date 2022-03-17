<?php
// Conexión a la base de datos y TODAS las funciones SQL necesarias.

ini_set("display_errors", true);
date_default_timezone_set("Europe/Madrid");
// usamos una cadena de conexión tipo DSN ya que resulta más compacto y visual
const DB_DSN = "mysql:host=localhost;dbname=pac3_daw";
const DB_USERNAME = "admin";
const DB_PASSWORD = "admin12345";

// https://www.php.net/manual/en/pdo.connections.php
// usamos PDO  PHP Data Objects ya que proporcionan mayor flexibilidad a la hora de
// acceder a la BD, además sería mucho más facil migrar a otro motor
$dbh = new PDO(DB_DSN, DB_USERNAME, DB_PASSWORD, array(
    PDO::ATTR_PERSISTENT => true
));

// obtenos los nombre de categorias para poder asociar la key de productos con su nombre
function get_categories(): bool|PDOStatement
{
    // declarmos el manejador de BD como global para poder acceder al PDO
    global $dbh;

    return $dbh->query('SELECT categoryID, name FROM category');
}

function get_products_count(): bool|PDOStatement
{
    global $dbh;

    return $dbh->query('SELECT COUNT(*) FROM product');
}

// En la obtencion de productos debemos tener en cuenta varios puntos.
// el orden que controlamos con ORDER BY y el paginado, concretamente el numero de registro a partir
// del cual mostremos resultados
// tambien hacemos un join con la tabla de categorias de modo que podamos devolver el nombre y no el id
function get_products($order_by = "ID", $order = "ASC", $page = 0): bool|PDOStatement
{
    global $dbh;

    return $dbh->query('
     SELECT p.ProductID as ID,
       c.Name as Categoria,
       p.Name as Nombre,
       FLOOR(p.Cost) AS Coste,
       FLOOR(p.Price) AS Precio
     FROM product p, category c
     WHERE p.CategoryID = c.CategoryID ORDER BY ' . $order_by . ' ' . $order . ' LIMIT ' . $page . ',10');
}

function get_product($id_product)
{
    global $dbh;

    return $dbh->query('
     SELECT p.ProductID as ID,
       p.CategoryID as Categoria,
       p.Name as Nombre,
       FLOOR(p.Cost) AS Coste,
       FLOOR(p.Price) AS Precio
     FROM product p
     WHERE p.ProductID = ' . $id_product);
}

// en esta ocasión usaremos bind variables para realizar el update
function update_product($id, $categoria, $nombre, $coste, $precio)
{
    global $dbh;

    $sql = "UPDATE product p SET p.CategoryID=?, p.Name=?, p.Cost=?, p.Price=? WHERE p.ProductID=?";
    $update = $dbh->prepare($sql);
    $update->execute([$categoria, $nombre, $coste, $precio, $id]);

    return $update->rowCount();
}

function create_product($id, $categoria, $nombre, $coste, $precio)
{
    global $dbh;

    $sql = "INSERT INTO product (ProductID, `Name`, Cost, Price, CategoryID) VALUES (?, ?, ?, ?, ?)";
    $insert = $dbh->prepare($sql);
    $insert->execute([$id, $nombre, $coste, $precio, $categoria]);

    return $insert->rowCount();
}

function delete_product($id)
{
    global $dbh;

    $sql = "DELETE FROM product WHERE ProductID=?";
    $delete = $dbh->prepare($sql);
    $delete->execute([$id]);

    return $delete->rowCount();
}

// al igual que en otras funciones, dotamos de valor defecto a ciertos parametros
function get_users($order_by = "UserID", $order = "ASC"): bool|PDOStatement
{
    global $dbh;

    return $dbh->query('SELECT * FROM `user` ORDER BY ' . $order_by . ' ' . $order);
}


function get_user($id)
{
    global $dbh;

    return $dbh->query('
     SELECT 
       Email,
       FullName as Nombre,
       LastAccess as Acceso,
       Enabled
     FROM `user`
     WHERE UserID = ' . $id);
}

function update_user($id, $nombre, $mail, $habilitado)
{
    global $dbh;

    $sql = "UPDATE `user` SET FullName=?, Email=?, Enabled=? WHERE UserID=?";
    $update = $dbh->prepare($sql);
    $update->execute([$nombre, $mail, $habilitado, $id]);

    // devolviendo el rowcount podremos comprobar si efectivamente se ha llevado a cabo algún cambio
    return $update->rowCount();
}

function create_user($id, $nombre, $mail, $habilitado, $password)
{
    global $dbh;

    $sql = "INSERT INTO `user` (UserId, FullName, Email, LastAccess, Enabled, Password) VALUES (?, ?, ?, NOW(), ?, ?)";
    $insert = $dbh->prepare($sql);
    $insert->execute([$id, $nombre, $mail, $habilitado, $password]);

    return $insert->rowCount();
}

// el usuario podrá acceder si está habilitado y su email existe en la BD
function validate_user($email)
{
    global $dbh;

    $sql = 'SELECT FullName, UserID FROM `user` WHERE Enabled = 1 AND Email =?';
    $validation = $dbh->prepare($sql);
    $validation->execute([$email]);

    return $validation->fetch();
}

function lock_user($mail)
{
    global $dbh;

    $sql = 'UPDATE `user` SET Enabled=0 WHERE Email=?';

    $lock = $dbh->prepare($sql);
    $lock->execute([$mail]);

    return $lock->rowCount();
}

function setup_autentication($autentication_mode)
{
    global $dbh;

    $sql = 'UPDATE setup SET `Autenticación`=?';

    $update_auth = $dbh->prepare($sql);
    $update_auth->execute([$autentication_mode]);

    return $update_auth->rowCount();
}

function validate_password($email, $password)
{
    global $dbh;

    $sql = 'SELECT count(*) FROM `user` WHERE Enabled = 1 AND Email =? AND Password = ?';
    $validation = $dbh->prepare($sql);
    $validation->execute([$email, $password]);

    return $validation->fetch();
}

function delete_user($id)
{
    global $dbh;


    $sql = "DELETE FROM `user` WHERE UserID=?";

    $delete = $dbh->prepare($sql);
    $delete->execute([$id]);

    return $delete->rowCount();
}

function get_setup(): bool|PDOStatement
{
    global $dbh;

    $sth_setup = $dbh->query('SELECT * FROM setup');

    return $sth_setup;
}