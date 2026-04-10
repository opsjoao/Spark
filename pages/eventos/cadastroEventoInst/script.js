const diasDaSemanaDiv = document.getElementById('diasDaSemana');

const diaPontualDiv = document.getElementById('diaPontual');

const radioSemanal = document.getElementById("semanal");
const radioPontual = document.getElementById("pontual");

const form = document.getElementById("form");
const inputDataEvento = document.getElementById("dataEvento");
const selectDias = document.getElementById("dias");

// Adiciona um "ouvinte de evento" (event listener) a cada input de rádio

  radioSemanal.addEventListener("change", function () {
    if (this.checked) {
      diasDaSemanaDiv.style.display = "block";
      diaPontualDiv.style.display = "none";
      inputDataEvento.required = false;
      selectDias.required = true;
    }
  });

  radioPontual.addEventListener("change", function () {
    if (this.checked) {
      diaPontualDiv.style.display = "block";
      diasDaSemanaDiv.style.display = "none";
      inputDataEvento.required = true;
      selectDias.required = false; 
    }
  });
  