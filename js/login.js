document
    .getElementById("loginForm")
    .addEventListener("submit", function (e) {

        e.preventDefault();

        const dados = new FormData();

        dados.append(
            "email",
            document.getElementById("email").value
        );

        dados.append(
            "senha",
            document.getElementById("senha").value
        );


        fetch("../php/login.php", {
            method: "POST",
            body: dados
        })

        .then(resposta => resposta.text())

        .then(tipo => {

            tipo = tipo.trim();

            console.log("Resposta do PHP:", tipo);


            if (tipo === "aluno") {

                window.location.href =
                    "aluno/aluno.html";

            }

            else if (tipo === "personal") {

                window.location.href =
                    "personal/personal.html";

            }

            else {

                alert(
                    "Email ou senha incorretos"
                );

            }

        })

        .catch(error => {

            console.error(error);

            alert(
                "Erro ao conectar com o servidor."
            );

        });

    });