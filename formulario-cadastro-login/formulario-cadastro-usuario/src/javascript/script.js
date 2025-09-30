// Aguarda o carregamento completo da página antes de executar qualquer script
document.addEventListener('DOMContentLoaded', function() {

    // ==========================================================
    // SELEÇÃO DE TODOS OS ELEMENTOS NO INÍCIO
    // ==========================================================
    const form = document.getElementById('form');
    const cpfInput = document.getElementById('cpf');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordIcons = document.querySelectorAll('.password-icon');
    
    // Seleciona os spans de feedback que JÁ EXISTEM no HTML
    const usernameFeedback = document.getElementById('username-feedback');
    const cpfFeedback = document.getElementById('cpf-feedback');
    const passwordFeedback = document.getElementById('password-feedback');
    const passwordErrorSpan = document.getElementById('password-error');


    // ==========================================================
    // 1. LÓGICA DE MOSTRAR/ESCONDER SENHA
    // ==========================================================
    passwordIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const input = this.closest('.input-field').querySelector('.form-control');
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

    // ==========================================================
    // 2. LÓGICA DA MÁSCARA DE CPF
    // ==========================================================
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
            if (value.length > 11) value = value.slice(0, 11);
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    }

    // ==========================================================
    // 3. LÓGICA DE VERIFICAÇÃO DE FORÇA DA SENHA
    // ==========================================================
    if (passwordInput && passwordFeedback) {
        passwordInput.addEventListener('input', function() {
            const senha = passwordInput.value;
            let forca = 0;
            let mensagem = '';
            let classeCss = '';

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
            passwordFeedback.className = 'password-feedback ' + classeCss;
        });
    }
    
    // ==========================================================
    // 4. LÓGICA DE VERIFICAÇÃO DE USERNAME EM TEMPO REAL
    // ==========================================================
    let debounceTimer;
    if (usernameInput && usernameFeedback) {
        usernameInput.addEventListener('keyup', function() {
            clearTimeout(debounceTimer);
            const username = usernameInput.value;

            if (username.length < 3) {
                usernameFeedback.textContent = '';
                usernameFeedback.className = 'feedback-message';
                return;
            }
            debounceTimer = setTimeout(() => {
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

    // ==========================================================
    // 5. FUNÇÕES DE VALIDAÇÃO (SENHA E CPF)
    // ==========================================================
    
    // Função que verifica se as senhas coincidem (estava faltando)
    function checkPasswords() {
        if (passwordInput.value !== confirmPasswordInput.value) {
            passwordErrorSpan.textContent = "As senhas não coincidem!";
            passwordErrorSpan.style.display = "block";
            return false;
        } else {
            passwordErrorSpan.textContent = "";
            passwordErrorSpan.style.display = "none";
            return true;
        }
    }
    
    // Adiciona a verificação em tempo real para o campo "Confirmar Senha"
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('keyup', checkPasswords);
    }

    // Função de validação de CPF (algoritmo padrão)
    function validaCPF(cpf) {
        cpf = cpf.replace(/\D/g, ''); // Remove máscara novamente por segurança
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

    // Adiciona verificação em tempo real para o CPF
    if (cpfInput && cpfFeedback) {
        // TROCADO 'blur' por 'input' para verificar enquanto digita
        cpfInput.addEventListener('input', function() {
            const cpf = cpfInput.value.replace(/\D/g, ''); // Remove a máscara

            // A verificação de validade só é feita quando o usuário digita os 11 números
            if (cpf.length === 11) {
                if (!validaCPF(cpf)) {
                    cpfFeedback.textContent = 'CPF inválido.';
                    cpfFeedback.className = 'feedback-message indisponivel';
                } else {
                    cpfFeedback.textContent = 'CPF válido!'; // Feedback positivo opcional
                    cpfFeedback.className = 'feedback-message disponivel';
                }
            } else {
                // Enquanto o usuário ainda não terminou de digitar, a mensagem fica limpa
                cpfFeedback.textContent = '';
                cpfFeedback.className = 'feedback-message';
            }
        });
    }

    // ==========================================================
    // 6. LÓGICA DE SUBMISSÃO DO FORMULÁRIO (VALIDAÇÃO FINAL)
    // ==========================================================
    if (form) {
        form.addEventListener('submit', function(e) {
            // Roda as validações uma última vez antes de enviar
            const senhasCoincidem = checkPasswords();
            const cpfPreenchido = cpfInput.value.length > 0;
            const cpfEhValido = cpfPreenchido ? validaCPF(cpfInput.value) : true; // Só valida se estiver preenchido

            // Se as senhas não coincidirem OU se o CPF foi preenchido mas é inválido
            if (!senhasCoincidem || !cpfEhValido) {
                e.preventDefault(); // Impede o envio do formulário

                // Animação de erro se as senhas não baterem
                if (!senhasCoincidem) {
                    passwordErrorSpan.classList.remove('shake-error');
                    void passwordErrorSpan.offsetWidth;
                    passwordErrorSpan.classList.add('shake-error');
                }
                
                // Mostra o erro do CPF se for o caso
                if (!cpfEhValido) {
                    cpfFeedback.textContent = 'CPF inválido.';
                    cpfFeedback.className = 'feedback-message indisponivel';
                }
            }
        });
    }
});