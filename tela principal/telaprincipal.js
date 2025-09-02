// Exemplo de interação simples
document.querySelectorAll(".bottombar a").forEach(link => {
  link.addEventListener("click", e => {
    document.querySelectorAll(".bottombar a").forEach(a => a.classList.remove("active"));
    e.currentTarget.classList.add("active");
  });
});
