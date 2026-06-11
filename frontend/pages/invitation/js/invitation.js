document.addEventListener("DOMContentLoaded", () => {
  const invitationsContainer = document.getElementById(
    "invitations-list-container",
  );

  if (invitationsContainer) {
    invitationsContainer.addEventListener("click", (event) => {
      const target = event.target;

      const isAccept = target.classList.contains("btn-action-accept");
      const isRefuse = target.classList.contains("btn-action-refuse");

      if (isAccept || isRefuse) {
        const invitationRow = target.closest(".invitation-row-card");

        if (invitationRow) {
          if (isAccept) {
            console.log("[Invitation] Invitation acceptée.");
          } else {
            console.log("[Invitation] Invitation refusée.");
          }

          invitationRow.style.transition = "all 0.3s ease";
          invitationRow.style.opacity = "0";
          invitationRow.style.transform = "translateX(20px)";

          setTimeout(() => {
            invitationRow.remove();
            if (invitationsContainer.children.length === 0) {
              invitationsContainer.innerHTML = `<p style="color: var(--text-light-gray); font-size: 13px; font-style: italic; padding: 10px 0;">Vous n'avez aucune invitation en attente.</p>`;
            }
          }, 300);
        }
      }
    });
  }
});
