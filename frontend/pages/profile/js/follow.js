document.addEventListener("DOMContentLoaded", () => {
  const followersModal = document.getElementById("followers-modal");
  const followingModal = document.getElementById("following-modal");

  const followersTrigger = document.getElementById("stat-followers-trigger");
  const followingTrigger = document.getElementById("stat-following-trigger");

  const btnCloseFollowers = document.getElementById("btn-close-followers");
  const btnCloseFollowing = document.getElementById("btn-close-following");

  const btnToggleFollow = document.getElementById("btn-toggle-follow");

  const followersContainer = document.getElementById(
    "followers-list-container",
  );
  const followingContainer = document.getElementById(
    "following-list-container",
  );

  // open follower modal
  if (followersTrigger) {
    followersTrigger.addEventListener("click", () => {
      followersModal.classList.remove("hidden");
      loadFollowDetails("followers", followersContainer);
    });
  }

  // open following modal
  if (followingTrigger) {
    followingTrigger.addEventListener("click", () => {
      followingModal.classList.remove("hidden");
      loadFollowDetails("following", followingContainer);
    });
  }

  // modal lcosures
  if (btnCloseFollowers) {
    btnCloseFollowers.addEventListener("click", () =>
      followersModal.classList.add("hidden"),
    );
  }
  if (btnCloseFollowing) {
    btnCloseFollowing.addEventListener("click", () =>
      followingModal.classList.add("hidden"),
    );
  }

  // ajax function to retrieve the list of people
  function loadFollowDetails(type, container) {
    container.textContent = "";
    const loadingMsg = document.createElement("p");
    loadingMsg.className = "loading-text";
    loadingMsg.textContent = "Chargement...";
    container.appendChild(loadingMsg);

    // we retrieve the id of the visited profile from the url
    const urlParams = new URLSearchParams(window.location.search);
    const profileId = urlParams.get("id") || ""; // empty means "my profile" for the backend

    fetch(
      `/backend/get_follow_list_action.php?type=${type}&profile_id=${profileId}`,
    )
      .then((res) => res.json())
      .then((data) => {
        container.textContent = "";

        if (!data.success) {
          const errorMsg = document.createElement("p");
          errorMsg.className = "error-text";
          errorMsg.textContent = "Erreur lors du chargement.";
          container.appendChild(errorMsg);
          return;
        }

        if (data.list.length === 0) {
          const noUsersMsg = document.createElement("p");
          noUsersMsg.className = "no-users-message";
          noUsersMsg.textContent = "Aucun utilisateur trouvé.";
          container.appendChild(noUsersMsg);
          return;
        }

        data.list.forEach((user) => {
          const userRow = document.createElement("div");
          userRow.className = "user-follow-row";

          const userFollowInfo = document.createElement("div");
          userFollowInfo.className = "user-follow-info";

          const userFollowAvatar = document.createElement("div");
          userFollowAvatar.className = "user-follow-avatar";

          const avatarUrl = user.avatar_url
            ? `/${user.avatar_url}`
            : "/frontend/assets/IMG/default-avatar.svg";
          userFollowAvatar.style.backgroundImage = `url('${avatarUrl}')`;

          // username + handle
          const textBlock = document.createElement("div");

          const usernameSpan = document.createElement("span");
          usernameSpan.className = "user-follow-username";
          usernameSpan.textContent = user.username;

          const handleSpan = document.createElement("span");
          handleSpan.className = "user-follow-handle";
          handleSpan.textContent = `@${user.username.toLowerCase().replace(/\s+/g, "")}`;

          textBlock.appendChild(usernameSpan);
          textBlock.appendChild(handleSpan);

          userFollowInfo.appendChild(userFollowAvatar);
          userFollowInfo.appendChild(textBlock);

          userRow.appendChild(userFollowInfo);
          container.appendChild(userRow);
        });
      })
      .catch(() => {
        container.textContent = "";
        const networkErrorMsg = document.createElement("p");
        networkErrorMsg.className = "error-text";
        networkErrorMsg.textContent = "Erreur réseau.";
        container.appendChild(networkErrorMsg);
      });
  }

  // managing the follow/unfollow button
  if (btnToggleFollow) {
    btnToggleFollow.addEventListener("click", () => {
      const targetUserId = btnToggleFollow.getAttribute("data-user-id");
      btnToggleFollow.disabled = true;

      const formData = new FormData();
      formData.append("target_user_id", targetUserId);

      fetch("/backend/toggle_follow_action.php", {
        method: "POST",
        body: formData,
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.success) {
            btnToggleFollow.textContent = "";
            const icon = document.createElement("i");

            if (data.status === "unfollow") {
              btnToggleFollow.setAttribute("data-status", "unfollow");
              btnToggleFollow.className = "btn-cancel";
              icon.className = "fa-solid fa-user-minus";
              btnToggleFollow.appendChild(icon);
              btnToggleFollow.appendChild(
                document.createTextNode(" Ne plus suivre"),
              );
            } else {
              btnToggleFollow.setAttribute("data-status", "follow");
              btnToggleFollow.className = "btn-primary";
              icon.className = "fa-solid fa-user-plus";
              btnToggleFollow.appendChild(icon);
              btnToggleFollow.appendChild(document.createTextNode(" Suivre"));
            }

            const statFollowersCount = document.getElementById(
              "stat-followers-count",
            );
            if (statFollowersCount) {
              statFollowersCount.textContent = data.followers_count;
            }
          } else {
            alert(data.error || "Une erreur est survenue.");
          }
        })
        .catch(() => alert("Erreur réseau."))
        .finally(() => {
          btnToggleFollow.disabled = false;
        });
    });
  }
});
