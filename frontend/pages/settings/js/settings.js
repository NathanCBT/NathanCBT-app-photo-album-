document.addEventListener("DOMContentLoaded", () => {
  const formIdentifiers = document.getElementById("form-update-identifiers");
  const formPassword = document.getElementById("form-update-password");
  const btnLogout = document.getElementById("btn-logout");
  const btnDeleteAccount = document.getElementById("btn-delete-account");

  function setupPasswordToggle(input, toggleBtn) {
    toggleBtn.addEventListener("click", (e) => {
      e.preventDefault();
      if (input.type === "password") {
        input.type = "text";
        toggleBtn.classList.add("visible");
      } else {
        input.type = "password";
        toggleBtn.classList.remove("visible");
      }
    });
  }

  const passwordInputs = [
    "current-password",
    "new-password",
    "confirm-password",
  ];
  passwordInputs.forEach((id) => {
    const input = document.getElementById(id);
    if (input) {
      const wrapper = input.closest(".password-wrapper");
      if (wrapper) {
        const toggleBtn = wrapper.querySelector(".toggle-password");
        if (toggleBtn) setupPasswordToggle(input, toggleBtn);
      }
    }
  });

  function showAlert(message, type = "success") {
    const existingPopup = document.querySelector(".popup-overlay");
    if (existingPopup) existingPopup.remove();

    const overlay = document.createElement("div");
    overlay.className = "popup-overlay";

    const popupBox = document.createElement("div");
    popupBox.className = `popup-message ${type === "error" ? "popup-danger" : "popup-success"}`;

    const text = document.createElement("p");
    text.textContent = message;

    const closeBtn = document.createElement("button");
    closeBtn.className = "popup-close";
    closeBtn.textContent = "Continuer";

    popupBox.appendChild(text);
    popupBox.appendChild(closeBtn);
    overlay.appendChild(popupBox);
    document.body.appendChild(overlay);

    closeBtn.focus();
    closeBtn.addEventListener("click", () => overlay.remove());
  }

  function showConfirm(message, onConfirm, isDanger = false) {
    const overlay = document.createElement("div");
    overlay.className = "popup-overlay";

    const popupBox = document.createElement("div");
    popupBox.className = `popup-message ${isDanger ? "popup-danger" : "popup-success"}`;

    const text = document.createElement("p");
    text.textContent = message;

    const actionsContainer = document.createElement("div");
    actionsContainer.className = "popup-actions";

    const cancelBtn = document.createElement("button");
    cancelBtn.className = "btn-secondary";
    cancelBtn.textContent = "Annuler";

    const confirmBtn = document.createElement("button");
    confirmBtn.className = `popup-close ${isDanger ? "btn-confirm-danger" : ""}`;
    confirmBtn.textContent = "Confirmer";

    actionsContainer.appendChild(cancelBtn);
    actionsContainer.appendChild(confirmBtn);
    popupBox.appendChild(text);
    popupBox.appendChild(actionsContainer);
    overlay.appendChild(popupBox);
    document.body.appendChild(overlay);

    cancelBtn.addEventListener("click", () => overlay.remove());
    confirmBtn.addEventListener("click", () => {
      overlay.remove();
      onConfirm();
    });
  }

  function showPrompt(message, onConfirm) {
    const overlay = document.createElement("div");
    overlay.className = "popup-overlay";

    const popupBox = document.createElement("div");
    popupBox.className = "popup-message popup-danger";

    const text = document.createElement("p");
    text.textContent = message;

    const inputGroup = document.createElement("div");
    inputGroup.className = "input-group input-group-prompt";

    const label = document.createElement("label");
    label.textContent = "Mot de passe de confirmation";
    inputGroup.appendChild(label);

    const passwordWrapper = document.createElement("div");
    passwordWrapper.className = "password-wrapper";

    const input = document.createElement("input");
    input.type = "password";
    input.placeholder = "Saisissez votre mot de passe actuel...";

    const toggleBtn = document.createElement("button");
    toggleBtn.className = "toggle-password";
    toggleBtn.type = "button";

    passwordWrapper.appendChild(input);
    passwordWrapper.appendChild(toggleBtn);
    inputGroup.appendChild(passwordWrapper);

    setupPasswordToggle(input, toggleBtn);

    const actionsContainer = document.createElement("div");
    actionsContainer.className = "popup-actions";

    const cancelBtn = document.createElement("button");
    cancelBtn.className = "btn-secondary";
    cancelBtn.textContent = "Annuler";

    const confirmBtn = document.createElement("button");
    confirmBtn.className = "popup-close btn-confirm-danger";
    confirmBtn.textContent = "Supprimer définitivement";

    actionsContainer.appendChild(cancelBtn);
    actionsContainer.appendChild(confirmBtn);
    popupBox.appendChild(text);
    popupBox.appendChild(inputGroup);
    popupBox.appendChild(actionsContainer);
    overlay.appendChild(popupBox);
    document.body.appendChild(overlay);

    input.focus();

    cancelBtn.addEventListener("click", () => overlay.remove());
    confirmBtn.addEventListener("click", () => {
      const val = input.value.trim();
      overlay.remove();
      onConfirm(val);
    });
  }

  function fetchCurrentSettings() {
    fetch("/backend/get_profile_data_action.php")
      .then((res) => {
        if (!res.ok) throw new Error();
        return res.json();
      })
      .then((data) => {
        if (data && !data.error) {
          const usernameInput = document.getElementById("settings-username");
          const emailInput = document.getElementById("settings-email");

          if (usernameInput) usernameInput.value = data.username || "";
          if (emailInput) emailInput.value = data.email || "";
        }
      })
      .catch(() =>
        showAlert(
          "Erreur lors de la récupération de vos informations.",
          "error",
        ),
      );
  }
  fetchCurrentSettings();

  // change of identification cards
  if (formIdentifiers) {
    formIdentifiers.addEventListener("submit", (e) => {
      e.preventDefault();

      const usernameInput = document.getElementById("settings-username");
      const emailInput = document.getElementById("settings-email");

      if (!usernameInput || !emailInput) return;

      const username = usernameInput.value.trim();
      const email = emailInput.value.trim();
      const updateData = {};

      if (username !== "") {
        if (username.length < 3) {
          showAlert(
            "Le pseudonyme doit contenir au moins 3 caractères.",
            "error",
          );
          return;
        }
        updateData.username = username;
      }

      if (email !== "") {
        updateData.email = email;
      }

      if (Object.keys(updateData).length === 0) {
        showAlert("Veuillez modifier au moins un champ.", "error");
        return;
      }

      fetch("/backend/update_account_identifiers_action.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(updateData),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.error) {
            showAlert(data.error, "error");
          } else {
            showAlert(
              "Vos identifiants ont été mis à jour avec succès !",
              "success",
            );
            fetchCurrentSettings();
          }
        })
        .catch(() =>
          showAlert("Erreur système lors de la modification.", "error"),
        );
    });
  }

  // change password
  if (formPassword) {
    formPassword.addEventListener("submit", (e) => {
      e.preventDefault();

      const currentPassword = document.getElementById("current-password").value;
      const newPassword = document.getElementById("new-password").value;
      const confirmPassword = document.getElementById("confirm-password").value;

      if (!currentPassword || !newPassword || !confirmPassword) {
        showAlert("Tous les champs de mot de passe sont requis.", "error");
        return;
      }

      if (newPassword !== confirmPassword) {
        showAlert("Les nouveaux mots de passe ne correspondent pas.", "error");
        return;
      }

      fetch("/backend/update_password_action.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ currentPassword, newPassword }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.error) {
            showAlert(data.error, "error");
          } else {
            showAlert("Mot de passe modifié avec succès !", "success");
            formPassword.reset();
            formPassword
              .querySelectorAll("input")
              .forEach((i) => (i.type = "password"));
            formPassword
              .querySelectorAll(".toggle-password")
              .forEach((b) => b.classList.remove("visible"));
          }
        })
        .catch(() =>
          showAlert(
            "Erreur système lors du changement de mot de passe.",
            "error",
          ),
        );
    });
  }

  // log out
  if (btnLogout) {
    btnLogout.addEventListener("click", () => {
      showConfirm("Voulez-vous vraiment vous déconnecter ?", () => {
        fetch("/backend/logout_action.php")
          .then((res) => res.json())
          .then(() => {
            window.location.href =
              "/frontend/pages/login-signin/html/login.php";
          })
          .catch(() => {
            window.location.href =
              "/frontend/pages/login-signin/html/login.php";
          });
      });
    });
  }

  // delet account
  if (btnDeleteAccount) {
    btnDeleteAccount.addEventListener("click", () => {
      showConfirm(
        "ATTENTION ! Cette action détruira définitivement vos albums, vos photos et votre compte. Continuer ?",
        () => {
          showPrompt(
            "Pour confirmer la désactivation définitive, veuillez saisir votre mot de passe actuel :",
            (passwordVerify) => {
              if (passwordVerify && passwordVerify.trim() !== "") {
                fetch("/backend/delete_account_action.php", {
                  method: "POST",
                  headers: { "Content-Type": "application/json" },
                  body: JSON.stringify({ password: passwordVerify.trim() }),
                })
                  .then((res) => res.json())
                  .then((data) => {
                    if (data.error) {
                      showAlert(data.error, "error");
                    } else {
                      showAlert(
                        "Votre compte a été supprimé. Au revoir !",
                        "success",
                      );
                      setTimeout(() => {
                        window.location.href =
                          "/frontend/pages/login-signin/html/register.php";
                      }, 2000);
                    }
                  })
                  .catch(() =>
                    showAlert(
                      "Erreur lors de la suppression du compte.",
                      "error",
                    ),
                  );
              } else {
                showAlert("Le mot de passe ne peut pas être vide.", "error");
              }
            },
          );
        },
        true,
      );
    });
  }
});
