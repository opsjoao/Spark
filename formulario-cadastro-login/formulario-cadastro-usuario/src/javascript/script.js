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
          submitButton = form.querySelector('button[type="submit"]'), // Seleciona o botão de criar conta
          feedbackSpans = document.querySelectorAll('.feedback-message'); // Pega TODOS os spans de feedback

    // ==========================================================
    // FUNÇÃO PRINCIPAL DE VALIDAÇÃO GERAL
    // ==========================================================
    function validateFormState() {
        let isFormValid = true;

        // 1. Verifica se algum span de feedback de erro está ativo
        feedbackSpans.forEach(span => {
            if (span.classList.contains('indisponivel') || span.classList.contains('fraca') || span.classList.contains('media')) {
                isFormValid = false;
            }
        });
        
        // Verifica o span de erro de senhas que não coincidem
        if (passwordErrorSpan && passwordErrorSpan.style.display === 'block') {
            isFormValid = false;
        }

        // 2. Verifica se os campos obrigatórios estão preenchidos
        const requiredInputs = form.querySelectorAll('input[required]');
        requiredInputs.forEach(input => {
            if (input.value.trim() === '') {
                isFormValid = false;
            }
        });
        
        // 3. Habilita ou desabilita o botão com base na validade
        submitButton.disabled = !isFormValid;
    }

    // --- FUNÇÃO DE VALIDAÇÃO DE CPF ---
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

    // --- LÓGICAS DE VALIDAÇÃO EM TEMPO REAL ---

    // Adiciona um "ouvinte" a todos os inputs do formulário para validar continuamente
    form.addEventListener('input', validateFormState);


    // ==========================================================
    // 1. LÓGICA DE MOSTRAR/ESCONDER SENHA
    // ==========================================================
    passwordIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const input = this.closest('.input-field').querySelector('.form-control');
            if (input.type === 'password') {
                input.type = 'text';
                this.classList.remove('fa-eye-slash'); this.classList.add('fa-eye');
            } else {
                input.type = 'password';
                this.classList.remove('fa-eye'); this.classList.add('fa-eye-slash');
            }
        });
    });


    // ==========================================================
    // 2. LÓGICA DA MÁSCARA DE CPF
    // ==========================================================
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    }


    // ==========================================================
    // 3. VERIFICAÇÕES EM TEMPO REAL (CPF, USERNAME, E-MAIL, SENHA)
    // ==========================================================

    // Validade e Disponibilidade do CPF
    if (cpfInput && cpfFeedback) {
        cpfInput.addEventListener('input', function() {
            const cpfLimpo = cpfInput.value.replace(/\D/g, '');
            if (cpfLimpo.length < 11) {
                cpfFeedback.textContent = '';
                cpfFeedback.className = 'feedback-message';
                return;
            }
            if (!validaCPF(cpfLimpo)) {
                cpfFeedback.textContent = 'CPF inválido.';
                cpfFeedback.className = 'feedback-message indisponivel';
                return;
            }
            fetch(`verificar_cpf.php?cpf=${cpfLimpo}`)
                .then(response => response.json())
                .then(data => {
                    cpfFeedback.textContent = data.disponivel ? 'CPF disponível!' : data.mensagem;
                    cpfFeedback.className = data.disponivel ? 'feedback-message disponivel' : 'feedback-message indisponivel';
                });
        });
    }

    // Disponibilidade do Username
    let usernameDebounceTimer;
    if (usernameInput && usernameFeedback) {
        usernameInput.addEventListener('keyup', function() {
            clearTimeout(usernameDebounceTimer);
            const username = usernameInput.value;
            if (username.length < 3) {
                usernameFeedback.textContent = '';
                usernameFeedback.className = 'feedback-message';
                return;
            }
            usernameDebounceTimer = setTimeout(() => {
                fetch(`verificar_username.php?username=${username}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.disponivel) {
                            usernameFeedback.textContent = data.mensagem;
                            usernameFeedback.className = 'feedback-message indisponivel';
                        } else {
                            usernameFeedback.textContent = ''; // Não mostra "disponível"
                            usernameFeedback.className = 'feedback-message';
                        }
                    });
            }, 500);
        });
    }
    
    // Validade e Disponibilidade do E-mail
    let emailDebounceTimer;
    if (emailInput && emailFeedback) {
        emailInput.addEventListener('keyup', function() {
            clearTimeout(emailDebounceTimer);
            const email = emailInput.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email.length > 0 && !emailRegex.test(email)) {
                emailFeedback.textContent = 'Formato de e-mail inválido.';
                emailFeedback.className = 'feedback-message indisponivel';
                return;
            } else if (email.length === 0) {
                emailFeedback.textContent = '';
                emailFeedback.className = 'feedback-message';
                return;
            }

            emailDebounceTimer = setTimeout(() => {
                fetch(`verificar_email.php?email=${email}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.disponivel) {
                            emailFeedback.textContent = data.mensagem;
                            emailFeedback.className = 'feedback-message indisponivel';
                        } else {
                            emailFeedback.textContent = ''; // Não mostra "disponível"
                            emailFeedback.className = 'feedback-message';
                        }
                    });
            }, 500);
        });
    }

    // Força da Senha
    if (passwordInput && passwordFeedback) {
        passwordInput.addEventListener('input', function() {
            const senha = passwordInput.value;
            let forca = 0, mensagem = '', classeCss = '';
            if (senha.length > 0) {
                if (senha.length >= 6) forca++;
                if (/[a-z]/.test(senha)) forca++;
                if (/[A-Z]/.test(senha)) forca++;
                if (/[0-9]/.test(senha)) forca++;
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

    // ==========================================================
    // 4. LÓGICA DE SUBMISSÃO DO FORMULÁRIO (VALIDAÇÃO FINAL)
    // ==========================================================
    if (form) {
        form.addEventListener('submit', function(e) {
            const senhasCoincidem = (passwordInput.value === confirmPasswordInput.value);
            
            if (!senhasCoincidem) {
                e.preventDefault(); // Impede o envio do formulário
                
                passwordErrorSpan.textContent = "As senhas não coincidem!";
                passwordErrorSpan.style.display = "block";
                
                // Animação de erro
                passwordErrorSpan.classList.remove('shake-error');
                void passwordErrorSpan.offsetWidth;
                passwordErrorSpan.classList.add('shake-error');
            } else {
                passwordErrorSpan.textContent = '';
                passwordErrorSpan.style.display = 'none';
            }
        });
    }
});