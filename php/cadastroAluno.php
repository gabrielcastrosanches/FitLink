<?php
    include "conexao.php";

    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $telefone = $_POST["telefone"];
    $data = $_POST["data_nascimento"];
    $genero = $_POST["genero"];
    $senha = $_POST["senha"];

    $senha = password_hash(
        $senha,
        PASSWORD_DEFAULT
    );

    $sql = "INSERT INTO tb_cadastroaluno
    (nome,email,telefone,data_nascimento,genero,senha)
    VALUES
    ('$nome',
    '$email',
    '$telefone',
    '$data',
    '$genero',
    '$senha')";

    if($conn->query($sql)){
        echo "
        <script>
        alert('Aluno cadastrado com sucesso!');
        window.location.href='../html/login.html';
        </script>
        ";
    }else{
        echo "
        <script>
        alert('Erro ao cadastrar aluno');
        window.history.back();
        </script>
        ";
    }

    $conn->close();
?>