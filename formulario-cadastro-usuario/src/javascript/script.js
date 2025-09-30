// Aguarda o carregamento completo da página
document.addEventListener('DOMContentLoaded', function() {

    // ===================================================================
    // 1. SELEÇÃO DE TODOS OS ELEMENTOS DO DOM (HTML)
    // ===================================================================
    const form = document.getElementById('form');
    const passwordIcons = document.querySelectorAll('.password-icon');
    const cpfInput = document.getElementById('cpf');
    const cpfError = document.getElementById('cpf-error');
    const senhaInput = document.getElementById('password');
    const csenhaInput = document.getElementById('confirm_password');
    const passwordErrorSpan = document.getElementById('password-error');
    const usernameInput = document.getElementById('username');
    const usernameFeedback = document.getElementById('username-feedback');

    // Cria o span para o feedback de força da senha (se não existir no HTML)
    const senhaFeedback = document.createElement('span');
    senhaFeedback.classList.add('password-feedback');
    senhaInput.closest('.input-box').appendChild(senhaFeedback);


    // ===================================================================
    // 2. FUNÇÕES GERAIS (VALIDAÇÃO, MÁSCARAS, ETC.)
    // ===================================================================

    // Função que valida o CPF
    function validaCPF(cpf) {
        cpf = cpf.replace(/[^\d]+/g, '');
        if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) {
            return false;
        }
        let soma = 0, resto;
        for (let i = 1; i <= 9; i++) soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        resto = (soma * 10) % 11;
        if ((resto === 10) || (resto === 11)) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;
        soma = 0;
        for (let i = 1; i <= 10; i++) soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        resto = (soma * 10) % 11;
        if ((resto === 10) || (resto === 11)) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) return false;
        return true;
    }

    // Função para formatar o CPF com a máscara
    function mascaraCPF(input) {
        let value = input.value.replace(/\D/g, '').substring(0, 11);
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d)/, '$1.$2');
        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        input.value = value;
    }

    // Função que verifica se as senhas coincidem
    function checkPasswords() {
        if (csenhaInput.value && csenhaInput.value !== senhaInput.value) {
            passwordErrorSpan.textContent = "As senhas não coincidem!";
            passwordErrorSpan.style.display = "block";
            return false;
        } else {
            passwordErrorSpan.textContent = "";
            passwordErrorSpan.style.display = "none";
            return true;
        }
    }


    // ===================================================================
    // 3. EVENT LISTENERS (AÇÕES DO USUÁRIO)
    // ===================================================================

    // --- Funcionalidade: Mostrar / Ocultar Senha ---
    passwordIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
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

    // --- Funcionalidade: Validação de CPF em tempo real ---
    cpfInput.addEventListener('input', () => {
        mascaraCPF(cpfInput);
        const cpf = cpfInput.value;
        if (cpf.length === 14) {
            if (validaCPF(cpf)) {
                cpfError.style.display = 'none';
                cpfInput.style.borderColor = 'green';
            } else {
                cpfError.textContent = 'CPF inválido'; // Adiciona texto ao span
                cpfError.style.display = 'block';
                cpfInput.style.borderColor = 'red';
            }
        } else {
            cpfError.style.display = 'none';
            cpfInput.style.borderColor = '';
        }
    });

    // --- Funcionalidade: Verificação de senhas que coincidem ---
    senhaInput.addEventListener('input', checkPasswords);
    csenhaInput.addEventListener('input', checkPasswords);

    // --- Funcionalidade: Verificação de força da senha ---
    senhaInput.addEventListener('input', function() {
        const senha = senhaInput.value;
        let forca = 0;
        let mensagem = '';
        let classeCss = '';

        if (senha.length > 0) {
            if (senha.length >= 8) forca++;
            if (senha.match(/[a-z]/)) forca++;
            if (senha.match(/[A-Z]/)) forca++;
            if (senha.match(/[0-9]/)) forca++;
            if (senha.match(/[^a-zA-Z0-9]/)) forca++;

            switch (forca) {
                case 1: case 2:
                    mensagem = 'Senha fraca';
                    classeCss = 'fraca';
                    break;
                case 3: case 4:
                    mensagem = 'Senha média';
                    classeCss = 'media';
                    break;
                case 5:
                    mensagem = 'Senha forte';
                    classeCss = 'forte';
                    break;
                default:
                    mensagem = 'Senha muito fraca';
                    classeCss = 'fraca';
            }
        }
        senhaFeedback.textContent = mensagem;
        senhaFeedback.className = 'password-feedback ' + classeCss;
    });

    // --- Funcionalidade: Verificação de username disponível ---
    let debounceTimer;
    usernameInput.addEventListener('keyup', function() {
        clearTimeout(debounceTimer);
        const username = usernameInput.value;
        if (username.length < 3) {
            usernameFeedback.textContent = '';
            usernameFeedback.className = 'username-feedback';
            return;
        }
        debounceTimer = setTimeout(() => {
            fetch(`verificar_username.php?username=${username}`)
                .then(response => response.json())
                .then(data => {
                    usernameFeedback.textContent = data.mensagem;
                    usernameFeedback.className = 'username-feedback ';
                    usernameFeedback.classList.add(data.disponivel ? 'disponivel' : 'indisponivel');
                });
        }, 500);
    });

    // --- Funcionalidade: Validação final ao enviar o formulário ---
    form.addEventListener('submit', function(e) {
        const senhasCoincidem = checkPasswords();
        const cpfPreenchido = cpfInput.value.length > 0;
        const cpfEhValido = validaCPF(cpfInput.value);

        // Se senhas não coincidem OU se o CPF foi preenchido mas é inválido
        if (!senhasCoincidem || (cpfPreenchido && !cpfEhValido)) {
            e.preventDefault(); // Impede o envio do formulário

            if (!senhasCoincidem) {
                alert('As senhas não coincidem. Por favor, verifique.');
            } else if (cpfPreenchido && !cpfEhValido) {
                alert('O CPF digitado é inválido. Por favor, corrija antes de continuar.');
                cpfError.style.display = 'block';
                cpfInput.style.borderColor = 'red';
            }
        }
    });

});