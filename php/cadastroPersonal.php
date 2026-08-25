<?php

include "conexao.php";


// ==========================================
// PEGAR DADOS DO FORMULÁRIO
// ==========================================

$cref = trim($_POST["id_cref"] ?? "");

$nome = trim($_POST["nome"] ?? "");

$email = trim($_POST["email"] ?? "");

$telefone = trim($_POST["telefone"] ?? "");

$data = $_POST["data_nascimento"] ?? "";

$genero = trim($_POST["genero"] ?? "");

$senha = $_POST["senha"] ?? "";


// ==========================================
// VERIFICAR CAMPOS
// ==========================================

if (
    $cref === "" ||
    $nome === "" ||
    $email === "" ||
    $senha === ""
) {

    die("Preencha todos os campos obrigatórios.");

}


// ==========================================
// VERIFICAR SE O CREF JÁ EXISTE
// ==========================================

$sqlVerificar = "
    SELECT id_cref
    FROM tb_cadastropersonal
    WHERE id_cref = ?
";


$stmt = $conn->prepare($sqlVerificar);

$stmt->bind_param(
    "i",
    $cref
);

$stmt->execute();

$resultado = $stmt->get_result();


if ($resultado->num_rows > 0) {

    die("Este CREF já está cadastrado.");

}

$stmt->close();


// ==========================================
// VERIFICAR SE O EMAIL JÁ EXISTE
// ==========================================

$sqlEmail = "
    SELECT id_cref
    FROM tb_cadastropersonal
    WHERE email = ?
";


$stmt = $conn->prepare($sqlEmail);

$stmt->bind_param(
    "s",
    $email
);

$stmt->execute();

$resultado = $stmt->get_result();


if ($resultado->num_rows > 0) {

    die("Este email já está cadastrado.");

}

$stmt->close();


// ==========================================
// CADASTRAR PERSONAL
// ==========================================

$sql = "
    INSERT INTO tb_cadastropersonal
    (
        id_cref,
        nome,
        email,
        telefone,
        data_nascimento,
        genero,
        senha
    )

    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?
    )
";


$senha = password_hash(
    $senha,
    PASSWORD_DEFAULT
);


$stmt = $conn->prepare($sql);


$stmt->bind_param(
    "issssss",
    $cref,
    $nome,
    $email,
    $telefone,
    $data,
    $genero,
    $senha
);


if ($stmt->execute()) {

    echo "
    <script>
    alert('Personal cadastrado com sucesso!');
    window.location.href='../html/login.html';
    </script>
    ";

} else {

    echo "Erro ao cadastrar Personal: " . $stmt->error;

}