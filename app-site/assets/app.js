(function () {
  const notifyToggle = document.getElementById("notifyToggle");
  const notifyPanel = document.getElementById("notifyPanel");
  const notifyList = document.getElementById("notifyList");
  const apiStatus = document.getElementById("apiStatus");
  const runAutomationBtn = document.getElementById("runAutomationBtn");
  const runSchedulerTickBtn = document.getElementById("runSchedulerTickBtn");
  const markNotificationsReadBtn = document.getElementById("markNotificationsReadBtn");
  const automationResult = document.getElementById("automationResult");
  const automationRuns = document.getElementById("automationRuns");
  const automationSettingsForm = document.getElementById("automationSettingsForm");
  const automationSettingsView = document.getElementById("automationSettingsView");
  const modules = [
    ["modLeads", "leads.summary"],
    ["modCrm", "crm.summary"],
    ["modSocial", "social.summary"],
    ["modWebops", "webops.summary"],
    ["modSeo", "seo.summary"],
  ];
  const bridgeSites = document.getElementById("bridgeSites");
  const crmConnectors = document.getElementById("crmConnectors");
  const crmConnectorForm = document.getElementById("crmConnectorForm");
  const runCrmSyncBtn = document.getElementById("runCrmSyncBtn");
  const runRetryQueueBtn = document.getElementById("runRetryQueueBtn");
  const crmSyncResult = document.getElementById("crmSyncResult");
  const crmSyncLog = document.getElementById("crmSyncLog");
  const crmRetryQueue = document.getElementById("crmRetryQueue");
  const socialConnectors = document.getElementById("socialConnectors");
  const socialConnectorForm = document.getElementById("socialConnectorForm");
  const runSocialSyncBtn = document.getElementById("runSocialSyncBtn");
  const runSocialRetryQueueBtn = document.getElementById("runSocialRetryQueueBtn");
  const socialSyncResult = document.getElementById("socialSyncResult");
  const socialSyncLog = document.getElementById("socialSyncLog");
  const socialRetryQueue = document.getElementById("socialRetryQueue");
  const webopsMonitors = document.getElementById("webopsMonitors");
  const webopsMonitorForm = document.getElementById("webopsMonitorForm");
  const runWebopsBtn = document.getElementById("runWebopsBtn");
  const runWebopsRetryQueueBtn = document.getElementById("runWebopsRetryQueueBtn");
  const webopsResult = document.getElementById("webopsResult");
  const webopsLog = document.getElementById("webopsLog");
  const webopsRetryQueue = document.getElementById("webopsRetryQueue");
  const seoProjects = document.getElementById("seoProjects");
  const seoProjectForm = document.getElementById("seoProjectForm");
  const seoResult = document.getElementById("seoResult");
  const seoAudits = document.getElementById("seoAudits");
  const seoExtensionEvents = document.getElementById("seoExtensionEvents");

  async function apiGet(action) {
    const res = await fetch("/api/index.php?action=" + encodeURIComponent(action), {
      credentials: "same-origin",
    });
    return res.json();
  }

  async function apiPost(action, payload) {
    const res = await fetch("/api/index.php?action=" + encodeURIComponent(action), {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-Token": window.appCsrfToken || "",
      },
      body: JSON.stringify(payload || {}),
    });
    return res.json();
  }

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
    } catch (err) {
      apiStatus.textContent = "Status fetch failed: " + (err && err.message ? err.message : "Unknown error");
    }
  }

  async function loadNotifications() {
    if (!notifyList) return;
    try {
      const data = await apiGet("notifications");
      notifyList.innerHTML = "";
      const items = data && Array.isArray(data.items) ? data.items : [];
      items.slice(0, 20).forEach(function (item) {
        const li = document.createElement("li");
        const state = item && Number(item.read) === 1 ? "read" : "unread";
        li.textContent = "[" + state + "] " + (item.message || "Notification");
        notifyList.appendChild(li);
      });
      if (items.length === 0) {
        const li = document.createElement("li");
        li.textContent = "No notifications.";
        notifyList.appendChild(li);
      }
    } catch (err) {
      notifyList.innerHTML = "<li>Failed to load notifications.</li>";
    }
  }

  async function loadAutomationRuns() {
    if (!automationRuns) return;
    try {
      const data = await apiGet("automation.runs");
      automationRuns.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      automationRuns.textContent = "Failed to load automation runs.";
    }
  }

  async function loadAutomationSettings() {
    if (!automationSettingsView) return;
    try {
      const data = await apiGet("automation.settings.get");
      automationSettingsView.textContent = JSON.stringify(data, null, 2);
      const settings = data && data.settings ? data.settings : {};
      const modulesCfg = settings.modules || {};
      const enabled = document.getElementById("automationEnabled");
      const interval = document.getElementById("automationInterval");
      const crm = document.getElementById("autoModuleCrm");
      const social = document.getElementById("autoModuleSocial");
      const webops = document.getElementById("autoModuleWebops");
      const seo = document.getElementById("autoModuleSeo");
      if (enabled) enabled.value = Number(settings.enabled) === 1 ? "1" : "0";
      if (interval) interval.value = String(settings.interval_minutes || 30);
      if (crm) crm.value = Number(modulesCfg.crm) === 1 ? "1" : "0";
      if (social) social.value = Number(modulesCfg.social) === 1 ? "1" : "0";
      if (webops) webops.value = Number(modulesCfg.webops) === 1 ? "1" : "0";
      if (seo) seo.value = Number(modulesCfg.seo) === 1 ? "1" : "0";
    } catch (err) {
      automationSettingsView.textContent = "Failed to load automation settings.";
    }
  }

  loadStatus();

  modules.forEach(async function (entry) {
    const el = document.getElementById(entry[0]);
    if (!el) return;
    try {
      const data = await apiGet(entry[1]);
      el.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      el.textContent = "Failed to load: " + entry[1];
    }
  });

  (async function loadBridgeSites() {
    if (!bridgeSites) return;
    try {
      const data = await apiGet("bridge.sites");
      bridgeSites.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      bridgeSites.textContent = "Failed to load bridge sites.";
    }
  })();

  async function loadCrmConnectors() {
    if (!crmConnectors) return;
    try {
      const data = await apiGet("crm.connectors.list");
      crmConnectors.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmConnectors.textContent = "Failed to load CRM connectors.";
    }
  }

  async function loadCrmSyncLog() {
    if (!crmSyncLog) return;
    try {
      const data = await apiGet("crm.push.log");
      crmSyncLog.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmSyncLog.textContent = "Failed to load CRM sync log.";
    }
  }

  async function loadRetryQueue() {
    if (!crmRetryQueue) return;
    try {
      const data = await apiGet("crm.retry.list");
      crmRetryQueue.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmRetryQueue.textContent = "Failed to load retry queue.";
    }
  }

  async function loadSocialConnectors() {
    if (!socialConnectors) return;
    try {
      const data = await apiGet("social.connectors.list");
      socialConnectors.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialConnectors.textContent = "Failed to load social connectors.";
    }
  }

  async function loadSocialSyncLog() {
    if (!socialSyncLog) return;
    try {
      const data = await apiGet("social.push.log");
      socialSyncLog.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialSyncLog.textContent = "Failed to load social sync log.";
    }
  }

  async function loadSocialRetryQueue() {
    if (!socialRetryQueue) return;
    try {
      const data = await apiGet("social.retry.list");
      socialRetryQueue.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialRetryQueue.textContent = "Failed to load social retry queue.";
    }
  }

  async function loadWebopsMonitors() {
    if (!webopsMonitors) return;
    try {
      const data = await apiGet("webops.monitors.list");
      webopsMonitors.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsMonitors.textContent = "Failed to load WebOps monitors.";
    }
  }

  async function loadWebopsLog() {
    if (!webopsLog) return;
    try {
      const data = await apiGet("webops.log");
      webopsLog.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsLog.textContent = "Failed to load WebOps log.";
    }
  }

  async function loadWebopsRetryQueue() {
    if (!webopsRetryQueue) return;
    try {
      const data = await apiGet("webops.retry.list");
      webopsRetryQueue.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsRetryQueue.textContent = "Failed to load WebOps retry queue.";
    }
  }

  async function loadSeoProjects() {
    if (!seoProjects) return;
    try {
      const data = await apiGet("seo.projects.list");
      seoProjects.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoProjects.textContent = "Failed to load SEO projects.";
    }
  }

  async function loadSeoAudits() {
    if (!seoAudits) return;
    try {
      const data = await apiGet("seo.audits.list");
      seoAudits.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoAudits.textContent = "Failed to load SEO audits.";
    }
  }

  async function loadSeoExtensionEvents() {
    if (!seoExtensionEvents) return;
    try {
      const data = await apiGet("seo.extension.events.list");
      seoExtensionEvents.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoExtensionEvents.textContent = "Failed to load extension events.";
    }
  }

  (async function initCrm() {
    await loadCrmConnectors();
    await loadCrmSyncLog();
    await loadRetryQueue();
    await loadSocialConnectors();
    await loadSocialSyncLog();
    await loadSocialRetryQueue();
    await loadWebopsMonitors();
    await loadWebopsLog();
    await loadWebopsRetryQueue();
    await loadSeoProjects();
    await loadSeoAudits();
    await loadSeoExtensionEvents();
    await loadNotifications();
    await loadAutomationRuns();
    await loadAutomationSettings();
  })();

  if (crmConnectorForm) {
    crmConnectorForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const saveBtn = document.getElementById("saveConnectorBtn");
    if (saveBtn) {
      saveBtn.addEventListener("click", async function () {
        const connectorId = document.getElementById("connectorId");
        const provider = document.getElementById("connectorProvider");
        const type = document.getElementById("connectorType");
        const status = document.getElementById("connectorStatus");
        const auth = document.getElementById("connectorAuth");
        const caps = document.getElementById("connectorCapabilities");
        const siteId = document.getElementById("connectorSiteId");
        const runMode = document.getElementById("connectorRunMode");
        const accessToken = document.getElementById("connectorAccessToken");
        const endpoint = document.getElementById("connectorEndpoint");
        const webhook = document.getElementById("connectorWebhook");
        const payload = {
          connector_id: connectorId && connectorId.value ? connectorId.value.trim() : "",
          provider: provider ? provider.value.trim() : "",
          type: type ? type.value : "external_api",
          status: status ? status.value : "planned",
          auth_mode: auth ? auth.value : "api_key",
          site_id: siteId ? siteId.value.trim() : "",
          capabilities: (caps && caps.value ? caps.value.split(",") : []).map(function (v) {
            return v.trim();
          }).filter(Boolean),
          config: {
            run_mode: runMode ? runMode.value : "dry_run",
            bridge_site_id: siteId ? siteId.value.trim() : "",
            access_token: accessToken ? accessToken.value.trim() : "",
            endpoint_url: endpoint ? endpoint.value.trim() : "",
            webhook_url: webhook ? webhook.value.trim() : "",
          },
        };
        const result = await apiPost("crm.connectors.save", payload);
        if (crmSyncResult) {
          crmSyncResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadCrmConnectors();
      });
    }
    const deleteBtn = document.getElementById("deleteConnectorBtn");
    if (deleteBtn) {
      deleteBtn.addEventListener("click", async function () {
        const connectorId = document.getElementById("connectorId");
        const id = connectorId && connectorId.value ? connectorId.value.trim() : "";
        if (!id) {
          if (crmSyncResult) {
            crmSyncResult.textContent = "Enter Connector ID to delete.";
          }
          return;
        }
        const result = await apiPost("crm.connectors.delete", { connector_id: id });
        if (crmSyncResult) {
          crmSyncResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadCrmConnectors();
      });
    }
    const testBtn = document.getElementById("testConnectorBtn");
    if (testBtn) {
      testBtn.addEventListener("click", async function () {
        const connectorId = document.getElementById("connectorId");
        const id = connectorId && connectorId.value ? connectorId.value.trim() : "";
        if (!id) {
          if (crmSyncResult) {
            crmSyncResult.textContent = "Enter Connector ID to test.";
          }
          return;
        }
        const result = await apiPost("crm.connectors.test", { connector_id: id });
        if (crmSyncResult) {
          crmSyncResult.textContent = JSON.stringify(result, null, 2);
        }
      });
    }
  }

  if (runCrmSyncBtn) {
    runCrmSyncBtn.addEventListener("click", async function () {
      const result = await apiPost("crm.push.sync", {});
      if (crmSyncResult) {
        crmSyncResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadCrmSyncLog();
      await loadRetryQueue();
      modules.forEach(async function (entry) {
        if (entry[1] !== "crm.summary") return;
        const el = document.getElementById(entry[0]);
        if (!el) return;
        const data = await apiGet("crm.summary");
        el.textContent = JSON.stringify(data, null, 2);
      });
    });
  }

  if (runRetryQueueBtn) {
    runRetryQueueBtn.addEventListener("click", async function () {
      const result = await apiPost("crm.retry.run", {});
      if (crmSyncResult) {
        crmSyncResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadRetryQueue();
      const crmData = await apiGet("crm.summary");
      const crmPanel = document.getElementById("modCrm");
      if (crmPanel) {
        crmPanel.textContent = JSON.stringify(crmData, null, 2);
      }
    });
  }

  if (socialConnectorForm) {
    socialConnectorForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const saveSocialBtn = document.getElementById("saveSocialConnectorBtn");
    if (saveSocialBtn) {
      saveSocialBtn.addEventListener("click", async function () {
        const connectorId = document.getElementById("socialConnectorId");
        const provider = document.getElementById("socialProvider");
        const type = document.getElementById("socialType");
        const status = document.getElementById("socialStatus");
        const auth = document.getElementById("socialAuth");
        const caps = document.getElementById("socialCapabilities");
        const siteId = document.getElementById("socialSiteId");
        const runMode = document.getElementById("socialRunMode");
        const webhook = document.getElementById("socialWebhook");
        const payload = {
          connector_id: connectorId && connectorId.value ? connectorId.value.trim() : "",
          provider: provider ? provider.value.trim() : "",
          type: type ? type.value : "external_api",
          status: status ? status.value : "planned",
          auth_mode: auth ? auth.value : "api_key",
          site_id: siteId ? siteId.value.trim() : "",
          capabilities: (caps && caps.value ? caps.value.split(",") : []).map(function (v) {
            return v.trim();
          }).filter(Boolean),
          config: {
            run_mode: runMode ? runMode.value : "dry_run",
            bridge_site_id: siteId ? siteId.value.trim() : "",
            webhook_url: webhook ? webhook.value.trim() : "",
          },
        };
        const result = await apiPost("social.connectors.save", payload);
        if (socialSyncResult) {
          socialSyncResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadSocialConnectors();
      });
    }

    const deleteSocialBtn = document.getElementById("deleteSocialConnectorBtn");
    if (deleteSocialBtn) {
      deleteSocialBtn.addEventListener("click", async function () {
        const connectorId = document.getElementById("socialConnectorId");
        const id = connectorId && connectorId.value ? connectorId.value.trim() : "";
        if (!id) {
          if (socialSyncResult) {
            socialSyncResult.textContent = "Enter Social Connector ID to delete.";
          }
          return;
        }
        const result = await apiPost("social.connectors.delete", { connector_id: id });
        if (socialSyncResult) {
          socialSyncResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadSocialConnectors();
      });
    }

    const testSocialBtn = document.getElementById("testSocialConnectorBtn");
    if (testSocialBtn) {
      testSocialBtn.addEventListener("click", async function () {
        const connectorId = document.getElementById("socialConnectorId");
        const id = connectorId && connectorId.value ? connectorId.value.trim() : "";
        if (!id) {
          if (socialSyncResult) {
            socialSyncResult.textContent = "Enter Social Connector ID to test.";
          }
          return;
        }
        const result = await apiPost("social.connectors.test", { connector_id: id });
        if (socialSyncResult) {
          socialSyncResult.textContent = JSON.stringify(result, null, 2);
        }
      });
    }
  }

  if (runSocialSyncBtn) {
    runSocialSyncBtn.addEventListener("click", async function () {
      const result = await apiPost("social.push.sync", {});
      if (socialSyncResult) {
        socialSyncResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadSocialSyncLog();
      await loadSocialRetryQueue();
      const socialData = await apiGet("social.summary");
      const socialPanel = document.getElementById("modSocial");
      if (socialPanel) {
        socialPanel.textContent = JSON.stringify(socialData, null, 2);
      }
    });
  }

  if (runSocialRetryQueueBtn) {
    runSocialRetryQueueBtn.addEventListener("click", async function () {
      const result = await apiPost("social.retry.run", {});
      if (socialSyncResult) {
        socialSyncResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadSocialRetryQueue();
      const socialData = await apiGet("social.summary");
      const socialPanel = document.getElementById("modSocial");
      if (socialPanel) {
        socialPanel.textContent = JSON.stringify(socialData, null, 2);
      }
    });
  }

  if (webopsMonitorForm) {
    webopsMonitorForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const saveWebopsBtn = document.getElementById("saveWebopsMonitorBtn");
    if (saveWebopsBtn) {
      saveWebopsBtn.addEventListener("click", async function () {
        const monitorId = document.getElementById("webopsMonitorId");
        const name = document.getElementById("webopsMonitorName");
        const type = document.getElementById("webopsMonitorType");
        const status = document.getElementById("webopsMonitorStatus");
        const target = document.getElementById("webopsMonitorTarget");
        const bridgeSite = document.getElementById("webopsBridgeSiteId");
        const runMode = document.getElementById("webopsRunMode");
        const webhook = document.getElementById("webopsWebhookUrl");
        const payload = {
          monitor_id: monitorId && monitorId.value ? monitorId.value.trim() : "",
          name: name ? name.value.trim() : "",
          type: type ? type.value : "uptime_http",
          status: status ? status.value : "active",
          target: target ? target.value.trim() : "",
          config: {
            bridge_site_id: bridgeSite ? bridgeSite.value.trim() : "",
            run_mode: runMode ? runMode.value : "live",
            webhook_url: webhook ? webhook.value.trim() : "",
          },
        };
        const result = await apiPost("webops.monitors.save", payload);
        if (webopsResult) {
          webopsResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadWebopsMonitors();
      });
    }

    const deleteWebopsBtn = document.getElementById("deleteWebopsMonitorBtn");
    if (deleteWebopsBtn) {
      deleteWebopsBtn.addEventListener("click", async function () {
        const monitorId = document.getElementById("webopsMonitorId");
        const id = monitorId && monitorId.value ? monitorId.value.trim() : "";
        if (!id) {
          if (webopsResult) webopsResult.textContent = "Enter Monitor ID to delete.";
          return;
        }
        const result = await apiPost("webops.monitors.delete", { monitor_id: id });
        if (webopsResult) {
          webopsResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadWebopsMonitors();
      });
    }

    const testWebopsBtn = document.getElementById("testWebopsMonitorBtn");
    if (testWebopsBtn) {
      testWebopsBtn.addEventListener("click", async function () {
        const monitorId = document.getElementById("webopsMonitorId");
        const id = monitorId && monitorId.value ? monitorId.value.trim() : "";
        if (!id) {
          if (webopsResult) webopsResult.textContent = "Enter Monitor ID to test.";
          return;
        }
        const result = await apiPost("webops.monitors.test", { monitor_id: id });
        if (webopsResult) {
          webopsResult.textContent = JSON.stringify(result, null, 2);
        }
      });
    }
  }

  if (runWebopsBtn) {
    runWebopsBtn.addEventListener("click", async function () {
      const result = await apiPost("webops.run", {});
      if (webopsResult) {
        webopsResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadWebopsLog();
      await loadWebopsRetryQueue();
      const data = await apiGet("webops.summary");
      const panel = document.getElementById("modWebops");
      if (panel) {
        panel.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (runWebopsRetryQueueBtn) {
    runWebopsRetryQueueBtn.addEventListener("click", async function () {
      const result = await apiPost("webops.retry.run", {});
      if (webopsResult) {
        webopsResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadWebopsRetryQueue();
      const data = await apiGet("webops.summary");
      const panel = document.getElementById("modWebops");
      if (panel) {
        panel.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (runAutomationBtn) {
    runAutomationBtn.addEventListener("click", async function () {
      const result = await apiPost("automation.run_all", {});
      if (automationResult) {
        automationResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadAutomationRuns();
      await loadNotifications();
      const refreshActions = ["crm.summary", "social.summary", "webops.summary", "seo.summary", "leads.summary"];
      for (let i = 0; i < modules.length; i += 1) {
        const entry = modules[i];
        if (refreshActions.indexOf(entry[1]) === -1) continue;
        const el = document.getElementById(entry[0]);
        if (!el) continue;
        const data = await apiGet(entry[1]);
        el.textContent = JSON.stringify(data, null, 2);
      }
      await loadAutomationSettings();
      await loadStatus();
    });
  }

  if (markNotificationsReadBtn) {
    markNotificationsReadBtn.addEventListener("click", async function () {
      const result = await apiPost("notifications.read_all", {});
      if (automationResult) {
        automationResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadNotifications();
    });
  }

  if (runSchedulerTickBtn) {
    runSchedulerTickBtn.addEventListener("click", async function () {
      const result = await apiPost("automation.scheduler.tick", {});
      if (automationResult) {
        automationResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadAutomationRuns();
      await loadNotifications();
      await loadAutomationSettings();
      await loadStatus();
    });
  }

  if (automationSettingsForm) {
    automationSettingsForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const saveAutomationBtn = document.getElementById("saveAutomationSettingsBtn");
    if (saveAutomationBtn) {
      saveAutomationBtn.addEventListener("click", async function () {
        const enabled = document.getElementById("automationEnabled");
        const interval = document.getElementById("automationInterval");
        const crm = document.getElementById("autoModuleCrm");
        const social = document.getElementById("autoModuleSocial");
        const webops = document.getElementById("autoModuleWebops");
        const seo = document.getElementById("autoModuleSeo");
        const payload = {
          enabled: enabled && enabled.value === "1" ? 1 : 0,
          interval_minutes: interval ? Number(interval.value || 30) : 30,
          modules: {
            crm: crm && crm.value === "1" ? 1 : 0,
            social: social && social.value === "1" ? 1 : 0,
            webops: webops && webops.value === "1" ? 1 : 0,
            seo: seo && seo.value === "1" ? 1 : 0,
          },
        };
        const result = await apiPost("automation.settings.save", payload);
        if (automationResult) {
          automationResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadAutomationSettings();
        await loadStatus();
      });
    }
  }

  if (seoProjectForm) {
    seoProjectForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const saveSeoBtn = document.getElementById("saveSeoProjectBtn");
    if (saveSeoBtn) {
      saveSeoBtn.addEventListener("click", async function () {
        const projectId = document.getElementById("seoProjectId");
        const name = document.getElementById("seoProjectName");
        const domain = document.getElementById("seoProjectDomain");
        const status = document.getElementById("seoProjectStatus");
        const payload = {
          project_id: projectId && projectId.value ? projectId.value.trim() : "",
          name: name ? name.value.trim() : "",
          domain: domain ? domain.value.trim() : "",
          status: status ? status.value : "active",
        };
        const result = await apiPost("seo.projects.save", payload);
        if (seoResult) {
          seoResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadSeoProjects();
      });
    }

    const deleteSeoBtn = document.getElementById("deleteSeoProjectBtn");
    if (deleteSeoBtn) {
      deleteSeoBtn.addEventListener("click", async function () {
        const projectId = document.getElementById("seoProjectId");
        const id = projectId && projectId.value ? projectId.value.trim() : "";
        if (!id) {
          if (seoResult) seoResult.textContent = "Enter SEO Project ID to delete.";
          return;
        }
        const result = await apiPost("seo.projects.delete", { project_id: id });
        if (seoResult) {
          seoResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadSeoProjects();
      });
    }

    const runSeoBtn = document.getElementById("runSeoAuditBtn");
    if (runSeoBtn) {
      runSeoBtn.addEventListener("click", async function () {
        const projectId = document.getElementById("seoProjectId");
        const id = projectId && projectId.value ? projectId.value.trim() : "";
        if (!id) {
          if (seoResult) seoResult.textContent = "Enter SEO Project ID to run audit.";
          return;
        }
        const result = await apiPost("seo.audit.run", { project_id: id });
        if (seoResult) {
          seoResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadSeoAudits();
        const data = await apiGet("seo.summary");
        const panel = document.getElementById("modSeo");
        if (panel) {
          panel.textContent = JSON.stringify(data, null, 2);
        }
      });
    }
  }
})();
