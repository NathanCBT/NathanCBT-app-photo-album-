document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("user-search-input");
  const searchResultsContainer = document.getElementById(
    "search-results-container",
  );
  const invitationsContainer = document.getElementById(
    "invitations-list-container",
  );

  // loading received invitations
  function loadInvitations() {
    invitationsContainer.textContent = "";

    const loadingMsg = document.createElement("p");
    loadingMsg.className = "loading-text";
    loadingMsg.textContent = "Chargement des invitations...";
    invitationsContainer.appendChild(loadingMsg);

    fetch(`/backend/get_invitations_action.php`)
      .then((res) => {
        if (!res.ok) throw new Error("Erreur réseau");
        return res.json();
      })
      .then((data) => {
        invitationsContainer.textContent = "";

        if (data.error) {
          const errorMsg = document.createElement("p");
          errorMsg.className = "error-text";
          errorMsg.textContent = data.error;
          invitationsContainer.appendChild(errorMsg);
          return;
        }

        if (!data || data.length === 0) {
          const noInvitMsg = document.createElement("p");
          noInvitMsg.className = "no-users-message";
          noInvitMsg.textContent =
            "Vous n'avez aucune invitation pour le moment.";
          invitationsContainer.appendChild(noInvitMsg);
          return;
        }

        data.forEach((invit) => {
          const card = document.createElement("div");
          card.className = "invitation-row-card";

          const avatar = document.createElement("div");
          avatar.className = "user-avatar-medium";
          const avatarUrl = invit.host_avatar
            ? `/${invit.host_avatar}`
            : "/frontend/assets/IMG/default-avatar.svg";
          avatar.style.backgroundImage = `url('${avatarUrl}')`;
          avatar.style.backgroundSize = "cover";

          const bodyText = document.createElement("div");
          bodyText.className = "invitation-body-text";

          const paragraph = document.createElement("p");
          const strongUser = document.createElement("strong");
          strongUser.textContent = invit.host_name;
          const strongAlbum = document.createElement("strong");
          strongAlbum.textContent = invit.album_title;

          paragraph.appendChild(strongUser);
          paragraph.appendChild(
            document.createTextNode(" vous invite à participer à l'album "),
          );
          paragraph.appendChild(strongAlbum);

          // level of rights granted
          const roleBadge = document.createElement("span");
          roleBadge.className = "role-badge";
          roleBadge.style.fontSize = "0.8rem";
          roleBadge.style.color = "var(--brand-pink)";
          roleBadge.style.display = "block";
          roleBadge.textContent = ` Droits accordés : ${invit.rights.toLowerCase()}`;

          bodyText.appendChild(paragraph);
          bodyText.appendChild(roleBadge);

          const actionsDiv = document.createElement("div");
          actionsDiv.className = "invitation-actions";

          const btnView = document.createElement("a");
          btnView.className = "btn-view-profile";
          btnView.textContent = "Voir";
          btnView.href = `/frontend/pages/album/html/view-album.php?id=${invit.album_id}`;

          actionsDiv.appendChild(btnView);
          card.appendChild(avatar);
          card.appendChild(bodyText);
          card.appendChild(actionsDiv);

          invitationsContainer.appendChild(card);
        });
      })
      .catch((err) => {
        invitationsContainer.textContent = "";
        const errorMsg = document.createElement("p");
        errorMsg.className = "error-text";
        errorMsg.textContent = "Impossible de charger les invitations.";
        invitationsContainer.appendChild(errorMsg);
      });
  }

  // user research
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      const query = searchInput.value.trim();

      if (query.length < 2) {
        searchResultsContainer.textContent = "";
        const infoMsg = document.createElement("p");
        infoMsg.className = "info-text";
        infoMsg.textContent =
          "Saisissez au moins 2 caractères pour démarrer la recherche.";
        searchResultsContainer.appendChild(infoMsg);
        return;
      }

      fetch(`/backend/search_users_action.php?q=${encodeURIComponent(query)}`)
        .then((res) => res.json())
        .then((users) => {
          searchResultsContainer.textContent = "";

          if (users.error) {
            const errorMsg = document.createElement("p");
            errorMsg.className = "error-text";
            errorMsg.textContent = users.error;
            searchResultsContainer.appendChild(errorMsg);
            return;
          }

          if (users.length === 0) {
            const noResultsMsg = document.createElement("p");
            noResultsMsg.className = "no-users-message";
            noResultsMsg.textContent = "Aucun utilisateur trouvé.";
            searchResultsContainer.appendChild(noResultsMsg);
            return;
          }

          // user card found
          users.forEach((user) => {
            const card = document.createElement("div");
            card.className = "user-result-card";

            const avatar = document.createElement("div");
            avatar.className = "user-avatar-medium";
            const avatarUrl = user.avatar_url
              ? `/${user.avatar_url}`
              : "/frontend/assets/IMG/default-avatar.svg";
            avatar.style.backgroundImage = `url('${avatarUrl}')`;

            const meta = document.createElement("div");
            meta.className = "user-meta";

            const displayName = document.createElement("span");
            displayName.className = "user-display-name";
            displayName.textContent = user.username;

            const handle = document.createElement("span");
            handle.className = "user-handle";
            handle.textContent = `@${user.username.toLowerCase().replace(/\s+/g, "")}`;

            meta.appendChild(displayName);
            meta.appendChild(handle);

            // link "Voir le profil" which will be used to view the profile
            const btnView = document.createElement("a");
            btnView.className = "btn-view-profile";
            btnView.textContent = "Voir le profil";
            btnView.href = `/frontend/pages/profile/html/profile-user.php?id=${user.id}`;

            card.appendChild(avatar);
            card.appendChild(meta);
            card.appendChild(btnView);

            searchResultsContainer.appendChild(card);
          });
        })
        .catch(() => {
          searchResultsContainer.textContent = "";
          const networkError = document.createElement("p");
          networkError.className = "error-text";
          networkError.textContent = "Erreur réseau lors de la recherche.";
          searchResultsContainer.appendChild(networkError);
        });
    });
  }

  loadInvitations();
});
