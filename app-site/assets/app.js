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
  const schedulerGateSummaryView = document.getElementById("schedulerGateSummaryView");
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
  const refreshReleaseGateBtn = document.getElementById("refreshReleaseGateBtn");
  const saveReleaseGateSettingsBtn = document.getElementById("saveReleaseGateSettingsBtn");
  const runReleaseGateWatchBtn = document.getElementById("runReleaseGateWatchBtn");
  const refreshReleaseGateRunsBtn = document.getElementById("refreshReleaseGateRunsBtn");
  const applyReleaseGateRunsFilterBtn = document.getElementById("applyReleaseGateRunsFilterBtn");
  const clearReleaseGateRunsFilterBtn = document.getElementById("clearReleaseGateRunsFilterBtn");
  const refreshReleaseGateRunsMetaBtn = document.getElementById("refreshReleaseGateRunsMetaBtn");
  const refreshReleaseGateQuickstatsBtn = document.getElementById("refreshReleaseGateQuickstatsBtn");
  const downloadReleaseGateRunsBtn = document.getElementById("downloadReleaseGateRunsBtn");
  const downloadReleaseGateBlockerReportBtn = document.getElementById("downloadReleaseGateBlockerReportBtn");
  const applyGatePresetBaselineMatchBtn = document.getElementById("applyGatePresetBaselineMatchBtn");
  const applyGatePresetBaselineCheckBtn = document.getElementById("applyGatePresetBaselineCheckBtn");
  const applyGatePresetSignoffWatchBtn = document.getElementById("applyGatePresetSignoffWatchBtn");
  const applyGatePresetSchedulerBlockedBtn = document.getElementById("applyGatePresetSchedulerBlockedBtn");
  const applyGatePresetManualBlockedBtn = document.getElementById("applyGatePresetManualBlockedBtn");
  const applyGatePresetSustainedActiveBtn = document.getElementById("applyGatePresetSustainedActiveBtn");
  const applyGatePresetSustainedClearBtn = document.getElementById("applyGatePresetSustainedClearBtn");
  const applyGatePresetSustainedAlertedBtn = document.getElementById("applyGatePresetSustainedAlertedBtn");
  const applyGatePresetStatusChangedBtn = document.getElementById("applyGatePresetStatusChangedBtn");
  const applyGatePresetSchedulerGroupBtn = document.getElementById("applyGatePresetSchedulerGroupBtn");
  const applyGatePresetManualGroupBtn = document.getElementById("applyGatePresetManualGroupBtn");
  const applyGatePresetHighNoiseBtn = document.getElementById("applyGatePresetHighNoiseBtn");
  const applyGatePresetLowNoiseBtn = document.getElementById("applyGatePresetLowNoiseBtn");
  const applyGatePresetToBlockedBtn = document.getElementById("applyGatePresetToBlockedBtn");
  const applyGatePresetToAllowedBtn = document.getElementById("applyGatePresetToAllowedBtn");
  const applyGatePresetSevereRatioBtn = document.getElementById("applyGatePresetSevereRatioBtn");
  const refreshWatchdogsStatusBtn = document.getElementById("refreshWatchdogsStatusBtn");
  const runWatchdogsCheckBtn = document.getElementById("runWatchdogsCheckBtn");
  const refreshWatchdogsRunsBtn = document.getElementById("refreshWatchdogsRunsBtn");
  const refreshWatchdogsIncidentBtn = document.getElementById("refreshWatchdogsIncidentBtn");
  const refreshWatchdogsIncidentSummaryBtn = document.getElementById("refreshWatchdogsIncidentSummaryBtn");
  const resolveWatchdogsIncidentBtn = document.getElementById("resolveWatchdogsIncidentBtn");
  const reopenWatchdogsIncidentBtn = document.getElementById("reopenWatchdogsIncidentBtn");
  const saveWatchdogsPolicyBtn = document.getElementById("saveWatchdogsPolicyBtn");
  const previewWatchdogsPolicyRestoreBtn = document.getElementById("previewWatchdogsPolicyRestoreBtn");
  const restoreWatchdogsPolicyBtn = document.getElementById("restoreWatchdogsPolicyBtn");
  const refreshWatchdogsPolicyHistoryBtn = document.getElementById("refreshWatchdogsPolicyHistoryBtn");
  const saveWatchdogsPolicyBaselineBtn = document.getElementById("saveWatchdogsPolicyBaselineBtn");
  const refreshWatchdogsPolicyBaselineBtn = document.getElementById("refreshWatchdogsPolicyBaselineBtn");
  const clearWatchdogsPolicyBaselineBtn = document.getElementById("clearWatchdogsPolicyBaselineBtn");
  const runWatchdogsPolicyBaselineCheckBtn = document.getElementById("runWatchdogsPolicyBaselineCheckBtn");
  const refreshWatchdogsPolicyBaselineRunsBtn = document.getElementById("refreshWatchdogsPolicyBaselineRunsBtn");
  const generateReleaseCandidateBtn = document.getElementById("generateReleaseCandidateBtn");
  const downloadArtifactManifestBtn = document.getElementById("downloadArtifactManifestBtn");
  const verifyArtifactManifestBtn = document.getElementById("verifyArtifactManifestBtn");
  const runInstallCheckBtn = document.getElementById("runInstallCheckBtn");
  const runDeploymentVerifyBtn = document.getElementById("runDeploymentVerifyBtn");
  const downloadHandoffBundleBtn = document.getElementById("downloadHandoffBundleBtn");
  const runCutoverPipelineBtn = document.getElementById("runCutoverPipelineBtn");
  const refreshCutoverReadinessBtn = document.getElementById("refreshCutoverReadinessBtn");
  const runSmokeSuiteBtn = document.getElementById("runSmokeSuiteBtn");
  const downloadSmokeHistoryBtn = document.getElementById("downloadSmokeHistoryBtn");
  const downloadCutoverEvidenceBtn = document.getElementById("downloadCutoverEvidenceBtn");
  const createCutoverSignoffBtn = document.getElementById("createCutoverSignoffBtn");
  const refreshCutoverSignoffsBtn = document.getElementById("refreshCutoverSignoffsBtn");
  const downloadLatestSignoffBtn = document.getElementById("downloadLatestSignoffBtn");
  const refreshActiveSignoffBtn = document.getElementById("refreshActiveSignoffBtn");
  const activateSignoffBtn = document.getElementById("activateSignoffBtn");
  const revokeSignoffBtn = document.getElementById("revokeSignoffBtn");
  const verifyLatestSignoffBtn = document.getElementById("verifyLatestSignoffBtn");
  const verifyAllSignoffsBtn = document.getElementById("verifyAllSignoffsBtn");
  const runSignoffIntegrityWatchBtn = document.getElementById("runSignoffIntegrityWatchBtn");
  const refreshSignoffIntegrityRunsBtn = document.getElementById("refreshSignoffIntegrityRunsBtn");
  const recordPublicSmokePassBtn = document.getElementById("recordPublicSmokePassBtn");
  const recordPublicSmokeFailBtn = document.getElementById("recordPublicSmokeFailBtn");
  const recordAuthSmokePassBtn = document.getElementById("recordAuthSmokePassBtn");
  const recordAuthSmokeFailBtn = document.getElementById("recordAuthSmokeFailBtn");
  const deploymentGuardForm = document.getElementById("deploymentGuardForm");
  const saveDeploymentGuardBtn = document.getElementById("saveDeploymentGuardBtn");
  const previewDeploymentGuardBtn = document.getElementById("previewDeploymentGuardBtn");
  const enableGuardBypassBtn = document.getElementById("enableGuardBypassBtn");
  const extendGuardBypassBtn = document.getElementById("extendGuardBypassBtn");
  const disableGuardBypassBtn = document.getElementById("disableGuardBypassBtn");
  const refreshBypassLogBtn = document.getElementById("refreshBypassLogBtn");
  const downloadIncidentReportBtn = document.getElementById("downloadIncidentReportBtn");
  const refreshIncidentReportsBtn = document.getElementById("refreshIncidentReportsBtn");
  const refreshIncidentSummaryBtn = document.getElementById("refreshIncidentSummaryBtn");
  const refreshIncidentSlaBtn = document.getElementById("refreshIncidentSlaBtn");
  const runIncidentSlaCheckBtn = document.getElementById("runIncidentSlaCheckBtn");
  const refreshIncidentSlaRunsBtn = document.getElementById("refreshIncidentSlaRunsBtn");
  const resolveIncidentBtn = document.getElementById("resolveIncidentBtn");
  const reopenIncidentBtn = document.getElementById("reopenIncidentBtn");
  const unlockDeploymentGuardBtn = document.getElementById("unlockDeploymentGuardBtn");
  const lockDeploymentGuardBtn = document.getElementById("lockDeploymentGuardBtn");
  const preflightView = document.getElementById("preflightView");
  const goLiveStatusView = document.getElementById("goLiveStatusView");
  const releaseGateFreshnessInput = document.getElementById("releaseGateFreshnessInput");
  const releaseGateRequireReadinessInput = document.getElementById("releaseGateRequireReadinessInput");
  const releaseGateRequirePublicSmokeInput = document.getElementById("releaseGateRequirePublicSmokeInput");
  const releaseGateRequireAuthSmokeInput = document.getElementById("releaseGateRequireAuthSmokeInput");
  const releaseGateRequireCutoverSignoffInput = document.getElementById("releaseGateRequireCutoverSignoffInput");
  const releaseGateRequireSignoffIntegrityInput = document.getElementById("releaseGateRequireSignoffIntegrityInput");
  const releaseGateRequireSignoffIntegrityWatchInput = document.getElementById("releaseGateRequireSignoffIntegrityWatchInput");
  const releaseGateRequirePolicyBaselineMatchInput = document.getElementById("releaseGateRequirePolicyBaselineMatchInput");
  const releaseGateRequirePolicyBaselineCheckInput = document.getElementById("releaseGateRequirePolicyBaselineCheckInput");
  const releaseGateSettingsView = document.getElementById("releaseGateSettingsView");
  const releaseGateView = document.getElementById("releaseGateView");
  const releaseGateWatchView = document.getElementById("releaseGateWatchView");
  const releaseGateQuickstatsView = document.getElementById("releaseGateQuickstatsView");
  const releaseGateRunsView = document.getElementById("releaseGateRunsView");
  const releaseGateRunSummaryView = document.getElementById("releaseGateRunSummaryView");
  const releaseGateRunDigestView = document.getElementById("releaseGateRunDigestView");
  const releaseGateRunsMetaView = document.getElementById("releaseGateRunsMetaView");
  const releaseGateSustainedView = document.getElementById("releaseGateSustainedView");
  const releaseGateRunsLimitInput = document.getElementById("releaseGateRunsLimitInput");
  const releaseGateRunsWindowInput = document.getElementById("releaseGateRunsWindowInput");
  const releaseGateRunsAllowedFilter = document.getElementById("releaseGateRunsAllowedFilter");
  const releaseGateRunsSustainedFilter = document.getElementById("releaseGateRunsSustainedFilter");
  const releaseGateRunsSustainedAlertFilter = document.getElementById("releaseGateRunsSustainedAlertFilter");
  const releaseGateRunsStatusChangeFilter = document.getElementById("releaseGateRunsStatusChangeFilter");
  const releaseGateRunsTransitionToFilter = document.getElementById("releaseGateRunsTransitionToFilter");
  const releaseGateRunsSourceGroupFilter = document.getElementById("releaseGateRunsSourceGroupFilter");
  const releaseGateRunsSourceFilter = document.getElementById("releaseGateRunsSourceFilter");
  const releaseGateRunsSourceContainsFilter = document.getElementById("releaseGateRunsSourceContainsFilter");
  const releaseGateRunsFailedItemFilter = document.getElementById("releaseGateRunsFailedItemFilter");
  const releaseGateRunsFailedItemMode = document.getElementById("releaseGateRunsFailedItemMode");
  const releaseGateRunsReasonMinInput = document.getElementById("releaseGateRunsReasonMinInput");
  const releaseGateRunsReasonMaxInput = document.getElementById("releaseGateRunsReasonMaxInput");
  const releaseGateRunsRecentRatioMinInput = document.getElementById("releaseGateRunsRecentRatioMinInput");
  const releaseGateRunsTransitionLimitInput = document.getElementById("releaseGateRunsTransitionLimitInput");
  const watchdogsStatusView = document.getElementById("watchdogsStatusView");
  const watchdogsCheckView = document.getElementById("watchdogsCheckView");
  const watchdogsRunsView = document.getElementById("watchdogsRunsView");
  const watchdogsIncidentView = document.getElementById("watchdogsIncidentView");
  const watchdogsIncidentSummaryView = document.getElementById("watchdogsIncidentSummaryView");
  const watchdogsIncidentNoteInput = document.getElementById("watchdogsIncidentNoteInput");
  const watchdogsAutoIncidentThresholdInput = document.getElementById("watchdogsAutoIncidentThresholdInput");
  const watchdogsAutoResolveThresholdInput = document.getElementById("watchdogsAutoResolveThresholdInput");
  const watchdogsPolicyHistoryIdInput = document.getElementById("watchdogsPolicyHistoryIdInput");
  const watchdogsPolicyRestoreMode = document.getElementById("watchdogsPolicyRestoreMode");
  const watchdogsPolicyView = document.getElementById("watchdogsPolicyView");
  const watchdogsPolicyPreviewView = document.getElementById("watchdogsPolicyPreviewView");
  const watchdogsPolicyBaselineView = document.getElementById("watchdogsPolicyBaselineView");
  const watchdogsPolicyBaselineCheckView = document.getElementById("watchdogsPolicyBaselineCheckView");
  const watchdogsPolicyHistoryView = document.getElementById("watchdogsPolicyHistoryView");
  const releaseCandidateView = document.getElementById("releaseCandidateView");
  const artifactManifestView = document.getElementById("artifactManifestView");
  const artifactBaselineInput = document.getElementById("artifactBaselineInput");
  const artifactVerifyView = document.getElementById("artifactVerifyView");
  const releaseLogView = document.getElementById("releaseLogView");
  const releaseCandidateNote = document.getElementById("releaseCandidateNote");
  const installCheckView = document.getElementById("installCheckView");
  const deploymentVerifyView = document.getElementById("deploymentVerifyView");
  const cutoverPipelineNote = document.getElementById("cutoverPipelineNote");
  const cutoverPipelineView = document.getElementById("cutoverPipelineView");
  const cutoverPipelineRunsView = document.getElementById("cutoverPipelineRunsView");
  const cutoverSmokeNote = document.getElementById("cutoverSmokeNote");
  const cutoverSignoffNote = document.getElementById("cutoverSignoffNote");
  const cutoverSignoffIdInput = document.getElementById("cutoverSignoffIdInput");
  const cutoverSignoffReasonInput = document.getElementById("cutoverSignoffReasonInput");
  const cutoverReadinessView = document.getElementById("cutoverReadinessView");
  const cutoverSmokeHistoryView = document.getElementById("cutoverSmokeHistoryView");
  const cutoverSmokeRecordView = document.getElementById("cutoverSmokeRecordView");
  const cutoverSignoffResultView = document.getElementById("cutoverSignoffResultView");
  const cutoverSignoffListView = document.getElementById("cutoverSignoffListView");
  const cutoverActiveSignoffView = document.getElementById("cutoverActiveSignoffView");
  const cutoverSignoffVerifyView = document.getElementById("cutoverSignoffVerifyView");
  const cutoverSignoffIntegrityWatchView = document.getElementById("cutoverSignoffIntegrityWatchView");
  const cutoverSignoffIntegrityRunsView = document.getElementById("cutoverSignoffIntegrityRunsView");
  const deploymentGuardView = document.getElementById("deploymentGuardView");
  const deploymentGuardPreviewView = document.getElementById("deploymentGuardPreviewView");
  const deploymentBypassLogView = document.getElementById("deploymentBypassLogView");
  const incidentReportNote = document.getElementById("incidentReportNote");
  const incidentReportsView = document.getElementById("incidentReportsView");
  const incidentSummaryView = document.getElementById("incidentSummaryView");
  const incidentSlaThresholdInput = document.getElementById("incidentSlaThresholdInput");
  const incidentSlaCooldownInput = document.getElementById("incidentSlaCooldownInput");
  const incidentSlaView = document.getElementById("incidentSlaView");
  const incidentSlaCheckView = document.getElementById("incidentSlaCheckView");
  const incidentSlaRunsView = document.getElementById("incidentSlaRunsView");
  const incidentReportIdInput = document.getElementById("incidentReportIdInput");
  const incidentStatusNoteInput = document.getElementById("incidentStatusNoteInput");
  const incidentStatusActionView = document.getElementById("incidentStatusActionView");
  const guardBypassReason = document.getElementById("guardBypassReason");
  const guardBypassDuration = document.getElementById("guardBypassDuration");
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
  const releaseGateRunsFilterStorageKey = "lc_release_gate_runs_filters_v1";

  async function apiGet(action) {
    const res = await fetch("/api/index.php?action=" + encodeURIComponent(action), {
      credentials: "same-origin",
    });
    return res.json();
  }

  async function apiGetWithParams(action, params) {
    const search = new URLSearchParams();
    search.set("action", action);
    Object.keys(params || {}).forEach(function (key) {
      const value = params[key];
      if (value === null || value === undefined || value === "") return;
      search.set(key, String(value));
    });
    const res = await fetch("/api/index.php?" + search.toString(), {
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
      if (schedulerGateSummaryView) {
        const summary = data && data.release_gate_watch_summary ? data.release_gate_watch_summary : {};
        const sustainedState = data && data.release_gate_sustained_state ? data.release_gate_sustained_state : {};
        const sustainedTimeline = data && data.release_gate_sustained_timeline ? data.release_gate_sustained_timeline : {};
        const quickstats = data && data.release_gate_quickstats ? data.release_gate_quickstats : {};
        const sustainedDigest = formatSustainedStateDigest(sustainedState);
        const sustainedTimelineDigest = formatSustainedTimelineDigest(sustainedTimeline);
        schedulerGateSummaryView.textContent = JSON.stringify({
          ok: true,
          release_gate_watch_summary: summary,
          release_gate_sustained_state: sustainedState,
          release_gate_sustained_timeline: sustainedTimeline,
          release_gate_quickstats: quickstats,
          release_gate_sustained_digest: sustainedDigest,
          release_gate_sustained_timeline_digest: sustainedTimelineDigest,
        }, null, 2);
      }
    } catch (err) {
      schedulerStatusView.textContent = "Failed to load scheduler status.";
      if (schedulerGateSummaryView) {
        schedulerGateSummaryView.textContent = "Failed to load scheduler gate summary.";
      }
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
    const freshness = releaseGateFreshnessInput && releaseGateFreshnessInput.value
      ? Number(releaseGateFreshnessInput.value)
      : 30;
    const safeFreshness = Number.isFinite(freshness) ? Math.max(5, Math.min(1440, Math.round(freshness))) : 30;
    try {
      const data = await apiGetWithParams("deployment.go_live_status", { freshness_minutes: safeFreshness });
      goLiveStatusView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      goLiveStatusView.textContent = "Failed to load go-live status.";
    }
  }

  async function loadReleaseGateSettings() {
    if (!releaseGateSettingsView) return;
    try {
      const data = await apiGet("deployment.release.gate.settings.get");
      releaseGateSettingsView.textContent = JSON.stringify(data, null, 2);
      const settings = data && data.settings ? data.settings : {};
      if (releaseGateFreshnessInput) {
        releaseGateFreshnessInput.value = String(settings.freshness_minutes || 30);
      }
      if (releaseGateRequireReadinessInput) {
        releaseGateRequireReadinessInput.checked = Number(settings.require_readiness) === 1;
      }
      if (releaseGateRequirePublicSmokeInput) {
        releaseGateRequirePublicSmokeInput.checked = Number(settings.require_public_smoke) === 1;
      }
      if (releaseGateRequireAuthSmokeInput) {
        releaseGateRequireAuthSmokeInput.checked = Number(settings.require_auth_smoke) === 1;
      }
      if (releaseGateRequireCutoverSignoffInput) {
        releaseGateRequireCutoverSignoffInput.checked = Number(settings.require_cutover_signoff) === 1;
      }
      if (releaseGateRequireSignoffIntegrityInput) {
        releaseGateRequireSignoffIntegrityInput.checked = Number(settings.require_signoff_integrity) === 1;
      }
      if (releaseGateRequireSignoffIntegrityWatchInput) {
        releaseGateRequireSignoffIntegrityWatchInput.checked = Number(settings.require_signoff_integrity_watch) === 1;
      }
      if (releaseGateRequirePolicyBaselineMatchInput) {
        releaseGateRequirePolicyBaselineMatchInput.checked = Number(settings.require_watchdogs_policy_baseline_match) === 1;
      }
      if (releaseGateRequirePolicyBaselineCheckInput) {
        releaseGateRequirePolicyBaselineCheckInput.checked = Number(settings.require_watchdogs_policy_baseline_check) === 1;
      }
    } catch (err) {
      releaseGateSettingsView.textContent = "Failed to load release gate settings.";
    }
  }

  async function loadReleaseGate() {
    if (!releaseGateView) return;
    const freshness = releaseGateFreshnessInput && releaseGateFreshnessInput.value
      ? Number(releaseGateFreshnessInput.value)
      : 30;
    const safeFreshness = Number.isFinite(freshness) ? Math.max(5, Math.min(1440, Math.round(freshness))) : 30;
    try {
      const data = await apiGetWithParams("deployment.release.gate", { freshness_minutes: safeFreshness });
      releaseGateView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      releaseGateView.textContent = "Failed to load release gate.";
    }
  }

  function collectReleaseGateRunsFilters() {
    const limitRaw = releaseGateRunsLimitInput && releaseGateRunsLimitInput.value
      ? Number(releaseGateRunsLimitInput.value)
      : 200;
    const safeLimit = Number.isFinite(limitRaw) ? Math.max(1, Math.min(400, Math.round(limitRaw))) : 200;
    const windowRaw = releaseGateRunsWindowInput && releaseGateRunsWindowInput.value
      ? Number(releaseGateRunsWindowInput.value)
      : 0;
    const safeWindow = Number.isFinite(windowRaw) ? Math.max(0, Math.min(400, Math.round(windowRaw))) : 0;
    const allowed = releaseGateRunsAllowedFilter && releaseGateRunsAllowedFilter.value
      ? releaseGateRunsAllowedFilter.value
      : "all";
    const sustained = releaseGateRunsSustainedFilter && releaseGateRunsSustainedFilter.value
      ? releaseGateRunsSustainedFilter.value
      : "all";
    const sustainedAlert = releaseGateRunsSustainedAlertFilter && releaseGateRunsSustainedAlertFilter.value
      ? releaseGateRunsSustainedAlertFilter.value
      : "all";
    const statusChange = releaseGateRunsStatusChangeFilter && releaseGateRunsStatusChangeFilter.value
      ? releaseGateRunsStatusChangeFilter.value
      : "all";
    const transitionTo = releaseGateRunsTransitionToFilter && releaseGateRunsTransitionToFilter.value
      ? releaseGateRunsTransitionToFilter.value
      : "all";
    const sourceGroup = releaseGateRunsSourceGroupFilter && releaseGateRunsSourceGroupFilter.value
      ? releaseGateRunsSourceGroupFilter.value
      : "all";
    const source = releaseGateRunsSourceFilter && releaseGateRunsSourceFilter.value
      ? releaseGateRunsSourceFilter.value.trim()
      : "";
    const sourceContains = releaseGateRunsSourceContainsFilter && releaseGateRunsSourceContainsFilter.value
      ? releaseGateRunsSourceContainsFilter.value.trim()
      : "";
    const failedItem = releaseGateRunsFailedItemFilter && releaseGateRunsFailedItemFilter.value
      ? releaseGateRunsFailedItemFilter.value.trim()
      : "";
    const failedItemMode = releaseGateRunsFailedItemMode && releaseGateRunsFailedItemMode.value
      ? releaseGateRunsFailedItemMode.value
      : "exact";
    const reasonMinRaw = releaseGateRunsReasonMinInput && releaseGateRunsReasonMinInput.value !== ""
      ? Number(releaseGateRunsReasonMinInput.value)
      : null;
    const reasonMaxRaw = releaseGateRunsReasonMaxInput && releaseGateRunsReasonMaxInput.value !== ""
      ? Number(releaseGateRunsReasonMaxInput.value)
      : null;
    const reasonMin = reasonMinRaw === null || !Number.isFinite(reasonMinRaw) ? null : Math.max(0, Math.min(50, Math.round(reasonMinRaw)));
    const reasonMax = reasonMaxRaw === null || !Number.isFinite(reasonMaxRaw) ? null : Math.max(0, Math.min(50, Math.round(reasonMaxRaw)));
    const recentRatioMinRaw = releaseGateRunsRecentRatioMinInput && releaseGateRunsRecentRatioMinInput.value !== ""
      ? Number(releaseGateRunsRecentRatioMinInput.value)
      : null;
    const recentRatioMin = recentRatioMinRaw === null || !Number.isFinite(recentRatioMinRaw)
      ? null
      : Math.max(0, Math.min(100, Math.round(recentRatioMinRaw * 10) / 10));
    const transitionLimitRaw = releaseGateRunsTransitionLimitInput && releaseGateRunsTransitionLimitInput.value
      ? Number(releaseGateRunsTransitionLimitInput.value)
      : 12;
    const transitionLimit = Number.isFinite(transitionLimitRaw) ? Math.max(1, Math.min(50, Math.round(transitionLimitRaw))) : 12;
    const filters = {
      limit: safeLimit,
      window: safeWindow,
      allowed: allowed,
      sustained: sustained,
      sustained_alert: sustainedAlert,
      status_change: statusChange,
      transition_to: transitionTo,
      source_group: sourceGroup,
      source: source,
      source_contains: sourceContains,
      failed_item: failedItem,
      failed_item_mode: failedItemMode,
      reason_count_min: reasonMin,
      reason_count_max: reasonMax,
      recent_ratio_min: recentRatioMin,
      transition_limit: transitionLimit,
    };
    try {
      if (window.localStorage) {
        window.localStorage.setItem(releaseGateRunsFilterStorageKey, JSON.stringify(filters));
      }
    } catch (err) {}
    return filters;
  }

  function formatReleaseGateRunDigest(summary, filters) {
    return formatReleaseGateRunDigestWithSustained(summary, filters, {}, {});
  }

  function formatSustainedStateDigest(sustainedState) {
    const s = sustainedState && typeof sustainedState === "object" ? sustainedState : {};
    const active = Number(s.active || 0) === 1;
    const ratioValue = Number(s.recent_ratio_percent || 0);
    const ratio = Number.isFinite(ratioValue) ? Math.round(ratioValue * 10) / 10 : 0;
    const windowRuns = Number(s.recent_window_runs || 0);
    const changedAt = s.last_changed_at ? String(s.last_changed_at) : "n/a";
    const alertAt = s.last_alert_at ? String(s.last_alert_at) : "n/a";
    return "state=" + (active ? "active" : "clear")
      + ", ratio=" + String(ratio) + "%"
      + ", window_runs=" + String(windowRuns)
      + ", last_changed_at=" + changedAt
      + ", last_alert_at=" + alertAt;
  }

  function formatSustainedTimelineDigest(sustainedTimeline) {
    const t = sustainedTimeline && typeof sustainedTimeline === "object" ? sustainedTimeline : {};
    const currentActive = Number(t.current_active || 0) === 1;
    const streakRuns = Number(t.current_streak_runs || 0);
    const currentSince = t.current_since ? String(t.current_since) : "n/a";
    const transitionCount = Number(t.transition_count || 0);
    const latestTransition = t.latest_transition && typeof t.latest_transition === "object" ? t.latest_transition : null;
    let latestText = "none";
    if (latestTransition) {
      latestText = String(latestTransition.transitioned_to || "unknown")
        + " at " + String(latestTransition.created_at || "n/a")
        + " via " + String(latestTransition.source || "n/a");
    }
    return "current_state=" + (currentActive ? "active" : "clear")
      + ", current_streak_runs=" + String(streakRuns)
      + ", current_since=" + currentSince
      + ", transitions=" + String(transitionCount)
      + ", latest_transition=" + latestText;
  }

  function formatReleaseGateRunDigestWithSustained(summary, filters, sustainedState, sustainedTimeline) {
    const s = summary && typeof summary === "object" ? summary : {};
    const f = filters && typeof filters === "object" ? filters : {};
    const totalRuns = Number(s.total_runs || 0);
    const allowedRuns = Number(s.allowed_runs || 0);
    const blockedRuns = Number(s.blocked_runs || 0);
    const baselineMatchBlocked = Number(s.baseline_match_blocked_runs || 0);
    const baselineCheckBlocked = Number(s.baseline_check_blocked_runs || 0);
    const signoffWatchBlocked = Number(s.signoff_integrity_watch_blocked_runs || 0);
    const sustainedActiveRuns = Number(s.sustained_active_runs || 0);
    const sustainedClearRuns = Number(s.sustained_clear_runs || 0);
    const sustainedAlertSentRuns = Number(s.sustained_alert_sent_runs || 0);
    const statusChangedRuns = Number(s.status_changed_runs || 0);
    const schedulerRuns = Number(s.scheduler_runs || 0);
    const manualRuns = Number(s.manual_runs || 0);
    const schedulerBlockedRuns = Number(s.scheduler_blocked_runs || 0);
    const manualBlockedRuns = Number(s.manual_blocked_runs || 0);
    const blockedRatio = Number(s.blocked_ratio_percent || 0);
    const allowedRatio = Number(s.allowed_ratio_percent || 0);
    const sustainedActiveRatio = Number(s.sustained_active_ratio_percent || 0);
    const sustainedAlertSentRatio = Number(s.sustained_alert_sent_ratio_percent || 0);
    const statusChangedRatio = Number(s.status_changed_ratio_percent || 0);
    const schedulerBlockedRatio = Number(s.scheduler_blocked_ratio_percent || 0);
    const manualBlockedRatio = Number(s.manual_blocked_ratio_percent || 0);
    const baselineMatchShare = Number(s.baseline_match_blocked_share_percent || 0);
    const baselineCheckShare = Number(s.baseline_check_blocked_share_percent || 0);
    const signoffShare = Number(s.signoff_integrity_watch_blocked_share_percent || 0);
    const latestAllowedRun = s.latest_allowed_run && typeof s.latest_allowed_run === "object" ? s.latest_allowed_run : null;
    const latestStatusChangeRun = s.latest_status_change_run && typeof s.latest_status_change_run === "object" ? s.latest_status_change_run : null;
    const topFailed = s.top_failed_items && typeof s.top_failed_items === "object" ? s.top_failed_items : {};
    const topFailedEntries = Object.entries(topFailed).slice(0, 5);
    const lines = [];
    lines.push("Release Gate Runs Digest");
    lines.push("Filters: limit=" + String(f.limit || 0) + ", window=" + String(f.window || 0) + ", allowed=" + String(f.allowed || "all")
      + ", sustained=" + String(f.sustained || "all")
      + ", sustained_alert=" + String(f.sustained_alert || "all")
      + ", status_change=" + String(f.status_change || "all")
      + ", transition_to=" + String(f.transition_to || "all")
      + ", source_group=" + String(f.source_group || "all")
      + ", source=" + String(f.source || "(any)")
      + ", source_contains=" + String(f.source_contains || "(any)")
      + ", failed_item=" + String(f.failed_item || "(any)")
      + ", failed_item_mode=" + String(f.failed_item_mode || "exact")
      + ", reason_count_min=" + (f.reason_count_min === null || f.reason_count_min === undefined ? "(any)" : String(f.reason_count_min))
      + ", reason_count_max=" + (f.reason_count_max === null || f.reason_count_max === undefined ? "(any)" : String(f.reason_count_max))
      + ", recent_ratio_min=" + (f.recent_ratio_min === null || f.recent_ratio_min === undefined ? "(any)" : String(f.recent_ratio_min))
      + ", transition_limit=" + String(f.transition_limit || 12));
    lines.push("Totals: total=" + String(totalRuns) + ", allowed=" + String(allowedRuns) + ", blocked=" + String(blockedRuns));
    lines.push("Ratios: allowed=" + String(allowedRatio) + "%, blocked=" + String(blockedRatio) + "%");
    lines.push("Blocker runs: baseline_match=" + String(baselineMatchBlocked)
      + ", baseline_check=" + String(baselineCheckBlocked)
      + ", signoff_watch=" + String(signoffWatchBlocked));
    lines.push("Blocked share: baseline_match=" + String(baselineMatchShare) + "%"
      + ", baseline_check=" + String(baselineCheckShare) + "%"
      + ", signoff_watch=" + String(signoffShare) + "%");
    lines.push("Sustained counters: active=" + String(sustainedActiveRuns)
      + ", clear=" + String(sustainedClearRuns)
      + ", alert_sent=" + String(sustainedAlertSentRuns)
      + ", status_changed=" + String(statusChangedRuns));
    lines.push("Source counters: scheduler_runs=" + String(schedulerRuns)
      + ", manual_runs=" + String(manualRuns)
      + ", scheduler_blocked=" + String(schedulerBlockedRuns)
      + ", manual_blocked=" + String(manualBlockedRuns));
    lines.push("Sustained ratios: active=" + String(sustainedActiveRatio) + "%"
      + ", alert_sent=" + String(sustainedAlertSentRatio) + "%"
      + ", status_changed=" + String(statusChangedRatio) + "%");
    lines.push("Source blocked ratios: scheduler=" + String(schedulerBlockedRatio) + "%"
      + ", manual=" + String(manualBlockedRatio) + "%");
    lines.push("Latest allowed run: " + (latestAllowedRun
      ? (String(latestAllowedRun.run_id || "n/a") + " @ " + String(latestAllowedRun.created_at || "n/a") + " via " + String(latestAllowedRun.source || "n/a"))
      : "none"));
    lines.push("Latest status change: " + (latestStatusChangeRun
      ? (String(latestStatusChangeRun.run_id || "n/a") + " @ " + String(latestStatusChangeRun.created_at || "n/a")
        + " via " + String(latestStatusChangeRun.source || "n/a")
        + " -> " + (Number(latestStatusChangeRun.allowed || 0) === 1 ? "allowed" : "blocked"))
      : "none"));
    lines.push("Sustained blocked trend: " + formatSustainedStateDigest(sustainedState));
    lines.push("Sustained transitions: " + formatSustainedTimelineDigest(sustainedTimeline));
    if (topFailedEntries.length > 0) {
      lines.push("Top failed items:");
      topFailedEntries.forEach(function (entry) {
        lines.push("- " + String(entry[0]) + ": " + String(entry[1]));
      });
    } else {
      lines.push("Top failed items: none");
    }
    return lines.join("\n");
  }

  async function loadReleaseGateRuns() {
    if (!releaseGateRunsView) return;
    const params = collectReleaseGateRunsFilters();
    try {
      const data = await apiGetWithParams("deployment.release.gate.runs", params);
      releaseGateRunsView.textContent = JSON.stringify(data, null, 2);
      const summary = data && data.summary ? data.summary : {};
      const sustainedState = data && data.sustained_state ? data.sustained_state : {};
      const sustainedTimeline = data && data.sustained_timeline ? data.sustained_timeline : {};
      const appliedFilters = data && data.applied_filters ? data.applied_filters : params;
      const sustainedTransitionLimit = data && data.sustained_transition_limit ? Number(data.sustained_transition_limit) : Number(params.transition_limit || 12);
      if (releaseGateRunSummaryView) {
        releaseGateRunSummaryView.textContent = JSON.stringify({
          ok: true,
          applied_filters: appliedFilters,
          summary: summary,
          sustained_state: sustainedState,
          sustained_timeline: sustainedTimeline,
          sustained_transition_limit: sustainedTransitionLimit,
        }, null, 2);
      }
      if (releaseGateRunDigestView) {
        const digestFilters = Object.assign({}, appliedFilters, { transition_limit: sustainedTransitionLimit });
        releaseGateRunDigestView.textContent = formatReleaseGateRunDigestWithSustained(summary, digestFilters, sustainedState, sustainedTimeline);
      }
      if (releaseGateSustainedView) {
        releaseGateSustainedView.textContent = JSON.stringify({
          ok: true,
          sustained_state: sustainedState,
          sustained_state_digest: formatSustainedStateDigest(sustainedState),
          sustained_timeline: sustainedTimeline,
          sustained_timeline_digest: formatSustainedTimelineDigest(sustainedTimeline),
          sustained_transition_limit: sustainedTransitionLimit,
        }, null, 2);
      }
      await loadReleaseGateRunsMeta(params);
      await loadReleaseGateQuickstats(params);
    } catch (err) {
      releaseGateRunsView.textContent = "Failed to load release gate runs.";
      if (releaseGateRunSummaryView) {
        releaseGateRunSummaryView.textContent = "Failed to load release gate run summary.";
      }
      if (releaseGateRunDigestView) {
        releaseGateRunDigestView.textContent = "Failed to load release gate run digest.";
      }
      if (releaseGateSustainedView) {
        releaseGateSustainedView.textContent = "Failed to load sustained gate trend.";
      }
      if (releaseGateRunsMetaView) {
        releaseGateRunsMetaView.textContent = "Failed to load gate filter metadata.";
      }
      if (releaseGateQuickstatsView) {
        releaseGateQuickstatsView.textContent = "Failed to load gate quickstats.";
      }
    }
  }

  async function loadReleaseGateRunsMeta(existingParams) {
    if (!releaseGateRunsMetaView) return;
    const params = existingParams && typeof existingParams === "object" ? existingParams : collectReleaseGateRunsFilters();
    try {
      const data = await apiGetWithParams("deployment.release.gate.runs.meta", params);
      releaseGateRunsMetaView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      releaseGateRunsMetaView.textContent = "Failed to load gate filter metadata.";
    }
  }

  async function loadReleaseGateQuickstats(existingParams) {
    if (!releaseGateQuickstatsView) return;
    const params = existingParams && typeof existingParams === "object" ? existingParams : collectReleaseGateRunsFilters();
    try {
      const data = await apiGetWithParams("deployment.release.gate.runs.quickstats", params);
      releaseGateQuickstatsView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      releaseGateQuickstatsView.textContent = "Failed to load gate quickstats.";
    }
  }

  async function downloadReleaseGateRuns() {
    const params = collectReleaseGateRunsFilters();
    const data = await apiGetWithParams("deployment.release.gate.runs.export", params);
    const filename = data && data.filename ? String(data.filename) : ("release-gate-runs-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function downloadReleaseGateBlockerReport() {
    const params = collectReleaseGateRunsFilters();
    const freshness = releaseGateFreshnessInput && releaseGateFreshnessInput.value
      ? Number(releaseGateFreshnessInput.value)
      : 30;
    const safeFreshness = Number.isFinite(freshness) ? Math.max(5, Math.min(1440, Math.round(freshness))) : 30;
    params.freshness_minutes = safeFreshness;
    const data = await apiGetWithParams("deployment.release.gate.blockers.report", params);
    const filename = data && data.filename ? String(data.filename) : ("release-gate-blockers-report-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  function resetReleaseGateRunsFilters() {
    if (releaseGateRunsLimitInput) {
      releaseGateRunsLimitInput.value = "200";
    }
    if (releaseGateRunsWindowInput) {
      releaseGateRunsWindowInput.value = "0";
    }
    if (releaseGateRunsAllowedFilter) {
      releaseGateRunsAllowedFilter.value = "all";
    }
    if (releaseGateRunsSustainedFilter) {
      releaseGateRunsSustainedFilter.value = "all";
    }
    if (releaseGateRunsSustainedAlertFilter) {
      releaseGateRunsSustainedAlertFilter.value = "all";
    }
    if (releaseGateRunsStatusChangeFilter) {
      releaseGateRunsStatusChangeFilter.value = "all";
    }
    if (releaseGateRunsTransitionToFilter) {
      releaseGateRunsTransitionToFilter.value = "all";
    }
    if (releaseGateRunsSourceGroupFilter) {
      releaseGateRunsSourceGroupFilter.value = "all";
    }
    if (releaseGateRunsSourceFilter) {
      releaseGateRunsSourceFilter.value = "";
    }
    if (releaseGateRunsSourceContainsFilter) {
      releaseGateRunsSourceContainsFilter.value = "";
    }
    if (releaseGateRunsFailedItemFilter) {
      releaseGateRunsFailedItemFilter.value = "";
    }
    if (releaseGateRunsFailedItemMode) {
      releaseGateRunsFailedItemMode.value = "exact";
    }
    if (releaseGateRunsReasonMinInput) {
      releaseGateRunsReasonMinInput.value = "";
    }
    if (releaseGateRunsReasonMaxInput) {
      releaseGateRunsReasonMaxInput.value = "";
    }
    if (releaseGateRunsRecentRatioMinInput) {
      releaseGateRunsRecentRatioMinInput.value = "";
    }
    if (releaseGateRunsTransitionLimitInput) {
      releaseGateRunsTransitionLimitInput.value = "12";
    }
    try {
      if (window.localStorage) {
        window.localStorage.removeItem(releaseGateRunsFilterStorageKey);
      }
    } catch (err) {}
  }

  function loadReleaseGateRunsFiltersFromStorage() {
    try {
      if (!window.localStorage) return;
      const raw = window.localStorage.getItem(releaseGateRunsFilterStorageKey);
      if (!raw) return;
      const stored = JSON.parse(raw);
      if (!stored || typeof stored !== "object") return;
      if (releaseGateRunsLimitInput && stored.limit !== undefined && stored.limit !== null) {
        const parsed = Number(stored.limit);
        const safe = Number.isFinite(parsed) ? Math.max(1, Math.min(400, Math.round(parsed))) : 200;
        releaseGateRunsLimitInput.value = String(safe);
      }
      if (releaseGateRunsWindowInput && stored.window !== undefined && stored.window !== null) {
        const parsed = Number(stored.window);
        const safe = Number.isFinite(parsed) ? Math.max(0, Math.min(400, Math.round(parsed))) : 0;
        releaseGateRunsWindowInput.value = String(safe);
      }
      if (releaseGateRunsAllowedFilter && typeof stored.allowed === "string") {
        const allowed = ["all", "allowed", "blocked"].includes(stored.allowed) ? stored.allowed : "all";
        releaseGateRunsAllowedFilter.value = allowed;
      }
      if (releaseGateRunsSustainedFilter && typeof stored.sustained === "string") {
        const sustained = ["all", "active", "clear"].includes(stored.sustained) ? stored.sustained : "all";
        releaseGateRunsSustainedFilter.value = sustained;
      }
      if (releaseGateRunsSustainedAlertFilter && typeof stored.sustained_alert === "string") {
        const sustainedAlert = ["all", "sent", "not_sent"].includes(stored.sustained_alert) ? stored.sustained_alert : "all";
        releaseGateRunsSustainedAlertFilter.value = sustainedAlert;
      }
      if (releaseGateRunsStatusChangeFilter && typeof stored.status_change === "string") {
        const statusChange = ["all", "changed", "stable"].includes(stored.status_change) ? stored.status_change : "all";
        releaseGateRunsStatusChangeFilter.value = statusChange;
      }
      if (releaseGateRunsTransitionToFilter && typeof stored.transition_to === "string") {
        const transitionTo = ["all", "to_blocked", "to_allowed"].includes(stored.transition_to) ? stored.transition_to : "all";
        releaseGateRunsTransitionToFilter.value = transitionTo;
      }
      if (releaseGateRunsSourceGroupFilter && typeof stored.source_group === "string") {
        const sourceGroup = ["all", "scheduler", "manual"].includes(stored.source_group) ? stored.source_group : "all";
        releaseGateRunsSourceGroupFilter.value = sourceGroup;
      }
      if (releaseGateRunsSourceFilter && typeof stored.source === "string") {
        releaseGateRunsSourceFilter.value = stored.source;
      }
      if (releaseGateRunsSourceContainsFilter && typeof stored.source_contains === "string") {
        releaseGateRunsSourceContainsFilter.value = stored.source_contains;
      }
      if (releaseGateRunsFailedItemFilter && typeof stored.failed_item === "string") {
        releaseGateRunsFailedItemFilter.value = stored.failed_item;
      }
      if (releaseGateRunsFailedItemMode && typeof stored.failed_item_mode === "string") {
        const mode = ["exact", "contains"].includes(stored.failed_item_mode) ? stored.failed_item_mode : "exact";
        releaseGateRunsFailedItemMode.value = mode;
      }
      if (releaseGateRunsReasonMinInput && stored.reason_count_min !== undefined && stored.reason_count_min !== null) {
        const parsed = Number(stored.reason_count_min);
        if (Number.isFinite(parsed)) {
          releaseGateRunsReasonMinInput.value = String(Math.max(0, Math.min(50, Math.round(parsed))));
        }
      }
      if (releaseGateRunsReasonMaxInput && stored.reason_count_max !== undefined && stored.reason_count_max !== null) {
        const parsed = Number(stored.reason_count_max);
        if (Number.isFinite(parsed)) {
          releaseGateRunsReasonMaxInput.value = String(Math.max(0, Math.min(50, Math.round(parsed))));
        }
      }
      if (releaseGateRunsRecentRatioMinInput && stored.recent_ratio_min !== undefined && stored.recent_ratio_min !== null) {
        const parsed = Number(stored.recent_ratio_min);
        if (Number.isFinite(parsed)) {
          releaseGateRunsRecentRatioMinInput.value = String(Math.max(0, Math.min(100, Math.round(parsed * 10) / 10)));
        }
      }
      if (releaseGateRunsTransitionLimitInput && stored.transition_limit !== undefined && stored.transition_limit !== null) {
        const parsed = Number(stored.transition_limit);
        const safe = Number.isFinite(parsed) ? Math.max(1, Math.min(50, Math.round(parsed))) : 12;
        releaseGateRunsTransitionLimitInput.value = String(safe);
      }
    } catch (err) {}
  }

  function applyReleaseGateRunsPreset(failedItem) {
    if (releaseGateRunsAllowedFilter) {
      releaseGateRunsAllowedFilter.value = "blocked";
    }
    if (releaseGateRunsSustainedFilter) {
      releaseGateRunsSustainedFilter.value = "all";
    }
    if (releaseGateRunsSustainedAlertFilter) {
      releaseGateRunsSustainedAlertFilter.value = "all";
    }
    if (releaseGateRunsStatusChangeFilter) {
      releaseGateRunsStatusChangeFilter.value = "all";
    }
    if (releaseGateRunsTransitionToFilter) {
      releaseGateRunsTransitionToFilter.value = "all";
    }
    if (releaseGateRunsSourceGroupFilter) {
      releaseGateRunsSourceGroupFilter.value = "all";
    }
    if (releaseGateRunsFailedItemFilter) {
      releaseGateRunsFailedItemFilter.value = failedItem || "";
    }
    if (releaseGateRunsFailedItemMode) {
      releaseGateRunsFailedItemMode.value = "exact";
    }
    if (releaseGateRunsReasonMinInput) {
      releaseGateRunsReasonMinInput.value = "";
    }
    if (releaseGateRunsReasonMaxInput) {
      releaseGateRunsReasonMaxInput.value = "";
    }
    if (releaseGateRunsRecentRatioMinInput) {
      releaseGateRunsRecentRatioMinInput.value = "";
    }
    if (releaseGateRunsSourceFilter) {
      releaseGateRunsSourceFilter.value = "";
    }
    if (releaseGateRunsSourceContainsFilter) {
      releaseGateRunsSourceContainsFilter.value = "";
    }
    if (releaseGateRunsLimitInput) {
      releaseGateRunsLimitInput.value = "200";
    }
    if (releaseGateRunsWindowInput) {
      releaseGateRunsWindowInput.value = "0";
    }
    if (releaseGateRunsTransitionLimitInput) {
      releaseGateRunsTransitionLimitInput.value = "12";
    }
  }

  function applyReleaseGateRunsSourcePreset(source) {
    if (releaseGateRunsAllowedFilter) {
      releaseGateRunsAllowedFilter.value = "blocked";
    }
    if (releaseGateRunsSustainedFilter) {
      releaseGateRunsSustainedFilter.value = "all";
    }
    if (releaseGateRunsSustainedAlertFilter) {
      releaseGateRunsSustainedAlertFilter.value = "all";
    }
    if (releaseGateRunsStatusChangeFilter) {
      releaseGateRunsStatusChangeFilter.value = "all";
    }
    if (releaseGateRunsTransitionToFilter) {
      releaseGateRunsTransitionToFilter.value = "all";
    }
    if (releaseGateRunsSourceGroupFilter) {
      releaseGateRunsSourceGroupFilter.value = "all";
    }
    if (releaseGateRunsSourceFilter) {
      releaseGateRunsSourceFilter.value = source || "";
    }
    if (releaseGateRunsSourceContainsFilter) {
      releaseGateRunsSourceContainsFilter.value = "";
    }
    if (releaseGateRunsFailedItemFilter) {
      releaseGateRunsFailedItemFilter.value = "";
    }
    if (releaseGateRunsFailedItemMode) {
      releaseGateRunsFailedItemMode.value = "exact";
    }
    if (releaseGateRunsReasonMinInput) {
      releaseGateRunsReasonMinInput.value = "";
    }
    if (releaseGateRunsReasonMaxInput) {
      releaseGateRunsReasonMaxInput.value = "";
    }
    if (releaseGateRunsRecentRatioMinInput) {
      releaseGateRunsRecentRatioMinInput.value = "";
    }
    if (releaseGateRunsLimitInput) {
      releaseGateRunsLimitInput.value = "200";
    }
    if (releaseGateRunsWindowInput) {
      releaseGateRunsWindowInput.value = "0";
    }
    if (releaseGateRunsTransitionLimitInput) {
      releaseGateRunsTransitionLimitInput.value = "12";
    }
  }

  function applyReleaseGateRunsSustainedPreset(sustained, allowed) {
    if (releaseGateRunsSustainedFilter) {
      releaseGateRunsSustainedFilter.value = sustained || "all";
    }
    if (releaseGateRunsSustainedAlertFilter) {
      releaseGateRunsSustainedAlertFilter.value = "all";
    }
    if (releaseGateRunsStatusChangeFilter) {
      releaseGateRunsStatusChangeFilter.value = "all";
    }
    if (releaseGateRunsTransitionToFilter) {
      releaseGateRunsTransitionToFilter.value = "all";
    }
    if (releaseGateRunsSourceGroupFilter) {
      releaseGateRunsSourceGroupFilter.value = "all";
    }
    if (releaseGateRunsAllowedFilter) {
      releaseGateRunsAllowedFilter.value = allowed || "all";
    }
    if (releaseGateRunsSourceFilter) {
      releaseGateRunsSourceFilter.value = "";
    }
    if (releaseGateRunsSourceContainsFilter) {
      releaseGateRunsSourceContainsFilter.value = "";
    }
    if (releaseGateRunsFailedItemFilter) {
      releaseGateRunsFailedItemFilter.value = "";
    }
    if (releaseGateRunsFailedItemMode) {
      releaseGateRunsFailedItemMode.value = "exact";
    }
    if (releaseGateRunsReasonMinInput) {
      releaseGateRunsReasonMinInput.value = "";
    }
    if (releaseGateRunsReasonMaxInput) {
      releaseGateRunsReasonMaxInput.value = "";
    }
    if (releaseGateRunsRecentRatioMinInput) {
      releaseGateRunsRecentRatioMinInput.value = "";
    }
    if (releaseGateRunsLimitInput) {
      releaseGateRunsLimitInput.value = "200";
    }
    if (releaseGateRunsWindowInput) {
      releaseGateRunsWindowInput.value = "0";
    }
    if (releaseGateRunsTransitionLimitInput) {
      releaseGateRunsTransitionLimitInput.value = "12";
    }
  }

  function applyReleaseGateRunsSustainedAlertPreset() {
    if (releaseGateRunsAllowedFilter) {
      releaseGateRunsAllowedFilter.value = "blocked";
    }
    if (releaseGateRunsSustainedFilter) {
      releaseGateRunsSustainedFilter.value = "active";
    }
    if (releaseGateRunsSustainedAlertFilter) {
      releaseGateRunsSustainedAlertFilter.value = "sent";
    }
    if (releaseGateRunsStatusChangeFilter) {
      releaseGateRunsStatusChangeFilter.value = "all";
    }
    if (releaseGateRunsTransitionToFilter) {
      releaseGateRunsTransitionToFilter.value = "all";
    }
    if (releaseGateRunsSourceGroupFilter) {
      releaseGateRunsSourceGroupFilter.value = "all";
    }
    if (releaseGateRunsSourceFilter) {
      releaseGateRunsSourceFilter.value = "";
    }
    if (releaseGateRunsSourceContainsFilter) {
      releaseGateRunsSourceContainsFilter.value = "";
    }
    if (releaseGateRunsFailedItemFilter) {
      releaseGateRunsFailedItemFilter.value = "";
    }
    if (releaseGateRunsFailedItemMode) {
      releaseGateRunsFailedItemMode.value = "exact";
    }
    if (releaseGateRunsReasonMinInput) {
      releaseGateRunsReasonMinInput.value = "";
    }
    if (releaseGateRunsReasonMaxInput) {
      releaseGateRunsReasonMaxInput.value = "";
    }
    if (releaseGateRunsRecentRatioMinInput) {
      releaseGateRunsRecentRatioMinInput.value = "";
    }
    if (releaseGateRunsLimitInput) {
      releaseGateRunsLimitInput.value = "200";
    }
    if (releaseGateRunsWindowInput) {
      releaseGateRunsWindowInput.value = "0";
    }
    if (releaseGateRunsTransitionLimitInput) {
      releaseGateRunsTransitionLimitInput.value = "12";
    }
  }

  function applyReleaseGateRunsStatusChangedPreset() {
    if (releaseGateRunsStatusChangeFilter) {
      releaseGateRunsStatusChangeFilter.value = "changed";
    }
    if (releaseGateRunsTransitionToFilter) {
      releaseGateRunsTransitionToFilter.value = "all";
    }
    if (releaseGateRunsSourceGroupFilter) {
      releaseGateRunsSourceGroupFilter.value = "all";
    }
    if (releaseGateRunsAllowedFilter) {
      releaseGateRunsAllowedFilter.value = "all";
    }
    if (releaseGateRunsSustainedFilter) {
      releaseGateRunsSustainedFilter.value = "all";
    }
    if (releaseGateRunsSustainedAlertFilter) {
      releaseGateRunsSustainedAlertFilter.value = "all";
    }
    if (releaseGateRunsSourceFilter) {
      releaseGateRunsSourceFilter.value = "";
    }
    if (releaseGateRunsSourceContainsFilter) {
      releaseGateRunsSourceContainsFilter.value = "";
    }
    if (releaseGateRunsFailedItemFilter) {
      releaseGateRunsFailedItemFilter.value = "";
    }
    if (releaseGateRunsFailedItemMode) {
      releaseGateRunsFailedItemMode.value = "exact";
    }
    if (releaseGateRunsReasonMinInput) {
      releaseGateRunsReasonMinInput.value = "";
    }
    if (releaseGateRunsReasonMaxInput) {
      releaseGateRunsReasonMaxInput.value = "";
    }
    if (releaseGateRunsRecentRatioMinInput) {
      releaseGateRunsRecentRatioMinInput.value = "";
    }
    if (releaseGateRunsLimitInput) {
      releaseGateRunsLimitInput.value = "200";
    }
    if (releaseGateRunsWindowInput) {
      releaseGateRunsWindowInput.value = "0";
    }
    if (releaseGateRunsTransitionLimitInput) {
      releaseGateRunsTransitionLimitInput.value = "12";
    }
  }

  function applyReleaseGateRunsSourceGroupPreset(group) {
    if (releaseGateRunsSourceGroupFilter) {
      releaseGateRunsSourceGroupFilter.value = group || "all";
    }
    if (releaseGateRunsAllowedFilter) {
      releaseGateRunsAllowedFilter.value = "all";
    }
    if (releaseGateRunsSustainedFilter) {
      releaseGateRunsSustainedFilter.value = "all";
    }
    if (releaseGateRunsSustainedAlertFilter) {
      releaseGateRunsSustainedAlertFilter.value = "all";
    }
    if (releaseGateRunsStatusChangeFilter) {
      releaseGateRunsStatusChangeFilter.value = "all";
    }
    if (releaseGateRunsTransitionToFilter) {
      releaseGateRunsTransitionToFilter.value = "all";
    }
    if (releaseGateRunsSourceFilter) {
      releaseGateRunsSourceFilter.value = "";
    }
    if (releaseGateRunsSourceContainsFilter) {
      releaseGateRunsSourceContainsFilter.value = "";
    }
    if (releaseGateRunsFailedItemFilter) {
      releaseGateRunsFailedItemFilter.value = "";
    }
    if (releaseGateRunsFailedItemMode) {
      releaseGateRunsFailedItemMode.value = "exact";
    }
    if (releaseGateRunsReasonMinInput) {
      releaseGateRunsReasonMinInput.value = "";
    }
    if (releaseGateRunsReasonMaxInput) {
      releaseGateRunsReasonMaxInput.value = "";
    }
    if (releaseGateRunsRecentRatioMinInput) {
      releaseGateRunsRecentRatioMinInput.value = "";
    }
    if (releaseGateRunsLimitInput) {
      releaseGateRunsLimitInput.value = "200";
    }
    if (releaseGateRunsWindowInput) {
      releaseGateRunsWindowInput.value = "0";
    }
    if (releaseGateRunsTransitionLimitInput) {
      releaseGateRunsTransitionLimitInput.value = "12";
    }
  }

  function applyReleaseGateRunsNoisePreset(reasonMin, reasonMax) {
    if (releaseGateRunsAllowedFilter) {
      releaseGateRunsAllowedFilter.value = "all";
    }
    if (releaseGateRunsSustainedFilter) {
      releaseGateRunsSustainedFilter.value = "all";
    }
    if (releaseGateRunsSustainedAlertFilter) {
      releaseGateRunsSustainedAlertFilter.value = "all";
    }
    if (releaseGateRunsStatusChangeFilter) {
      releaseGateRunsStatusChangeFilter.value = "all";
    }
    if (releaseGateRunsTransitionToFilter) {
      releaseGateRunsTransitionToFilter.value = "all";
    }
    if (releaseGateRunsSourceGroupFilter) {
      releaseGateRunsSourceGroupFilter.value = "all";
    }
    if (releaseGateRunsSourceFilter) {
      releaseGateRunsSourceFilter.value = "";
    }
    if (releaseGateRunsSourceContainsFilter) {
      releaseGateRunsSourceContainsFilter.value = "";
    }
    if (releaseGateRunsFailedItemFilter) {
      releaseGateRunsFailedItemFilter.value = "";
    }
    if (releaseGateRunsFailedItemMode) {
      releaseGateRunsFailedItemMode.value = "exact";
    }
    if (releaseGateRunsReasonMinInput) {
      releaseGateRunsReasonMinInput.value = reasonMin === null || reasonMin === undefined ? "" : String(reasonMin);
    }
    if (releaseGateRunsReasonMaxInput) {
      releaseGateRunsReasonMaxInput.value = reasonMax === null || reasonMax === undefined ? "" : String(reasonMax);
    }
    if (releaseGateRunsRecentRatioMinInput) {
      releaseGateRunsRecentRatioMinInput.value = "";
    }
    if (releaseGateRunsLimitInput) {
      releaseGateRunsLimitInput.value = "200";
    }
    if (releaseGateRunsWindowInput) {
      releaseGateRunsWindowInput.value = "0";
    }
    if (releaseGateRunsTransitionLimitInput) {
      releaseGateRunsTransitionLimitInput.value = "12";
    }
  }

  function applyReleaseGateRunsTransitionPreset(direction) {
    if (releaseGateRunsTransitionToFilter) {
      releaseGateRunsTransitionToFilter.value = direction || "all";
    }
    if (releaseGateRunsStatusChangeFilter) {
      releaseGateRunsStatusChangeFilter.value = "changed";
    }
    if (releaseGateRunsAllowedFilter) {
      releaseGateRunsAllowedFilter.value = "all";
    }
    if (releaseGateRunsSustainedFilter) {
      releaseGateRunsSustainedFilter.value = "all";
    }
    if (releaseGateRunsSustainedAlertFilter) {
      releaseGateRunsSustainedAlertFilter.value = "all";
    }
    if (releaseGateRunsSourceGroupFilter) {
      releaseGateRunsSourceGroupFilter.value = "all";
    }
    if (releaseGateRunsSourceFilter) {
      releaseGateRunsSourceFilter.value = "";
    }
    if (releaseGateRunsSourceContainsFilter) {
      releaseGateRunsSourceContainsFilter.value = "";
    }
    if (releaseGateRunsFailedItemFilter) {
      releaseGateRunsFailedItemFilter.value = "";
    }
    if (releaseGateRunsFailedItemMode) {
      releaseGateRunsFailedItemMode.value = "exact";
    }
    if (releaseGateRunsReasonMinInput) {
      releaseGateRunsReasonMinInput.value = "";
    }
    if (releaseGateRunsReasonMaxInput) {
      releaseGateRunsReasonMaxInput.value = "";
    }
    if (releaseGateRunsRecentRatioMinInput) {
      releaseGateRunsRecentRatioMinInput.value = "";
    }
    if (releaseGateRunsLimitInput) {
      releaseGateRunsLimitInput.value = "200";
    }
    if (releaseGateRunsWindowInput) {
      releaseGateRunsWindowInput.value = "0";
    }
    if (releaseGateRunsTransitionLimitInput) {
      releaseGateRunsTransitionLimitInput.value = "12";
    }
  }

  function applyReleaseGateRunsSevereRatioPreset() {
    if (releaseGateRunsRecentRatioMinInput) {
      releaseGateRunsRecentRatioMinInput.value = "70";
    }
    if (releaseGateRunsAllowedFilter) {
      releaseGateRunsAllowedFilter.value = "blocked";
    }
    if (releaseGateRunsSustainedFilter) {
      releaseGateRunsSustainedFilter.value = "active";
    }
    if (releaseGateRunsSustainedAlertFilter) {
      releaseGateRunsSustainedAlertFilter.value = "all";
    }
    if (releaseGateRunsStatusChangeFilter) {
      releaseGateRunsStatusChangeFilter.value = "all";
    }
    if (releaseGateRunsTransitionToFilter) {
      releaseGateRunsTransitionToFilter.value = "all";
    }
    if (releaseGateRunsSourceGroupFilter) {
      releaseGateRunsSourceGroupFilter.value = "all";
    }
    if (releaseGateRunsSourceFilter) {
      releaseGateRunsSourceFilter.value = "";
    }
    if (releaseGateRunsSourceContainsFilter) {
      releaseGateRunsSourceContainsFilter.value = "";
    }
    if (releaseGateRunsFailedItemFilter) {
      releaseGateRunsFailedItemFilter.value = "";
    }
    if (releaseGateRunsFailedItemMode) {
      releaseGateRunsFailedItemMode.value = "exact";
    }
    if (releaseGateRunsReasonMinInput) {
      releaseGateRunsReasonMinInput.value = "";
    }
    if (releaseGateRunsReasonMaxInput) {
      releaseGateRunsReasonMaxInput.value = "";
    }
    if (releaseGateRunsLimitInput) {
      releaseGateRunsLimitInput.value = "200";
    }
    if (releaseGateRunsWindowInput) {
      releaseGateRunsWindowInput.value = "0";
    }
    if (releaseGateRunsTransitionLimitInput) {
      releaseGateRunsTransitionLimitInput.value = "12";
    }
  }

  async function loadWatchdogsStatus() {
    if (!watchdogsStatusView) return;
    const freshness = releaseGateFreshnessInput && releaseGateFreshnessInput.value
      ? Number(releaseGateFreshnessInput.value)
      : 30;
    const safeFreshness = Number.isFinite(freshness) ? Math.max(5, Math.min(1440, Math.round(freshness))) : 30;
    try {
      const data = await apiGetWithParams("deployment.watchdogs.status", { freshness_minutes: safeFreshness });
      watchdogsStatusView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      watchdogsStatusView.textContent = "Failed to load watchdogs status.";
    }
  }

  async function loadWatchdogsRuns() {
    if (!watchdogsRunsView) return;
    try {
      const data = await apiGet("deployment.watchdogs.runs");
      watchdogsRunsView.textContent = JSON.stringify(data, null, 2);
      const state = data && data.state ? data.state : {};
      if (watchdogsCheckView && state && Object.keys(state).length > 0) {
        watchdogsCheckView.textContent = JSON.stringify({ ok: true, state: state }, null, 2);
      }
    } catch (err) {
      watchdogsRunsView.textContent = "Failed to load watchdogs check runs.";
    }
  }

  async function loadWatchdogsOpenIncident() {
    if (!watchdogsIncidentView) return;
    try {
      const data = await apiGet("deployment.watchdogs.incident.open");
      watchdogsIncidentView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      watchdogsIncidentView.textContent = "No open watchdog incident.";
    }
  }

  async function loadWatchdogsIncidentSummary() {
    if (!watchdogsIncidentSummaryView) return;
    try {
      const data = await apiGet("deployment.watchdogs.incident.summary");
      watchdogsIncidentSummaryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      watchdogsIncidentSummaryView.textContent = "Failed to load watchdogs incident summary.";
    }
  }

  async function loadWatchdogsPolicy() {
    if (!watchdogsPolicyView) return;
    try {
      const data = await apiGet("deployment.watchdogs.policy.get");
      watchdogsPolicyView.textContent = JSON.stringify(data, null, 2);
      const settings = data && data.settings ? data.settings : {};
      if (watchdogsAutoIncidentThresholdInput) {
        watchdogsAutoIncidentThresholdInput.value = String(settings.auto_incident_threshold || 2);
      }
      if (watchdogsAutoResolveThresholdInput) {
        watchdogsAutoResolveThresholdInput.value = String(settings.auto_resolve_ok_streak || 2);
      }
    } catch (err) {
      watchdogsPolicyView.textContent = "Failed to load watchdogs policy.";
    }
  }

  async function loadWatchdogsPolicyHistory() {
    if (!watchdogsPolicyHistoryView) return;
    try {
      const data = await apiGet("deployment.watchdogs.policy.history");
      watchdogsPolicyHistoryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      watchdogsPolicyHistoryView.textContent = "Failed to load watchdogs policy history.";
    }
  }

  async function previewWatchdogsPolicyRestore() {
    if (!watchdogsPolicyPreviewView) return;
    const historyId = watchdogsPolicyHistoryIdInput && watchdogsPolicyHistoryIdInput.value ? watchdogsPolicyHistoryIdInput.value.trim() : "";
    const mode = watchdogsPolicyRestoreMode && watchdogsPolicyRestoreMode.value ? watchdogsPolicyRestoreMode.value : "previous";
    const params = { mode: mode };
    if (historyId !== "") {
      params.history_id = historyId;
    }
    try {
      const result = await apiGetWithParams("deployment.watchdogs.policy.preview", params);
      watchdogsPolicyPreviewView.textContent = JSON.stringify(result, null, 2);
    } catch (err) {
      watchdogsPolicyPreviewView.textContent = "Failed to preview watchdogs policy restore.";
    }
  }

  async function loadWatchdogsPolicyBaseline() {
    if (!watchdogsPolicyBaselineView) return;
    try {
      const data = await apiGet("deployment.watchdogs.policy.baseline.get");
      watchdogsPolicyBaselineView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      watchdogsPolicyBaselineView.textContent = "Failed to load watchdogs policy baseline.";
    }
  }

  async function saveWatchdogsPolicyBaseline() {
    const historyId = watchdogsPolicyHistoryIdInput && watchdogsPolicyHistoryIdInput.value ? watchdogsPolicyHistoryIdInput.value.trim() : "";
    const mode = watchdogsPolicyRestoreMode && watchdogsPolicyRestoreMode.value ? watchdogsPolicyRestoreMode.value : "previous";
    const result = await apiPost("deployment.watchdogs.policy.baseline.set", {
      history_id: historyId,
      mode: mode,
      source: "dashboard_baseline",
    });
    if (watchdogsPolicyBaselineView) {
      watchdogsPolicyBaselineView.textContent = JSON.stringify(result, null, 2);
    }
    await loadWatchdogsPolicyBaseline();
    await loadWatchdogsPolicyBaselineRuns();
    await loadWatchdogsPolicy();
    await loadWatchdogsPolicyHistory();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function clearWatchdogsPolicyBaseline() {
    const result = await apiPost("deployment.watchdogs.policy.baseline.clear", {
      source: "dashboard_baseline",
    });
    if (watchdogsPolicyBaselineView) {
      watchdogsPolicyBaselineView.textContent = JSON.stringify(result, null, 2);
    }
    await loadWatchdogsPolicyBaseline();
    await loadWatchdogsPolicyBaselineRuns();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function loadWatchdogsPolicyBaselineRuns() {
    if (!watchdogsPolicyBaselineCheckView) return;
    try {
      const data = await apiGet("deployment.watchdogs.policy.baseline.runs");
      watchdogsPolicyBaselineCheckView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      watchdogsPolicyBaselineCheckView.textContent = "Failed to load watchdogs policy baseline checks.";
    }
  }

  async function runWatchdogsPolicyBaselineCheck() {
    const result = await apiPost("deployment.watchdogs.policy.baseline.check", {
      source: "dashboard_manual",
    });
    if (watchdogsPolicyBaselineCheckView) {
      watchdogsPolicyBaselineCheckView.textContent = JSON.stringify(result, null, 2);
    }
    await loadWatchdogsPolicyBaselineRuns();
    await loadWatchdogsPolicyBaseline();
    await loadSchedulerStatus();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function resolveWatchdogsIncident() {
    const note = watchdogsIncidentNoteInput && watchdogsIncidentNoteInput.value ? watchdogsIncidentNoteInput.value.trim() : "";
    const result = await apiPost("deployment.watchdogs.incident.resolve", { note: note });
    if (watchdogsIncidentView) {
      watchdogsIncidentView.textContent = JSON.stringify(result, null, 2);
    }
    await loadWatchdogsOpenIncident();
    await loadIncidentReports();
    await loadIncidentSummary();
    await loadIncidentSla();
    await loadWatchdogsRuns();
    await loadWatchdogsStatus();
    await loadWatchdogsIncidentSummary();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function reopenWatchdogsIncident() {
    const note = watchdogsIncidentNoteInput && watchdogsIncidentNoteInput.value ? watchdogsIncidentNoteInput.value.trim() : "";
    const result = await apiPost("deployment.watchdogs.incident.reopen", { note: note });
    if (watchdogsIncidentView) {
      watchdogsIncidentView.textContent = JSON.stringify(result, null, 2);
    }
    await loadWatchdogsOpenIncident();
    await loadIncidentReports();
    await loadIncidentSummary();
    await loadIncidentSla();
    await loadWatchdogsRuns();
    await loadWatchdogsStatus();
    await loadWatchdogsIncidentSummary();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function saveWatchdogsPolicy() {
    const incidentThreshold = watchdogsAutoIncidentThresholdInput && watchdogsAutoIncidentThresholdInput.value
      ? Number(watchdogsAutoIncidentThresholdInput.value)
      : 2;
    const resolveThreshold = watchdogsAutoResolveThresholdInput && watchdogsAutoResolveThresholdInput.value
      ? Number(watchdogsAutoResolveThresholdInput.value)
      : 2;
    const safeIncidentThreshold = Number.isFinite(incidentThreshold) ? Math.max(1, Math.min(10, Math.round(incidentThreshold))) : 2;
    const safeResolveThreshold = Number.isFinite(resolveThreshold) ? Math.max(1, Math.min(10, Math.round(resolveThreshold))) : 2;
    const result = await apiPost("deployment.watchdogs.policy.save", {
      auto_incident_threshold: safeIncidentThreshold,
      auto_resolve_ok_streak: safeResolveThreshold,
      source: "dashboard_manual",
    });
    if (watchdogsPolicyView) {
      watchdogsPolicyView.textContent = JSON.stringify(result, null, 2);
    }
    if (watchdogsPolicyPreviewView) {
      watchdogsPolicyPreviewView.textContent = "No restore preview yet.";
    }
    await loadWatchdogsPolicy();
    await loadWatchdogsPolicyHistory();
    await loadWatchdogsPolicyBaseline();
    await loadWatchdogsPolicyBaselineRuns();
    await loadWatchdogsRuns();
    await loadWatchdogsStatus();
    await loadSchedulerStatus();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function restoreWatchdogsPolicy() {
    const historyId = watchdogsPolicyHistoryIdInput && watchdogsPolicyHistoryIdInput.value ? watchdogsPolicyHistoryIdInput.value.trim() : "";
    const mode = watchdogsPolicyRestoreMode && watchdogsPolicyRestoreMode.value ? watchdogsPolicyRestoreMode.value : "previous";
    const result = await apiPost("deployment.watchdogs.policy.restore", {
      history_id: historyId,
      mode: mode,
      source: "dashboard_restore",
    });
    if (watchdogsPolicyView) {
      watchdogsPolicyView.textContent = JSON.stringify(result, null, 2);
    }
    if (watchdogsPolicyPreviewView) {
      watchdogsPolicyPreviewView.textContent = JSON.stringify(result, null, 2);
    }
    await loadWatchdogsPolicy();
    await loadWatchdogsPolicyHistory();
    await loadWatchdogsPolicyBaseline();
    await loadWatchdogsPolicyBaselineRuns();
    await loadWatchdogsRuns();
    await loadWatchdogsStatus();
    await loadSchedulerStatus();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function runWatchdogsCheck() {
    const freshness = releaseGateFreshnessInput && releaseGateFreshnessInput.value
      ? Number(releaseGateFreshnessInput.value)
      : 30;
    const safeFreshness = Number.isFinite(freshness) ? Math.max(5, Math.min(1440, Math.round(freshness))) : 30;
    const result = await apiPost("deployment.watchdogs.check", {
      source: "dashboard_manual",
      freshness_minutes: safeFreshness,
    });
    if (watchdogsCheckView) {
      watchdogsCheckView.textContent = JSON.stringify(result, null, 2);
    }
    await loadWatchdogsStatus();
    await loadWatchdogsRuns();
    await loadWatchdogsOpenIncident();
    await loadWatchdogsIncidentSummary();
    await loadSchedulerStatus();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function runReleaseGateWatch() {
    const freshness = releaseGateFreshnessInput && releaseGateFreshnessInput.value
      ? Number(releaseGateFreshnessInput.value)
      : 30;
    const safeFreshness = Number.isFinite(freshness) ? Math.max(5, Math.min(1440, Math.round(freshness))) : 30;
    const result = await apiPost("deployment.release.gate.watch", {
      source: "dashboard_manual",
      freshness_minutes: safeFreshness,
    });
    if (releaseGateWatchView) {
      releaseGateWatchView.textContent = JSON.stringify(result, null, 2);
    }
    await loadReleaseGate();
    await loadGoLiveStatus();
    await loadReleaseGateRuns();
    await loadWatchdogsStatus();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
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
      const launchWindowEnabled = document.getElementById("guardLaunchWindowEnabled");
      const launchWindowStart = document.getElementById("guardLaunchWindowStart");
      const launchWindowEnd = document.getElementById("guardLaunchWindowEnd");
      const backup = document.getElementById("guardBackupVerified");
      const cron = document.getElementById("guardCronConfigured");
      const rollback = document.getElementById("guardRollbackReady");
      const dns = document.getElementById("guardDnsReady");
      if (enforced) enforced.checked = Number(guard.enforced) === 1;
      if (launchWindowEnabled) launchWindowEnabled.checked = Number(guard.launch_window_enabled) === 1;
      if (launchWindowStart) launchWindowStart.value = guard.launch_window_start ? String(guard.launch_window_start).slice(0, 16) : "";
      if (launchWindowEnd) launchWindowEnd.value = guard.launch_window_end ? String(guard.launch_window_end).slice(0, 16) : "";
      if (guardBypassReason) guardBypassReason.value = guard.emergency_bypass_reason ? String(guard.emergency_bypass_reason) : "";
      if (backup) backup.checked = Number(checklist.backup_verified) === 1;
      if (cron) cron.checked = Number(checklist.cron_configured) === 1;
      if (rollback) rollback.checked = Number(checklist.rollback_plan_ready) === 1;
      if (dns) dns.checked = Number(checklist.dns_domain_ready) === 1;
    } catch (err) {
      deploymentGuardView.textContent = "Failed to load deployment guard.";
    }
  }

  async function loadCutoverPipelineRuns() {
    if (!cutoverPipelineRunsView) return;
    try {
      const data = await apiGet("deployment.pipeline.runs");
      cutoverPipelineRunsView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      cutoverPipelineRunsView.textContent = "Failed to load cutover pipeline history.";
    }
  }

  async function loadCutoverReadiness() {
    if (!cutoverReadinessView) return;
    try {
      const data = await apiGet("deployment.cutover.readiness");
      cutoverReadinessView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      cutoverReadinessView.textContent = "Failed to load cutover readiness.";
    }
  }

  async function loadSmokeHistory() {
    if (!cutoverSmokeHistoryView) return;
    try {
      const data = await apiGet("deployment.smoke.history");
      cutoverSmokeHistoryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      cutoverSmokeHistoryView.textContent = "Failed to load smoke history.";
    }
  }

  async function loadCutoverSignoffs() {
    if (!cutoverSignoffListView) return;
    try {
      const data = await apiGet("deployment.cutover.signoff.list");
      cutoverSignoffListView.textContent = JSON.stringify(data, null, 2);
      const latest = data && data.latest && data.latest.latest ? data.latest.latest : null;
      if (cutoverSignoffIdInput && latest && latest.signoff_id) {
        cutoverSignoffIdInput.value = String(latest.signoff_id);
      }
    } catch (err) {
      cutoverSignoffListView.textContent = "Failed to load cutover signoffs.";
    }
  }

  async function loadActiveCutoverSignoff() {
    if (!cutoverActiveSignoffView) return;
    try {
      const data = await apiGet("deployment.cutover.signoff.active");
      cutoverActiveSignoffView.textContent = JSON.stringify(data, null, 2);
      const active = data && data.active && data.active.active ? data.active.active : null;
      if (cutoverSignoffIdInput && active && active.signoff_id) {
        cutoverSignoffIdInput.value = String(active.signoff_id);
      }
    } catch (err) {
      cutoverActiveSignoffView.textContent = "Failed to load active signoff.";
    }
  }

  async function verifyLatestCutoverSignoff() {
    if (!cutoverSignoffVerifyView) return;
    try {
      const data = await apiGet("deployment.cutover.signoff.verify");
      cutoverSignoffVerifyView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      cutoverSignoffVerifyView.textContent = "Failed to verify latest signoff.";
    }
  }

  async function verifyAllCutoverSignoffs() {
    if (!cutoverSignoffVerifyView) return;
    try {
      const data = await apiGetWithParams("deployment.cutover.signoff.verify_all", { limit: 200 });
      cutoverSignoffVerifyView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      cutoverSignoffVerifyView.textContent = "Failed to verify all signoffs.";
    }
  }

  async function loadCutoverSignoffIntegrityRuns() {
    if (!cutoverSignoffIntegrityRunsView) return;
    try {
      const data = await apiGet("deployment.cutover.signoff.integrity.runs");
      cutoverSignoffIntegrityRunsView.textContent = JSON.stringify(data, null, 2);
      const state = data && data.state ? data.state : {};
      if (cutoverSignoffIntegrityWatchView && state && Object.keys(state).length > 0) {
        cutoverSignoffIntegrityWatchView.textContent = JSON.stringify({ ok: true, state: state }, null, 2);
      }
    } catch (err) {
      cutoverSignoffIntegrityRunsView.textContent = "Failed to load signoff integrity watch runs.";
    }
  }

  async function runCutoverSignoffIntegrityWatch(source) {
    const safeSource = source && String(source).trim() ? String(source).trim() : "dashboard_manual";
    const result = await apiPost("deployment.cutover.signoff.integrity.watch", { source: safeSource });
    if (cutoverSignoffIntegrityWatchView) {
      cutoverSignoffIntegrityWatchView.textContent = JSON.stringify(result, null, 2);
    }
    await loadCutoverSignoffIntegrityRuns();
    await loadActiveCutoverSignoff();
    await verifyLatestCutoverSignoff();
    await loadReleaseGate();
    await loadGoLiveStatus();
    await loadWatchdogsStatus();
    await loadSchedulerStatus();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function recordSmokeRun(smokeType, okValue) {
    const note = cutoverSmokeNote && cutoverSmokeNote.value ? cutoverSmokeNote.value.trim() : "";
    const payload = {
      smoke_type: smokeType,
      ok: okValue ? 1 : 0,
      source: "dashboard_manual",
      note: note,
      details: {
        ui_action: "cutover_readiness_card",
      },
    };
    const result = await apiPost("deployment.smoke.report", payload);
    if (cutoverSmokeRecordView) {
      cutoverSmokeRecordView.textContent = JSON.stringify(result, null, 2);
    }
    await loadCutoverReadiness();
    await loadSmokeHistory();
    await loadReleaseGate();
    await loadWatchdogsStatus();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function runSmokeSuite() {
    const note = cutoverSmokeNote && cutoverSmokeNote.value ? cutoverSmokeNote.value.trim() : "";
    const result = await apiPost("deployment.smoke.suite", { note: note });
    if (cutoverSmokeRecordView) {
      cutoverSmokeRecordView.textContent = JSON.stringify(result, null, 2);
    }
    await loadCutoverReadiness();
    await loadSmokeHistory();
    await loadReleaseGate();
    await loadWatchdogsStatus();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function createCutoverSignoff() {
    const note = cutoverSignoffNote && cutoverSignoffNote.value ? cutoverSignoffNote.value.trim() : "";
    const result = await apiPost("deployment.cutover.signoff.create", { note: note });
    if (cutoverSignoffResultView) {
      cutoverSignoffResultView.textContent = JSON.stringify(result, null, 2);
    }
    const signoff = result && result.signoff ? result.signoff : null;
    if (cutoverSignoffIdInput && signoff && signoff.signoff_id) {
      cutoverSignoffIdInput.value = String(signoff.signoff_id);
    }
    await loadCutoverSignoffs();
    await loadActiveCutoverSignoff();
    await verifyLatestCutoverSignoff();
    await loadCutoverReadiness();
    await loadReleaseGate();
    await loadGoLiveStatus();
    await loadWatchdogsStatus();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function activateCutoverSignoff() {
    const signoffId = cutoverSignoffIdInput && cutoverSignoffIdInput.value ? cutoverSignoffIdInput.value.trim() : "";
    if (!signoffId) {
      if (cutoverSignoffResultView) cutoverSignoffResultView.textContent = "Enter signoff ID first.";
      return;
    }
    const result = await apiPost("deployment.cutover.signoff.activate", { signoff_id: signoffId });
    if (cutoverSignoffResultView) {
      cutoverSignoffResultView.textContent = JSON.stringify(result, null, 2);
    }
    await loadCutoverSignoffs();
    await loadActiveCutoverSignoff();
    await verifyLatestCutoverSignoff();
    await loadReleaseGate();
    await loadGoLiveStatus();
    await loadWatchdogsStatus();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function revokeCutoverSignoff() {
    const signoffId = cutoverSignoffIdInput && cutoverSignoffIdInput.value ? cutoverSignoffIdInput.value.trim() : "";
    const reason = cutoverSignoffReasonInput && cutoverSignoffReasonInput.value ? cutoverSignoffReasonInput.value.trim() : "";
    if (!signoffId) {
      if (cutoverSignoffResultView) cutoverSignoffResultView.textContent = "Enter signoff ID first.";
      return;
    }
    const result = await apiPost("deployment.cutover.signoff.revoke", { signoff_id: signoffId, reason: reason });
    if (cutoverSignoffResultView) {
      cutoverSignoffResultView.textContent = JSON.stringify(result, null, 2);
    }
    await loadCutoverSignoffs();
    await loadActiveCutoverSignoff();
    await verifyLatestCutoverSignoff();
    await loadReleaseGate();
    await loadGoLiveStatus();
    await loadWatchdogsStatus();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
  }

  async function loadBypassLog() {
    if (!deploymentBypassLogView) return;
    try {
      const data = await apiGet("deployment.guard.bypass.log");
      deploymentBypassLogView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      deploymentBypassLogView.textContent = "Failed to load bypass log.";
    }
  }

  async function loadIncidentReports() {
    if (!incidentReportsView) return;
    try {
      const data = await apiGet("deployment.incident.reports");
      incidentReportsView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      incidentReportsView.textContent = "Failed to load incident reports.";
    }
  }

  async function loadIncidentSummary() {
    if (!incidentSummaryView) return;
    try {
      const data = await apiGet("deployment.incident.summary");
      incidentSummaryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      incidentSummaryView.textContent = "Failed to load incident summary.";
    }
  }

  async function loadIncidentSla() {
    if (!incidentSlaView) return;
    const threshold = incidentSlaThresholdInput && incidentSlaThresholdInput.value ? Number(incidentSlaThresholdInput.value) : 120;
    const safeThreshold = Number.isFinite(threshold) ? Math.max(5, Math.min(10080, Math.round(threshold))) : 120;
    try {
      const data = await apiGetWithParams("deployment.incident.sla", { threshold_minutes: safeThreshold });
      incidentSlaView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      incidentSlaView.textContent = "Failed to load incident SLA.";
    }
  }

  async function loadIncidentSlaRuns() {
    if (!incidentSlaRunsView) return;
    try {
      const data = await apiGet("deployment.incident.sla.runs");
      incidentSlaRunsView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      incidentSlaRunsView.textContent = "Failed to load incident SLA runs.";
    }
  }

  function collectDeploymentGuardPayload() {
    const enforced = document.getElementById("guardEnforced");
    const launchWindowEnabled = document.getElementById("guardLaunchWindowEnabled");
    const launchWindowStart = document.getElementById("guardLaunchWindowStart");
    const launchWindowEnd = document.getElementById("guardLaunchWindowEnd");
    const backup = document.getElementById("guardBackupVerified");
    const cron = document.getElementById("guardCronConfigured");
    const rollback = document.getElementById("guardRollbackReady");
    const dns = document.getElementById("guardDnsReady");
    return {
      enforced: enforced && enforced.checked ? 1 : 0,
      launch_window_enabled: launchWindowEnabled && launchWindowEnabled.checked ? 1 : 0,
      launch_window_start: launchWindowStart && launchWindowStart.value ? launchWindowStart.value.trim() : "",
      launch_window_end: launchWindowEnd && launchWindowEnd.value ? launchWindowEnd.value.trim() : "",
      checklist: {
        backup_verified: backup && backup.checked ? 1 : 0,
        cron_configured: cron && cron.checked ? 1 : 0,
        rollback_plan_ready: rollback && rollback.checked ? 1 : 0,
        dns_domain_ready: dns && dns.checked ? 1 : 0,
      },
    };
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

  loadReleaseGateRunsFiltersFromStorage();
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
    await loadReleaseGateSettings();
    await loadGoLiveStatus();
    await loadReleaseGate();
    await loadReleaseGateRuns();
    await loadWatchdogsStatus();
    await loadWatchdogsRuns();
    await loadWatchdogsOpenIncident();
    await loadWatchdogsIncidentSummary();
    await loadWatchdogsPolicy();
    await loadWatchdogsPolicyHistory();
    await loadWatchdogsPolicyBaseline();
    await loadWatchdogsPolicyBaselineRuns();
    await loadReleaseLog();
    await loadArtifactManifest();
    await loadCutoverReadiness();
    await loadSmokeHistory();
    await loadCutoverSignoffs();
    await loadActiveCutoverSignoff();
    await verifyLatestCutoverSignoff();
    await loadCutoverSignoffIntegrityRuns();
    await loadCutoverPipelineRuns();
    await loadBypassLog();
    await loadIncidentReports();
    await loadIncidentSummary();
    await loadIncidentSla();
    await loadIncidentSlaRuns();
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
      await loadReleaseGateRuns();
      await loadReleaseGate();
      await loadGoLiveStatus();
      await loadWatchdogsStatus();
      await loadWatchdogsRuns();
      await loadWatchdogsOpenIncident();
      await loadWatchdogsIncidentSummary();
      await loadCutoverSignoffIntegrityRuns();
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
      await loadReleaseGate();
      await loadWatchdogsStatus();
    });
  }

  if (refreshReleaseGateBtn) {
    refreshReleaseGateBtn.addEventListener("click", async function () {
      await loadReleaseGate();
      await loadGoLiveStatus();
      await loadReleaseGateRuns();
      await loadWatchdogsStatus();
    });
  }

  if (saveReleaseGateSettingsBtn) {
    saveReleaseGateSettingsBtn.addEventListener("click", async function () {
      const freshness = releaseGateFreshnessInput && releaseGateFreshnessInput.value
        ? Number(releaseGateFreshnessInput.value)
        : 30;
      const safeFreshness = Number.isFinite(freshness) ? Math.max(5, Math.min(1440, Math.round(freshness))) : 30;
      const payload = {
        freshness_minutes: safeFreshness,
        require_readiness: releaseGateRequireReadinessInput && releaseGateRequireReadinessInput.checked ? 1 : 0,
        require_public_smoke: releaseGateRequirePublicSmokeInput && releaseGateRequirePublicSmokeInput.checked ? 1 : 0,
        require_auth_smoke: releaseGateRequireAuthSmokeInput && releaseGateRequireAuthSmokeInput.checked ? 1 : 0,
        require_cutover_signoff: releaseGateRequireCutoverSignoffInput && releaseGateRequireCutoverSignoffInput.checked ? 1 : 0,
        require_signoff_integrity: releaseGateRequireSignoffIntegrityInput && releaseGateRequireSignoffIntegrityInput.checked ? 1 : 0,
        require_signoff_integrity_watch: releaseGateRequireSignoffIntegrityWatchInput && releaseGateRequireSignoffIntegrityWatchInput.checked ? 1 : 0,
        require_watchdogs_policy_baseline_match: releaseGateRequirePolicyBaselineMatchInput && releaseGateRequirePolicyBaselineMatchInput.checked ? 1 : 0,
        require_watchdogs_policy_baseline_check: releaseGateRequirePolicyBaselineCheckInput && releaseGateRequirePolicyBaselineCheckInput.checked ? 1 : 0,
      };
      const result = await apiPost("deployment.release.gate.settings.save", payload);
      if (releaseGateSettingsView) {
        releaseGateSettingsView.textContent = JSON.stringify(result, null, 2);
      }
      await loadReleaseGateSettings();
      await loadReleaseGate();
      await loadGoLiveStatus();
      await loadReleaseGateRuns();
      await loadWatchdogsStatus();
      await loadAuditLog();
      await loadStatus();
    });
  }

  if (runReleaseGateWatchBtn) {
    runReleaseGateWatchBtn.addEventListener("click", async function () {
      await runReleaseGateWatch();
    });
  }

  if (refreshReleaseGateRunsBtn) {
    refreshReleaseGateRunsBtn.addEventListener("click", async function () {
      await loadReleaseGateRuns();
      await loadWatchdogsStatus();
    });
  }

  if (applyReleaseGateRunsFilterBtn) {
    applyReleaseGateRunsFilterBtn.addEventListener("click", async function () {
      await loadReleaseGateRuns();
    });
  }

  if (clearReleaseGateRunsFilterBtn) {
    clearReleaseGateRunsFilterBtn.addEventListener("click", async function () {
      resetReleaseGateRunsFilters();
      await loadReleaseGateRuns();
    });
  }

  if (refreshReleaseGateRunsMetaBtn) {
    refreshReleaseGateRunsMetaBtn.addEventListener("click", async function () {
      await loadReleaseGateRunsMeta();
    });
  }

  if (refreshReleaseGateQuickstatsBtn) {
    refreshReleaseGateQuickstatsBtn.addEventListener("click", async function () {
      await loadReleaseGateQuickstats();
    });
  }

  if (downloadReleaseGateRunsBtn) {
    downloadReleaseGateRunsBtn.addEventListener("click", async function () {
      await downloadReleaseGateRuns();
    });
  }

  if (downloadReleaseGateBlockerReportBtn) {
    downloadReleaseGateBlockerReportBtn.addEventListener("click", async function () {
      await downloadReleaseGateBlockerReport();
    });
  }

  if (applyGatePresetBaselineMatchBtn) {
    applyGatePresetBaselineMatchBtn.addEventListener("click", async function () {
      applyReleaseGateRunsPreset("watchdogs_policy_baseline_match");
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetBaselineCheckBtn) {
    applyGatePresetBaselineCheckBtn.addEventListener("click", async function () {
      applyReleaseGateRunsPreset("watchdogs_policy_baseline_check_ok_and_fresh");
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetSignoffWatchBtn) {
    applyGatePresetSignoffWatchBtn.addEventListener("click", async function () {
      applyReleaseGateRunsPreset("cutover_signoff_integrity_watch_valid_and_fresh");
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetSchedulerBlockedBtn) {
    applyGatePresetSchedulerBlockedBtn.addEventListener("click", async function () {
      applyReleaseGateRunsSourcePreset("scheduler_tick_run");
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetManualBlockedBtn) {
    applyGatePresetManualBlockedBtn.addEventListener("click", async function () {
      applyReleaseGateRunsSourcePreset("dashboard_manual");
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetSustainedActiveBtn) {
    applyGatePresetSustainedActiveBtn.addEventListener("click", async function () {
      applyReleaseGateRunsSustainedPreset("active", "blocked");
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetSustainedClearBtn) {
    applyGatePresetSustainedClearBtn.addEventListener("click", async function () {
      applyReleaseGateRunsSustainedPreset("clear", "all");
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetSustainedAlertedBtn) {
    applyGatePresetSustainedAlertedBtn.addEventListener("click", async function () {
      applyReleaseGateRunsSustainedAlertPreset();
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetStatusChangedBtn) {
    applyGatePresetStatusChangedBtn.addEventListener("click", async function () {
      applyReleaseGateRunsStatusChangedPreset();
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetSchedulerGroupBtn) {
    applyGatePresetSchedulerGroupBtn.addEventListener("click", async function () {
      applyReleaseGateRunsSourceGroupPreset("scheduler");
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetManualGroupBtn) {
    applyGatePresetManualGroupBtn.addEventListener("click", async function () {
      applyReleaseGateRunsSourceGroupPreset("manual");
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetHighNoiseBtn) {
    applyGatePresetHighNoiseBtn.addEventListener("click", async function () {
      applyReleaseGateRunsNoisePreset(3, null);
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetLowNoiseBtn) {
    applyGatePresetLowNoiseBtn.addEventListener("click", async function () {
      applyReleaseGateRunsNoisePreset(null, 1);
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetToBlockedBtn) {
    applyGatePresetToBlockedBtn.addEventListener("click", async function () {
      applyReleaseGateRunsTransitionPreset("to_blocked");
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetToAllowedBtn) {
    applyGatePresetToAllowedBtn.addEventListener("click", async function () {
      applyReleaseGateRunsTransitionPreset("to_allowed");
      await loadReleaseGateRuns();
    });
  }

  if (applyGatePresetSevereRatioBtn) {
    applyGatePresetSevereRatioBtn.addEventListener("click", async function () {
      applyReleaseGateRunsSevereRatioPreset();
      await loadReleaseGateRuns();
    });
  }

  if (refreshWatchdogsStatusBtn) {
    refreshWatchdogsStatusBtn.addEventListener("click", async function () {
      await loadWatchdogsStatus();
      await loadWatchdogsRuns();
      await loadWatchdogsOpenIncident();
      await loadWatchdogsIncidentSummary();
    });
  }

  if (runWatchdogsCheckBtn) {
    runWatchdogsCheckBtn.addEventListener("click", async function () {
      await runWatchdogsCheck();
    });
  }

  if (refreshWatchdogsRunsBtn) {
    refreshWatchdogsRunsBtn.addEventListener("click", async function () {
      await loadWatchdogsRuns();
      await loadWatchdogsOpenIncident();
      await loadWatchdogsIncidentSummary();
      await loadSchedulerStatus();
    });
  }

  if (refreshWatchdogsIncidentBtn) {
    refreshWatchdogsIncidentBtn.addEventListener("click", async function () {
      await loadWatchdogsOpenIncident();
      await loadWatchdogsRuns();
      await loadWatchdogsStatus();
      await loadWatchdogsIncidentSummary();
    });
  }

  if (refreshWatchdogsIncidentSummaryBtn) {
    refreshWatchdogsIncidentSummaryBtn.addEventListener("click", async function () {
      await loadWatchdogsIncidentSummary();
      await loadWatchdogsOpenIncident();
      await loadWatchdogsRuns();
    });
  }

  if (resolveWatchdogsIncidentBtn) {
    resolveWatchdogsIncidentBtn.addEventListener("click", async function () {
      await resolveWatchdogsIncident();
    });
  }

  if (reopenWatchdogsIncidentBtn) {
    reopenWatchdogsIncidentBtn.addEventListener("click", async function () {
      await reopenWatchdogsIncident();
    });
  }

  if (saveWatchdogsPolicyBtn) {
    saveWatchdogsPolicyBtn.addEventListener("click", async function () {
      await saveWatchdogsPolicy();
    });
  }

  if (previewWatchdogsPolicyRestoreBtn) {
    previewWatchdogsPolicyRestoreBtn.addEventListener("click", async function () {
      await previewWatchdogsPolicyRestore();
    });
  }

  if (restoreWatchdogsPolicyBtn) {
    restoreWatchdogsPolicyBtn.addEventListener("click", async function () {
      await restoreWatchdogsPolicy();
    });
  }

  if (refreshWatchdogsPolicyHistoryBtn) {
    refreshWatchdogsPolicyHistoryBtn.addEventListener("click", async function () {
      await loadWatchdogsPolicyHistory();
      await loadWatchdogsPolicy();
    });
  }

  if (saveWatchdogsPolicyBaselineBtn) {
    saveWatchdogsPolicyBaselineBtn.addEventListener("click", async function () {
      await saveWatchdogsPolicyBaseline();
    });
  }

  if (refreshWatchdogsPolicyBaselineBtn) {
    refreshWatchdogsPolicyBaselineBtn.addEventListener("click", async function () {
      await loadWatchdogsPolicyBaseline();
      await loadWatchdogsPolicy();
    });
  }

  if (clearWatchdogsPolicyBaselineBtn) {
    clearWatchdogsPolicyBaselineBtn.addEventListener("click", async function () {
      await clearWatchdogsPolicyBaseline();
    });
  }

  if (runWatchdogsPolicyBaselineCheckBtn) {
    runWatchdogsPolicyBaselineCheckBtn.addEventListener("click", async function () {
      await runWatchdogsPolicyBaselineCheck();
    });
  }

  if (refreshWatchdogsPolicyBaselineRunsBtn) {
    refreshWatchdogsPolicyBaselineRunsBtn.addEventListener("click", async function () {
      await loadWatchdogsPolicyBaselineRuns();
      await loadWatchdogsPolicyBaseline();
    });
  }

  if (generateReleaseCandidateBtn) {
    generateReleaseCandidateBtn.addEventListener("click", async function () {
      const note = releaseCandidateNote && releaseCandidateNote.value ? releaseCandidateNote.value.trim() : "";
      const freshness = releaseGateFreshnessInput && releaseGateFreshnessInput.value
        ? Number(releaseGateFreshnessInput.value)
        : 30;
      const safeFreshness = Number.isFinite(freshness) ? Math.max(5, Math.min(1440, Math.round(freshness))) : 30;
      const result = await apiPost("deployment.release.candidate", {
        note: note,
        freshness_minutes: safeFreshness,
      });
      if (releaseCandidateView) {
        releaseCandidateView.textContent = JSON.stringify(result, null, 2);
      }
      await loadReleaseLog();
      await loadNotifications();
      await loadAuditLog();
      await loadStatus();
      await loadGoLiveStatus();
      await loadReleaseGate();
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
      await loadWatchdogsStatus();
      await loadCutoverReadiness();
    });
  }

  if (refreshCutoverReadinessBtn) {
    refreshCutoverReadinessBtn.addEventListener("click", async function () {
      await loadCutoverReadiness();
      await loadSmokeHistory();
      await loadCutoverSignoffs();
      await loadActiveCutoverSignoff();
      await verifyLatestCutoverSignoff();
      await loadCutoverSignoffIntegrityRuns();
    });
  }

  if (runSmokeSuiteBtn) {
    runSmokeSuiteBtn.addEventListener("click", async function () {
      await runSmokeSuite();
    });
  }

  if (downloadSmokeHistoryBtn) {
    downloadSmokeHistoryBtn.addEventListener("click", async function () {
      const data = await apiGet("deployment.smoke.history");
      if (cutoverSmokeHistoryView) {
        cutoverSmokeHistoryView.textContent = JSON.stringify(data, null, 2);
      }
      const history = data && data.history ? data.history : data;
      const stamp = history && history.generated_at ? String(history.generated_at).replace(/[:.]/g, "-") : "smoke_history";
      downloadJsonFile("deployment_smoke_history_" + stamp + ".json", data);
    });
  }

  if (downloadCutoverEvidenceBtn) {
    downloadCutoverEvidenceBtn.addEventListener("click", async function () {
      const note = cutoverSmokeNote && cutoverSmokeNote.value ? cutoverSmokeNote.value.trim() : "";
      const data = await apiGetWithParams("deployment.cutover.evidence.bundle", { note: note });
      if (cutoverSmokeRecordView) {
        cutoverSmokeRecordView.textContent = JSON.stringify(data, null, 2);
      }
      const bundle = data && data.bundle ? data.bundle : data;
      const bundleId = bundle && bundle.bundle_id ? bundle.bundle_id : "cutover_evidence_bundle";
      downloadJsonFile(bundleId + ".json", data);
      await loadAuditLog();
      await loadStatus();
    });
  }

  if (createCutoverSignoffBtn) {
    createCutoverSignoffBtn.addEventListener("click", async function () {
      await createCutoverSignoff();
    });
  }

  if (refreshCutoverSignoffsBtn) {
    refreshCutoverSignoffsBtn.addEventListener("click", async function () {
      await loadCutoverSignoffs();
      await loadActiveCutoverSignoff();
      await verifyLatestCutoverSignoff();
    });
  }

  if (downloadLatestSignoffBtn) {
    downloadLatestSignoffBtn.addEventListener("click", async function () {
      const data = await apiGet("deployment.cutover.signoff.latest");
      if (cutoverSignoffResultView) {
        cutoverSignoffResultView.textContent = JSON.stringify(data, null, 2);
      }
      const latest = data && data.latest ? data.latest : {};
      const signoff = latest && latest.latest ? latest.latest : null;
      if (!signoff || !signoff.signoff_id) {
        return;
      }
      downloadJsonFile(String(signoff.signoff_id) + ".json", data);
    });
  }

  if (refreshActiveSignoffBtn) {
    refreshActiveSignoffBtn.addEventListener("click", async function () {
      await loadActiveCutoverSignoff();
    });
  }

  if (activateSignoffBtn) {
    activateSignoffBtn.addEventListener("click", async function () {
      await activateCutoverSignoff();
    });
  }

  if (revokeSignoffBtn) {
    revokeSignoffBtn.addEventListener("click", async function () {
      await revokeCutoverSignoff();
    });
  }

  if (verifyLatestSignoffBtn) {
    verifyLatestSignoffBtn.addEventListener("click", async function () {
      await verifyLatestCutoverSignoff();
    });
  }

  if (verifyAllSignoffsBtn) {
    verifyAllSignoffsBtn.addEventListener("click", async function () {
      await verifyAllCutoverSignoffs();
    });
  }

  if (runSignoffIntegrityWatchBtn) {
    runSignoffIntegrityWatchBtn.addEventListener("click", async function () {
      await runCutoverSignoffIntegrityWatch("dashboard_manual");
    });
  }

  if (refreshSignoffIntegrityRunsBtn) {
    refreshSignoffIntegrityRunsBtn.addEventListener("click", async function () {
      await loadCutoverSignoffIntegrityRuns();
      await loadWatchdogsStatus();
      await loadSchedulerStatus();
    });
  }

  if (recordPublicSmokePassBtn) {
    recordPublicSmokePassBtn.addEventListener("click", async function () {
      await recordSmokeRun("public", true);
    });
  }

  if (recordPublicSmokeFailBtn) {
    recordPublicSmokeFailBtn.addEventListener("click", async function () {
      await recordSmokeRun("public", false);
    });
  }

  if (recordAuthSmokePassBtn) {
    recordAuthSmokePassBtn.addEventListener("click", async function () {
      await recordSmokeRun("auth", true);
    });
  }

  if (recordAuthSmokeFailBtn) {
    recordAuthSmokeFailBtn.addEventListener("click", async function () {
      await recordSmokeRun("auth", false);
    });
  }

  if (runCutoverPipelineBtn) {
    runCutoverPipelineBtn.addEventListener("click", async function () {
      const note = cutoverPipelineNote && cutoverPipelineNote.value ? cutoverPipelineNote.value.trim() : "";
      const result = await apiPost("deployment.pipeline.run", { note: note });
      if (cutoverPipelineView) {
        cutoverPipelineView.textContent = JSON.stringify(result, null, 2);
      }
      await loadCutoverPipelineRuns();
      await loadInstallCheck();
      await loadPreflight();
      await loadGoLiveStatus();
      await loadWatchdogsStatus();
      await loadDeploymentGuard();
      await loadAuditLog();
      await loadNotifications();
      await loadStatus();
      await loadCutoverReadiness();
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
      await loadWatchdogsStatus();
      await loadCutoverReadiness();
    });
  }

  if (deploymentGuardForm) {
    deploymentGuardForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
  }

  if (saveDeploymentGuardBtn) {
    saveDeploymentGuardBtn.addEventListener("click", async function () {
      const payload = collectDeploymentGuardPayload();
      const result = await apiPost("deployment.guard.save", payload);
      if (deploymentGuardView) {
        deploymentGuardView.textContent = JSON.stringify(result, null, 2);
      }
      await loadAuditLog();
      await loadStatus();
      await loadDeploymentGuard();
      await loadGoLiveStatus();
      await loadCutoverReadiness();
    });
  }

  if (previewDeploymentGuardBtn) {
    previewDeploymentGuardBtn.addEventListener("click", async function () {
      const payload = collectDeploymentGuardPayload();
      const result = await apiPost("deployment.guard.preview", payload);
      if (deploymentGuardPreviewView) {
        deploymentGuardPreviewView.textContent = JSON.stringify(result, null, 2);
      }
    });
  }

  if (enableGuardBypassBtn) {
    enableGuardBypassBtn.addEventListener("click", async function () {
      const reason = guardBypassReason && guardBypassReason.value ? guardBypassReason.value.trim() : "";
      const durationMinutes = guardBypassDuration && guardBypassDuration.value ? Number(guardBypassDuration.value) : 30;
      const result = await apiPost("deployment.guard.bypass.enable", {
        reason: reason,
        duration_minutes: durationMinutes,
      });
      if (deploymentGuardView) {
        deploymentGuardView.textContent = JSON.stringify(result, null, 2);
      }
      await loadDeploymentGuard();
      await loadGoLiveStatus();
      await loadNotifications();
      await loadAuditLog();
      await loadStatus();
      await loadBypassLog();
      await loadCutoverReadiness();
    });
  }

  if (extendGuardBypassBtn) {
    extendGuardBypassBtn.addEventListener("click", async function () {
      const reason = guardBypassReason && guardBypassReason.value ? guardBypassReason.value.trim() : "";
      const durationMinutes = guardBypassDuration && guardBypassDuration.value ? Number(guardBypassDuration.value) : 30;
      const result = await apiPost("deployment.guard.bypass.extend", {
        reason: reason,
        duration_minutes: durationMinutes,
      });
      if (deploymentGuardView) {
        deploymentGuardView.textContent = JSON.stringify(result, null, 2);
      }
      await loadDeploymentGuard();
      await loadGoLiveStatus();
      await loadNotifications();
      await loadAuditLog();
      await loadStatus();
      await loadBypassLog();
      await loadCutoverReadiness();
    });
  }

  if (disableGuardBypassBtn) {
    disableGuardBypassBtn.addEventListener("click", async function () {
      const result = await apiPost("deployment.guard.bypass.disable", {});
      if (deploymentGuardView) {
        deploymentGuardView.textContent = JSON.stringify(result, null, 2);
      }
      await loadDeploymentGuard();
      await loadGoLiveStatus();
      await loadNotifications();
      await loadAuditLog();
      await loadStatus();
      await loadBypassLog();
      await loadCutoverReadiness();
    });
  }

  if (refreshBypassLogBtn) {
    refreshBypassLogBtn.addEventListener("click", async function () {
      await loadBypassLog();
    });
  }

  if (downloadIncidentReportBtn) {
    downloadIncidentReportBtn.addEventListener("click", async function () {
      const note = incidentReportNote && incidentReportNote.value ? incidentReportNote.value.trim() : "";
      const data = await apiGetWithParams("deployment.incident.report", { note: note });
      if (deploymentGuardView) {
        deploymentGuardView.textContent = JSON.stringify(data, null, 2);
      }
      const report = data && data.report ? data.report : data;
      const reportId = report && report.report_id ? report.report_id : "incident_report";
      downloadJsonFile(reportId + ".json", data);
      await loadBypassLog();
      await loadAuditLog();
      await loadNotifications();
      await loadStatus();
      await loadIncidentReports();
      await loadIncidentSummary();
      await loadCutoverReadiness();
    });
  }

  if (refreshIncidentReportsBtn) {
    refreshIncidentReportsBtn.addEventListener("click", async function () {
      await loadIncidentReports();
    });
  }

  if (refreshIncidentSummaryBtn) {
    refreshIncidentSummaryBtn.addEventListener("click", async function () {
      await loadIncidentSummary();
    });
  }

  if (refreshIncidentSlaBtn) {
    refreshIncidentSlaBtn.addEventListener("click", async function () {
      await loadIncidentSla();
    });
  }

  if (runIncidentSlaCheckBtn) {
    runIncidentSlaCheckBtn.addEventListener("click", async function () {
      const threshold = incidentSlaThresholdInput && incidentSlaThresholdInput.value ? Number(incidentSlaThresholdInput.value) : 120;
      const cooldown = incidentSlaCooldownInput && incidentSlaCooldownInput.value ? Number(incidentSlaCooldownInput.value) : 30;
      const safeThreshold = Number.isFinite(threshold) ? Math.max(5, Math.min(10080, Math.round(threshold))) : 120;
      const safeCooldown = Number.isFinite(cooldown) ? Math.max(1, Math.min(1440, Math.round(cooldown))) : 30;
      const result = await apiPost("deployment.incident.sla.check", {
        threshold_minutes: safeThreshold,
        cooldown_minutes: safeCooldown,
      });
      if (incidentSlaCheckView) {
        incidentSlaCheckView.textContent = JSON.stringify(result, null, 2);
      }
      await loadIncidentSla();
      await loadIncidentSlaRuns();
      await loadNotifications();
      await loadAuditLog();
      await loadStatus();
      await loadCutoverReadiness();
    });
  }

  if (refreshIncidentSlaRunsBtn) {
    refreshIncidentSlaRunsBtn.addEventListener("click", async function () {
      await loadIncidentSlaRuns();
    });
  }

  async function runIncidentStatusAction(status) {
    const reportId = incidentReportIdInput && incidentReportIdInput.value ? incidentReportIdInput.value.trim() : "";
    const note = incidentStatusNoteInput && incidentStatusNoteInput.value ? incidentStatusNoteInput.value.trim() : "";
    if (!reportId) {
      if (incidentStatusActionView) incidentStatusActionView.textContent = "Enter incident report ID first.";
      return;
    }
    const result = await apiPost("deployment.incident.resolve", {
      report_id: reportId,
      status: status,
      note: note,
    });
    if (incidentStatusActionView) {
      incidentStatusActionView.textContent = JSON.stringify(result, null, 2);
    }
    await loadIncidentReports();
    await loadNotifications();
    await loadAuditLog();
    await loadStatus();
    await loadIncidentSummary();
    await loadIncidentSla();
    await loadCutoverReadiness();
  }

  if (resolveIncidentBtn) {
    resolveIncidentBtn.addEventListener("click", async function () {
      await runIncidentStatusAction("resolved");
    });
  }

  if (reopenIncidentBtn) {
    reopenIncidentBtn.addEventListener("click", async function () {
      await runIncidentStatusAction("reopened");
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
      await loadCutoverReadiness();
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
      await loadCutoverReadiness();
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
