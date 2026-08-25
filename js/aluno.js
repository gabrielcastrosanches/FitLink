// ==================================================
// FITLINK - ÁREA DO ALUNO
// ==================================================


// ==================================================
// CONFIGURAÇÃO
// ==================================================

const URL_PHP =
    "../../php/aluno.php";


// ==================================================
// ELEMENTOS DA PÁGINA
// ==================================================

const listaPersonais =
    document.getElementById(
        "listaPersonais"
    );

const campoPesquisa =
    document.getElementById(
        "campoPesquisa"
    );


// ==================================================
// VARIÁVEL COM TODOS OS PERSONAIS
// ==================================================

let personais = [];


// ==================================================
// CARREGAR PERSONAIS
// ==================================================

function carregarPersonais() {

    mostrarCarregando();


    fetch(
        URL_PHP +
        "?acao=listar_personais"
    )

    .then(response => {

        if (!response.ok) {

            throw new Error(
                "Erro HTTP " +
                response.status
            );

        }

        return response.json();

    })

    .then(dados => {

        console.log(
            "Dados recebidos:",
            dados
        );


        // ------------------------------------------
        // VERIFICAR ERRO DO PHP
        // ------------------------------------------

        if (
            dados &&
            dados.erro
        ) {

            mostrarErro(
                dados.erro
            );

            return;

        }


        // ------------------------------------------
        // VERIFICAR FORMATO
        // ------------------------------------------

        if (
            !Array.isArray(dados)
        ) {

            mostrarErro(
                "O servidor não retornou uma lista de profissionais."
            );

            return;

        }


        // ------------------------------------------
        // SALVAR DADOS
        // ------------------------------------------

        personais = dados;


        // ------------------------------------------
        // MOSTRAR
        // ------------------------------------------

        mostrarPersonais(
            personais
        );

    })

    .catch(error => {

        console.error(
            "Erro:",
            error
        );


        mostrarErro(
            "Não foi possível carregar os profissionais."
        );

    });

}


// ==================================================
// MOSTRAR PERSONAIS
// ==================================================

function mostrarPersonais(
    lista
) {

    listaPersonais.innerHTML = "";


    // ------------------------------------------
    // NENHUM PERSONAL
    // ------------------------------------------

    if (
        lista.length === 0
    ) {

        listaPersonais.innerHTML = `

            <div class="sem-resultados">

                <h3>
                    Nenhum profissional encontrado
                </h3>

                <p>
                    Ainda não existem profissionais
                    disponíveis.
                </p>

            </div>

        `;

        return;

    }


    // ------------------------------------------
    // CRIAR CARDS
    // ------------------------------------------

    lista.forEach(
        personal => {

            const card =
                criarCard(
                    personal
                );


            listaPersonais.appendChild(
                card
            );

        }
    );

}


// ==================================================
// CRIAR CARD
// ==================================================

function criarCard(
    personal
) {

    const card =
        document.createElement(
            "article"
        );


    card.className =
        "card-personal";


    // ==================================================
    // NOME
    // ==================================================

    const nome =
        escaparHTML(
            personal.nome
        );


    // ==================================================
    // CREF
    // ==================================================

    const cref =
        escaparHTML(
            personal.id_cref
        );


    // ==================================================
    // TELEFONE
    // ==================================================

    const telefone =
        escaparHTML(
            personal.telefone ||
            "Não informado"
        );


    // ==================================================
    // AVALIAÇÃO
    // ==================================================

    let avaliacaoTexto =
        "Sem avaliações";


    if (
        personal.avaliacao !== null &&
        personal.avaliacao !== undefined &&
        personal.avaliacao !== ""
    ) {

        avaliacaoTexto =
            Number(
                personal.avaliacao
            ).toFixed(1);

    }


    // ==================================================
    // VALOR
    // ==================================================

    let valorTexto =
        "Não informado";


    if (
        personal.valor_minimo !== null &&
        personal.valor_minimo !== undefined &&
        personal.valor_minimo !== ""
    ) {

        valorTexto =
            formatarMoeda(
                personal.valor_minimo
            );

    }


    // ==================================================
    // CIDADE
    // ==================================================

    const cidade =
        escaparHTML(
            personal.cidades ||
            "Não informado"
        );


    // ==================================================
    // ACADEMIAS
    // ==================================================

    let academiasHTML =
        "";


    if (
        personal.academias
    ) {

        const academias =
            personal.academias
                .split("||")
                .filter(
                    academia =>
                        academia.trim() !== ""
                );


        academiasHTML =
            academias
                .map(
                    academia => `

                        <span class="academia">

                            ${escaparHTML(
                                academia
                            )}

                        </span>

                    `
                )
                .join("");

    }


    if (
        academiasHTML === ""
    ) {

        academiasHTML = `

            <span class="academia">

                Nenhuma informada

            </span>

        `;

    }


    // ==================================================
    // HTML DO CARD
    // ==================================================

    card.innerHTML = `

        <div class="card-cabecalho">

            <div>

                <h3 class="nome-personal">

                    ${nome}

                </h3>


                <div class="cref">

                    CREF:
                    ${cref}

                </div>

            </div>


            <div class="avaliacao">

                ⭐
                ${avaliacaoTexto}

            </div>

        </div>



        <div class="informacoes">


            <div class="info">

                <span class="info-icone">
                    📍
                </span>

                <span>

                    <strong>
                        Cidade:
                    </strong>

                    ${cidade}

                </span>

            </div>



            <div class="info">

                <span class="info-icone">
                    📞
                </span>

                <span>

                    <strong>
                        Telefone:
                    </strong>

                    ${telefone}

                </span>

            </div>



            <div class="academias">

                <div class="academias-titulo">

                    🏋️
                    <strong>
                        Academias onde atua
                    </strong>

                </div>


                <div class="lista-academias">

                    ${academiasHTML}

                </div>

            </div>


        </div>



        <div class="card-rodape">


            <div class="preco-container">

                <span class="preco-label">

                    A partir de

                </span>


                <span class="preco">

                    ${valorTexto}

                </span>

            </div>


            <button
                class="btn-ver"
                onclick="verPersonal(
                    '${personal.id_cref}'
                )"
            >

                Agendar
            </button>


        </div>

    `;


    return card;

}


