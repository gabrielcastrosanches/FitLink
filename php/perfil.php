<?php

session_start();

include "conexao.php";


// ==========================================
// VERIFICAR LOGIN
// ==========================================

if (
    !isset($_SESSION["tipo_usuario"]) ||
    $_SESSION["tipo_usuario"] !== "personal"
) {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );

    echo json_encode([
        "erro" =>
            "Usuário não está logado como Personal."
    ]);

    exit;

}


$id_cref =
    intval(
        $_SESSION["id_cref"]
    );


// ==========================================
// CARREGAR PERFIL
// ==========================================

if (
    isset($_GET["acao"]) &&
    $_GET["acao"] === "perfil"
) {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );


    $sql = "

        SELECT

            id_cref,
            nome,
            email,
            telefone,
            data_nascimento,
            genero

        FROM tb_cadastropersonal

        WHERE id_cref = ?

        LIMIT 1

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


    if (
        $resultado->num_rows === 0
    ) {

        echo json_encode([
            "erro" =>
                "Personal não encontrado."
        ]);

        exit;

    }


    $personal =
        $resultado->fetch_assoc();


    echo json_encode(
        $personal,
        JSON_UNESCAPED_UNICODE
    );


    $stmt->close();

    exit;

}


// ==========================================
// BUSCAR ACADEMIAS NO OPENSTREETMAP
// RAIO: 25 KM
// ==========================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["acao"]) &&
    $_POST["acao"] === "buscar_academias"
) {

    header(
        "Content-Type: application/json; charset=UTF-8"
    );


    $latitude =
        floatval(
            $_POST["latitude"] ?? 0
        );


    $longitude =
        floatval(
            $_POST["longitude"] ?? 0
        );


    if (
        $latitude == 0 ||
        $longitude == 0
    ) {

        echo json_encode([
            "erro" =>
                "Localização inválida."
        ]);

        exit;

    }


    /*
     * ==========================================
     * RAIO DA BUSCA
     * ==========================================
     *
     * 50000 metros = 50 quilômetros
     */

    $raio =
        50000;


    /*
     * ==========================================
     * CONSULTA OVERPASS
     * ==========================================
     *
     * Procuramos:
     *
     * leisure=fitness_centre
     * sport=gym
     * sport=fitness
     *
     * dentro de um raio de 25 km.
     */

    $query = <<<OVERPASS

[out:json][timeout:40];

(

    nwr(
        around:$raio,
        $latitude,
        $longitude
    )["leisure"="fitness_centre"];

    nwr(
        around:$raio,
        $latitude,
        $longitude
    )["sport"~"^(gym|fitness)$",i];

);

out center tags;

OVERPASS;


    $url =
        "https://overpass-api.de/api/interpreter";


    $ch =
        curl_init($url);


    curl_setopt(
        $ch,
        CURLOPT_POST,
        true
    );


    curl_setopt(
        $ch,
        CURLOPT_POSTFIELDS,
        http_build_query([
            "data" => $query
        ])
    );


    curl_setopt(
        $ch,
        CURLOPT_RETURNTRANSFER,
        true
    );


    curl_setopt(
        $ch,
        CURLOPT_FOLLOWLOCATION,
        true
    );


    curl_setopt(
        $ch,
        CURLOPT_CONNECTTIMEOUT,
        15
    );


    curl_setopt(
        $ch,
        CURLOPT_TIMEOUT,
        45
    );


    curl_setopt(
        $ch,
        CURLOPT_USERAGENT,
        "FitLink/1.0 (projeto acadêmico)"
    );


    $resposta =
        curl_exec($ch);


    $httpCode =
        curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );


    $erroCurl =
        curl_error($ch);


    curl_close($ch);


    if (
        $resposta === false
    ) {

        echo json_encode([

            "erro" =>
                "Erro ao conectar com o OpenStreetMap.",

            "detalhes" =>
                $erroCurl

        ]);

        exit;

    }


    if (
        $httpCode < 200 ||
        $httpCode >= 300
    ) {

        echo json_encode([

            "erro" =>
                "O serviço de localização está temporariamente indisponível.",

            "codigo" =>
                $httpCode

        ]);

        exit;

    }


    $dados =
        json_decode(
            $resposta,
            true
        );


    if (
        !is_array($dados)
    ) {

        echo json_encode([
            "erro" =>
                "Resposta inválida do OpenStreetMap."
        ]);

        exit;

    }


    $academias = [];

    $idsEncontrados = [];


    if (
        isset($dados["elements"]) &&
        is_array($dados["elements"])
    ) {


        foreach (
            $dados["elements"]
            as $elemento
        ) {


            $tipo =
                $elemento["type"]
                ?? "";


            $id =
                $elemento["id"]
                ?? "";


            if (
                $tipo === "" ||
                $id === ""
            ) {

                continue;

            }


            $osmId =
                $tipo . "/" . $id;


            if (
                isset(
                    $idsEncontrados[$osmId]
                )
            ) {

                continue;

            }


            $idsEncontrados[$osmId] =
                true;


            $tags =
                $elemento["tags"]
                ?? [];


            /*
             * NOME
             */

            $nome =
                $tags["name"]
                ?? "";


            if (
                trim($nome) === ""
            ) {

                $nome =
                    "Academia sem nome";

            }


            /*
             * ENDEREÇO
             */

            $endereco =
                montarEndereco(
                    $tags
                );


            /*
             * COORDENADAS
             */

            $lat =
                null;


            $lng =
                null;


            if (
                $tipo === "node"
            ) {

                $lat =
                    $elemento["lat"]
                    ?? null;

                $lng =
                    $elemento["lon"]
                    ?? null;

            }

            else {

                $lat =
                    $elemento[
                        "center"
                    ]["lat"]
                    ?? null;


                $lng =
                    $elemento[
                        "center"
                    ]["lon"]
                    ?? null;

            }


            if (
                $lat === null ||
                $lng === null
            ) {

                continue;

            }


            /*
             * DISTÂNCIA
             */

            $distanciaKm =
                calcularDistancia(

                    $latitude,
                    $longitude,

                    floatval($lat),
                    floatval($lng)

                );


            /*
             * FORMATAR DISTÂNCIA
             */

            if (
                $distanciaKm < 1
            ) {

                $distancia =
                    round(
                        $distanciaKm * 1000
                    ) .
                    " m de distância";

            }

            else {

                $distancia =
                    number_format(
                        $distanciaKm,
                        1,
                        ",",
                        "."
                    ) .
                    " km de distância";

            }


            $academias[] = [

                "osm_id" =>
                    $osmId,

                "nome" =>
                    $nome,

                "endereco" =>
                    $endereco,

                "latitude" =>
                    floatval($lat),

                "longitude" =>
                    floatval($lng),

                "distancia" =>
                    $distancia,

                "distancia_km" =>
                    $distanciaKm

            ];

        }

    }


    /*
     * ORDENAR DA MAIS PRÓXIMA
     * PARA A MAIS DISTANTE
     */

    usort(

        $academias,

        function(
            $a,
            $b
        ) {

            return
                $a["distancia_km"]
                <=>
                $b["distancia_km"];

        }

    );


    /*
     * MOSTRAR ATÉ 50 ACADEMIAS
     *
     * Antes estava em 20.
     * Como agora buscamos 25 km,
     * aumentamos também a quantidade
     * de resultados.
     */

    $academias =
        array_slice(
            $academias,
            0,
            50
        );


    echo json_encode(

        [

            "academias" =>
                $academias,

            "raio_km" =>
                25

        ],

        JSON_UNESCAPED_UNICODE

    );


    exit;

}


