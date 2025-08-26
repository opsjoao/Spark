const screenSettings = document.getElementById('screen-settings');
const screenEdit     = document.getElementById('screen-edit');

const displayName  = document.getElementById('displayName');
const displayEmail = document.getElementById('displayEmail');

const inName  = document.getElementById('inName');
const inEmail = document.getElementById('inEmail');
const inPhone = document.getElementById('inPhone');

function goToEdit(){
  screenSettings.classList.remove('active');
  screenEdit.classList.add('active');
  window.scrollTo({top:0, behavior:'instant'});
}

function goBack(){
  screenEdit.classList.remove('active');
  screenSettings.classList.add('active');
  window.scrollTo({top:0, behavior:'instant'});
}

function saveProfile(){
  displayName.textContent  = inName.value.trim()  || 'Nome de Usuário';
  displayEmail.textContent = inEmail.value.trim() || 'usuario@email.com';

  goBack();
  toast('Perfil salvo!');
}

function toast(msg){
  const el = document.createElement('div');
  el.textContent = msg;
  el.style.cssText = `
    position: fixed; left: 50%; bottom: 80px; transform: translateX(-50%);
    background: #222; color: #fff; padding: 10px 14px; border-radius: 999px;
    font-size: 14px; box-shadow: 0 8px 20px rgba(0,0,0,.2); z-index: 9999;
  `;
  document.body.appendChild(el);
  setTimeout(()=>{ el.remove(); }, 1500);
}
