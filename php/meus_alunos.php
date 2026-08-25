<?php

include "conexao.php";


// ==========================================
// CONFIGURAÇÃO
// ==========================================

header("Content-Type: text/plain; charset=UTF-8");


// ==========================================
// VERIFICAR CONEXÃO
// ==========================================

if ($conn->connect_error) {

    echo "Erro de conexão com o banco.";

    exit;

}


// ==========================================
// PEGAR AÇÃO
// ==========================================

$acao =
    $_GET["acao"]
    ?? $_POST["acao"]
    ?? "";


// ==========================================
// LISTAR ALUNOS
// ==========================================

if ($acao === "listar") {


    $id_cref =
        intval(
            $_GET["id_cref"] ?? 0
        );


    if ($id_cref <= 0) {

        echo json_encode(
            [],
            JSON_UNESCAPED_UNICODE
        );

        exit;

    }



    /*
    =====================================================
    CONSULTA

    tb_aulamarcada
          |
          | id_agenda
          ↓
    tb_agenda
          |
          | id_cref
          ↓
    tb_cadastropersonal


    tb_aulamarcada
          |
          | id_aluno
          ↓
    tb_cadastroaluno
    =====================================================
    */


    $sql = "

        SELECT

            a.id_aula,

            aluno.id_aluno,

            aluno.nome,

            aluno.email,

            aluno.telefone,

            a.status,

            agenda.id_agenda,

            DATE_FORMAT(
                agenda.dia,
                '%d/%m/%Y'
            ) AS dia,

            TIME_FORMAT(
                agenda.horario,
                '%H:%i'
            ) AS horario


        FROM tb_aulamarcada AS a


        INNER JOIN tb_cadastroaluno AS aluno

            ON aluno.id_aluno =
               a.id_aluno


        INNER JOIN tb_agenda AS agenda

            ON agenda.id_agenda =
               a.id_agenda


        WHERE agenda.id_cref =
              $id_cref


        ORDER BY

            agenda.dia ASC,

            agenda.horario ASC

    ";



    $resultado =
        $conn->query($sql);



    if (!$resultado) {

        echo json_encode(
            [
                "erro" =>
                    $conn->error
            ],
            JSON_UNESCAPED_UNICODE
        );

        exit;

    }



    $alunos = [];



    while (
        $linha =
        $resultado->fetch_assoc()
    ) {

        $alunos[] = [

            "id_aula" =>
                $linha["id_aula"],

            "id_aluno" =>
                $linha["id_aluno"],

            "nome" =>
                $linha["nome"],

            "email" =>
                $linha["email"],

            "telefone" =>
                $linha["telefone"],

            "status" =>
                $linha["status"],

            "id_agenda" =>
                $linha["id_agenda"],

            "dia" =>
                $linha["dia"],

            "horario" =>
                $linha["horario"]

        ];

    }



    echo json_encode(
        $alunos,
        JSON_UNESCAPED_UNICODE
    );


    $conn->close();

    exit;

}



// ==========================================
// ALTERAR STATUS DA AULA
// ==========================================

if ($acao === "alterar_status") {


    $id_aula =
        intval(
            $_POST["id_aula"] ?? 0
        );


    $status =
        $_POST["status"] ?? "";



    /*
    ==============================================
    STATUS PERMITIDOS
    ==============================================
    */

    $statusPermitidos = [

        "confirmada",

        "cancelada",

        "remarcada",

        "pendente"

    ];



    if (
        $id_aula <= 0 ||
        !in_array(
            $status,
            $statusPermitidos
        )
    ) {

        echo "Dados inválidos.";

        exit;

    }



    /*
    ==============================================
    ATUALIZAR
    ==============================================
    */

    $statusSeguro =
        $conn->real_escape_string(
            $status
        );



    $sql = "

        UPDATE tb_aulamarcada

        SET status =
            '$statusSeguro'

        WHERE id_aula =
            $id_aula

    ";



    if (
        $conn->query($sql)
    ) {

        echo "sucesso";

    }

    else {

        echo
            "Erro ao atualizar: "
            . $conn->error;

    }



    $conn->close();

    exit;

}



// ==========================================
// AÇÃO INVÁLIDA
// ==========================================

echo "Ação inválida.";

$conn->close();

?>