// ==================================================
// PESQUISA
// ==================================================

campoPesquisa.addEventListener(
    "input",
    function () {

        const termo =
            this.value
                .trim()
                .toLowerCase();


        // ------------------------------------------
        // SEM PESQUISA
        // ------------------------------------------

        if (
            termo === ""
        ) {

            mostrarPersonais(
                personais
            );

            return;

        }


        // ------------------------------------------
        // FILTRAR
        // ------------------------------------------

        const resultados =
            personais.filter(
                personal => {

                    const nome =
                        String(
                            personal.nome ||
                            ""
                        ).toLowerCase();


                    const academias =
                        String(
                            personal.academias ||
                            ""
                        ).toLowerCase();


                    const cidade =
                        String(
                            personal.cidades ||
                            ""
                        ).toLowerCase();


                    return (
                        nome.includes(
                            termo
                        ) ||

                        academias.includes(
                            termo
                        ) ||

                        cidade.includes(
                            termo
                        )
                    );

                }
            );


        mostrarPersonais(
            resultados
        );

    }
);


// ==================================================
// FORMATAR MOEDA
// ==================================================

function formatarMoeda(
    valor
) {

    return Number(
        valor
    ).toLocaleString(
        "pt-BR",
        {
            style:
                "currency",

            currency:
                "BRL"
        }
    );

}


// ==================================================
// ESCAPAR HTML
// ==================================================

function escaparHTML(
    texto
) {

    if (
        texto === null ||
        texto === undefined
    ) {

        return "";

    }


    return String(texto)

        .replace(
            /&/g,
            "&amp;"
        )

        .replace(
            /</g,
            "&lt;"
        )

        .replace(
            />/g,
            "&gt;"
        )

        .replace(
            /"/g,
            "&quot;"
        )

        .replace(
            /'/g,
            "&#039;"
        );

}


// ==================================================
// VER PERFIL DO PERSONAL
// ==================================================

function verPersonal(
    idCref
) {

    window.location.href =
        "perfil-personal.html?id=" +
        encodeURIComponent(
            idCref
        );

}


// ==================================================
// CARREGANDO
// ==================================================

function mostrarCarregando() {

    listaPersonais.innerHTML = `

        <div class="carregando">

            <div class="spinner"></div>

            <p>
                Carregando profissionais...
            </p>

        </div>

    `;

}


// ==================================================
// ERRO
// ==================================================

function mostrarErro(
    mensagem
) {

    listaPersonais.innerHTML = `

        <div class="erro">

            <h3>
                Erro ao carregar
            </h3>

            <p>

                ${escaparHTML(
                    mensagem
                )}

            </p>

        </div>

    `;

}


// ==================================================
// SAIR
// ==================================================

function sair() {

    window.location.href =
        "../../php/logout.php";

}


// ==================================================
// INICIAR
// ==================================================

carregarPersonais();