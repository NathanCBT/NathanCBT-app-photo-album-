document.addEventListener("DOMContentLoaded", () => {
  const bridge = document.getElementById("album-bridge");
  if (!bridge) return;

  const albumId = bridge.getAttribute("data-id");
  const canModify = bridge.getAttribute("data-can-modify") === "1";
  const canComment = bridge.getAttribute("data-can-comment") === "1";

  const photosGrid = document.getElementById("album-photos-grid");
  const photoCountSpan = document.getElementById("photo-count-span");

  let localPhotosArray = [];
  let currentPhotoIndex = 0;
  let activePhotoIdForComments = null;

  const dbTags = [
    { id: 1, name: "Famille" },
    { id: 2, name: "Amis" },
    { id: 3, name: "Couple" },
    { id: 4, name: "Voyage" },
    { id: 5, name: "Sorties" },
    { id: 6, name: "Nature" },
    { id: 7, name: "Paysages" },
    { id: 8, name: "Animaux" },
    { id: 9, name: "Ville" },
    { id: 10, name: "Plage & Mer" },
    { id: 11, name: "Montagne" },
    { id: 12, name: "Musées & Châteaux" },
    { id: 13, name: "Parc d'attractions" },
    { id: 14, name: "Voitures & Véhicules" },
    { id: 15, name: "Soirées & Événements" },
  ];

  const modal = document.getElementById("photo-detail-modal");
  const modalImg = document.getElementById("modal-target-img");
  const modalDate = document.getElementById("modal-photo-date");
  const modalDesc = document.getElementById("modal-photo-description");
  const modalTagsContainer = document.getElementById(
    "modal-photo-tags-container",
  );
  const commentFormContainer = document.getElementById(
    "comment-form-container",
  );
  const btnFav = document.getElementById("btn-favorite-photo");

  if (!canComment && commentFormContainer) {
    commentFormContainer.style.display = "none";
  }

  function loadAlbumPhotosGrid() {
    fetch(`/backend/get_album_photos_action.php?album_id=${albumId}`)
      .then((res) => {
        if (!res.ok) throw new Error("Impossible de charger les photos");
        return res.json();
      })
      .then((photos) => {
        localPhotosArray = photos;
        renderPhotosGrid();
      })
      .catch((err) => console.error(err));
  }

  function renderPhotosGrid() {
    if (photoCountSpan) {
      photoCountSpan.textContent = `${localPhotosArray.length} photo${localPhotosArray.length > 1 ? "s" : ""}`;
    }

    if (!photosGrid) return;

    photosGrid.innerHTML = "";

    if (localPhotosArray.length === 0) {
      const noPhoto = document.createElement("p");
      noPhoto.className = "no-photos-msg";
      noPhoto.style.textAlign = "center";
      noPhoto.style.gridColumn = "1/-1";
      noPhoto.style.opacity = "0.5";
      noPhoto.textContent =
        "Cet album ne contient aucune photo pour le moment.";
      photosGrid.appendChild(noPhoto);
      return;
    }

    localPhotosArray.forEach((photo, index) => {
      const photoCard = document.createElement("div");
      photoCard.className = "photo-grid-card";
      photoCard.style.position = "relative";
      photoCard.style.cursor = "pointer";

      const img = document.createElement("img");
      img.src = `/${photo.file_path}`;
      img.alt = photo.description || "Photo souvenir";
      img.loading = "lazy";
      photoCard.appendChild(img);

      if (canModify) {
        const btnDeleteDirect = document.createElement("button");
        btnDeleteDirect.type = "button";
        btnDeleteDirect.className = "btn-delete-photo-direct";
        btnDeleteDirect.title = "Supprimer la photo";

        const iconTrash = document.createElement("i");
        iconTrash.className = "fa-solid fa-trash";
        btnDeleteDirect.appendChild(iconTrash);

        btnDeleteDirect.addEventListener("click", (e) => {
          e.stopPropagation();
          executePhotoDeletion(photo.id);
        });

        photoCard.appendChild(btnDeleteDirect);
      }

      photoCard.addEventListener("click", () => {
        const dynamicIndex = localPhotosArray.findIndex(
          (p) => p.id === photo.id,
        );
        if (dynamicIndex !== -1) {
          openPhotoModal(dynamicIndex);
        }
      });

      photosGrid.appendChild(photoCard);
    });
  }

  function openPhotoModal(index) {
    currentPhotoIndex = index;
    const photo = localPhotosArray[currentPhotoIndex];
    if (!photo) return;

    modalImg.src = `/${photo.file_path}`;
    modalImg.setAttribute("data-photo-id", photo.id);

    if (photo.shot_at) {
      const dateOptions = { day: "numeric", month: "long", year: "numeric" };
      modalDate.textContent = new Date(photo.shot_at).toLocaleDateString(
        "fr-FR",
        dateOptions,
      );
    } else {
      modalDate.textContent = "Date inconnue";
    }

    modalDesc.textContent =
      photo.description || "Aucune description pour cette photo.";

    modalTagsContainer.innerHTML = "";

    if (photo.tags && photo.tags.length > 0) {
      photo.tags.forEach((tag) => {
        const tagChip = document.createElement("span");
        tagChip.className = "tag-mini-chip";
        tagChip.textContent = tag.name;
        tagChip.style.border = "1px solid var(--brand-red, #ff4d4d)";
        tagChip.style.padding = "4px 12px";
        tagChip.style.borderRadius = "20px";
        tagChip.style.marginRight = "8px";
        tagChip.style.fontSize = "0.85rem";
        modalTagsContainer.appendChild(tagChip);
      });
    }

    if (btnFav) {
      const iconFav = btnFav.querySelector("i");
      iconFav.className = "fa-regular fa-heart";
      btnFav.style.color = "#ffffff";

      fetch(`/backend/handle_favorite_action.php?photo_id=${photo.id}`)
        .then((res) => res.json())
        .then((data) => {
          if (data.is_favorite) {
            iconFav.className = "fa-solid fa-heart";
            btnFav.style.color = "var(--brand-red, #ff4d4d)";
          }
        })
        .catch((err) => console.error("Erreur favoris:", err));
    }

    loadPhotoComments(photo.id);
    modal.classList.remove("hidden");
  }

  function executePhotoDeletion(photoId) {
    if (
      !confirm(
        "Êtes-vous sûr de vouloir supprimer définitivement cette photo ?",
      )
    )
      return;

    fetch("/backend/delete_photo_action.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ photo_id: photoId }),
    })
      .then((res) => {
        if (!res.ok) throw new Error("Erreur lors de la suppression");
        return res.json();
      })
      .then((data) => {
        if (data.success) {
          localPhotosArray = localPhotosArray.filter((p) => p.id !== photoId);
          renderPhotosGrid();
        }
      })
      .catch((err) => {
        console.error(err);
        alert("Impossible de supprimer la photo.");
      });
  }

  const prevBtn = document.querySelector(".prev-arrow");
  const nextBtn = document.querySelector(".next-arrow");

  if (prevBtn && nextBtn) {
    prevBtn.addEventListener("click", () => {
      if (localPhotosArray.length === 0) return;
      let newIndex = currentPhotoIndex - 1;
      if (newIndex < 0) newIndex = localPhotosArray.length - 1;
      openPhotoModal(newIndex);
    });

    nextBtn.addEventListener("click", () => {
      if (localPhotosArray.length === 0) return;
      let newIndex = currentPhotoIndex + 1;
      if (newIndex >= localPhotosArray.length) newIndex = 0;
      openPhotoModal(newIndex);
    });
  }

  const closeBtn = document.querySelector(".btn-close-modal");
  if (closeBtn) {
    closeBtn.addEventListener("click", () => {
      modal.classList.add("hidden");
    });
  }

  function loadPhotoComments(photoId) {
    const commentsList = document.getElementById("modal-comments-list");
    if (!commentsList) return;

    activePhotoIdForComments = photoId;

    commentsList.innerHTML = "";

    fetch(`/backend/handle_comments_action.php?photo_id=${photoId}`)
      .then((res) => res.json())
      .then((comments) => {
        // if the user has changed their photo in the meantime the result is ignored
        if (activePhotoIdForComments !== photoId) return;

        // clear again to ensure that another fetch hasn't written at the same time - avoid the comment duplication bug
        commentsList.innerHTML = "";

        if (!comments || comments.length === 0) {
          const placeholder = document.createElement("p");
          placeholder.style.opacity = "0.5";
          placeholder.style.fontStyle = "italic";
          placeholder.className = "no-comments-msg";
          placeholder.textContent = "Aucun commentaire pour le moment.";
          commentsList.appendChild(placeholder);
          return;
        }

        comments.forEach((comment) => {
          const commentItem = document.createElement("div");
          commentItem.className = "comment-item";

          const avatar = document.createElement("div");
          avatar.className = "comment-avatar";
          const avatarUrl = comment.avatar_url
            ? `/${comment.avatar_url}`
            : "/frontend/assets/IMG/default-avatar.svg";
          avatar.style.backgroundImage = `url('${avatarUrl}')`;

          const contentDiv = document.createElement("div");
          contentDiv.className = "comment-content";

          const author = document.createElement("p");
          author.className = "comment-author";
          author.textContent = comment.username;

          const text = document.createElement("p");
          text.className = "comment-text";
          text.textContent = comment.content;

          contentDiv.appendChild(author);
          contentDiv.appendChild(text);
          commentItem.appendChild(avatar);
          commentItem.appendChild(contentDiv);
          commentsList.appendChild(commentItem);
        });

        commentsList.scrollTop = commentsList.scrollHeight;
      })
      .catch((err) => console.error("Erreur commentaires:", err));
  }

  const btnSubmitComment = document.getElementById("btn-submit-comment");
  const inputNewComment = document.getElementById("input-new-comment");

  if (btnSubmitComment && inputNewComment) {
    const submitCommentAction = (e) => {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }

      if (btnSubmitComment.disabled) return;

      const text = inputNewComment.value.trim();
      if (!text) return;

      const currentPhoto = localPhotosArray[currentPhotoIndex];
      if (!currentPhoto) return;

      btnSubmitComment.disabled = true;

      fetch("/backend/handle_comments_action.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          photo_id: currentPhoto.id,
          content: text,
        }),
      })
        .then((res) => {
          if (!res.ok) throw new Error("Erreur lors de l'envoi");
          return res.json();
        })
        .then((data) => {
          if (data.success) {
            inputNewComment.value = "";
            // reload only if you are still on the same photo
            if (activePhotoIdForComments === currentPhoto.id) {
              loadPhotoComments(currentPhoto.id);
            }
          }
        })
        .catch((err) => {
          console.error(err);
          alert("Impossible d'envoyer le commentaire.");
        })
        .finally(() => {
          btnSubmitComment.disabled = false;
        });
    };

    const newBtn = btnSubmitComment.cloneNode(true);
    btnSubmitComment.parentNode.replaceChild(newBtn, btnSubmitComment);
    newBtn.id = "btn-submit-comment"; // Conserver l'ID d'origine sur le clone

    newBtn.addEventListener("click", submitCommentAction);

    inputNewComment.onkeydown = (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        submitCommentAction(e);
      }
    };
  }

  if (btnFav) {
    btnFav.addEventListener("click", () => {
      const currentPhoto = localPhotosArray[currentPhotoIndex];
      if (!currentPhoto) return;

      fetch("/backend/handle_favorite_action.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ photo_id: currentPhoto.id }),
      })
        .then((res) => res.json())
        .then((data) => {
          const iconFav = btnFav.querySelector("i");
          if (data.is_favorite) {
            iconFav.className = "fa-solid fa-heart";
            btnFav.style.color = "var(--brand-red, #ff4d4d)";
          } else {
            iconFav.className = "fa-regular fa-heart";
            btnFav.style.color = "#ffffff";
          }
        })
        .catch((err) => console.error("Erreur favoris:", err));
    });
  }

  loadAlbumPhotosGrid();

  // section upload
  const btnAddPhotosTrigger = document.getElementById("btn-add-photos-trigger");
  const uploadModal = document.getElementById("upload-photos-modal");
  const btnCloseUploadModal = document.getElementById("btn-close-upload-modal");
  const btnCancelUpload = document.getElementById("btn-cancel-upload");
  const dropzoneArea = document.getElementById("dropzone-area");
  const inputHiddenFile = document.getElementById("input-hidden-file");
  const uploadPreviewContainer = document.getElementById(
    "upload-preview-container",
  );
  const formUploadPhotosGallery = document.getElementById(
    "form-upload-photos-gallery",
  );

  let selectedFilesArray = [];

  if (btnAddPhotosTrigger) {
    btnAddPhotosTrigger.addEventListener("click", () => {
      selectedFilesArray = [];
      if (uploadPreviewContainer) {
        uploadPreviewContainer.innerHTML = "";
      }
      if (uploadModal) uploadModal.classList.remove("hidden");
    });
  }

  const closeUploadModalFunc = () => {
    if (uploadModal) uploadModal.classList.add("hidden");
  };
  if (btnCloseUploadModal)
    btnCloseUploadModal.addEventListener("click", closeUploadModalFunc);
  if (btnCancelUpload)
    btnCancelUpload.addEventListener("click", closeUploadModalFunc);

  if (dropzoneArea && inputHiddenFile) {
    dropzoneArea.addEventListener("click", (e) => {
      // if the click comes directly from the input itself, then nothing is done
      if (e.target === inputHiddenFile) return;

      e.preventDefault();
      e.stopPropagation();
      inputHiddenFile.click();
    });

    dropzoneArea.addEventListener("dragover", (e) => {
      e.preventDefault();
      dropzoneArea.classList.add("dragover");
    });

    dropzoneArea.addEventListener("dragleave", () => {
      dropzoneArea.classList.remove("dragover");
    });

    dropzoneArea.addEventListener("drop", (e) => {
      e.preventDefault();
      dropzoneArea.classList.remove("dragover");
      if (e.dataTransfer.files.length > 0) {
        handleFileSelection(e.dataTransfer.files);
      }
    });

    inputHiddenFile.addEventListener("change", (e) => {
      if (e.target.files.length > 0) {
        handleFileSelection(e.target.files);
        inputHiddenFile.value = "";
      }
    });
  }

  function handleFileSelection(files) {
    const maxFileSize = 5 * 1024 * 1024; // 5Mo

    Array.from(files).forEach((file) => {
      if (!file.type.startsWith("image/")) return;

      if (file.size > maxFileSize) {
        if (typeof showErrorPopup === "function") {
          showErrorPopup(
            `Le fichier ${file.name} est trop lourd (Maximum 5Mo).`,
          );
        } else {
          alert(`Le fichier ${file.name} est trop lourd (Maximum 5Mo).`);
        }
        return;
      }

      const fileId = "file_" + Math.random().toString(36).substr(2, 9);
      selectedFilesArray.push({ id: fileId, file: file });

      const reader = new FileReader();
      reader.onload = (e) => {
        const card = document.createElement("div");
        card.className = "preview-item-card";
        card.id = `card-${fileId}`;

        const imgBubble = document.createElement("div");
        imgBubble.className = "preview-image-bubble";
        imgBubble.style.backgroundImage = `url('${e.target.result}')`;

        const btnRemove = document.createElement("button");
        btnRemove.type = "button";
        btnRemove.className = "btn-remove-preview-img";

        const iconTrash = document.createElement("i");
        iconTrash.className = "fa-solid fa-trash";
        btnRemove.appendChild(iconTrash);

        btnRemove.addEventListener("click", () => {
          selectedFilesArray = selectedFilesArray.filter(
            (f) => f.id !== fileId,
          );
          card.remove();
        });

        imgBubble.appendChild(btnRemove);
        card.appendChild(imgBubble);

        const fieldsContainer = document.createElement("div");
        fieldsContainer.className = "preview-fields-container";

        // description
        const labelDesc = document.createElement("label");
        labelDesc.textContent = "Description de la photo";
        const inputDesc = document.createElement("textarea");
        inputDesc.placeholder = "Un souvenir particulier, une anecdote...";
        inputDesc.rows = 2;
        inputDesc.id = `desc-${fileId}`;
        fieldsContainer.appendChild(labelDesc);
        fieldsContainer.appendChild(inputDesc);

        // date
        const labelDate = document.createElement("label");
        labelDate.textContent = "Date de prise";
        const inputDate = document.createElement("input");
        inputDate.type = "date";
        inputDate.id = `date-${fileId}`;
        inputDate.value = new Date().toISOString().substring(0, 10);
        fieldsContainer.appendChild(labelDate);
        fieldsContainer.appendChild(inputDate);

        // photo tags
        const labelTags = document.createElement("label");
        labelTags.textContent = "Étiquettes de la photo";
        fieldsContainer.appendChild(labelTags);

        const tagsGrid = document.createElement("div");
        tagsGrid.className = "photo-tags-checkboxes-grid";

        dbTags.forEach((tag) => {
          const chipLabel = document.createElement("label");
          chipLabel.className = "photo-tag-checkbox-chip";

          const checkbox = document.createElement("input");
          checkbox.type = "checkbox";
          checkbox.name = `tags-${fileId}[]`;
          checkbox.value = tag.id;

          const span = document.createElement("span");
          span.textContent = tag.name;

          chipLabel.appendChild(checkbox);
          chipLabel.appendChild(span);
          tagsGrid.appendChild(chipLabel);
        });
        fieldsContainer.appendChild(tagsGrid);

        card.appendChild(fieldsContainer);
        if (uploadPreviewContainer) {
          uploadPreviewContainer.appendChild(card);
        }
      };
      reader.readAsDataURL(file);
    });
  }

  if (formUploadPhotosGallery) {
    formUploadPhotosGallery.addEventListener("submit", (e) => {
      e.preventDefault();

      if (selectedFilesArray.length === 0) {
        alert("Veuillez sélectionner au moins une photo.");
        return;
      }

      const btnSubmit = document.getElementById("btn-submit-upload-gallery");
      if (btnSubmit) {
        btnSubmit.disabled = true;
        btnSubmit.textContent = "Importation en cours...";
      }

      const formData = new FormData();
      formData.append("album_id", albumId);

      selectedFilesArray.forEach((item, index) => {
        formData.append(`photos[${index}]`, item.file);

        const descVal = document.getElementById(`desc-${item.id}`)?.value || "";
        const dateVal = document.getElementById(`date-${item.id}`)?.value || "";
        formData.append(`descriptions[${index}]`, descVal);
        formData.append(`dates[${index}]`, dateVal);

        const checkedCheckboxes = document.querySelectorAll(
          `input[name="tags-${item.id}[]"]:checked`,
        );
        const tagIds = Array.from(checkedCheckboxes).map((cb) => cb.value);
        formData.append(`tags[${index}]`, JSON.stringify(tagIds));
      });

      fetch("/backend/add_photos_action.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => {
          if (!res.ok) throw new Error("Erreur lors de l'importation");
          return res.json();
        })
        .then((data) => {
          if (data.success) {
            closeUploadModalFunc();
            loadAlbumPhotosGrid();
          } else {
            alert(data.error || "Une erreur est survenue.");
          }
        })
        .catch((err) => {
          console.error(err);
          alert("Erreur réseau ou fichier trop volumineux.");
        })
        .finally(() => {
          if (btnSubmit) {
            btnSubmit.disabled = false;
            btnSubmit.textContent = "Importer les photos";
          }
        });
    });
  }

  // display album tags
  const albumTagsContainer = document.getElementById("album-tags-container");
  if (albumTagsContainer) {
    albumTagsContainer.innerHTML = "";

    try {
      const rawAlbumTags = bridge.getAttribute("data-album-tags");
      const albumTagsArray = rawAlbumTags ? JSON.parse(rawAlbumTags) : [];

      if (albumTagsArray && albumTagsArray.length > 0) {
        albumTagsArray.forEach((tag) => {
          const tagChip = document.createElement("span");
          tagChip.className = "tag-mini-chip";
          tagChip.textContent = tag.name;

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
      }
    } catch (e) {
      console.error("Erreur lors du traitement des étiquettes de l'album :", e);
    }
  }
});
