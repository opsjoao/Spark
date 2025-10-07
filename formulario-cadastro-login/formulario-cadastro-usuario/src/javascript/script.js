document.addEventListener('DOMContentLoaded', () => {
    // Atalho para selecionar elementos por ID
    const $ = id => document.getElementById(id);

    // Seleção de todos os elementos
    const form = $('form');
    const cpfInput = $('cpf');
    const usernameInput = $('username');
    const emailInput = $('email');
    const passwordInput = $('password');
    const confirmPasswordInput = $('confirm_password');
    const passwordIcons = document.querySelectorAll('.password-icon');
    const submitButton = form.querySelector('button[type="submit"]');

    // Seleciona os spans de feedback
    const usernameFeedback = $('username-feedback');
    const cpfFeedback = $('cpf-feedback');
    const emailFeedback = $('email-feedback');
    const passwordFeedback = $('password-feedback');
    const passwordErrorSpan = $('password-error');

    // Variáveis de estado para rastrear a validade de cada campo
    let isUsernameValid = false;
    let isEmailValid = false;
    let isCpfValid = true; // Começa como true pois é opcional

    // ==========================================================
    // FUNÇÃO PRINCIPAL DE VALIDAÇÃO GERAL
    // ==========================================================
    function validateFormState() {
        let isFormValid = true;

        // 1. Verifica os campos obrigatórios
        form.querySelectorAll('input[required]').forEach(input => {
            if (input.value.trim() === '') {
                isFormValid = false;
            }
        });

        // 2. Verifica o status das validações em tempo real
        if (!isUsernameValid || !isEmailValid || !isCpfValid) {
            isFormValid = false;
        }

        // 3. Verifica se as senhas coincidem
        if (passwordInput.value !== confirmPasswordInput.value) {
            isFormValid = false;
        }

        // 4. Verifica se a senha é pelo menos 'média'
        if (passwordFeedback.classList.contains('fraca') || passwordFeedback.classList.contains('muito-fraca')) {
             isFormValid = false;
        }

        // Habilita ou desabilita o botão
        submitButton.disabled = !isFormValid;
    }


    // ==========================================================
    // FUNÇÃO GERAL DE VALIDAÇÃO DE CPF
    // ==========================================================
    function validaCPF(cpf) {
        cpf = String(cpf).replace(/\D/g, ''); // Garante que é uma string e remove a máscara
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) return false;
        
        let soma = 0;
        let resto;

        for (let i = 1; i <= 9; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(9, 10))) return false;

        soma = 0;
        for (let i = 1; i <= 10; i++) {
            soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }
        resto = (soma * 10) % 11;
        if (resto === 10 || resto === 11) resto = 0;
        if (resto !== parseInt(cpf.substring(10, 11))) return false;
        
        return true;
    }


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
    // LÓGICA UNIFICADA DE CPF (MÁSCARA E VALIDAÇÃO)
    // ==========================================================
    if (cpfInput && cpfFeedback) {
        cpfInput.addEventListener('input', e => {
            // 1. Aplica a máscara
            let value = e.target.value.replace(/\D/g, '').slice(0, 11);
            e.target.value = value.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            
            // 2. Faz a validação em tempo real
            const cpfLimpo = value;
            isCpfAvailable = false; // Assume que é inválido até que se prove o contrário

            if (cpfLimpo.length === 0) {
                cpfFeedback.textContent = '';
                cpfFeedback.className = 'feedback-message';
                isCpfAvailable = true; // Vazio é permitido (campo não obrigatório)
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
                validateFormState();
                return;
            }
            
            // 3. Se o formato for válido, verifica no banco
            fetch(`verificar_cpf.php?cpf=${cpfLimpo}`)
                .then(r => r.json())
                .then(data => {
                    isCpfAvailable = data.disponivel; // Atualiza o estado
                    cpfFeedback.textContent = data.disponivel ? '' : data.mensagem;
                    cpfFeedback.className = 'feedback-message ' + (data.disponivel ? 'disponivel' : 'indisponivel');
                    validateFormState();
                });
        });
    }
    // ==========================================================
    // 3. LÓGICA DE VERIFICAÇÃO DE FORÇA DA SENHA
    // ==========================================================
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
    
     // --- VERIFICAÇÕES EM TEMPO REAL ---

    // Adiciona um "ouvinte" geral a todos os inputs para revalidar
    form.addEventListener('input', validateFormState);

    // Verificação de Username
    let userTimer;
    if (usernameInput) {
        usernameInput.addEventListener('keyup', () => {
            clearTimeout(userTimer);
            const val = usernameInput.value;
            if (val.length < 3) {
                usernameFeedback.textContent = '';
                isUsernameValid = false; // Inválido se for muito curto
                validateFormState();
                return;
            }
            userTimer = setTimeout(() => {
                fetch(`verificar_username.php?username=${val}`).then(r => r.json()).then(d => {
                    isUsernameValid = d.disponivel; // Atualiza o estado
                    usernameFeedback.textContent = d.disponivel ? '' : d.mensagem;
                    usernameFeedback.className = 'feedback-message ' + (d.disponivel ? 'disponivel' : 'indisponivel');
                    validateFormState();
                });
            }, 500);
        });
    }

    // Verificação de E-mail
    let emailTimer;
    if (emailInput) {
        emailInput.addEventListener('keyup', () => {
            clearTimeout(emailTimer);
            const val = emailInput.value;
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!re.test(val)) {
                emailFeedback.textContent = 'Formato de e-mail inválido.';
                emailFeedback.className = 'feedback-message indisponivel';
                isEmailValid = false; // Inválido
                validateFormState();
                return;
            }
            
            emailTimer = setTimeout(() => {
                fetch(`verificar_email.php?email=${val}`).then(r => r.json()).then(d => {
                    isEmailValid = d.disponivel; // Atualiza o estado
                    emailFeedback.textContent = d.disponivel ? '' : d.mensagem;
                    emailFeedback.className = 'feedback-message ' + (d.disponivel ? 'disponivel' : 'indisponivel');
                    validateFormState();
                });
            }, 500);
        });
    }

    // Verificação de CPF
    if (cpfInput) {
        cpfInput.addEventListener('input', () => {
            const cpfLimpo = cpfInput.value.replace(/\D/g, '');
            if (cpfLimpo.length === 0) {
                cpfFeedback.textContent = '';
                isCpfValid = true; // Vazio é válido
                validateFormState();
                return;
            }
            if (cpfLimpo.length < 11) {
                cpfFeedback.textContent = '';
                isCpfValid = false; // Incompleto é inválido
                validateFormState();
                return;
            }
            if (!validaCPF(cpfLimpo)) {
                cpfFeedback.textContent = 'CPF inválido.';
                cpfFeedback.className = 'feedback-message indisponivel';
                isCpfValid = false; // Inválido
                validateFormState();
                return;
            }
            fetch(`verificar_cpf.php?cpf=${cpfLimpo}`).then(r => r.json()).then(d => {
                isCpfValid = d.disponivel; // Atualiza o estado
                cpfFeedback.textContent = d.disponivel ? '' : d.mensagem;
                cpfFeedback.className = 'feedback-message ' + (d.disponivel ? 'disponivel' : 'indisponivel');
                validateFormState();
            });
        });
    }

    // Verificação de Força da Senha
    if (passwordInput) { /* ... sua lógica de força da senha ... */ }

    // Verificação de Senhas que Coincidem
    function checkPasswords() {
        if (passwordInput.value !== confirmPasswordInput.value) {
            passwordErrorSpan.textContent = "As senhas não coincidem!";
            passwordErrorSpan.classList.add('indisponivel');
        } else {
            passwordErrorSpan.textContent = "";
            passwordErrorSpan.classList.remove('indisponivel');
        }
        validateFormState();
    }
    passwordInput?.addEventListener('keyup', checkPasswords);
    confirmPasswordInput?.addEventListener('keyup', checkPasswords);

    // Validação final ao enviar o formulário
    form?.addEventListener('submit', e => {
        validateFormState();
        if (submitButton.disabled) {
            e.preventDefault();
            alert('Por favor, corrija os erros no formulário antes de continuar.');
        }
    });

    // Chama a função no início para desabilitar o botão
    validateFormState();
});