(function () {
  const notifyToggle = document.getElementById("notifyToggle");
  const notifyPanel = document.getElementById("notifyPanel");
  const notifyList = document.getElementById("notifyList");
  const apiStatus = document.getElementById("apiStatus");
  const modules = [
    ["modLeads", "leads.summary"],
    ["modCrm", "crm.summary"],
    ["modSocial", "social.summary"],
    ["modWebops", "webops.summary"],
    ["modSeo", "seo.summary"],
  ];
  const bridgeSites = document.getElementById("bridgeSites");
  const crmConnectors = document.getElementById("crmConnectors");

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

  modules.forEach(async function (entry) {
    const el = document.getElementById(entry[0]);
    if (!el) return;
    try {
      const res = await fetch("/api/index.php?action=" + encodeURIComponent(entry[1]), {
        credentials: "same-origin",
      });
      const data = await res.json();
      el.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      el.textContent = "Failed to load: " + entry[1];
    }
  });

  (async function loadBridgeSites() {
    if (!bridgeSites) return;
    try {
      const res = await fetch("/api/index.php?action=bridge.sites", {
        credentials: "same-origin",
      });
      const data = await res.json();
      bridgeSites.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      bridgeSites.textContent = "Failed to load bridge sites.";
    }
  })();

  (async function loadCrmConnectors() {
    if (!crmConnectors) return;
    try {
      const res = await fetch("/api/index.php?action=crm.connectors.list", {
        credentials: "same-origin",
      });
      const data = await res.json();
      crmConnectors.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmConnectors.textContent = "Failed to load CRM connectors.";
    }
  })();
})();
