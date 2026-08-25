// ==========================================
// CARREGAR PERFIL
// ==========================================

function carregarPerfil() {

    fetch("../../php/perfil.php?acao=perfil")

        .then(response => response.json())

        .then(dados => {

            if (dados.erro) {

                alert(dados.erro);

                return;
            }


            document.getElementById("nome").value =
                dados.nome || "";


            document.getElementById("email").value =
                dados.email || "";


            document.getElementById("telefone").value =
                dados.telefone || "";


            document.getElementById("data_nascimento").value =
                dados.data_nascimento || "";


            document.getElementById("genero").value =
                dados.genero || "";


            document.getElementById("cref").value =
                dados.id_cref || "";

        })

        .catch(error => {

            console.error(error);

            alert(
                "Erro ao carregar o perfil."
            );

        });

}


// ==========================================
// SALVAR PERFIL
// ==========================================

document
    .getElementById("perfilForm")
    .addEventListener(
        "submit",
        function(e) {

            e.preventDefault();


            const dados =
                new FormData();


            dados.append(
                "acao",
                "editar"
            );


            dados.append(
                "nome",
                document.getElementById("nome").value
            );


            dados.append(
                "email",
                document.getElementById("email").value
            );


            dados.append(
                "telefone",
                document.getElementById("telefone").value
            );


            dados.append(
                "data_nascimento",
                document.getElementById(
                    "data_nascimento"
                ).value
            );


            dados.append(
                "genero",
                document.getElementById("genero").value
            );


            fetch(
                "../../php/perfil.php",
                {
                    method: "POST",
                    body: dados
                }
            )

            .then(response =>
                response.text()
            )

            .then(resultado => {

                alert(resultado);

                carregarPerfil();

            })

            .catch(error => {

                console.error(error);

                alert(
                    "Erro ao salvar alterações."
                );

            });

        }
    );


// ==========================================
// BUSCAR ACADEMIAS
// ==========================================

function buscarAcademias() {

    const botao =
        document.getElementById(
            "btnLocalizacao"
        );


    const status =
        document.getElementById(
            "statusLocalizacao"
        );


    const lista =
        document.getElementById(
            "listaAcademias"
        );


    botao.disabled = true;


    status.textContent =
        "Procurando academias em Ourinhos...";


    lista.innerHTML = "";


    // ==========================================
    // LOCALIZAÇÃO FIXA - OURINHOS/SP
    // ==========================================
    //
    // A localização real do dispositivo foi
    // desativada.
    //
    // O sistema NÃO utiliza:
    //
    // navigator.geolocation.getCurrentPosition()
    //
    // A busca sempre será feita a partir
    // destas coordenadas de Ourinhos-SP.
    //

    const latitude = -22.979;

    const longitude = -49.871;


    status.textContent =
        "Localização definida como Ourinhos. Procurando academias próximas...";


    // ==========================================
    // ENVIAR LOCALIZAÇÃO PARA O PHP
    // ==========================================

    const dados =
        new FormData();


    dados.append(
        "acao",
        "buscar_academias"
    );


    dados.append(
        "latitude",
        latitude
    );


    dados.append(
        "longitude",
        longitude
    );


    fetch(
        "../../php/perfil.php",
        {
            method: "POST",
            body: dados
        }
    )

    .then(response =>
        response.json()
    )

    .then(resultado => {

        botao.disabled = false;


        if (resultado.erro) {

            status.textContent =
                resultado.erro;

            return;

        }


        status.textContent =
            resultado.academias.length +
            " academias encontradas em um raio de 50 km de Ourinhos.";


        mostrarAcademias(
            resultado.academias
        );

    })

    .catch(error => {

        console.error(error);

        botao.disabled = false;

        status.textContent =
            "Erro ao buscar academias.";

    });

}


// ==========================================
// MOSTRAR ACADEMIAS
// ==========================================

function mostrarAcademias(academias) {

    const lista =
        document.getElementById(
            "listaAcademias"
        );


    const botaoSalvar =
        document.getElementById(
            "btnSalvarAcademias"
        );


    lista.innerHTML = "";


    if (
        !academias ||
        academias.length === 0
    ) {

        lista.innerHTML = `

            <div class="mensagem">

                <span>
                    🏋️
                </span>

                <p>
                    Nenhuma academia encontrada
                    nessa região.
                </p>

            </div>

        `;


        botaoSalvar.style.display =
            "none";


        return;

    }


    academias.forEach(
        function(academia) {

            const item =
                document.createElement(
                    "label"
                );


            item.className =
                "academia-item";


            item.innerHTML = `

                <div class="academia-topo">

                    <input
                        type="checkbox"
                        class="academia-checkbox"
                        value="${escaparHTML(
                            academia.osm_id
                        )}"
                    >

                    <div class="academia-info">

                        <span
                            class="academia-nome"
                        >

                            ${escaparHTML(
                                academia.nome
                            )}

                        </span>


                        <span
                            class="academia-endereco"
                        >

                            ${escaparHTML(
                                academia.endereco
                            )}

                        </span>


                        ${
                            academia.distancia
                            ?
                            `
                            <span
                                class="distancia"
                            >

                                ${escaparHTML(
                                    academia.distancia
                                )}

                            </span>
                            `
                            :
                            ""
                        }

                    </div>

                </div>

            `;


            const checkbox =
                item.querySelector(
                    ".academia-checkbox"
                );


            checkbox.addEventListener(
                "change",
                function() {

                    if (
                        checkbox.checked
                    ) {

                        item.classList.add(
                            "selecionada"
                        );

                    }

                    else {

                        item.classList.remove(
                            "selecionada"
                        );

                    }

                }
            );


            lista.appendChild(item);

        }
    );


    botaoSalvar.style.display =
        "inline-block";

}


// ==========================================
// SALVAR ACADEMIAS
// ==========================================

function salvarAcademias() {

    const selecionadas =
        document.querySelectorAll(
            ".academia-checkbox:checked"
        );


    if (
        selecionadas.length === 0
    ) {

        alert(
            "Selecione pelo menos uma academia."
        );

        return;

    }


    const academias = [];


    selecionadas.forEach(
        function(checkbox) {

            academias.push(
                checkbox.value
            );

        }
    );


    const dados =
        new FormData();


    dados.append(
        "acao",
        "salvar_academias"
    );


    academias.forEach(
        function(osmId) {

            dados.append(
                "osm_ids[]",
                osmId
            );

        }
    );


    fetch(
        "../../php/perfil.php",
        {
            method: "POST",
            body: dados
        }
    )

    .then(response =>
        response.text()
    )

    .then(resultado => {

        alert(resultado);

    })

    .catch(error => {

        console.error(error);

        alert(
            "Erro ao salvar academias."
        );

    });

}


// ==========================================
// ESCAPAR HTML
// ==========================================

function escaparHTML(texto) {

    if (!texto) {

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


// ==========================================
// SAIR
// ==========================================

function sair() {

    window.location.href =
        "../login.html";

}


// ==========================================
// INICIAR
// ==========================================

carregarPerfil();