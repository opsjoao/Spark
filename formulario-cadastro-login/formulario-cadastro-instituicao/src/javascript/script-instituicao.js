document.addEventListener('DOMContentLoaded', function() {

    // --- SELEÇÃO DE ELEMENTOS ---
    const form = document.getElementById('form');
    const cnpjInput = document.getElementById('cnpj');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordIcons = document.querySelectorAll('.password-icon');
    const submitButton = form.querySelector('button[type="submit"]');

    // Função auxiliar para criar/selecionar spans de feedback
    function getOrCreateFeedbackSpan(inputId, className) {
        let feedbackSpan = document.getElementById(inputId + '-feedback');
        if (!feedbackSpan) {
            const inputElement = document.getElementById(inputId);
            if (inputElement) {
                feedbackSpan = document.createElement('span');
                feedbackSpan.id = inputId + '-feedback';
                feedbackSpan.classList.add(className);
                inputElement.closest('.input-box').appendChild(feedbackSpan);
            }
        }
        return feedbackSpan;
    }

    // Seleciona ou cria os spans de feedback
    const usernameFeedback = getOrCreateFeedbackSpan('username', 'feedback-message');
    const cnpjFeedback = getOrCreateFeedbackSpan('cnpj', 'feedback-message');
    const emailFeedback = getOrCreateFeedbackSpan('email', 'feedback-message');
    const passwordFeedback = getOrCreateFeedbackSpan('password', 'feedback-message');
    const passwordErrorSpan = getOrCreateFeedbackSpan('confirm_password', 'feedback-message');
    passwordErrorSpan.style.textAlign = 'left'; // Alinha o erro de senha

    // Variáveis de estado para validação
    let isUsernameAvailable = false;
    let isEmailAvailable = false;
    let isCnpjAvailable = true; // Começa como true (válido), pois é opcional

    // ==========================================================
    // FUNÇÃO PRINCIPAL DE VALIDAÇÃO GERAL
    // ==========================================================
    function validateFormState() {
        if (!submitButton) return;
        let isFormValid = true;

        form.querySelectorAll('input[required]').forEach(input => {
            if (input.value.trim() === '') isFormValid = false;
        });
        if (!isUsernameAvailable || !isEmailAvailable || !isCnpjAvailable) {
            isFormValid = false;
        }
        if (passwordInput.value !== confirmPasswordInput.value) {
            isFormValid = false;
        }
        if (passwordFeedback && (passwordFeedback.classList.contains('fraca') || passwordFeedback.classList.contains('media'))) {
             isFormValid = false;
        }
        submitButton.disabled = !isFormValid;
    }

    // ==========================================================
    // FUNÇÃO DE VALIDAÇÃO DE CNPJ (ALGORITMO COMPLETO)
    // ==========================================================
    function validaCNPJ(cnpj) {
        cnpj = String(cnpj).replace(/[^\d]+/g,'');
        if(cnpj == '' || cnpj.length != 14 || /^(\d)\1+$/.test(cnpj)) return false;
        
        let tamanho = cnpj.length - 2;
        let numeros = cnpj.substring(0,tamanho);
        let digitos = cnpj.substring(tamanho);
        let soma = 0;
        let pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) {
            soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
            if (pos < 2) pos = 9;
        }
        let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != parseInt(digitos.charAt(0))) return false;
        
        tamanho = tamanho + 1;
        numeros = cnpj.substring(0,tamanho);
        soma = 0;
        pos = tamanho - 7;
        for (let i = tamanho; i >= 1; i--) {
            soma += parseInt(numeros.charAt(tamanho - i)) * pos--;
            if (pos < 2) pos = 9;
        }
        resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
        if (resultado != parseInt(digitos.charAt(1))) return false;
        
        return true;
    }
    
    // ==========================================================
    // FUNÇÃO QUE VERIFICA SENHAS (COMPLETA)
    // ==========================================================
    function checkPasswords() {
        if (passwordInput.value !== confirmPasswordInput.value) {
            passwordErrorSpan.textContent = "As senhas não coincidem!";
            passwordErrorSpan.className = "feedback-message indisponivel";
            return false;
        } else {
            passwordErrorSpan.textContent = "";
            passwordErrorSpan.className = "feedback-message";
            return true;
        }
    }


    // --- LÓGICA DOS EVENTOS ---

    form?.addEventListener('input', validateFormState);

    // 1. Mostrar/Esconder Senha
    passwordIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const input = this.closest('.input-field').querySelector('.form-control');
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('fa-eye', isPassword);
            icon.classList.toggle('fa-eye-slash', !isPassword);
        });
    });

    // --- 2. Máscara de CNPJ e Validação (CORRIGIDO) ---
    if (cnpjInput && cnpjFeedback) {
    cnpjInput.addEventListener('input', e => {
        // Aplica a máscara (seu código existente)
        let value = e.target.value.replace(/\D/g, '').slice(0, 14);
        e.target.value = value.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/\.(\d{3})(\d)/, '.$1/$2').replace(/(\d{4})(\d)/, '$1-$2');
        
        const cnpjLimpo = value;
        isCnpjAvailable = false; // Assume que é inválido até que a validação seja concluída

        // --- LÓGICA DE CORREÇÃO AQUI ---
        // Se o campo não tiver 14 dígitos, ele está "em digitação" ou "opcional/vazio".
        // Em ambos os casos, não deve mostrar erro.
        if (cnpjLimpo.length < 14) {
            cnpjFeedback.textContent = ''; // Limpa qualquer mensagem de erro anterior
            cnpjFeedback.className = 'feedback-message';
            
            // O campo só é "válido" (para o botão) se estiver vazio, já que é opcional.
            isCnpjAvailable = (cnpjLimpo.length === 0); 
            validateFormState();
            return; // Para a execução aqui.
        }
        
        // --- SE CHEGOU AQUI, TEM 14 DÍGITOS ---
        
        // 1. Valida o formato
        if (!validaCNPJ(cnpjLimpo)) {
            cnpjFeedback.textContent = 'CNPJ inválido.';
            cnpjFeedback.className = 'feedback-message indisponivel';
            validateFormState();
            return;
        }
        
        // 2. Se o formato for válido, verifica no banco
        fetch(`verificar_cnpj.php?cnpj=${cnpjLimpo}`).then(r => r.json()).then(data => {
            isCnpjAvailable = data.disponivel;
            cnpjFeedback.textContent = data.disponivel ? '' : data.mensagem; // Não mostra "disponível"
            cnpjFeedback.className = 'feedback-message ' + (data.disponivel ? 'disponivel' : 'indisponivel');
            validateFormState();
        });
    });
}

    // 3. ==========================================================
    // VERIFICAÇÃO DE FORÇA DA SENHA (MENSAGENS ATUALIZADAS)
    // ==========================================================
    if (passwordInput && passwordFeedback) {
        passwordInput.addEventListener('input', () => {
            const senha = passwordInput.value;
            let forca = 0, mensagem = '', classeCss = '';
            if (senha.length > 0) {
                if (senha.length >= 6) forca++; if (/[a-z]/.test(senha)) forca++;
                if (/[A-Z]/.test(senha)) forca++; if (/\d/.test(senha)) forca++;
                if (/[^a-zA-Z0-9]/.test(senha)) forca++;
                
                // Mensagens atualizadas para incluir o aviso
                switch (forca) {
                    case 1: case 2:
                        mensagem = 'Senha fraca. É necessária uma senha forte.';
                        classeCss = 'fraca'; // Classe de erro
                        break;
                    case 3: case 4:
                        mensagem = 'Senha média. Para se cadastrar, crie uma senha forte.';
                        classeCss = 'media'; // Classe de aviso/erro
                        break;
                    case 5:
                        mensagem = 'Senha forte!';
                        classeCss = 'forte'; // Classe de sucesso
                        break;
                    default:
                        mensagem = 'Senha muito fraca. É necessária uma senha forte.';
                        classeCss = 'fraca';
                }
            }
            passwordFeedback.textContent = mensagem;
            passwordFeedback.className = 'feedback-message ' + classeCss;
        });
    }
    
    // 4. Verificação de Username
    let userTimer;
    if (usernameInput && usernameFeedback) {
        usernameInput.addEventListener('keyup', () => {
            clearTimeout(userTimer);
            const val = usernameInput.value;
            isUsernameAvailable = false;
            if (val.length < 3) { usernameFeedback.textContent = ''; validateFormState(); return; }
            userTimer = setTimeout(() => {
                fetch(`../formulario-cadastro-usuario/verificar_username.php?username=${val}`).then(r => r.json()).then(d => {
                    isUsernameAvailable = d.disponivel;
                    usernameFeedback.textContent = d.disponivel ? '' : d.mensagem;
                    usernameFeedback.className = 'feedback-message' + (d.disponivel ? ' disponivel' : ' indisponivel');
                    validateFormState();
                });
            }, 500);
        });
    }

    // 5. Verificação de E-mail
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
                fetch(`../formulario-cadastro-usuario/verificar_email.php?email=${val}`).then(r => r.json()).then(d => {
                    isEmailAvailable = d.disponivel;
                    emailFeedback.textContent = d.disponivel ? '' : d.mensagem;
                    emailFeedback.className = 'feedback-message ' + (d.disponivel ? ' disponivel' : ' indisponivel');
                    validateFormState();
                });
            }, 500);
        });
    }

    // 6. Verificação de Senhas que Coincidem
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('keyup', checkPasswords);
    }
    
    // 7. Validação Final ao Enviar o Formulário
    form?.addEventListener('submit', e => {
        validateFormState(); // Roda uma última vez
        if (submitButton.disabled) {
            e.preventDefault();
            alert('Por favor, corrija os erros ou preencha todos os campos obrigatórios.');
            
            // Força a exibição de erros que só aparecem no submit
            checkPasswords();
            if (cnpjInput.value.length > 0 && !validaCNPJ(cnpjInput.value)) {
                cnpjFeedback.textContent = 'CNPJ inválido.';
                cnpjFeedback.className = 'feedback-message indisponivel';
            }
        }
    });

    // Chama a função no início para desabilitar o botão
    validateFormState();
});