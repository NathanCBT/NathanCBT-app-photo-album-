document.addEventListener("DOMContentLoaded", () => {
  const bridge = document.getElementById("profile-bridge");
  if (!bridge) return;

  const userGrid = document.getElementById("user-albums-grid");
  const statAlbums = document.getElementById("stat-albums-count");
  const statPhotos = document.getElementById("stat-photos-count");

  const editModal = document.getElementById("edit-profile-modal");
  const btnTriggerEdit = document.getElementById("btn-edit-profile-trigger");
  const btnCloseEdit = document.getElementById("btn-close-edit-modal");
  const formEditProfile = document.getElementById("form-edit-profile");

  const displayAvatar = document.getElementById("profile-avatar-display");
  const displayBanner = document.getElementById("profile-banner-display");
  const displayBio = document.getElementById("profile-bio-display");

  const textareaBio = document.getElementById("textarea-bio");
  const bioCounter = document.getElementById("bio-counter");
  const maxLength = 160;

  function updateCounter() {
    if (!textareaBio || !bioCounter) return;
    const remaining = maxLength - textareaBio.value.length;
    bioCounter.textContent = `${remaining} caractère${remaining > 1 ? "s" : ""} restant${remaining > 1 ? "s" : ""}`;

    if (remaining <= 10) {
      bioCounter.style.color = "var(--brand-pink, #ba004d)";
    } else {
      bioCounter.style.color = "var(--text-light-gray)";
    }
  }

  function loadProfileData() {
    const urlParams = new URLSearchParams(window.location.search);
    const profileId = urlParams.get("id");

    const fetchUrl = profileId
      ? `/backend/get_profile_data_action.php?id=${profileId}`
      : `/backend/get_profile_data_action.php`;

    fetch(fetchUrl)
      .then((res) => {
        if (!res.ok)
          throw new Error("Erreur de chargement des données de profil");
        return res.json();
      })
      .then((result) => {
        if (result.success) {
          // update of targeted user statistics
          if (statAlbums) statAlbums.textContent = result.stats.albums_count;
          if (statPhotos) statPhotos.textContent = result.stats.photos_count;

          const statFollowers = document.getElementById("stat-followers-count");
          const statFollowing = document.getElementById("stat-following-count");
          if (statFollowers && result.stats.followers_count !== undefined) {
            statFollowers.textContent = result.stats.followers_count;
          }
          if (statFollowing && result.stats.following_count !== undefined) {
            statFollowing.textContent = result.stats.following_count;
          }

          // media alignment 'vatar, banner, bio)
          if (result.user) {
            if (displayBio) {
              displayBio.textContent =
                result.user.bio || "Aucune biographie pour le moment.";
              if (textareaBio) textareaBio.value = result.user.bio || "";
            }
            if (displayAvatar && result.user.avatar) {
              displayAvatar.style.backgroundImage = `url('/${result.user.avatar.replace(/^\//, "")}')`;
            }
            if (displayBanner) {
              if (result.user.banner) {
                displayBanner.style.backgroundImage = `url('/${result.user.banner.replace(/^\//, "")}')`;
              } else {
                displayBanner.style.backgroundImage = "none";
              }
            }
          }

          renderUserAlbums(result.albums);
        }
      })
      .catch((err) => console.error(err));
  }

  function renderUserAlbums(albums) {
    if (!userGrid) return;
    userGrid.textContent = "";

    if (!albums || albums.length === 0) {
      const noAlbumMsg = document.createElement("p");
      noAlbumMsg.className = "no-albums-message";
      noAlbumMsg.textContent = "Pas d'album photo créé pour le moment.";
      userGrid.appendChild(noAlbumMsg);
      return;
    }

    albums.forEach((album) => {
      const albumLink = document.createElement("a");
      albumLink.href = `/frontend/pages/album/html/view-album.php?id=${album.id}`;
      albumLink.className = "profile-album-card-wrapper";

      const card = document.createElement("div");
      card.className = "profile-album-card";

      const coverUrl = album.cover_url
        ? `/${album.cover_url}`
        : "/frontend/assets/IMG/default-cover.jpg";
      card.style.backgroundImage = `url('${coverUrl}')`;

      const overlay = document.createElement("div");
      overlay.className = "profile-album-overlay";

      const title = document.createElement("h3");
      title.className = "profile-album-title";
      title.textContent = album.title;

      const visibilityBadge = document.createElement("span");
      visibilityBadge.className = `visibility-badge ${album.visibility}`;

      const icon = document.createElement("i");
      if (album.visibility === "privé") {
        icon.className = "fa-solid fa-lock";
      } else if (album.visibility === "restreint") {
        icon.className = "fa-solid fa-user-group";
      } else {
        icon.className = "fa-solid fa-earth-americas";
      }

      visibilityBadge.appendChild(icon);
      overlay.appendChild(title);
      overlay.appendChild(visibilityBadge);
      card.appendChild(overlay);
      albumLink.appendChild(card);
      userGrid.appendChild(albumLink);
    });
  }

  // events for the modal
  if (btnTriggerEdit && editModal) {
    btnTriggerEdit.addEventListener("click", () => {
      editModal.classList.remove("hidden");
      // initializes the countdown on opening with the current bio
      updateCounter();
    });
  }

  if (btnCloseEdit && editModal) {
    btnCloseEdit.addEventListener("click", () =>
      editModal.classList.add("hidden"),
    );
  }

  // listener for input in the textarea
  if (textareaBio) {
    textareaBio.addEventListener("input", updateCounter);
  }

  if (formEditProfile) {
    formEditProfile.addEventListener("submit", (e) => {
      e.preventDefault();
      const btnSubmit = document.getElementById("btn-submit-profile");
      if (btnSubmit) btnSubmit.disabled = true;

      const formData = new FormData(formEditProfile);

      fetch("/backend/update_profile_action.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => {
          if (!res.ok) throw new Error("Erreur de sauvegarde");
          return res.json();
        })
        .then((data) => {
          if (data.success) {
            if (displayBio) displayBio.textContent = data.bio;
            if (data.avatar && displayAvatar) {
              displayAvatar.style.backgroundImage = `url('/${data.avatar}')`;
            }
            if (data.banner && displayBanner) {
              displayBanner.style.backgroundImage = `url('/${data.banner}')`;
            }
            editModal.classList.add("hidden");
          }
        })
        .catch((err) =>
          alert("Impossible de modifier les informations de profil."),
        )
        .finally(() => {
          if (btnSubmit) btnSubmit.disabled = false;
        });
    });
  }

  loadProfileData();
});
