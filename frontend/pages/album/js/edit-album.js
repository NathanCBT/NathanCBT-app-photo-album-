document.addEventListener("DOMContentLoaded", () => {
  const btnEditAlbumDetails = document.querySelector(".btn-edit-album-details");
  const editAlbumModal = document.getElementById("edit-album-modal");
  const btnCloseEditModal = document.getElementById("btn-close-edit-modal");
  const btnCancelEdit = document.getElementById("btn-cancel-edit");
  const formEditAlbum = document.getElementById("form-edit-album");

  const editTagsModal = document.getElementById("edit-tags-modal");
  const btnCloseEditTags = document.getElementById("btn-close-edit-tags");
  const editTagsCloud = document.getElementById("edit-tags-cloud");
  const editSelectedTagsContainer =
    document.getElementById("edit-selected-tags");
  const editHiddenTagsInputs = document.getElementById(
    "edit-hidden-tags-inputs",
  );

  const editTagsPickerZone = document.querySelector(".edit-tags-picker-zone");

  const albumBridge = document.getElementById("album-bridge");
  let currentAlbumTags = [];
  if (albumBridge && albumBridge.getAttribute("data-album-tags")) {
    try {
      currentAlbumTags = JSON.parse(
        albumBridge.getAttribute("data-album-tags"),
      );
    } catch (e) {
      console.error(e);
    }
  }

  let selectedTagIds = currentAlbumTags.map((t) => parseInt(t.id));

  if (!btnEditAlbumDetails || !editAlbumModal) return;

  function renderSelectedTags() {
    if (!editSelectedTagsContainer || !editHiddenTagsInputs) return;
    editSelectedTagsContainer.innerHTML = "";
    editHiddenTagsInputs.innerHTML = "";

    if (!editTagsCloud) return;

    const tagItems = editTagsCloud.querySelectorAll(".tag-item");
    tagItems.forEach((item) => {
      const id = parseInt(item.getAttribute("data-id"));
      if (selectedTagIds.includes(id)) {
        item.classList.add("selected");

        const badge = document.createElement("span");
        badge.className = "selected-tag-badge";
        badge.textContent = item.textContent;
        editSelectedTagsContainer.appendChild(badge);

        const hiddenInput = document.createElement("input");
        hiddenInput.type = "hidden";
        hiddenInput.name = "tags[]";
        hiddenInput.value = id;
        editHiddenTagsInputs.appendChild(hiddenInput);
      } else {
        item.classList.remove("selected");
      }
    });
  }

  btnEditAlbumDetails.addEventListener("click", () => {
    editAlbumModal.classList.remove("hidden");
    renderSelectedTags();
  });

  if (editTagsPickerZone && editTagsModal) {
    editTagsPickerZone.addEventListener("click", (e) => {
      if (e.target.classList.contains("selected-tag-badge")) return;

      editTagsModal.classList.remove("hidden");
      editTagsModal.style.zIndex = "2000";
    });
  }

  if (editTagsCloud) {
    editTagsCloud.addEventListener("click", (e) => {
      if (e.target.classList.contains("tag-item")) {
        const tagId = parseInt(e.target.getAttribute("data-id"));
        if (selectedTagIds.includes(tagId)) {
          selectedTagIds = selectedTagIds.filter((id) => id !== tagId);
        } else {
          selectedTagIds.push(tagId);
        }
        e.target.classList.toggle("selected");
      }
    });
  }

  if (btnCloseEditTags) {
    btnCloseEditTags.addEventListener("click", () => {
      editTagsModal.classList.add("hidden");
      renderSelectedTags();
    });
  }

  const closeEditModal = () => {
    editAlbumModal.classList.add("hidden");
    if (formEditAlbum) formEditAlbum.reset();
    selectedTagIds = currentAlbumTags.map((t) => parseInt(t.id));
    renderSelectedTags();
  };

  if (btnCloseEditModal)
    btnCloseEditModal.addEventListener("click", closeEditModal);
  if (btnCancelEdit) btnCancelEdit.addEventListener("click", closeEditModal);

  if (formEditAlbum) {
    formEditAlbum.addEventListener("submit", (e) => {
      e.preventDefault();

      const btnSubmit = document.getElementById("btn-submit-edit-album");
      if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.textContent = "Modification en cours...";
      }

      const formData = new FormData(formEditAlbum);

      fetch("/backend/edit_album_action.php", {
        method: "POST",
        body: formData,
      })
        .then((res) =>
          res.text().then((text) => {
            try {
              const json = JSON.parse(text);
              return { ok: res.ok, status: res.status, data: json };
            } catch (err) {
              throw new Error(
                `Le serveur a renvoyé une réponse invalide. Contenu : ${text}`,
              );
            }
          }),
        )
        .then((response) => {
          if (!response.ok) {
            throw new Error(
              response.data.error || `Erreur serveur (Code ${response.status})`,
            );
          }

          const result = response.data;

          // update the title, description and banner
          const mainTitleView = document.querySelector(".album-view-title");
          if (mainTitleView) mainTitleView.textContent = result.data.title;

          const mainDescView = document.querySelector(
            ".album-view-description",
          );
          if (mainDescView) {
            mainDescView.innerHTML = result.data.description
              ? result.data.description.replace(/\n/g, "<br>")
              : "Aucune description pour cet album.";
          }

          const bannerHero = document.querySelector(".album-banner-hero");
          if (bannerHero && result.data.cover_url) {
            bannerHero.style.backgroundImage = `url('/${result.data.cover_url}')`;
          }

          // update the display of tags on the album's main page
          const albumTagsContainer = document.getElementById(
            "album-tags-container",
          );
          if (albumTagsContainer && result.data.tags) {
            albumTagsContainer.innerHTML = "";

            result.data.tags.forEach((t) => {
              const tagChip = document.createElement("span");
              tagChip.className = "tag-mini-chip";
              tagChip.textContent = t.name;

              tagChip.style.border = "1px solid var(--brand-red, #ff4d4d)";
              tagChip.style.padding = "4px 12px";
              tagChip.style.borderRadius = "20px";
              tagChip.style.marginRight = "8px";
              tagChip.style.fontSize = "0.85rem";
              tagChip.style.color = "var(--white, #ffffff)";
              tagChip.style.display = "inline-block";
              tagChip.style.marginTop = "5px";

              albumTagsContainer.appendChild(tagChip);
            });

            albumBridge.setAttribute(
              "data-album-tags",
              JSON.stringify(result.data.tags),
            );
            currentAlbumTags = result.data.tags;
          }

          closeEditModal();
        })
        .catch((err) => {
          console.error(err);
          alert(err.message);
        })
        .finally(() => {
          if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.textContent = "Enregistrer les modifications";
          }
        });
    });
  }
});
