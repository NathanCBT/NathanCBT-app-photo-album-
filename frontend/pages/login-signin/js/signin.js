document.addEventListener("DOMContentLoaded", () => {
  const globalForm = document.querySelector(
    "form[action*='register_action.php']",
  );

  const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9]).{14,}$/;
  const passwordErrorMessage =
    "Le mot de passe doit comporter au moins 14 caractères, une majuscule, une minuscule et un caractère spécial.";

  const step1Div = document.getElementById("step-1");
  const step2Div = document.getElementById("step-2");
  const btnToStep2 = document.getElementById("btn-to-step-2");

  // avatar
  const btnBrowse = document.getElementById("btn-browse-avatar");
  const avatarInput = document.getElementById("avatar-file-input");
  const avatarPreview = document.getElementById("avatar-preview");

  if (btnToStep2) {
    btnToStep2.addEventListener("click", () => {
      const nameInput = document.getElementById("name");
      const emailInput = document.getElementById("email");
      const passwordInput = document.getElementById("reg-password");
      const confirmInput = document.getElementById("confirm-password");

      if (!nameInput.value.trim() || nameInput.value.trim().length < 2) {
        console.error("Nom invalide.");
        showErrorPopup("Veuillez entrer un prénom et un nom valides.");
        return;
      }

      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(emailInput.value.trim())) {
        console.error("Email invalide.");
        showErrorPopup("L'adresse e-mail saisie n'est pas valide.");
        return;
      }

      if (!passwordPattern.test(passwordInput.value)) {
        console.error("Mot de passe non conforme.");
        showErrorPopup(passwordErrorMessage);
        return;
      }

      if (passwordInput.value !== confirmInput.value) {
        console.error("Erreur, les deux mots de passe ne sont pas identiques");
        showErrorPopup("Les deux mots de passe saisis ne correspondent pas.");
        return;
      }

      step1Div.classList.remove("active");
      step2Div.classList.add("active");
    });
  }

  // submit
  if (globalForm) {
    globalForm.addEventListener("submit", (e) => {
      const usernameInput = document.getElementById("username");
      const passwordInput = document.getElementById("reg-password");
      const confirmInput = document.getElementById("confirm-password");

      if (
        !usernameInput.value.trim() ||
        usernameInput.value.trim().length < 3
      ) {
        e.preventDefault();
        console.error("Le pseudonyme est vide ou trop court.");
        showErrorPopup(
          "Veuillez saisir un pseudonyme contenant au moins 3 caractères.",
        );
        return;
      }

      if (!passwordPattern.test(passwordInput.value)) {
        e.preventDefault();
        console.error("Mot de passe non conforme.");
        showErrorPopup(passwordErrorMessage);
        return;
      }

      if (passwordInput.value !== confirmInput.value) {
        e.preventDefault();
        console.error("Erreur, les deux mots de passe ne sont pas identiques");
        showErrorPopup("Les deux mots de passe saisis ne correspondent pas.");
        return;
      }

      console.log("formulaire valide, envoi au serveur PHP");
    });
  }

  const phpErrorBridge = document.getElementById("php-error-bridge");
  if (phpErrorBridge) {
    const serverError = phpErrorBridge.getAttribute("data-message");
    if (serverError) {
      showErrorPopup(serverError);
    }
  }

  // avatar
  if (btnBrowse && avatarInput) {
    btnBrowse.addEventListener("click", () => avatarInput.click());

    // manage file switching and preview
    avatarInput.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (file) {
        if (file.size > 3 * 1024 * 1024) {
          alert("L'image est trop lourde (max 3Mo pour la photo de profil).");
          return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
          avatarPreview.innerHTML = "";
          avatarPreview.style.backgroundImage = `url('${event.target.result}')`;
          avatarPreview.style.borderStyle = "solid";
        };
        reader.readAsDataURL(file);
      }
    });
  }
});
