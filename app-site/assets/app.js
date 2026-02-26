(function () {
  const notifyToggle = document.getElementById("notifyToggle");
  const notifyPanel = document.getElementById("notifyPanel");
  const notifyList = document.getElementById("notifyList");
  const apiStatus = document.getElementById("apiStatus");

  if (notifyToggle && notifyPanel) {
    notifyToggle.addEventListener("click", function () {
      notifyPanel.classList.toggle("hidden");
    });
  }

  async function loadStatus() {
    if (!apiStatus) return;
    try {
      const res = await fetch("/api/index.php?action=status", {
        credentials: "same-origin",
      });
      const data = await res.json();
      apiStatus.textContent = JSON.stringify(data, null, 2);
      if (notifyList) {
        const li = document.createElement("li");
        li.textContent = "API status loaded at " + new Date().toLocaleTimeString();
        notifyList.prepend(li);
      }
    } catch (err) {
      apiStatus.textContent = "Status fetch failed: " + (err && err.message ? err.message : "Unknown error");
    }
  }

  loadStatus();
})();
