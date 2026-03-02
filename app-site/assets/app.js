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
  const schedulerStatusView = document.getElementById("schedulerStatusView");
  const cronHelpView = document.getElementById("cronHelpView");
  const notificationSettingsForm = document.getElementById("notificationSettingsForm");
  const notificationSettingsView = document.getElementById("notificationSettingsView");
  const exportBackupBtn = document.getElementById("exportBackupBtn");
  const importBackupBtn = document.getElementById("importBackupBtn");
  const refreshAuditBtn = document.getElementById("refreshAuditBtn");
  const backupPayload = document.getElementById("backupPayload");
  const backupResult = document.getElementById("backupResult");
  const auditLogView = document.getElementById("auditLogView");
  const runPreflightBtn = document.getElementById("runPreflightBtn");
  const downloadDeployReportBtn = document.getElementById("downloadDeployReportBtn");
  const refreshGoLiveStatusBtn = document.getElementById("refreshGoLiveStatusBtn");
  const generateReleaseCandidateBtn = document.getElementById("generateReleaseCandidateBtn");
  const downloadArtifactManifestBtn = document.getElementById("downloadArtifactManifestBtn");
  const verifyArtifactManifestBtn = document.getElementById("verifyArtifactManifestBtn");
  const runInstallCheckBtn = document.getElementById("runInstallCheckBtn");
  const runDeploymentVerifyBtn = document.getElementById("runDeploymentVerifyBtn");
  const downloadHandoffBundleBtn = document.getElementById("downloadHandoffBundleBtn");
  const deploymentGuardForm = document.getElementById("deploymentGuardForm");
  const saveDeploymentGuardBtn = document.getElementById("saveDeploymentGuardBtn");
  const unlockDeploymentGuardBtn = document.getElementById("unlockDeploymentGuardBtn");
  const lockDeploymentGuardBtn = document.getElementById("lockDeploymentGuardBtn");
  const preflightView = document.getElementById("preflightView");
  const goLiveStatusView = document.getElementById("goLiveStatusView");
  const releaseCandidateView = document.getElementById("releaseCandidateView");
  const artifactManifestView = document.getElementById("artifactManifestView");
  const artifactBaselineInput = document.getElementById("artifactBaselineInput");
  const artifactVerifyView = document.getElementById("artifactVerifyView");
  const releaseLogView = document.getElementById("releaseLogView");
  const releaseCandidateNote = document.getElementById("releaseCandidateNote");
  const installCheckView = document.getElementById("installCheckView");
  const deploymentVerifyView = document.getElementById("deploymentVerifyView");
  const deploymentGuardView = document.getElementById("deploymentGuardView");
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
  let lastNotificationToneKey = "";

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

      const settings = data && data.settings ? data.settings : {};
      const soundEnabled = Number(settings.sound_enabled) === 1;
      const mode = settings.sound_mode || "critical_only";
      const unread = Number(data.unread || 0);
      const criticalUnread = Number(data.critical_unread || 0);
      const shouldSound = soundEnabled && (
        (mode === "all" && unread > 0) ||
        (mode === "critical_only" && criticalUnread > 0)
      );
      const toneKey = [mode, unread, criticalUnread, items[0] && items[0].id ? items[0].id : ""].join("|");
      if (shouldSound && toneKey !== lastNotificationToneKey) {
        try {
          const AudioCtx = window.AudioContext || window.webkitAudioContext;
          if (AudioCtx) {
            const ctx = new AudioCtx();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = "sine";
            osc.frequency.value = 880;
            gain.gain.value = 0.04;
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.14);
          }
        } catch (toneErr) {}
      }
      lastNotificationToneKey = toneKey;
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

  async function loadSchedulerStatus() {
    if (!schedulerStatusView) return;
    try {
      const data = await apiGet("automation.scheduler.status");
      schedulerStatusView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      schedulerStatusView.textContent = "Failed to load scheduler status.";
    }
  }

  async function loadCronHelp() {
    if (!cronHelpView) return;
    try {
      const data = await apiGet("automation.scheduler.cron_help");
      cronHelpView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      cronHelpView.textContent = "Failed to load cron helper.";
    }
  }

  async function loadNotificationSettings() {
    if (!notificationSettingsView) return;
    try {
      const data = await apiGet("notifications.settings.get");
      notificationSettingsView.textContent = JSON.stringify(data, null, 2);
      const settings = data && data.settings ? data.settings : {};
      const soundEnabled = document.getElementById("notifSoundEnabled");
      const soundMode = document.getElementById("notifSoundMode");
      if (soundEnabled) soundEnabled.value = Number(settings.sound_enabled) === 1 ? "1" : "0";
      if (soundMode) soundMode.value = settings.sound_mode || "critical_only";
    } catch (err) {
      notificationSettingsView.textContent = "Failed to load notification settings.";
    }
  }

  async function loadAuditLog() {
    if (!auditLogView) return;
    try {
      const data = await apiGet("audit.log");
      auditLogView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      auditLogView.textContent = "Failed to load audit log.";
    }
  }

  async function loadPreflight() {
    if (!preflightView) return;
    try {
      const data = await apiGet("deployment.preflight");
      preflightView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      preflightView.textContent = "Failed to load deployment preflight.";
    }
  }

  async function loadGoLiveStatus() {
    if (!goLiveStatusView) return;
    try {
      const data = await apiGet("deployment.go_live_status");
      goLiveStatusView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      goLiveStatusView.textContent = "Failed to load go-live status.";
    }
  }

  async function loadReleaseLog() {
    if (!releaseLogView) return;
    try {
      const data = await apiGet("deployment.release.log");
      releaseLogView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      releaseLogView.textContent = "Failed to load release log.";
    }
  }

  async function loadArtifactManifest() {
    if (!artifactManifestView) return;
    try {
      const data = await apiGet("deployment.artifact.manifest");
      artifactManifestView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      artifactManifestView.textContent = "Failed to load artifact manifest.";
    }
  }

  async function loadInstallCheck() {
    if (!installCheckView) return;
    try {
      const data = await apiGet("install.check");
      installCheckView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      installCheckView.textContent = "Failed to load install check.";
    }
  }

  async function loadDeploymentGuard() {
    if (!deploymentGuardView) return;
    try {
      const data = await apiGet("deployment.guard.status");
      deploymentGuardView.textContent = JSON.stringify(data, null, 2);
      const guard = data && data.guard ? data.guard : {};
      const checklist = guard && guard.checklist ? guard.checklist : {};
      const enforced = document.getElementById("guardEnforced");
      const backup = document.getElementById("guardBackupVerified");
      const cron = document.getElementById("guardCronConfigured");
      const rollback = document.getElementById("guardRollbackReady");
      const dns = document.getElementById("guardDnsReady");
      if (enforced) enforced.checked = Number(guard.enforced) === 1;
      if (backup) backup.checked = Number(checklist.backup_verified) === 1;
      if (cron) cron.checked = Number(checklist.cron_configured) === 1;
      if (rollback) rollback.checked = Number(checklist.rollback_plan_ready) === 1;
      if (dns) dns.checked = Number(checklist.dns_domain_ready) === 1;
    } catch (err) {
      deploymentGuardView.textContent = "Failed to load deployment guard.";
    }
  }

  function downloadJsonFile(filename, payload) {
    try {
      const blob = new Blob([JSON.stringify(payload, null, 2)], { type: "application/json;charset=utf-8" });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      window.URL.revokeObjectURL(url);
    } catch (err) {}
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
    await loadSchedulerStatus();
    await loadCronHelp();
    await loadNotificationSettings();
    await loadAuditLog();
    await loadGoLiveStatus();
    await loadReleaseLog();
    await loadArtifactManifest();
    await loadPreflight();
    await loadInstallCheck();
    await loadDeploymentGuard();
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
      await loadSchedulerStatus();
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
        await loadSchedulerStatus();
        await loadStatus();
      });
    }
  }

  if (notificationSettingsForm) {
    notificationSettingsForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const saveNotifBtn = document.getElementById("saveNotificationSettingsBtn");
    if (saveNotifBtn) {
      saveNotifBtn.addEventListener("click", async function () {
        const soundEnabled = document.getElementById("notifSoundEnabled");
        const soundMode = document.getElementById("notifSoundMode");
        const payload = {
          sound_enabled: soundEnabled && soundEnabled.value === "1" ? 1 : 0,
          sound_mode: soundMode ? soundMode.value : "critical_only",
        };
        const result = await apiPost("notifications.settings.save", payload);
        if (automationResult) {
          automationResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadNotificationSettings();
        await loadNotifications();
      });
    }
  }

  if (exportBackupBtn) {
    exportBackupBtn.addEventListener("click", async function () {
      const result = await apiGet("backup.export");
      if (backupPayload) {
        backupPayload.value = JSON.stringify(result, null, 2);
      }
      if (backupResult) {
        backupResult.textContent = "Backup exported.";
      }
      await loadAuditLog();
    });
  }

  if (importBackupBtn) {
    importBackupBtn.addEventListener("click", async function () {
      if (!backupPayload || !backupPayload.value.trim()) {
        if (backupResult) backupResult.textContent = "Paste backup payload JSON first.";
        return;
      }
      let payload;
      try {
        payload = JSON.parse(backupPayload.value);
      } catch (err) {
        if (backupResult) backupResult.textContent = "Invalid JSON payload.";
        return;
      }
      const result = await apiPost("backup.import", payload);
      if (backupResult) {
        backupResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadAuditLog();
      await loadStatus();
    });
  }

  if (refreshAuditBtn) {
    refreshAuditBtn.addEventListener("click", async function () {
      await loadAuditLog();
    });
  }

  if (runPreflightBtn) {
    runPreflightBtn.addEventListener("click", async function () {
      await loadPreflight();
      await loadGoLiveStatus();
    });
  }

  if (refreshGoLiveStatusBtn) {
    refreshGoLiveStatusBtn.addEventListener("click", async function () {
      await loadGoLiveStatus();
    });
  }

  if (generateReleaseCandidateBtn) {
    generateReleaseCandidateBtn.addEventListener("click", async function () {
      const note = releaseCandidateNote && releaseCandidateNote.value ? releaseCandidateNote.value.trim() : "";
      const result = await apiPost("deployment.release.candidate", { note: note });
      if (releaseCandidateView) {
        releaseCandidateView.textContent = JSON.stringify(result, null, 2);
      }
      await loadReleaseLog();
      await loadNotifications();
      await loadAuditLog();
      await loadStatus();
      await loadGoLiveStatus();
      await loadDeploymentGuard();
      await loadArtifactManifest();
    });
  }

  if (downloadArtifactManifestBtn) {
    downloadArtifactManifestBtn.addEventListener("click", async function () {
      const data = await apiGet("deployment.artifact.manifest");
      if (artifactManifestView) {
        artifactManifestView.textContent = JSON.stringify(data, null, 2);
      }
      const manifest = data && data.manifest ? data.manifest : data;
      const manifestId = manifest && manifest.manifest_id ? manifest.manifest_id : "artifact_manifest";
      downloadJsonFile(manifestId + ".json", data);
      await loadAuditLog();
      await loadStatus();
    });
  }

  if (verifyArtifactManifestBtn) {
    verifyArtifactManifestBtn.addEventListener("click", async function () {
      if (!artifactBaselineInput || !artifactBaselineInput.value.trim()) {
        if (artifactVerifyView) artifactVerifyView.textContent = "Paste baseline manifest JSON first.";
        return;
      }
      let baseline;
      try {
        baseline = JSON.parse(artifactBaselineInput.value);
      } catch (err) {
        if (artifactVerifyView) artifactVerifyView.textContent = "Invalid baseline JSON.";
        return;
      }
      const result = await apiPost("deployment.artifact.verify", { baseline: baseline });
      if (artifactVerifyView) {
        artifactVerifyView.textContent = JSON.stringify(result, null, 2);
      }
      await loadNotifications();
      await loadAuditLog();
      await loadStatus();
      await loadGoLiveStatus();
    });
  }

  if (downloadDeployReportBtn) {
    downloadDeployReportBtn.addEventListener("click", async function () {
      const data = await apiGet("deployment.report");
      if (preflightView) {
        preflightView.textContent = JSON.stringify(data, null, 2);
      }
      const report = data && data.report ? data.report : data;
      const reportId = report && report.report_id ? report.report_id : "deployment_report";
      downloadJsonFile(reportId + ".json", data);
    });
  }

  if (runInstallCheckBtn) {
    runInstallCheckBtn.addEventListener("click", async function () {
      await loadInstallCheck();
    });
  }

  if (runDeploymentVerifyBtn) {
    runDeploymentVerifyBtn.addEventListener("click", async function () {
      const result = await apiPost("deployment.verify", {});
      if (deploymentVerifyView) {
        deploymentVerifyView.textContent = JSON.stringify(result, null, 2);
      }
      await loadAuditLog();
      await loadStatus();
      await loadDeploymentGuard();
      await loadGoLiveStatus();
    });
  }

  if (downloadHandoffBundleBtn) {
    downloadHandoffBundleBtn.addEventListener("click", async function () {
      const data = await apiGet("deployment.handoff.bundle");
      if (deploymentVerifyView) {
        deploymentVerifyView.textContent = JSON.stringify(data, null, 2);
      }
      const bundle = data && data.bundle ? data.bundle : data;
      const bundleId = bundle && bundle.bundle_id ? bundle.bundle_id : "handoff_bundle";
      downloadJsonFile(bundleId + ".json", data);
      await loadAuditLog();
      await loadStatus();
      await loadDeploymentGuard();
      await loadGoLiveStatus();
    });
  }

  if (deploymentGuardForm) {
    deploymentGuardForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
  }

  if (saveDeploymentGuardBtn) {
    saveDeploymentGuardBtn.addEventListener("click", async function () {
      const enforced = document.getElementById("guardEnforced");
      const backup = document.getElementById("guardBackupVerified");
      const cron = document.getElementById("guardCronConfigured");
      const rollback = document.getElementById("guardRollbackReady");
      const dns = document.getElementById("guardDnsReady");
      const payload = {
        enforced: enforced && enforced.checked ? 1 : 0,
        checklist: {
          backup_verified: backup && backup.checked ? 1 : 0,
          cron_configured: cron && cron.checked ? 1 : 0,
          rollback_plan_ready: rollback && rollback.checked ? 1 : 0,
          dns_domain_ready: dns && dns.checked ? 1 : 0,
        },
      };
      const result = await apiPost("deployment.guard.save", payload);
      if (deploymentGuardView) {
        deploymentGuardView.textContent = JSON.stringify(result, null, 2);
      }
      await loadAuditLog();
      await loadStatus();
      await loadDeploymentGuard();
      await loadGoLiveStatus();
    });
  }

  if (unlockDeploymentGuardBtn) {
    unlockDeploymentGuardBtn.addEventListener("click", async function () {
      const result = await apiPost("deployment.guard.unlock", {});
      if (deploymentGuardView) {
        deploymentGuardView.textContent = JSON.stringify(result, null, 2);
      }
      await loadAuditLog();
      await loadStatus();
      await loadDeploymentGuard();
      await loadGoLiveStatus();
    });
  }

  if (lockDeploymentGuardBtn) {
    lockDeploymentGuardBtn.addEventListener("click", async function () {
      const result = await apiPost("deployment.guard.lock", {});
      if (deploymentGuardView) {
        deploymentGuardView.textContent = JSON.stringify(result, null, 2);
      }
      await loadAuditLog();
      await loadStatus();
      await loadDeploymentGuard();
    });
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
