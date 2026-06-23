document.addEventListener("DOMContentLoaded", () => {
  const assetsImgPath = "../../../assets/IMG/assets.svg";

  const phpErrorBridge = document.getElementById("php-error-bridge");
  if (phpErrorBridge) {
    const errorMessage = phpErrorBridge.getAttribute("data-message");
    if (errorMessage && typeof showErrorPopup === "function") {
      showErrorPopup(errorMessage);
    }
  }

  // album creation form
  const createForm = document.getElementById("form-create-album");

  if (createForm) {
    const bannerPreview = document.getElementById("banner-preview");
    const bannerInput = document.getElementById("banner-input");

    if (bannerPreview && bannerInput) {
      bannerPreview.addEventListener("click", () => bannerInput.click());
      bannerInput.addEventListener("change", (e) => {
        const file = e.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = (event) => {
            bannerPreview.style.backgroundImage = `url('${event.target.result}')`;
            bannerPreview.style.backgroundSize = "cover";
            bannerPreview.style.backgroundPosition = "center";
            const span = bannerPreview.querySelector("span");
            if (span) span.style.display = "none";
          };
          reader.readAsDataURL(file);
        }
      });
    }

    // invitatation section with restrictions
    const radioRestreint = document.querySelector('input[value="restreint"]');
    const inviteSection = document.getElementById("invitation-section");
    const allRadios = document.querySelectorAll('input[name="visibility"]');

    if (radioRestreint && inviteSection) {
      allRadios.forEach((radio) => {
        radio.addEventListener("change", () => {
          if (radioRestreint.checked) {
            inviteSection.classList.remove("hidden");
          } else {
            inviteSection.classList.add("hidden");
          }
        });
      });
    }

    const tagsTrigger = document.getElementById("tags-trigger");
    const tagsModal = document.getElementById("tags-modal");
    const tagsCloud = document.getElementById("tags-cloud");
    const selectedTagsDiv = document.getElementById("selected-tags");
    const hiddenTagsInputs = document.getElementById("hidden-tags-inputs");

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

    if (tagsTrigger && tagsModal) {
      // selectable labels in the modal
      dbTags.forEach((tag) => {
        const span = document.createElement("span");
        span.className = "tag-chip";
        span.textContent = tag.name;
        span.setAttribute("data-id", tag.id);
        span.addEventListener("click", () => span.classList.toggle("active"));
        tagsCloud.appendChild(span);
      });

      // prevents the click from propagating if a child tag deletion button is clicked
      tagsTrigger.addEventListener("click", (e) => {
        if (e.target.tagName !== "INPUT") {
          tagsModal.classList.remove("hidden");
        }
      });

      document.getElementById("close-tags").addEventListener("click", () => {
        tagsModal.classList.add("hidden");
        selectedTagsDiv.innerHTML = "";
        hiddenTagsInputs.innerHTML = "";

        const activeChips = tagsCloud.querySelectorAll(".tag-chip.active");
        activeChips.forEach((chip) => {
          const tagId = chip.getAttribute("data-id");
          const tagName = chip.textContent;

          const miniSpan = document.createElement("span");
          miniSpan.className = "tag-mini";
          miniSpan.textContent = tagName;
          selectedTagsDiv.appendChild(miniSpan);

          // hidden field to send the ID
          const hiddenInput = document.createElement("input");
          hiddenInput.type = "hidden";
          hiddenInput.name = "album_tags[]";
          hiddenInput.value = tagId;
          hiddenTagsInputs.appendChild(hiddenInput);
        });
      });
    }

    // upload photos
    const uploadTrigger = document.getElementById("upload-trigger");
    const photoInput = document.getElementById("photo-upload");
    const uploadPreview = document.getElementById("upload-preview");

    let uploadedFilesMap = {};

    if (uploadTrigger && photoInput && uploadPreview) {
      uploadTrigger.addEventListener("click", () => photoInput.click());

      photoInput.addEventListener("change", (e) => {
        const files = e.target.files;
        const maxFileSize = 5 * 1024 * 1024; // 5Mo by photo

        Array.from(files).forEach((file) => {
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

          const uniqueId = Date.now() + Math.random().toString(36).substr(2, 5);

          uploadedFilesMap[uniqueId] = file;

          const reader = new FileReader();
          reader.onload = (event) => {
            const itemCard = document.createElement("div");
            itemCard.className = "preview-item-card";
            itemCard.setAttribute("data-id", uniqueId);

            const imgBubble = document.createElement("div");
            imgBubble.className = "preview-image-bubble";
            imgBubble.style.backgroundImage = `url('${event.target.result}')`;

            // button to delete the sending list
            const btnRemove = document.createElement("button");
            btnRemove.type = "button";
            btnRemove.className = "btn-remove-preview-img";
            btnRemove.innerHTML = '<i class="fa-solid fa-trash"></i>';
            btnRemove.addEventListener("click", () => {
              itemCard.remove();
              delete uploadedFilesMap[uniqueId]; // Supprime aussi le fichier du tableau d'envoi !
            });
            imgBubble.appendChild(btnRemove);

            const fieldsContainer = document.createElement("div");
            fieldsContainer.className = "preview-fields-container";

            // description
            const descLabel = document.createElement("label");
            descLabel.textContent = "Description de la photo";
            const descInput = document.createElement("textarea");
            descInput.name = `photo_descriptions[${uniqueId}]`;
            descInput.placeholder = "Un souvenir particulier, une anecdote...";
            descInput.rows = 2;

            // date
            const dateLabel = document.createElement("label");
            dateLabel.textContent = "Date de prise";
            const dateInput = document.createElement("input");
            dateInput.type = "date";
            dateInput.name = `photo_dates[${uniqueId}]`;

            const tagPhotoLabel = document.createElement("label");
            tagPhotoLabel.textContent = "Étiquettes de la photo";

            const tagsContainer = document.createElement("div");
            tagsContainer.className = "photo-tags-checkboxes-grid";

            dbTags.forEach((t) => {
              const checkboxLabel = document.createElement("label");
              checkboxLabel.className = "photo-tag-checkbox-chip";

              const inputCheckbox = document.createElement("input");
              inputCheckbox.type = "checkbox";
              inputCheckbox.name = `photo_tags[${uniqueId}][]`;
              inputCheckbox.value = t.id;

              const spanText = document.createElement("span");
              spanText.textContent = t.name;

              checkboxLabel.appendChild(inputCheckbox);
              checkboxLabel.appendChild(spanText);
              tagsContainer.appendChild(checkboxLabel);
            });

            fieldsContainer.appendChild(descLabel);
            fieldsContainer.appendChild(descInput);
            fieldsContainer.appendChild(dateLabel);
            fieldsContainer.appendChild(dateInput);
            fieldsContainer.appendChild(tagPhotoLabel);
            fieldsContainer.appendChild(tagsContainer);

            itemCard.appendChild(imgBubble);
            itemCard.appendChild(fieldsContainer);

            uploadPreview.appendChild(itemCard);
          };
          reader.readAsDataURL(file);
        });

        // resets the input value to allow reselecting the same file if needed
        photoInput.value = "";
      });
    }

    createForm.addEventListener("submit", (e) => {
      e.preventDefault();

      const titleInput = createForm.querySelector(".title-input");
      if (!titleInput.value.trim()) {
        if (typeof showErrorPopup === "function") {
          showErrorPopup("Votre album doit obligatoirement posséder un nom");
        } else {
          alert("Votre album doit obligatoirement posséder un nom");
        }
        return;
      }

      const formData = new FormData(createForm);

      formData.delete("photos[]");

      Object.keys(uploadedFilesMap).forEach((uniqueId) => {
        formData.append(`photos[${uniqueId}]`, uploadedFilesMap[uniqueId]);
      });

      fetch("/backend/create_album_action.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => {
          window.location.href =
            "/frontend/pages/album/html/mes-albums.php?success=1";
        })
        .catch((err) => {
          console.error("Erreur lors de la création :", err);
          alert("Une erreur est survenue lors de l'enregistrement de l'album.");
        });
    });
  }

  const albumGrid = document.getElementById("all-albums-grid");
  if (albumGrid) {
    fetch("/backend/get_albums_action.php")
      .then((response) => {
        if (!response.ok) {
          throw new Error("Erreur lors de la récupération des albums");
        }
        return response.json();
      })
      .then((albums) => {
        albumGrid.innerHTML = "";

        if (albums.length === 0) {
          const emptyMessage = document.createElement("p");
          emptyMessage.className = "user-name";
          emptyMessage.style.gridColumn = "1/-1";
          emptyMessage.style.textAlign = "center";
          emptyMessage.style.opacity = "0.6";
          emptyMessage.textContent = "Vous n'avez pas encore créé d'album.";
          albumGrid.appendChild(emptyMessage);
          return;
        }

        // loop on each album
        albums.forEach((album) => {
          const bannerUrl = album.cover_url
            ? `/${album.cover_url}`
            : "/frontend/assets/IMG/default-cover.jpg";

          // album map container
          const card = document.createElement("div");
          card.className = "album-card";

          card.addEventListener("click", () => {
            window.location.href = `view-album.php?id=${album.id}`;
          });

          const imgContainer = document.createElement("div");
          imgContainer.className = "album-img-container";
          imgContainer.style.backgroundImage = `url('${bannerUrl}')`;

          card.appendChild(imgContainer);

          const infoBlock = document.createElement("div");
          infoBlock.className = "album-info";

          // header info
          const infoHeader = document.createElement("div");
          infoHeader.className = "album-info-header";
          infoHeader.style.display = "flex";
          infoHeader.style.justifyContent = "between";
          infoHeader.style.alignItems = "center";

          const albumTitle = document.createElement("h4");
          albumTitle.textContent = album.title;
          albumTitle.style.flex = "1";

          const btnDelete = document.createElement("button");
          btnDelete.className = "btn-delete-album";
          btnDelete.title = "Supprimer l'album";
          btnDelete.setAttribute("data-id", album.id);

          btnDelete.style.background = "none";
          btnDelete.style.border = "none";
          btnDelete.style.color = "var(--brand-red, #ff4d4d)";
          btnDelete.style.cursor = "pointer";
          btnDelete.style.padding = "5px 10px";
          btnDelete.style.fontSize = "1rem";

          const iconDelete = document.createElement("i");
          iconDelete.className = "fa-solid fa-trash";
          btnDelete.appendChild(iconDelete);

          // deleting an album
          btnDelete.addEventListener("click", (e) => {
            e.stopPropagation(); // prevents the album from opening

            if (
              confirm(
                "Êtes-vous sûr de vouloir supprimer cet album et toutes ses photos ?",
              )
            ) {
              fetch("/backend/delete_album_action.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ album_id: album.id }),
              })
                .then((res) => {
                  if (!res.ok) throw new Error("Erreur de suppression");
                  return res.json();
                })
                .then((data) => {
                  if (data.success) {
                    // visually removes the map from the grid without reloading the page
                    card.remove();

                    if (albumGrid.children.length === 0) {
                      const emptyMessage = document.createElement("p");
                      emptyMessage.className = "user-name";
                      emptyMessage.style.gridColumn = "1/-1";
                      emptyMessage.style.textAlign = "center";
                      emptyMessage.style.opacity = "0.6";
                      emptyMessage.textContent =
                        "Vous n'avez pas encore créé d'album.";
                      albumGrid.appendChild(emptyMessage);
                    }
                  }
                })
                .catch((err) => {
                  console.error(err);
                  alert("Impossible de supprimer l'album.");
                });
            }
          });

          infoHeader.appendChild(albumTitle);
          infoHeader.appendChild(btnDelete);

          const albumDesc = document.createElement("p");
          albumDesc.textContent = album.description || "Aucune description";

          infoBlock.appendChild(infoHeader);
          infoBlock.appendChild(albumDesc);

          card.appendChild(infoBlock);

          albumGrid.appendChild(card);
        });
      })
      .catch((error) => {
        console.error("Erreur:", error);
        albumGrid.innerHTML = "";
        const errorMessage = document.createElement("p");
        errorMessage.style.color = "var(--brand-red)";
        errorMessage.style.gridColumn = "1/-1";
        errorMessage.style.textAlign = "center";
        errorMessage.textContent = "Impossible de charger les albums.";
        albumGrid.appendChild(errorMessage);
      });
  }
});
