// Aguarda o carregamento completo da página antes de executar qualquer script
document.addEventListener('DOMContentLoaded', function() {

    // ==========================================================
    // SELEÇÃO DE TODOS OS ELEMENTOS NO INÍCIO
    // ==========================================================
    const form = document.getElementById('form');
    const cpfInput = document.getElementById('cpf');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordIcons = document.querySelectorAll('.password-icon');
    
    // Seleciona os spans de feedback que JÁ EXISTEM no HTML
    const usernameFeedback = document.getElementById('username-feedback');
    const cpfFeedback = document.getElementById('cpf-feedback');
    const emailFeedback = document.getElementById('email-feedback'); // Certifique-se que <span id="email-feedback"> existe no seu HTML
    const passwordFeedback = document.getElementById('password-feedback');
    const passwordErrorSpan = document.getElementById('password-error');


    // ==========================================================
    // FUNÇÃO GERAL DE VALIDAÇÃO DE CPF
    // ==========================================================
    function validaCPF(cpf) {
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
    // 3. VERIFICAÇÕES EM TEMPO REAL (USERNAME, CPF, E-MAIL, SENHA)
    // ==========================================================

    // Força da Senha
    if (passwordInput && passwordFeedback) {
        passwordInput.addEventListener('input', function() {
            const senha = passwordInput.value;
            let forca = 0, mensagem = '', classeCss = '';
            if (senha.length > 0) {
                if (senha.length >= 6) forca++;
                if (senha.match(/[a-z]/)) forca++;
                if (senha.match(/[A-Z]/)) forca++;
                if (senha.match(/[0-9]/)) forca++;
                if (senha.match(/[^a-zA-Z0-9]/)) forca++;
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
                            usernameFeedback.textContent = '';
                            usernameFeedback.className = 'feedback-message';
                        }
                    });
            }, 500);
        });
    }

    // Validade e Disponibilidade do E-mail
    let emailDebounceTimer;
    if (emailInput) { // Apenas verifica se o input de e-mail existe
        
        // Cria o span para o feedback uma única vez
        const emailFeedback = document.createElement('span');
        emailFeedback.classList.add('feedback-message');
        // Insere o span no local correto, dentro do .input-box
        emailInput.closest('.input-box').appendChild(emailFeedback);

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
                            emailFeedback.textContent = 'E-mail disponível!';
                            emailFeedback.className = 'feedback-message disponivel';
                        }
                    });
            }, 500);
        });
    }

     // --- LÓGICA DE VALIDAÇÃO DE CPF EM TEMPO REAL (CORRIGIDA) ---
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
            
            // Se o formato for válido, verifica no banco de dados se já está em uso
            fetch(`verificar_cpf.php?cpf=${cpfLimpo}`)
                .then(response => response.json())
                .then(data => {
                    if (data.disponivel) {
                        cpfFeedback.textContent = 'CPF disponível!';
                        cpfFeedback.className = 'feedback-message disponivel';
                    } else {
                        // A mensagem de erro agora vem diretamente do PHP
                        cpfFeedback.textContent = data.mensagem;
                        cpfFeedback.className = 'feedback-message indisponivel';
                    }
                })
                .catch(error => console.error('Erro ao verificar CPF:', error));
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
            }
        });
    }
});