document.addEventListener('DOMContentLoaded', function() {

    // --- SELEÇÃO DE ELEMENTOS ---
    const form = document.getElementById('form');
    const cnpjInput = document.getElementById('cnpj');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordIcons = document.querySelectorAll('.password-icon');
    
    // Função auxiliar para criar spans de feedback dinamicamente
    function getOrCreateFeedbackSpan(inputId, className) {
        let feedbackSpan = document.getElementById(inputId + '-feedback');
        if (!feedbackSpan) {
            const inputElement = document.getElementById(inputId);
            feedbackSpan = document.createElement('span');
            feedbackSpan.id = inputId + '-feedback';
            feedbackSpan.classList.add(className);
            inputElement.closest('.input-box').appendChild(feedbackSpan);
        }
        return feedbackSpan;
    }

    // --- 1. LÓGICA DE MOSTRAR/ESCONDER SENHA ---
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

    // --- 2. LÓGICA DA MÁSCARA DE CNPJ ---
    if (cnpjInput) {
        cnpjInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 14) value = value.slice(0, 14);
            value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        });
    }

    // --- 3. VERIFICAÇÃO DE FORÇA DA SENHA ---
    if (passwordInput) {
        const passwordFeedback = getOrCreateFeedbackSpan('password', 'feedback-message');
        passwordInput.addEventListener('input', function() {
            // ... (A lógica de verificação de força da senha continua a mesma)
        });
    }
    
    // --- 4. VERIFICAÇÃO DE USERNAME EM TEMPO REAL ---
    let usernameDebounceTimer;
    if (usernameInput) {
        const usernameFeedback = getOrCreateFeedbackSpan('username', 'feedback-message');
        usernameInput.addEventListener('keyup', function() {
            clearTimeout(usernameDebounceTimer);
            const username = usernameInput.value;
            // ... (Lógica de fetch para verificar username continua a mesma)
        });
    }

    // --- 5. VERIFICAÇÃO DE E-MAIL EM TEMPO REAL ---
    let emailDebounceTimer;
    if (emailInput) {
        const emailFeedback = getOrCreateFeedbackSpan('email', 'feedback-message');
        emailInput.addEventListener('keyup', function() {
             clearTimeout(emailDebounceTimer);
             const email = emailInput.value;
             // ... (Lógica de fetch para verificar e-mail continua a mesma)
        });
    }

    // --- 6. FUNÇÕES DE VALIDAÇÃO (SENHA E CNPJ) ---
    function checkPasswords() { /* ... */ }
    if (confirmPasswordInput) { /* ... */ }
    function validaCNPJ(cnpj) { /* ... */ }

    if (cnpjInput) {
        const cnpjFeedback = getOrCreateFeedbackSpan('cnpj', 'feedback-message');
        cnpjInput.addEventListener('blur', function() {
            const cnpj = cnpjInput.value;
            if (cnpj.length > 0 && !validaCNPJ(cnpj)) {
                cnpjFeedback.textContent = 'CNPJ inválido.';
                cnpjFeedback.className = 'feedback-message indisponivel';
            } else {
                cnpjFeedback.textContent = '';
                cnpjFeedback.className = 'feedback-message';
            }
        });
    }
    // --- 7. LÓGICA DE SUBMISSÃO DO FORMULÁRIO ---
    if (form) {
    form.addEventListener('submit', function(e) {
        const senhasCoincidem = checkPasswords();
        const cnpjPreenchido = cnpjInput.value.length > 0;
        // Só valida o CNPJ se ele tiver sido preenchido
        const cnpjEhValido = !cnpjPreenchido || (cnpjPreenchido && validaCNPJ(cnpjInput.value));

            if (!senhasCoincidem || !cnpjEhValido) {
                e.preventDefault(); 
                if (!senhasCoincidem) {
                     passwordErrorSpan.classList.remove('shake-error');
                     void passwordErrorSpan.offsetWidth;
                     passwordErrorSpan.classList.add('shake-error');
                }
                if (!cnpjEhValido) {
                    cnpjFeedback.textContent = 'CNPJ inválido.';
                    cnpjFeedback.className = 'feedback-message indisponivel';
                }
            }
        });
    }
});