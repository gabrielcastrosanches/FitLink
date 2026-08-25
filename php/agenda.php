<?php

include "conexao.php";

header("Content-Type: application/json; charset=UTF-8");


// ==================================================
// VERIFICAR CONEXÃO
// ==================================================

if ($conn->connect_error) {

    echo json_encode([
        "erro" => "Erro de conexão com o banco."
    ]);

    exit;

}


// ==================================================
// AÇÃO
// ==================================================

$acao =
    $_GET["acao"]
    ?? $_POST["acao"]
    ?? "";


// ==================================================
// LISTAR AGENDA
// ==================================================

if ($acao === "listar") {


    $id_cref =
        intval(
            $_GET["id_cref"] ?? 0
        );


    $data =
        $_GET["data"] ?? "";



    // ----------------------------------------------
    // VALIDAR CREF
    // ----------------------------------------------

    if ($id_cref <= 0) {

        echo json_encode([
            "erro" => "ID do Personal inválido."
        ]);

        exit;

    }



    // ----------------------------------------------
    // VALIDAR DATA
    // ----------------------------------------------

    $dataObjeto =
        DateTime::createFromFormat(
            "Y-m-d",
            $data
        );


    if (
        !$dataObjeto ||
        $dataObjeto->format("Y-m-d") !== $data
    ) {

        echo json_encode([
            "erro" => "Data inválida."
        ]);

        exit;

    }



    // ----------------------------------------------
    // CONSULTA
    // ----------------------------------------------

    $sql = "

        SELECT

            agenda.id_agenda,

            agenda.dia,

            TIME_FORMAT(
                agenda.horario,
                '%H:%i'
            ) AS horario,


            aula.id_aula,

            aula.status,


            aluno.id_aluno,

            aluno.nome AS nome_aluno,

            aluno.email AS email_aluno,

            aluno.telefone AS telefone_aluno


        FROM tb_agenda AS agenda


        LEFT JOIN tb_aulamarcada AS aula

            ON aula.id_agenda =
               agenda.id_agenda


        LEFT JOIN tb_cadastroaluno AS aluno

            ON aluno.id_aluno =
               aula.id_aluno


        WHERE

            agenda.id_cref = $id_cref

            AND agenda.dia = '$data'


        ORDER BY

            agenda.horario ASC

    ";



    $resultado =
        $conn->query($sql);



    if (!$resultado) {

        echo json_encode([
            "erro" => $conn->error
        ]);

        exit;

    }



    $agenda = [];



    while (
        $linha =
        $resultado->fetch_assoc()
    ) {

        $agenda[] = [

            "id_agenda" =>
                $linha["id_agenda"],

            "dia" =>
                $linha["dia"],

            "horario" =>
                $linha["horario"],

            "id_aula" =>
                $linha["id_aula"],

            "status" =>
                $linha["status"],

            "id_aluno" =>
                $linha["id_aluno"],

            "nome_aluno" =>
                $linha["nome_aluno"],

            "email_aluno" =>
                $linha["email_aluno"],

            "telefone_aluno" =>
                $linha["telefone_aluno"]

        ];

    }



    echo json_encode(
        $agenda,
        JSON_UNESCAPED_UNICODE
    );


    $conn->close();

    exit;

}


// ==================================================
// AÇÃO INVÁLIDA
// ==================================================

echo json_encode([
    "erro" => "Ação inválida."
]);

$conn->close();

?>