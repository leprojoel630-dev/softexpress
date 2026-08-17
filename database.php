<?php

/*
|--------------------------------------------------------------------------
| SOFTEXPRESS - CONNEXION À LA BASE DE DONNÉES
|--------------------------------------------------------------------------
| Ce fichier fournit :
|
| $conn = connexion MySQLi
| $pdo  = connexion PDO
|
| Les deux utilisent la même base de données.
|--------------------------------------------------------------------------
*/

$host = "localhost";
$user = "root";
$password = "";
$database = "softexpress";



/*
|--------------------------------------------------------------------------
| CONNEXION MYSQLI
|--------------------------------------------------------------------------
| Utilisée par les pages existantes qui travaillent avec $conn.
|--------------------------------------------------------------------------
*/

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {

    die(
        "Erreur de connexion à la base de données : "
        . $conn->connect_error
    );
}

$conn->set_charset("utf8mb4");



/*
|--------------------------------------------------------------------------
| CONNEXION PDO
|--------------------------------------------------------------------------
| Utilisée par les pages qui travaillent avec $pdo.
|--------------------------------------------------------------------------
*/

try {

    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    die(
        "Erreur de connexion PDO à la base de données : "
        . $e->getMessage()
    );
}