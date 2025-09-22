const amigos = [
  {
    id: 1,
    nome: "Luis Inácio Bolsonaro",
    avatar: "https://i.pravatar.cc/150?img=10"
  },
  {
    id: 2,
    nome: "Roberta Carla",
    avatar: "https://i.pravatar.cc/150?img=20"
  },
  {
    id: 3,
    nome: "Ronaldo Sulista",
    avatar: "https://i.pravatar.cc/150?img=30"
  }
];

const lista = document.getElementById('friends-list');

amigos.forEach(amigo => {
  const item = document.createElement('div');
  item.className = 'friend-item';
  item.innerHTML = `
    <img class="friend-avatar" src="${amigo.avatar}" alt="${amigo.nome}">
    <span>${amigo.nome}</span>
  `;
  item.addEventListener('click', () => {
    window.location.href = `perfil.html?id=${amigo.id}`;
  });
  lista.appendChild(item);
});

// Adicionar Amigo
const addFriend = document.createElement('div');
addFriend.className = 'add-friend-item';
addFriend.innerHTML = `
  <span class="add-icon">➕</span>
  <span>Adicionar Amigo</span>
`;
lista.appendChild(addFriend);
