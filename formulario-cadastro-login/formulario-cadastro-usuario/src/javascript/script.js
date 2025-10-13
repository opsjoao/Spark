document.addEventListener('DOMContentLoaded', () => {
    // Seleciona todos os elementos do formulário
    const form = document.getElementById('form');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const cpfInput = document.getElementById('cpf');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const submitButton = form.querySelector('button[type="submit"]');

    // Função para mostrar/esconder feedback
    const setFeedback = (elementId, message, isError) => {
        const feedbackEl = document.getElementById(elementId);
        if (feedbackEl) {
            feedbackEl.textContent = message;
            feedbackEl.className = isError ? 'feedback-message indisponivel' : 'feedback-message disponivel';
        }
    };

    // Objeto para rastrear a validade de cada campo
    const validationStatus = {
        username: false,
        email: false,
        cpf: true, // Opcional, então começa como válido
        passwordStrength: false,
        passwordsMatch: false
    };

    // Função principal que habilita/desabilita o botão
    const validateFormState = () => {
        const allRequiredFilled = [...form.querySelectorAll('input[required]')].every(input => input.value.trim() !== '');
        const allValid = Object.values(validationStatus).every(status => status === true);
        submitButton.disabled = !allRequiredFilled || !allValid;
    };

    // Função de validação de formato de CPF
    const validaCPF = (cpf) => { /* Seu algoritmo de validação de CPF aqui */ return true; };
    
    // --- EVENT LISTENERS ---

    // Validação de USERNAME
    usernameInput.addEventListener('keyup', () => {
        const username = usernameInput.value;
        validationStatus.username = false;
        if (username.length < 3) {
            setFeedback('username-feedback', 'Mínimo 3 caracteres.', true);
            return validateFormState();
        }
        fetch(`verificar_username.php?username=${username}`).then(r => r.json()).then(data => {
            validationStatus.username = data.disponivel;
            setFeedback('username-feedback', data.disponivel ? '' : data.mensagem, !data.disponivel);
            validateFormState();
        });
    });

    // Validação de E-MAIL
    emailInput.addEventListener('keyup', () => {
        const email = emailInput.value;
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        validationStatus.email = false;
        if (!re.test(email)) {
            setFeedback('email-feedback', 'Formato de e-mail inválido.', true);
            return validateFormState();
        }
        fetch(`verificar_email.php?email=${email}`).then(r => r.json()).then(data => {
            validationStatus.email = data.disponivel;
            setFeedback('email-feedback', data.disponivel ? '' : data.mensagem, !data.disponivel);
            validateFormState();
        });
    });

    // Validação de CPF
    cpfInput.addEventListener('input', (e) => {
        let value = e.target.value.replace(/\D/g, '').slice(0, 11);
        e.target.value = value.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        
        const cpfLimpo = value;
        validationStatus.cpf = false;
        if (cpfLimpo.length === 0) {
            setFeedback('cpf-feedback', '', false);
            validationStatus.cpf = true;
            return validateFormState();
        }
        if (cpfLimpo.length < 11) { setFeedback('cpf-feedback', '', false); return validateFormState(); }
        if (!validaCPF(cpfLimpo)) {
            setFeedback('cpf-feedback', 'CPF inválido.', true);
            return validateFormState();
        }
        fetch(`verificar_cpf.php?cpf=${cpfLimpo}`).then(r => r.json()).then(data => {
            validationStatus.cpf = data.disponivel;
            setFeedback('cpf-feedback', data.disponivel ? '' : data.mensagem, !data.disponivel);
            validateFormState();
        });
    });

    // Validação de FORÇA DA SENHA
    passwordInput.addEventListener('input', () => {
        const senha = passwordInput.value;
        let forca = 0;
        if (senha.length >= 6) forca++; if (/[a-z]/.test(senha)) forca++;
        if (/[A-Z]/.test(senha)) forca++; if (/\d/.test(senha)) forca++;
        if (/[^a-zA-Z0-9]/.test(senha)) forca++;
        
        validationStatus.passwordStrength = forca >= 3; // Senha 'média' ou 'forte' é válida
        
        const msgs = ['Muito Fraca', 'Fraca', 'Fraca', 'Média', 'Média', 'Forte'];
        const cls = ['indisponivel', 'indisponivel', 'indisponivel', 'disponivel', 'disponivel', 'disponivel'];
        setFeedback('password-feedback', senha ? `Senha ${msgs[forca]}` : '', forca < 3 && senha.length > 0);
        validateFormState();
    });

    // Validação de SENHAS QUE COINCIDEM
    const checkPasswords = () => {
        const match = passwordInput.value === confirmPasswordInput.value;
        validationStatus.passwordsMatch = match;
        setFeedback('password-error', match ? '' : 'As senhas não coincidem.', !match);
        validateFormState();
    };
    passwordInput.addEventListener('keyup', checkPasswords);
    confirmPasswordInput.addEventListener('keyup', checkPasswords);

    // Validação FINAL no envio
    form.addEventListener('submit', (e) => {
        validateFormState(); // Roda a validação final
        if (submitButton.disabled) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos corretamente.');
        }
    });

    // Estado inicial do botão
    validateFormState();
});