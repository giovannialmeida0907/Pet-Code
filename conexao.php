<?php 

$hostname = "gondola.proxy.rlwy.net"; 
$bancodedados = "cadastro";
$usuario = "root";
$senha = "AVXdHsTinBMCgzCUzCREWXdqFoDbqSaw";
$table_name = "usuario";

function conectar_banco() {
    global $hostname, $usuario, $senha, $bancodedados;
    
    $mysqli = new mysqli($hostname, $usuario, $senha, $bancodedados);

    if ($mysqli->connect_errno) {
        die("Falha ao conectar: (" . $mysqli->connect_errno . ")" . $mysqli->connect_error);
    }
    
    return $mysqli;
}
?>