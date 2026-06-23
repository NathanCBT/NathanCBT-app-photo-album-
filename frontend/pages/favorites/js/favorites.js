document.addEventListener("DOMContentLoaded", () => {
  const favoritesGrid = document.getElementById("favorites-grid");
  const favoritesCount = document.getElementById("favorites-count");

  loadFavorites();

  function loadFavorites() {
    fetch("/backend/get_favorites_action.php")
      .then((res) => res.json())
      .then((data) => {
        if (data.success) {
          renderFavorites(data.results);
        } else {
          console.error("Erreur lors du chargement :", data.message);
        }
      })
      .catch((err) => console.error("Erreur réseau :", err));
  }

  function renderFavorites(photos) {
    if (favoritesCount) {
      favoritesCount.textContent = photos.length;
    }

    if (!favoritesGrid) return;
    favoritesGrid.textContent = "";

    if (photos.length === 0) {
      const emptyDiv = document.createElement("div");
      emptyDiv.className = "empty-favorites";

      const emptyPara = document.createElement("p");
      emptyPara.textContent = "Vous n'avez pas encore de photos favorites.";

      emptyDiv.appendChild(emptyPara);
      favoritesGrid.appendChild(emptyDiv);
      return;
    }

    const fragment = document.createDocumentFragment();

    photos.forEach((photo) => {
      const rawPath = photo.file_path || "";
      if (!rawPath) return;

      const cleanPath = rawPath.replace(/^frontend\//, "");

      // global map container
      const wrapper = document.createElement("div");
      wrapper.className = "photo-card-wrapper";
      wrapper.id = `fav-card-${photo.id}`;

      // link to the photo album
      const link = document.createElement("a");
      link.href = `../../album/html/view-album.php?id=${photo.album_id}`;
      link.className = "photo-card";
      link.title = `Album : ${photo.album_title || "Voir l'album"} - ${photo.description || "Photo"}`;

      const img = document.createElement("img");
      img.src = `../../../${cleanPath}`;
      img.alt = photo.description || "Photo Memora";
      img.addEventListener("error", () => {
        img.src = "../../../assets/IMG/assets.svg";
      });

      const heartBtn = document.createElement("button");
      heartBtn.className = "favorite-toggle-btn";
      heartBtn.title = "Retirer des favoris";

      const heartIcon = document.createElement("i");
      heartIcon.className = "fa-solid fa-heart";

      heartBtn.appendChild(heartIcon);

      // intercept the click on the heart to prevent triggering the album link
      heartBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        removeFavorite(photo.id);
      });

      link.appendChild(img);
      wrapper.appendChild(link);
      wrapper.appendChild(heartBtn);
      fragment.appendChild(wrapper);
    });

    favoritesGrid.appendChild(fragment);
  }

  function removeFavorite(photoId) {
    // action call that manages the favorites toggle
    fetch("/backend/handle_favorite_action.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ photo_id: photoId }),
    })
      .then((res) => res.json())
      .then((data) => {
        const cardToRemove = document.getElementById(`fav-card-${photoId}`);
        if (cardToRemove) {
          cardToRemove.style.transition = "all 0.3s ease";
          cardToRemove.style.opacity = "0";
          cardToRemove.style.transform = "scale(0.8)";

          setTimeout(() => {
            cardToRemove.remove();
            // decrement the counter
            if (favoritesCount) {
              const currentCount =
                parseInt(favoritesCount.textContent, 10) || 0;
              favoritesCount.textContent = Math.max(0, currentCount - 1);

              if (currentCount - 1 === 0) {
                loadFavorites();
              }
            }
          }, 300);
        }
      })
      .catch((err) => console.error("Erreur lors de la suppression :", err));
  }
});
