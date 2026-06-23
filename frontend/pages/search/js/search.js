document.addEventListener("DOMContentLoaded", () => {
  const filterToggleBtn = document.getElementById("btn-submit-search");
  const searchSidebar = document.querySelector(".search-sidebar");
  const filterForm = document.getElementById("form-search-filters");
  const searchInput = document.getElementById("global-search-input");

  const resultsCount = document.getElementById("results-count");
  const resultsGrid =
    document.getElementById("search-results-grid") ||
    document.querySelector(".photos-grid");

  // opening/closing (toggle) the sidebar
  if (filterToggleBtn && searchSidebar) {
    filterToggleBtn.addEventListener("click", (e) => {
      e.stopPropagation(); // prevents immediate closure if the button is clicked
      searchSidebar.classList.toggle("active");
    });
  }

  // close the sidebar if you click outside
  document.addEventListener("click", (e) => {
    if (searchSidebar && searchSidebar.classList.contains("active")) {
      if (!searchSidebar.contains(e.target) && e.target !== filterToggleBtn) {
        searchSidebar.classList.remove("active");
      }
    }
  });

  // managing the submission of the filter form
  if (filterForm) {
    filterForm.addEventListener("submit", (e) => {
      e.preventDefault();
      triggerSearch();
    });
  }

  // search initiated
  let debounceTimer;
  if (searchInput) {
    searchInput.addEventListener("input", () => {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        triggerSearch();
      }, 400);
    });
  }

  function triggerSearch() {
    if (!searchInput) return;

    const query = searchInput.value.trim();
    const checkedTags = [];
    document
      .querySelectorAll('input[name="tags[]"]:checked')
      .forEach((checkbox) => {
        checkedTags.push(checkbox.value);
      });

    const dateFrom = document.getElementById("filter-date-from")?.value || "";
    const dateTo = document.getElementById("filter-date-to")?.value || "";
    const albumScopeElement = document.querySelector(
      'input[name="album_scope"]:checked',
    );
    const albumScope = albumScopeElement ? albumScopeElement.value : "owned";

    const searchPayload = {
      search: query,
      tags: checkedTags,
      date_from: dateFrom,
      date_to: dateTo,
      scope: albumScope,
    };

    fetch("/backend/search_photos_action.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(searchPayload),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          renderResults(data.results);
        } else {
          console.error("Erreur renvoyée par le serveur :", data.message);
        }
      })
      .catch((err) => console.error("Erreur lors de la requête Fetch :", err));
  }

  function renderResults(photos) {
    if (resultsCount) {
      resultsCount.textContent = photos.length;
    }

    if (!resultsGrid) {
      console.error(
        "Erreur : L'élément conteneur de la grille de résultats est introuvable.",
      );
      return;
    }

    resultsGrid.textContent = "";

    if (photos.length === 0) {
      const emptyDiv = document.createElement("div");
      emptyDiv.className = "empty-search-state";

      const emptyPara = document.createElement("p");
      emptyPara.textContent =
        "Aucune photo ne correspond à vos critères de recherche.";

      emptyDiv.appendChild(emptyPara);
      resultsGrid.appendChild(emptyDiv);
      return;
    }

    const fragment = document.createDocumentFragment();

    // generation of photo cards
    photos.forEach((photo) => {
      const rawPath = photo.file_path || "";
      const albumId = photo.album_id;

      if (!rawPath) return;

      const cleanPath = rawPath.replace(/^frontend\//, "");

      const link = document.createElement("a");
      link.href = `../../album/html/view-album.php?id=${albumId}`;
      link.className = "photo-card-link";

      const card = document.createElement("div");
      card.className = "photo-card";

      const albumTitle = photo.album_title || "Voir l'album";
      const photoDesc = photo.description || "Photo";
      card.title = `Album : ${albumTitle} - ${photoDesc}`;

      const img = document.createElement("img");
      img.src = `../../../${cleanPath}`;
      img.alt = photoDesc;

      img.addEventListener("error", () => {
        img.src = "../../../assets/IMG/assets.svg";
      });

      card.appendChild(img);
      link.appendChild(card);
      fragment.appendChild(link);
    });

    resultsGrid.appendChild(fragment);
  }

  /* triggerSearch(); */
});
