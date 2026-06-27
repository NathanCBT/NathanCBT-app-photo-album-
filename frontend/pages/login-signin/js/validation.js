function showErrorPopup(messageText) {
  const overlay = document.createElement("div");
  overlay.className = "popup-overlay";

  const box = document.createElement("div");
  box.className = "popup-message";

  const textNode = document.createElement("p");
  textNode.textContent = messageText;

  const closeBtn = document.createElement("button");
  closeBtn.className = "popup-close";
  closeBtn.textContent = "Fermer";

  closeBtn.addEventListener("click", () => {
    console.log("Fermeture de la pop-up d'erreur");
    overlay.remove();
  });

  box.appendChild(textNode);
  box.appendChild(closeBtn);
  overlay.appendChild(box);
  document.body.appendChild(overlay);
}

// buttons hide password
document.addEventListener("DOMContentLoaded", () => {
  const toggleButtons = document.querySelectorAll(".toggle-password");

  toggleButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const wrapper = btn.parentElement;
      const input = wrapper ? wrapper.querySelector("input") : null;

      if (input && (input.type === "password" || input.type === "text")) {
        input.type = input.type === "password" ? "text" : "password";
      }
    });
  });
});
