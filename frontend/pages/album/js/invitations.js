document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("user-search-input");
  const invitedList = document.getElementById("invited-users");

  if (!searchInput || !invitedList) return;

  // suggestions box
  const autocompleteContainer = document.createElement("div");
  autocompleteContainer.className = "autocomplete-suggestions";
  searchInput.parentNode.appendChild(autocompleteContainer);

  searchInput.addEventListener("input", () => {
    const query = searchInput.value.trim();

    if (query.length < 2) {
      autocompleteContainer.innerHTML = "";
      return;
    }

    // ajax call to the suggestions box
    fetch(`/backend/search_users_action.php?q=${encodeURIComponent(query)}`)
      .then((response) => response.json())
      .then((users) => {
        // clean old suggestions
        while (autocompleteContainer.firstChild) {
          autocompleteContainer.removeChild(autocompleteContainer.firstChild);
        }

        if (users.length === 0) {
          const noResult = document.createElement("div");
          noResult.className = "suggestion-item no-result";
          noResult.textContent = "Aucun utilisateur trouvé";
          autocompleteContainer.appendChild(noResult);
          return;
        }

        users.forEach((user) => {
          // cannot add the same user twice
          if (document.getElementById(`invited-user-${user.id}`)) return;

          // suggestion line
          const suggestion = document.createElement("div");
          suggestion.className = "suggestion-item";

          const img = document.createElement("img");
          img.className = "suggestion-avatar";

          img.src = user.avatar_url
            ? `/${user.avatar_url}`
            : "/frontend/assets/IMG/default-avatar.png";
          img.alt = "";

          const span = document.createElement("span");
          span.textContent = user.username;

          suggestion.appendChild(img);
          suggestion.appendChild(span);

          // addition to the final list
          suggestion.addEventListener("click", () => {
            addSelectedUser(user);
            searchInput.value = "";
            while (autocompleteContainer.firstChild) {
              autocompleteContainer.removeChild(
                autocompleteContainer.firstChild,
              );
            }
          });

          autocompleteContainer.appendChild(suggestion);
        });
      })
      .catch((err) => console.error("Erreur de recherche:", err));
  });

  document.addEventListener("click", (e) => {
    if (e.target !== searchInput) {
      while (autocompleteContainer.firstChild) {
        autocompleteContainer.removeChild(autocompleteContainer.firstChild);
      }
    }
  });

  // inject the user into the list
  function addSelectedUser(user) {
    const row = document.createElement("div");
    row.className = "invited-user-row";
    row.id = `invited-user-${user.id}`;

    const inputId = document.createElement("input");
    inputId.type = "hidden";
    inputId.name = "invited_users_ids[]";
    inputId.value = user.id;
    row.appendChild(inputId);

    const userMeta = document.createElement("div");
    userMeta.className = "user-meta";

    const avatar = document.createElement("img");
    avatar.className = "invited-avatar";

    avatar.src = user.avatar_url
      ? `/${user.avatar_url}`
      : "/frontend/assets/IMG/default-avatar.png";
    avatar.alt = "Avatar";

    const nameSpan = document.createElement("span");
    nameSpan.className = "invited-name";
    nameSpan.textContent = user.username;

    userMeta.appendChild(avatar);
    userMeta.appendChild(nameSpan);
    row.appendChild(userMeta);

    const actionsDiv = document.createElement("div");
    actionsDiv.className = "user-rights-actions";

    // rights selector
    const select = document.createElement("select");
    select.name = "invited_users_rights[]";
    select.className = "rights-select";

    const rights = ["Peut voir", "Peut commenter", "Peut modifier"];
    rights.forEach((rightText) => {
      const option = document.createElement("option");
      option.value = rightText;
      option.textContent = rightText;
      select.appendChild(option);
    });

    // removing the user from the list
    const btnRemove = document.createElement("button");
    btnRemove.type = "button";
    btnRemove.className = "btn-remove-invite";
    btnRemove.title = "Retirer";

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
});
