document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("edit-user-search-input");
  const invitedList = document.getElementById("edit-invited-users");

  if (!searchInput || !invitedList) return;

  const autocompleteContainer = document.createElement("div");
  autocompleteContainer.className = "edit-autocomplete-suggestions";
  searchInput.parentNode.appendChild(autocompleteContainer);

  searchInput.addEventListener("input", () => {
    const query = searchInput.value.trim();

    if (query.length < 2) {
      clearSuggestions();
      return;
    }

    fetch(`/backend/search_users_action.php?q=${encodeURIComponent(query)}`)
      .then((response) => response.json())
      .then((users) => {
        clearSuggestions();

        if (users.length === 0) {
          const noResult = document.createElement("div");
          noResult.className = "edit-suggestion-item no-result";
          noResult.textContent = "Aucun utilisateur trouvé";
          autocompleteContainer.appendChild(noResult);
          return;
        }

        users.forEach((user) => {
          if (document.getElementById(`edit-invited-user-${user.id}`)) return;

          const suggestion = document.createElement("div");
          suggestion.className = "edit-suggestion-item";

          const img = document.createElement("img");
          img.className = "edit-suggestion-avatar";
          img.src = user.avatar_url
            ? `/${user.avatar_url}`
            : "/frontend/assets/IMG/default-avatar.png";
          img.alt = "";

          const span = document.createElement("span");
          span.textContent = user.username;

          suggestion.appendChild(img);
          suggestion.appendChild(span);

          suggestion.addEventListener("click", () => {
            addSelectedUser(user);
            searchInput.value = "";
            clearSuggestions();
          });

          autocompleteContainer.appendChild(suggestion);
        });
      })
      .catch((err) => console.error("Erreur de recherche :", err));
  });

  document.addEventListener("click", (e) => {
    if (e.target !== searchInput) {
      clearSuggestions();
    }
  });

  function clearSuggestions() {
    while (autocompleteContainer.firstChild) {
      autocompleteContainer.removeChild(autocompleteContainer.firstChild);
    }
  }

  function addSelectedUser(user, existingRight = "Peut voir") {
    const row = document.createElement("div");
    row.className = "edit-invited-user-row";
    row.id = `edit-invited-user-${user.id}`;

    const inputId = document.createElement("input");
    inputId.type = "hidden";
    inputId.name = "invited_users_ids[]";
    inputId.value = user.id;
    row.appendChild(inputId);

    // user metadata
    const userMeta = document.createElement("div");
    userMeta.className = "user-meta";

    const avatar = document.createElement("img");
    avatar.className = "invited-avatar";
    avatar.src = user.avatar_url
      ? `/${user.avatar_url}`
      : "/frontend/assets/IMG/default-avatar.png";
    avatar.alt = "";

    const nameSpan = document.createElement("span");
    nameSpan.className = "invited-name";
    nameSpan.textContent = user.username;

    userMeta.appendChild(avatar);
    userMeta.appendChild(nameSpan);
    row.appendChild(userMeta);

    // action section
    const actionsDiv = document.createElement("div");
    actionsDiv.className = "user-rights-actions";

    const select = document.createElement("select");
    select.name = "invited_users_rights[]";
    select.className = "rights-select";

    const rights = ["Peut voir", "Peut commenter", "Peut modifier"];
    rights.forEach((rightText) => {
      const option = document.createElement("option");
      option.value = rightText;
      option.textContent = rightText;
      if (rightText === existingRight) {
        option.selected = true;
      }
      select.appendChild(option);
    });

    const btnRemove = document.createElement("button");
    btnRemove.type = "button";
    btnRemove.className = "btn-remove-invite";
    btnRemove.setAttribute("title", "Retirer");

    const icon = document.createElement("i");
    icon.className = "fa-solid fa-xmark";
    btnRemove.appendChild(icon);

    btnRemove.addEventListener("click", () => {
      row.remove();
    });

    actionsDiv.appendChild(select);
    actionsDiv.appendChild(btnRemove);
    row.appendChild(actionsDiv);

    invitedList.appendChild(row);
  }

  window.addEditContributeurRow = addSelectedUser;
});
