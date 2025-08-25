const passwordIcons = document.querySelectorAll('.password-icon');


passwordIcons.forEach(icon => {
    icon.addEventListener('click', function () {
        const input = this.parentElement.querySelector('.form-control');
        input.type = input.type === 'password' ? 'text' : 'password';
        this.classList.toggle('fa-eye');
    })
})
document.getElementById('cpf').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
    if (value.length > 11) value = value.slice(0, 11);
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d)/, '$1.$2');
    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    e.target.value = value;
});
document.getElementById('form').addEventListener('submit', function(e) {


const senha = document.getElementById('password').value;
const csenha = document.getElementById('confirm_password').value;


  if (senha !== csenha) {
    alert ("Senhas diferentes!");
    e.preventDefault();
    return;
  }
}
);