// ==========================================
// EDITAR PERFIL
// ==========================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["acao"]) &&
    $_POST["acao"] === "editar"
) {


    $nome =
        trim(
            $_POST["nome"] ?? ""
        );


    $email =
        trim(
            $_POST["email"] ?? ""
        );


    $telefone =
        trim(
            $_POST["telefone"] ?? ""
        );


    $data_nascimento =
        $_POST["data_nascimento"]
        ?? "";


    $genero =
        trim(
            $_POST["genero"] ?? ""
        );


    if (
        $nome === "" ||
        $email === ""
    ) {

        echo
            "Nome e e-mail são obrigatórios.";

        exit;

    }


    $sql = "

        UPDATE tb_cadastropersonal

        SET

            nome = ?,

            email = ?,

            telefone = ?,

            data_nascimento =
                NULLIF(?, ''),

            genero = ?

        WHERE id_cref = ?

    ";


    $stmt =
        $conn->prepare($sql);


    $stmt->bind_param(

        "sssssi",

        $nome,
        $email,
        $telefone,
        $data_nascimento,
        $genero,
        $id_cref

    );


    if (
        $stmt->execute()
    ) {

        $_SESSION[
            "nome_personal"
        ] = $nome;


        echo
            "Perfil atualizado com sucesso!";

    }

    else {

        echo
            "Erro ao atualizar o perfil.";

    }


    $stmt->close();

    exit;

}


