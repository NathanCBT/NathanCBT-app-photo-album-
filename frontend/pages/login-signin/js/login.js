document.addEventListener("DOMContentLoaded", () => {
  const phpErrorBridge = document.getElementById("php-error-bridge");
  if (phpErrorBridge) {
    const errorMessage = phpErrorBridge.getAttribute("data-message");

    if (errorMessage && typeof showErrorPopup === "function") {
      showErrorPopup(errorMessage);
    }
  }

  const loginForm = document.getElementById("form-login");

  if (loginForm) {
    loginForm.addEventListener("submit", (e) => {
      const identifierInput = document.getElementById("email");
      const passwordInput = document.getElementById("password");

      if (!identifierInput.value.trim()) {
        e.preventDefault();
        console.error("le champ de l'identifiant est vide");
        showErrorPopup("le champ email ou pseudonyme ne peut pas être vide");
        return;
      }

      if (!passwordInput.value) {
        e.preventDefault();
        console.error("Le champ mot de passe est vide.");
        showErrorPopup("Veuillez saisir votre mot de passe.");
        return;
      }

      console.log(
        "champs valides côté Front, envoi des identifiants au serveur PHP",
      );
    });
  }
});
