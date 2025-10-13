document.addEventListener('DOMContentLoaded', () => {
    // Atalho para selecionar elementos por ID
    const $ = id => document.getElementById(id);

    // Seleção de todos os elementos
    const form = $('form'),
          cpfInput = $('cpf'),
          usernameInput = $('username'),
          emailInput = $('email'),
          passwordInput = $('password'),
          confirmPasswordInput = $('confirm_password'),
          passwordIcons = document.querySelectorAll('.password-icon'),
          submitButton = form.querySelector('button[type="submit"]');

    // Seleciona os spans de feedback
    const usernameFeedback = $('username-feedback'),
          cpfFeedback = $('cpf-feedback'),
          emailFeedback = $('email-feedback'),
          passwordFeedback = $('password-feedback'),
          passwordErrorSpan = $('password-error');

    // Variáveis de estado para rastrear a validade
    let isUsernameAvailable = false,
        isEmailAvailable = false,
        isCpfAvailable = true; // Começa como true, pois o campo pode estar vazio

    // ==========================================================
    // FUNÇÃO PRINCIPAL DE VALIDAÇÃO GERAL
    // ==========================================================
    function validateFormState() {
        if (!submitButton) return;

        let isFormValid = true;

        // 1. Verifica se os campos obrigatórios estão preenchidos
        form.querySelectorAll('input[required]').forEach(input => {
            if (input.value.trim() === '') {
                isFormValid = false;
            }
        });

        // 2. Verifica o resultado das validações assíncronas
        if (!isUsernameAvailable || !isEmailAvailable || !isCpfAvailable) {
            isFormValid = false;
        }

        // 3. Verifica se as senhas coincidem e se a senha tem força mínima
        if (passwordInput.value !== confirmPasswordInput.value || 
            (passwordFeedback && (passwordFeedback.classList.contains('fraca') || passwordFeedback.classList.contains('media')))
           ) {
            isFormValid = false;
        }
        
        // Habilita ou desabilita o botão
        submitButton.disabled = !isFormValid;
    }

    // ==========================================================
    // FUNÇÃO DE VALIDAÇÃO DE CPF (ALGORITMO)
    // ==========================================================
    const validaCPF = cpf => {
        cpf = String(cpf).replace(/\D/g, '');
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
        let soma = 0, resto;
        for (let i = 1; i <= 9; i++) soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;
        soma = 0;
        for (let i = 1; i <= 10; i++) soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) return false;
        return true;
    };

    // --- LÓGICA DOS EVENTOS ---

    // Adiciona um "ouvinte" geral ao formulário para revalidar a cada digitação
    form?.addEventListener('input', validateFormState);

    // Mostrar/Esconder Senha
    passwordIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const input = this.closest('.input-field').querySelector('.form-control');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', isPassword);
            icon.classList.toggle('fa-eye-slash', !isPassword);
        });
    });

    // Máscara de CPF
    cpfInput?.addEventListener('input', e => {
        let value = e.target.value.replace(/\D/g, '').slice(0, 11);
        e.target.value = value.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    });
    
    // --- VERIFICAÇÕES EM TEMPO REAL ---

    // Verificação de CPF
    if (cpfInput && cpfFeedback) {
        cpfInput.addEventListener('input', () => {
            const cpfLimpo = cpfInput.value.replace(/\D/g, '');
            isCpfAvailable = false; // Invalida enquanto digita

            if (cpfLimpo.length === 0) {
                cpfFeedback.textContent = '';
                cpfFeedback.className = 'feedback-message';
                isCpfAvailable = true; // Vazio é permitido
                validateFormState();
                return;
            }
            if (cpfLimpo.length < 11) {
                cpfFeedback.textContent = 'CPF incompleto.';
                cpfFeedback.className = 'feedback-message indisponivel';
                validateFormState();
                return;
            }
            if (!validaCPF(cpfLimpo)) {
                cpfFeedback.textContent = 'CPF inválido.';
                cpfFeedback.className = 'feedback-message indisponivel';
                isCpfAvailable = false;
                validateFormState();
                return;
            }
            fetch(`verificar_cpf.php?cpf=${cpfLimpo}`).then(r => r.json()).then(d => {
                isCpfAvailable = d.disponivel;
                cpfFeedback.textContent = d.disponivel ? 'CPF disponível!' : d.mensagem;
                cpfFeedback.className = 'feedback-message ' + (d.disponivel ? 'disponivel' : 'indisponivel');
                validateFormState();
            });
        });
    }

    // Verificação de Força da Senha
    if (passwordInput && passwordFeedback) {
        passwordInput.addEventListener('input', () => {
            const senha = passwordInput.value;
            let forca = 0, mensagem = '', classeCss = '';
            if (senha.length > 0) {
                if (senha.length >= 6) forca++; if (/[a-z]/.test(senha)) forca++;
                if (/[A-Z]/.test(senha)) forca++; if (/\d/.test(senha)) forca++;
                if (/[^a-zA-Z0-9]/.test(senha)) forca++;
                switch (forca) {
                    case 1: case 2: mensagem = 'Senha fraca'; classeCss = 'fraca'; break;
                    case 3: case 4: mensagem = 'Senha média'; classeCss = 'media'; break;
                    case 5: mensagem = 'Senha forte'; classeCss = 'forte'; break;
                    default: mensagem = 'Senha muito fraca'; classeCss = 'fraca';
                }
            }
            passwordFeedback.textContent = mensagem;
            passwordFeedback.className = 'feedback-message ' + classeCss;
        });
    }
    
    // Verificação de Username
    let userTimer;
    if (usernameInput && usernameFeedback) {
        usernameInput.addEventListener('keyup', () => {
            clearTimeout(userTimer);
            const val = usernameInput.value;
            isUsernameAvailable = false;
            if (val.length < 3) { usernameFeedback.textContent = ''; validateFormState(); return; }
            userTimer = setTimeout(() => {
                fetch(`verificar_username.php?username=${val}`).then(r => r.json()).then(d => {
                    isUsernameAvailable = d.disponivel;
                    usernameFeedback.textContent = d.disponivel ? '' : d.mensagem;
                    usernameFeedback.className = 'feedback-message' + (d.disponivel ? ' disponivel' : ' indisponivel');
                    validateFormState();
                });
            }, 500);
        });
    }

    // Verificação de E-mail
    let emailTimer;
    if (emailInput && emailFeedback) {
        emailInput.addEventListener('keyup', () => {
            clearTimeout(emailTimer);
            const val = emailInput.value, re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            isEmailAvailable = false;
            if (val && !re.test(val)) {
                emailFeedback.textContent = 'Formato de e-mail inválido.';
                emailFeedback.className = 'feedback-message indisponivel';
                validateFormState();
                return;
            }
            if (!val) { emailFeedback.textContent = ''; validateFormState(); return; }
            emailTimer = setTimeout(() => {
                fetch(`verificar_email.php?email=${val}`).then(r => r.json()).then(d => {
                    isEmailAvailable = d.disponivel;
                    emailFeedback.textContent = d.disponivel ? '' : d.mensagem;
                    emailFeedback.className = 'feedback-message ' + (d.disponivel ? ' disponivel' : ' indisponivel');
                    validateFormState();
                });
            }, 500);
        });
    }

    // Verificação de Senhas que Coincidem
    function checkPasswords() {
        if (passwordInput.value !== confirmPasswordInput.value) {
            passwordErrorSpan.textContent = "As senhas não coincidem!";
            passwordErrorSpan.classList.add('indisponivel');
        } else {
            passwordErrorSpan.textContent = "";
            passwordErrorSpan.classList.remove('indisponivel');
        }
    }
    passwordInput?.addEventListener('keyup', checkPasswords);
    confirmPasswordInput?.addEventListener('keyup', checkPasswords);

    // Validação final ao enviar o formulário
    form?.addEventListener('submit', e => {
        validateFormState();
        if (submitButton.disabled) {
            e.preventDefault();
            alert('Por favor, corrija os erros ou preencha todos os campos obrigatórios.');
        }
    });

    // Chama a função no início para desabilitar o botão
    validateFormState();
});