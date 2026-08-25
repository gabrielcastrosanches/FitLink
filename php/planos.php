<?php

session_start();

include "conexao.php";

header(
    "Content-Type: application/json; charset=UTF-8"
);


// ==========================================
// VERIFICAR LOGIN
// ==========================================

if (
    !isset($_SESSION["id_cref"])
) {

    echo json_encode([
        "erro" => "Personal não está logado."
    ]);

    exit;

}


$id_cref =
    intval(
        $_SESSION["id_cref"]
    );


if ($id_cref <= 0) {

    echo json_encode([
        "erro" => "CREF inválido."
    ]);

    exit;

}


// ==========================================
// AÇÃO
// ==========================================

$acao =
    $_GET["acao"]
    ?? $_POST["acao"]
    ?? "";


// ==========================================
// LISTAR
// ==========================================

if ($acao === "listar") {


    $sql = "

        SELECT
            id_plano,
            titulo,
            beneficios,
            valor,
            validade,
            id_cref

        FROM tb_planos

        WHERE id_cref = ?

        ORDER BY id_plano DESC

    ";


    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(
        "i",
        $id_cref
    );


    $stmt->execute();


    $resultado =
        $stmt->get_result();


    $planos = [];


    while (
        $plano =
        $resultado->fetch_assoc()
    ) {

        $planos[] = $plano;

    }


    echo json_encode(
        $planos,
        JSON_UNESCAPED_UNICODE
    );


    $stmt->close();

    $conn->close();

    exit;

}



// ==========================================
// BUSCAR
// ==========================================

if ($acao === "buscar") {


    $id_plano =
        intval(
            $_GET["id_plano"] ?? 0
        );


    if ($id_plano <= 0) {

        echo json_encode([
            "erro" => "Plano inválido."
        ]);

        exit;

    }


    $sql = "

        SELECT
            id_plano,
            titulo,
            beneficios,
            valor,
            validade,
            id_cref

        FROM tb_planos

        WHERE
            id_plano = ?

            AND id_cref = ?

        LIMIT 1

    ";


    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(
        "ii",
        $id_plano,
        $id_cref
    );


    $stmt->execute();


    $resultado =
        $stmt->get_result();


    if (
        $resultado->num_rows === 0
    ) {

        echo json_encode([
            "erro" => "Plano não encontrado."
        ]);

        exit;

    }


    $plano =
        $resultado->fetch_assoc();


    echo json_encode(
        $plano,
        JSON_UNESCAPED_UNICODE
    );


    $stmt->close();

    $conn->close();

    exit;

}



// ==========================================
// SALVAR
// ==========================================

if ($acao === "salvar") {


    $id_plano =
        intval(
            $_POST["id_plano"] ?? 0
        );


    $titulo =
        trim(
            $_POST["titulo"] ?? ""
        );


    $beneficios =
        trim(
            $_POST["beneficios"] ?? ""
        );


    $valor =
        $_POST["valor"] ?? "";


    $validade =
        intval(
            $_POST["validade"] ?? 0
        );



    // ======================================
    // VALIDAR
    // ======================================

    if ($titulo === "") {

        echo json_encode([
            "erro" => "Informe o nome do plano."
        ]);

        exit;

    }


    if ($beneficios === "") {

        echo json_encode([
            "erro" => "Informe os benefícios."
        ]);

        exit;

    }


    if (
        $valor === ""
        ||
        !is_numeric($valor)
        ||
        floatval($valor) < 0
    ) {

        echo json_encode([
            "erro" => "Informe um valor válido."
        ]);

        exit;

    }


    if ($validade <= 0) {

        echo json_encode([
            "erro" => "Informe a validade do plano."
        ]);

        exit;

    }


    $valor =
        floatval($valor);



    // ======================================
    // EDITAR
    // ======================================

    if ($id_plano > 0) {


        $sql = "

            UPDATE tb_planos

            SET
                titulo = ?,
                beneficios = ?,
                valor = ?,
                validade = ?

            WHERE
                id_plano = ?

                AND id_cref = ?

        ";


        $stmt =
            $conn->prepare($sql);


        $stmt->bind_param(
            "ssdiii",
            $titulo,
            $beneficios,
            $valor,
            $validade,
            $id_plano,
            $id_cref
        );


        if ($stmt->execute()) {


            echo json_encode([
                "sucesso" => true,
                "mensagem" => "Plano atualizado com sucesso!"
            ]);


        } else {


            echo json_encode([
                "erro" => $stmt->error
            ]);

        }


        $stmt->close();

        $conn->close();

        exit;

    }



    // ======================================
    // CRIAR
    // ======================================

    $sql = "

        INSERT INTO tb_planos
        (
            titulo,
            beneficios,
            valor,
            validade,
            id_cref
        )

        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
        )

    ";


    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(
        "ssdii",
        $titulo,
        $beneficios,
        $valor,
        $validade,
        $id_cref
    );


    if ($stmt->execute()) {


        echo json_encode([
            "sucesso" => true,
            "mensagem" => "Plano criado com sucesso!"
        ]);


    } else {


        echo json_encode([
            "erro" => $stmt->error
        ]);

    }


    $stmt->close();

    $conn->close();

    exit;

}



// ==========================================
// EXCLUIR
// ==========================================

if ($acao === "excluir") {


    $id_plano =
        intval(
            $_POST["id_plano"] ?? 0
        );


    if ($id_plano <= 0) {

        echo json_encode([
            "erro" => "Plano inválido."
        ]);

        exit;

    }


    $sql = "

        DELETE FROM tb_planos

        WHERE
            id_plano = ?

            AND id_cref = ?

    ";


    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(
        "ii",
        $id_plano,
        $id_cref
    );


    if ($stmt->execute()) {


        if (
            $stmt->affected_rows > 0
        ) {


            echo json_encode([
                "sucesso" => true,
                "mensagem" => "Plano excluído com sucesso!"
            ]);


        } else {


            echo json_encode([
                "erro" => "Plano não encontrado."
            ]);

        }


    } else {


        echo json_encode([
            "erro" => $stmt->error
        ]);

    }


    $stmt->close();

    $conn->close();

    exit;

}



// ==========================================
// AÇÃO INVÁLIDA
// ==========================================

echo json_encode([
    "erro" => "Ação inválida."
]);


$conn->close();

?>