// ==========================================
// SALVAR ACADEMIAS
// ==========================================

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["acao"]) &&
    $_POST["acao"] === "salvar_academias"
) {


    $osmIds =
        $_POST["osm_ids"]
        ?? [];


    if (
        !is_array($osmIds)
    ) {

        $osmIds = [];

    }


    if (
        count($osmIds) === 0
    ) {

        echo
            "Nenhuma academia selecionada.";

        exit;

    }


    $conn->begin_transaction();


    try {


        /*
         * Remover vínculos antigos.
         */

        $sqlDelete = "

            DELETE FROM
                tb_personalacademia

            WHERE id_cref = ?

        ";


        $stmtDelete =
            $conn->prepare(
                $sqlDelete
            );


        $stmtDelete->bind_param(
            "i",
            $id_cref
        );


        $stmtDelete->execute();


        $stmtDelete->close();


        /*
         * Adicionar novas academias.
         */

        foreach (
            $osmIds
            as $osmId
        ) {


            $osmId =
                trim($osmId);


            if (
                $osmId === ""
            ) {

                continue;

            }


            /*
             * Separar tipo e ID.
             */

            $partes =
                explode(
                    "/",
                    $osmId
                );


            if (
                count($partes) !== 2
            ) {

                continue;

            }


            $tipoOsm =
                $partes[0];


            $idOsm =
                intval(
                    $partes[1]
                );


            if (
                !in_array(
                    $tipoOsm,
                    [
                        "node",
                        "way",
                        "relation"
                    ]
                )
            ) {

                continue;

            }


            /*
             * Consultar novamente o OSM
             * para pegar os dados da academia.
             */

            $query = <<<OVERPASS

[out:json][timeout:20];

$tipoOsm($idOsm);

out center tags;

OVERPASS;


            $ch =
                curl_init(
                    "https://overpass-api.de/api/interpreter"
                );


            curl_setopt(
                $ch,
                CURLOPT_POST,
                true
            );


            curl_setopt(
                $ch,
                CURLOPT_POSTFIELDS,
                http_build_query([
                    "data" => $query
                ])
            );


            curl_setopt(
                $ch,
                CURLOPT_RETURNTRANSFER,
                true
            );


            curl_setopt(
                $ch,
                CURLOPT_TIMEOUT,
                25
            );


            curl_setopt(
                $ch,
                CURLOPT_USERAGENT,
                "FitLink/1.0 (projeto acadêmico)"
            );


            $resposta =
                curl_exec($ch);


            curl_close($ch);


            $dados =
                json_decode(
                    $resposta,
                    true
                );


            if (
                !isset(
                    $dados["elements"][0]
                )
            ) {

                continue;

            }


            $elemento =
                $dados[
                    "elements"
                ][0];


            $tags =
                $elemento["tags"]
                ?? [];


            $nome =
                $tags["name"]
                ?? "Academia";


            $endereco =
                montarEndereco(
                    $tags
                );


            /*
             * Coordenadas
             */

            if (
                $tipoOsm === "node"
            ) {

                $lat =
                    $elemento["lat"]
                    ?? null;

                $lng =
                    $elemento["lon"]
                    ?? null;

            }

            else {

                $lat =
                    $elemento[
                        "center"
                    ]["lat"]
                    ?? null;

                $lng =
                    $elemento[
                        "center"
                    ]["lon"]
                    ?? null;

            }


            /*
             * Identificador OSM.
             */

            $identificador =
                $osmId;


            /*
             * Verificar se academia
             * já existe.
             */

            $sqlBusca = "

                SELECT
                    id_academia

                FROM tb_academia

                WHERE place_id = ?

                LIMIT 1

            ";


            $stmtBusca =
                $conn->prepare(
                    $sqlBusca
                );


            $stmtBusca->bind_param(
                "s",
                $identificador
            );


            $stmtBusca->execute();


            $resultado =
                $stmtBusca->get_result();


            if (
                $resultado->num_rows > 0
            ) {


                $academia =
                    $resultado->fetch_assoc();


                $id_academia =
                    intval(
                        $academia[
                            "id_academia"
                        ]
                    );

            }

            else {


                /*
                 * Criar academia.
                 */

                $sqlInsert = "

                    INSERT INTO tb_academia

                    (
                        nomeacademia,
                        endereco,
                        place_id,
                        latitude,
                        longitude
                    )

                    VALUES
                    (?, ?, ?, ?, ?)

                ";


                $stmtInsert =
                    $conn->prepare(
                        $sqlInsert
                    );


                $stmtInsert->bind_param(

                    "sssdd",

                    $nome,
                    $endereco,
                    $identificador,
                    $lat,
                    $lng

                );


                $stmtInsert->execute();


                $id_academia =
                    $conn->insert_id;


                $stmtInsert->close();

            }


            $stmtBusca->close();


            /*
             * Criar relação entre
             * Personal e Academia.
             */

            $sqlRelacao = "

                INSERT INTO
                    tb_personalacademia

                (
                    id_cref,
                    id_academia
                )

                VALUES (?, ?)

            ";


            $stmtRelacao =
                $conn->prepare(
                    $sqlRelacao
                );


            $stmtRelacao->bind_param(

                "ii",

                $id_cref,
                $id_academia

            );


            $stmtRelacao->execute();


            $stmtRelacao->close();

        }


        $conn->commit();


        echo
            "Academias salvas com sucesso!";

    }

    catch (
        Exception $e
    ) {


        $conn->rollback();


        echo
            "Erro ao salvar academias: " .
            $e->getMessage();

    }


    exit;

}


