document.addEventListener("DOMContentLoaded", () => {
  function copyToClipboardFallback(text, successCallback, errorCallback) {
    const textArea = document.createElement("textarea");
    textArea.value = text;

    // avoid page scrolling during injection
    textArea.style.top = "0";
    textArea.style.left = "0";
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
      const successful = document.execCommand("copy");
      if (successful) {
        successCallback();
      } else {
        errorCallback("execCommand a échoué");
      }
    } catch (err) {
      errorCallback(err);
    }

    document.body.removeChild(textArea);
  }

  const btnShareAlbum = document.querySelector(".btn-share-album");

  if (btnShareAlbum) {
    btnShareAlbum.addEventListener("click", () => {
      const albumUrl = window.location.href;

      const handleSuccess = () => {
        const originalContent = btnShareAlbum.innerHTML;
        btnShareAlbum.innerHTML = `<i class="fa-solid fa-check" style="color: #2ec4b6;"></i>`;
        alert("Lien de l'album copié dans le presse-papier !");
        setTimeout(() => {
          btnShareAlbum.innerHTML = originalContent;
        }, 2000);
      };

      const handleFailure = (err) => {
        console.error("Erreur lors de la copie du lien de l'album : ", err);
        alert("Impossible de copier le lien automatiquement.");
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
          .writeText(albumUrl)
          .then(handleSuccess)
          .catch(handleFailure);
      } else {
        copyToClipboardFallback(albumUrl, handleSuccess, handleFailure);
      }
    });
  }

  const btnCopyPhotoLink = document.getElementById("btn-copy-link");
  const modalImg = document.getElementById("modal-target-img");

  if (btnCopyPhotoLink && modalImg) {
    btnCopyPhotoLink.addEventListener("click", () => {
      const photoId = modalImg.getAttribute("data-photo-id");
      if (!photoId) return;

      const currentUrl = new URL(window.location.href);
      currentUrl.searchParams.set("photo_id", photoId);
      const photoAlbumUrl = currentUrl.toString();

      const handleSuccess = () => {
        const icon = btnCopyPhotoLink.querySelector("i");
        const originalColor = btnCopyPhotoLink.style.color;

        if (icon) icon.className = "fa-solid fa-check";
        btnCopyPhotoLink.style.color = "#2ec4b6";

        alert("Lien de la photo (dans cet album) copié !");

        setTimeout(() => {
          if (icon) icon.className = "fa-solid fa-link";
          btnCopyPhotoLink.style.color = originalColor || "#ffffff";
        }, 2000);
      };

      const handleFailure = (err) => {
        console.error("Erreur lors de la copie du lien de la photo : ", err);
        alert("Impossible de copier le lien automatiquement.");
      };

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
          .writeText(photoAlbumUrl)
          .then(handleSuccess)
          .catch(handleFailure);
      } else {
        copyToClipboardFallback(photoAlbumUrl, handleSuccess, handleFailure);
      }
    });
  }
});
