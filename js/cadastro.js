function verificarTipo(){
    let tipo = document.getElementById("tipo_usuario").value;
    let aluno = document.getElementById("cadastroAluno");
    let personal = document.getElementById("cadastroPersonal");

    if(tipo === "aluno"){
        aluno.style.display = "block";
        personal.style.display = "none";

    }else if(tipo === "personal"){
        aluno.style.display = "none";
        personal.style.display = "block";

    }else{
        aluno.style.display = "none";
        personal.style.display = "none";
    }
}