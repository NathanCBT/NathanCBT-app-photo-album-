document.addEventListener("DOMContentLoaded", () => {
  fetch("../../../../backend/get_dashboard_data.php")
    .then((response) => {
      if (!response.ok) throw new Error("Erreur HTTP " + response.status);
      return response.json();
    })
    .then((data) => {
      if (data.error) {
        console.error("Erreur :", data.error);
        return;
      }

      renderRecentAlbums(data.albums);
      renderInvitations(data.invitations);
      renderFavorites(data.favorites);
    })
    .catch((error) => console.error("Erreur de récupération :", error));
});

function renderRecentAlbums(albums) {
  const container = document.getElementById("recent-albums-container");
  if (!container) return;
  container.textContent = "";

  if (!albums || albums.length === 0) {
    const emptyState = document.createElement("p");
    emptyState.className = "empty-state-text";
    emptyState.textContent = "Vous n'avez pas encore créé d'album.";
    container.appendChild(emptyState);
    return;
  }

  albums.forEach((album) => {
    const albumCard = document.createElement("div");
    albumCard.className = "dashboard-album-card";

    const bannerPath = album.banner
      ? `/${album.banner}`
      : "/frontend/assets/IMG/assets.svg";

    const thumbnailBox = document.createElement("div");
    thumbnailBox.className = "album-thumbnail-box";
    thumbnailBox.style.backgroundImage = `url('${bannerPath}')`;
    thumbnailBox.style.backgroundSize = "cover";
    thumbnailBox.style.backgroundPosition = "center";

    // album title
    const albumTitle = document.createElement("p");
    albumTitle.className = "album-title-text";
    albumTitle.textContent = album.title;

    // photos counter
    const albumCounter = document.createElement("p");
    albumCounter.className = "album-counter-text";
    albumCounter.textContent = `${album.photos_count} photo${album.photos_count > 1 ? "s" : ""}`;

    albumCard.appendChild(thumbnailBox);
    albumCard.appendChild(albumTitle);
    albumCard.appendChild(albumCounter);

    albumCard.addEventListener("click", () => {
      window.location.href = `../../album/html/view-album.php?id=${album.id}`;
    });

    container.appendChild(albumCard);
  });
}

function renderInvitations(invitationsData) {
  const container = document.getElementById("invitations-container");
  if (!container) return;
  container.textContent = "";

  const list =
    invitationsData && invitationsData.list ? invitationsData.list : [];

  if (list.length === 0) {
    const emptyState = document.createElement("p");
    emptyState.className = "empty-state-text";
    emptyState.textContent = "Aucune invitation reçue.";
    container.appendChild(emptyState);
    return;
  }

  list.forEach((invite) => {
    const inviteCard = document.createElement("div");
    inviteCard.className = "invitation-mini-card";

    const avatarPath = invite.avatar
      ? `/${invite.avatar}`
      : "/frontend/assets/IMG/default-avatar.svg";

    const avatarBox = document.createElement("div");
    avatarBox.className = "user-avatar-small";
    avatarBox.style.backgroundImage = `url('${avatarPath}')`;
    avatarBox.style.backgroundSize = "cover";

    const infoBox = document.createElement("div");
    infoBox.className = "invitation-info";

    const invitationText = document.createElement("p");
    invitationText.className = "invitation-text";

    const strongSender = document.createElement("strong");
    strongSender.textContent = invite.sender_name;

    const strongAlbum = document.createElement("strong");
    strongAlbum.textContent = `“${invite.album_title}”`;

    invitationText.appendChild(strongSender);
    invitationText.appendChild(
      document.createTextNode(" vous invite à contribuer à "),
    );
    invitationText.appendChild(strongAlbum);

    infoBox.appendChild(invitationText);

    inviteCard.appendChild(avatarBox);
    inviteCard.appendChild(infoBox);

    inviteCard.style.cursor = "pointer";
    inviteCard.addEventListener("click", () => {
      window.location.href = "../../invitation/html/invitation.php";
    });

    container.appendChild(inviteCard);
  });

  // displays "+ X" if there are more than 3 invitations
  if (invitationsData.more_count > 0) {
    const indicator = document.createElement("div");
    indicator.className = "invitation-more-indicator";
    indicator.textContent = `+ ${invitationsData.more_count}`;
    container.appendChild(indicator);
  }
}

function renderFavorites(favorites) {
  const container = document.getElementById("favorites-photos-container");
  if (!container) return;
  container.textContent = "";

  if (!favorites || favorites.length === 0) {
    const emptyState = document.createElement("p");
    emptyState.className = "empty-state-text";
    emptyState.textContent = "Pas encore de photos en favoris.";
    container.appendChild(emptyState);
    return;
  }

  // a maximum of 6 photos are extracted from the table to display only 6
  const favoritesToDisplay = favorites.slice(0, 6);

  favoritesToDisplay.forEach((fav) => {
    const photoItem = document.createElement("div");
    photoItem.className = "favorite-photo-item";

    const photoPath = `/${fav.file_path}`;
    photoItem.style.backgroundImage = `url('${photoPath}')`;
    photoItem.style.backgroundSize = "cover";
    photoItem.style.backgroundPosition = "center";

    photoItem.style.cursor = "pointer";
    photoItem.addEventListener("click", () => {
      window.location.href = "../../favorites/html/favorites.php";
    });

    container.appendChild(photoItem);
  });
}
