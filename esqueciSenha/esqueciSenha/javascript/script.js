// Aguarda o carregamento completo do DOM para garantir que os elementos existem
document.addEventListener('DOMContentLoaded', function() {
    // 1. Pega os elementos do formulário
    const form = document.getElementById('form');
    const emailInput = document.getElementById('email');
    const confirmEmailInput = document.getElementById('confirm_email');
    const emailErrorSpan = document.getElementById('email-error');

    // 2. Adiciona um "ouvinte" de evento para o envio do formulário
    form.addEventListener('submit', function(event) {
        // Pega os valores dos campos
        const email = emailInput.value;
        const confirmEmail = confirmEmailInput.value;

        // 3. Verifica se os e-mails são diferentes
        if (email !== confirmEmail) {
            // Se forem diferentes, impede o envio do formulário
            event.preventDefault();
            
            // 4. Exibe a mensagem de erro
            emailErrorSpan.textContent = 'Os e-mails digitados não são iguais.';
            emailErrorSpan.style.display = 'block'; // Torna o <span> visível
            
            // 5. Opcional: Adiciona um estilo visual de erro aos campos
            emailInput.classList.add('error');
            confirmEmailInput.classList.add('error');
        } else {
            // Se forem iguais, oculta qualquer mensagem de erro
            emailErrorSpan.style.display = 'none'; // Esconde o <span>
            emailInput.classList.remove('error');
            confirmEmailInput.classList.remove('error');
            // O formulário continua com o envio normalmente
        }
    });

    // Opcional: Adiciona um ouvinte para limpar o erro enquanto o usuário digita
    confirmEmailInput.addEventListener('input', function() {
        emailErrorSpan.style.display = 'none';
        emailInput.classList.remove('error');
        confirmEmailInput.classList.remove('error');
    });
});