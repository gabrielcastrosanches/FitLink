<?php

session_start();

include "conexao.php";

$email = trim($_POST["email"] ?? "");
$senha = $_POST["senha"] ?? "";

if ($email === "" || $senha === "") {
    echo "erro";
    exit;
}


// ==========================================
// LOGIN DO ALUNO
// ==========================================

$sqlAluno = "
    SELECT id_aluno, nome, email, senha
    FROM tb_cadastroaluno
    WHERE email = ?
    LIMIT 1
";

$stmtAluno = $conn->prepare($sqlAluno);

$stmtAluno->bind_param("s", $email);

$stmtAluno->execute();

$resultadoAluno = $stmtAluno->get_result();

if ($resultadoAluno->num_rows > 0) {

    $aluno = $resultadoAluno->fetch_assoc();

    if (password_verify($senha, $aluno["senha"])) {

        $_SESSION["tipo_usuario"] = "aluno";
        $_SESSION["id_aluno"] = $aluno["id_aluno"];
        $_SESSION["nome_aluno"] = $aluno["nome"];

        echo "aluno";
        exit;
    }
}

$stmtAluno->close();


// ==========================================
// LOGIN DO PERSONAL
// ==========================================

$sqlPersonal = "
    SELECT id_cref, nome, email, senha
    FROM tb_cadastropersonal
    WHERE email = ?
    LIMIT 1
";

$stmtPersonal = $conn->prepare($sqlPersonal);

$stmtPersonal->bind_param("s", $email);

$stmtPersonal->execute();

$resultadoPersonal = $stmtPersonal->get_result();

if ($resultadoPersonal->num_rows > 0) {

    $personal = $resultadoPersonal->fetch_assoc();

    if (password_verify($senha, $personal["senha"])) {

        $_SESSION["tipo_usuario"] = "personal";
        $_SESSION["id_cref"] = $personal["id_cref"];
        $_SESSION["nome_personal"] = $personal["nome"];

        echo "personal";
        exit;
    }
}

$stmtPersonal->close();


// ==========================================
// LOGIN INVÁLIDO
// ==========================================

echo "erro";

$conn->close();

?>