// ==========================================
// MONTAR ENDEREÇO
// ==========================================

function montarEndereco(
    $tags
) {


    $partes = [];


    /*
     * Rua
     */

    if (
        isset(
            $tags["addr:street"]
        )
    ) {

        $rua =
            $tags["addr:street"];


        if (
            isset(
                $tags["addr:housenumber"]
            )
        ) {

            $rua .=
                ", " .
                $tags["addr:housenumber"];

        }


        $partes[] =
            $rua;

    }


    /*
     * Bairro
     */

    if (
        isset(
            $tags["addr:suburb"]
        )
    ) {

        $partes[] =
            $tags["addr:suburb"];

    }


    /*
     * Cidade
     */

    if (
        isset(
            $tags["addr:city"]
        )
    ) {

        $partes[] =
            $tags["addr:city"];

    }


    if (
        count($partes) === 0
    ) {

        return
            "Endereço não informado no OpenStreetMap";

    }


    return
        implode(
            " - ",
            $partes
        );

}


// ==========================================
// CALCULAR DISTÂNCIA
// ==========================================

function calcularDistancia(

    $lat1,
    $lon1,
    $lat2,
    $lon2

) {


    $raioTerra =
        6371;


    $dLat =
        deg2rad(
            $lat2 - $lat1
        );


    $dLon =
        deg2rad(
            $lon2 - $lon1
        );


    $a =

        sin($dLat / 2) *
        sin($dLat / 2)

        +

        cos(
            deg2rad($lat1)
        )

        *

        cos(
            deg2rad($lat2)
        )

        *

        sin($dLon / 2) *
        sin($dLon / 2);


    $c =
        2 *
        atan2(
            sqrt($a),
            sqrt(1 - $a)
        );


    return
        $raioTerra * $c;

}


// ==========================================
// AÇÃO INVÁLIDA
// ==========================================

header(
    "Content-Type: application/json; charset=UTF-8"
);


echo json_encode([

    "erro" =>
        "Ação inválida."

]);


$conn->close();

?>