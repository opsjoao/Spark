const passwordIcons = document.querySelectorAll('.password-icon');


passwordIcons.forEach(icon => {
    icon.addEventListener('click', function () {
        const input = this.parentElement.querySelector('.form-control');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.classList.toggle('fa-eye');
    })
})
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
    if (value.length > 11) value = value.slice(0, 11);
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = value;
});


const senhaInput = document.getElementById('password');
const csenhaInput = document.getElementById('confirm_password');
const errorSpan = document.getElementById('password-error');

function checkPasswords() {
    if (csenhaInput.value !== senhaInput.value) {
        errorSpan.textContent = "As senhas não coincidem!";
        errorSpan.style.display = "block";
        return false; // <-- IMPORTANTE: Retorna 'false' em caso de erro
    } else {
        errorSpan.textContent = "";
        errorSpan.style.display = "none";
        return true; // <-- IMPORTANTE: Retorna 'true' em caso de sucesso
    }
}

senhaInput.addEventListener('input', checkPasswords);
csenhaInput.addEventListener('input', checkPasswords);

// Evento de submissão do formulário
form.addEventListener('submit', function(e) {
    // Primeiro, previne o envio para podermos validar
    e.preventDefault();

    // Chama a função de verificação
    const senhasCoincidem = checkPasswords();

    // Se as senhas NÃO coincidirem
    if (!senhasCoincidem) {
        // Remove a classe de animação (caso já exista) para poder reativá-la
        errorSpan.classList.remove('shake-error');

        // Um pequeno truque para forçar o navegador a refazer a animação
        void errorSpan.offsetWidth;

        // Adiciona a classe que ativa a animação CSS
        errorSpan.classList.add('shake-error');

    } else {
        form.submit(); // Senhas coincidem, pode enviar o formulário
    }
});
