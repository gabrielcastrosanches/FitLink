<?php

session_start();

header(
    "Content-Type: application/json; charset=UTF-8"
);

include "conexao.php";


// ==================================================
// VERIFICAR CONEXÃO
// ==================================================

if (!$conn) {

    echo json_encode([
        "erro" =>
            "Não foi possível conectar ao banco de dados."
    ]);

    exit;

}


// ==================================================
// DEFINIR AÇÃO
// ==================================================

$acao =
    $_GET["acao"]
    ?? $_POST["acao"]
    ?? "";


// ==================================================
// LISTAR PERSONAIS
// ==================================================

if (
    $acao === "listar_personais"
) {


    /*
    ==================================================
    CONSULTA DOS PROFISSIONAIS

    Busca:

    - CREF
    - Nome
    - Telefone
    - Menor valor dos planos
    - Quantidade de planos
    - Média das avaliações
    - Academias
    - Cidade
    - Estado
    ==================================================
    */


    $sql = "

        SELECT

            p.id_cref,

            p.nome,

            p.email,

            p.telefone,


            MIN(
                pl.valor
            ) AS valor_minimo,


            COUNT(
                DISTINCT pl.id_plano
            ) AS quantidade_planos,


            ROUND(
                AVG(
                    f.avaliacao
                ),
                1
            ) AS avaliacao,


            GROUP_CONCAT(
                DISTINCT
                a.nomeacademia
                ORDER BY
                a.nomeacademia
                SEPARATOR '||'
            ) AS academias,


            GROUP_CONCAT(
                DISTINCT
                a.cidade
                ORDER BY
                a.cidade
                SEPARATOR '||'
            ) AS cidades,


            GROUP_CONCAT(
                DISTINCT
                a.estado
                ORDER BY
                a.estado
                SEPARATOR '||'
            ) AS estados


        FROM
            tb_cadastropersonal AS p


        LEFT JOIN
            tb_planos AS pl

        ON
            pl.id_cref =
            p.id_cref


        LEFT JOIN
            tb_feedbacks AS f

        ON
            f.id_cref =
            p.id_cref


        LEFT JOIN
            tb_personalacademia AS pa

        ON
            pa.id_cref =
            p.id_cref


        LEFT JOIN
            tb_academia AS a

        ON
            a.id_academia =
            pa.id_academia


        GROUP BY

            p.id_cref,

            p.nome,

            p.email,

            p.telefone


        ORDER BY

            p.nome ASC

    ";


    // ==================================================
    // EXECUTAR
    // ==================================================

    $resultado =
        $conn->query(
            $sql
        );


    // ==================================================
    // VERIFICAR ERRO
    // ==================================================

    if (!$resultado) {

        echo json_encode([

            "erro" =>
                "Erro na consulta ao banco: " .
                $conn->error

        ]);

        $conn->close();

        exit;

    }


    // ==================================================
    // MONTAR ARRAY
    // ==================================================

    $personais = [];


    while (
        $linha =
        $resultado->fetch_assoc()
    ) {

        $personais[] =
            $linha;

    }


    // ==================================================
    // RETORNAR JSON
    // ==================================================

    echo json_encode(
        $personais,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


    $conn->close();

    exit;

}


// ==================================================
// AÇÃO INVÁLIDA
// ==================================================

echo json_encode([

    "erro" =>
        "Ação não encontrada."

]);


$conn->close();

?>