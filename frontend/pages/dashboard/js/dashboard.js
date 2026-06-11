document.addEventListener("DOMContentLoaded", () => {
  const assetsImgPath = "../../../assets/IMG/assets.svg";

  const recentAlbumsContainer = document.getElementById(
    "recent-albums-container",
  );
  if (recentAlbumsContainer) {
    const fakeAlbums = [
      { title: "Voyage en Italie", photosCount: 24 },
      { title: "Voyage en Italie", photosCount: 24 },
      { title: "Voyage en Italie", photosCount: 24 },
      { title: "Voyage en ...", photosCount: 24 },
    ];

    fakeAlbums.forEach((album) => {
      const albumCard = document.createElement("div");
      albumCard.className = "dashboard-album-card";

      albumCard.innerHTML = `
                <div class="album-thumbnail-box" style="background-image: url('${assetsImgPath}');"></div>
                <p class="album-title-text">${album.title}</p>
                <p class="album-counter-text">${album.photosCount} photos</p>
            `;

      albumCard.addEventListener("click", () => {
        window.location.href = "../../album/html/mes-albums.html";
      });

      recentAlbumsContainer.appendChild(albumCard);
    });
  }

  const favoritesPhotosContainer = document.getElementById(
    "favorites-photos-container",
  );
  if (favoritesPhotosContainer) {
    const totalPhotosToSimulate = 5;

    for (let i = 0; i < totalPhotosToSimulate; i++) {
      const photoItem = document.createElement("div");
      photoItem.className = "favorite-photo-item";
      photoItem.style.backgroundImage = `url('${assetsImgPath}')`;

      favoritesPhotosContainer.appendChild(photoItem);
    }
  }
});
