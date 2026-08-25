<?php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "fitlink";

$conn = new mysqli(
    $servidor,
    $usuario,
    $senha,
    $banco
);

if($conn->connect_error){
    die("Erro na conexão");
}
?>