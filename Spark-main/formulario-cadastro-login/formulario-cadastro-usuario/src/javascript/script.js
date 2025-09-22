// Aguarda o carregamento completo da página
document.addEventListener('DOMContentLoaded', function() {

    // --- CORREÇÃO: Declarando a variável 'form' ---
    const form = document.getElementById('form');
    // ---------------------------------------------

    const passwordIcons = document.querySelectorAll('.password-icon');
    
    passwordIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const input = this.parentElement.querySelector('.form-control');
            
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            } else {
                input.type = 'password';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            }
        });
    });

    // Máscara para o campo CPF
    document.getElementById('cpf').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        e.target.value = value;
    });

    // Seleciona os campos de senha e o span de erro
    const senhaInput = document.getElementById('password');
    const csenhaInput = document.getElementById('confirm_password');
    const errorSpan = document.getElementById('password-error');

    // Função que verifica se as senhas coincidem
    function checkPasswords() {
        if (csenhaInput.value !== senhaInput.value) {
            errorSpan.textContent = "As senhas não coincidem!";
            errorSpan.style.display = "block";
            return false;
        } else {
            errorSpan.textContent = "";
            errorSpan.style.display = "none";
            return true;
        }
    }

    // Verifica as senhas em tempo real enquanto o usuário digita
    senhaInput.addEventListener('input', checkPasswords);
    csenhaInput.addEventListener('input', checkPasswords);

    // Evento de submissão do formulário
    form.addEventListener('submit', function(e) {
        // Chama a função de verificação uma última vez antes de enviar
        const senhasCoincidem = checkPasswords();

        // Se as senhas NÃO coincidirem
        if (!senhasCoincidem) {
            // Previne o envio do formulário
            e.preventDefault();

            // Lógica da animação
            errorSpan.classList.remove('shake-error');
            void errorSpan.offsetWidth; // Força o "repaint" da animação
            errorSpan.classList.add('shake-error');
        } else {
            // Se as senhas coincidirem, o formulário é enviado normalmente.
            // Não precisamos do form.submit() aqui, pois o comportamento padrão já faz isso.
        }
    });
});