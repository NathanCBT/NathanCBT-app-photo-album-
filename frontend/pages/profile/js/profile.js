document.addEventListener("DOMContentLoaded", () => {
  // simulation album injection
  const userGrid = document.getElementById("user-albums-grid");
  const publicGrid = document.getElementById("public-albums-grid");

  const totalAlbumsToSimulate = 4;
  const assetsImgPath = "../../../assets/IMG/assets.svg";

  function injectSimulatedAlbums(targetGrid) {
    if (!targetGrid) return;
    console.log(`injection d'albums.`);

    for (let i = 0; i < totalAlbumsToSimulate; i++) {
      const card = document.createElement("div");
      card.className = "album-card-simulated";
      card.style.backgroundImage = `url('${assetsImgPath}')`;
      targetGrid.appendChild(card);
    }
  }

  injectSimulatedAlbums(userGrid);
  injectSimulatedAlbums(publicGrid);

  const followBtn = document.getElementById("follow-toggle-btn");
  const followersCounter = document.getElementById("followers-count");

  if (followBtn && followersCounter) {
    let isFollowing = false;
    let currentFollowers = parseInt(followersCounter.textContent, 10) || 72;

    followBtn.addEventListener("click", () => {
      isFollowing = !isFollowing;

      if (isFollowing) {
        currentFollowers++;
        followBtn.textContent = "Abonné(e)";
        followBtn.classList.add("following");
      } else {
        currentFollowers--;
        followBtn.textContent = "Suivre";
        followBtn.classList.remove("following");
      }
      followersCounter.textContent = currentFollowers;
    });
  }
});
