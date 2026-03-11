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
  const downloadReleaseGateQuickstatsBtn = document.getElementById("downloadReleaseGateQuickstatsBtn");
  const refreshReleaseGateOpsSnapshotBtn = document.getElementById("refreshReleaseGateOpsSnapshotBtn");
  const downloadReleaseGateOpsSnapshotBtn = document.getElementById("downloadReleaseGateOpsSnapshotBtn");
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
  const releaseGateOpsSnapshotView = document.getElementById("releaseGateOpsSnapshotView");
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
  const refreshLeadsInventoryBtn = document.getElementById("refreshLeadsInventoryBtn");
  const refreshLeadsListBtn = document.getElementById("refreshLeadsListBtn");
  const refreshLeadsPushPlanBtn = document.getElementById("refreshLeadsPushPlanBtn");
  const refreshLeadsQualityBtn = document.getElementById("refreshLeadsQualityBtn");
  const refreshLeadsDeliveryHistoryBtn = document.getElementById("refreshLeadsDeliveryHistoryBtn");
  const refreshLeadsReviewQueueBtn = document.getElementById("refreshLeadsReviewQueueBtn");
  const downloadLeadsReviewQueueBtn = document.getElementById("downloadLeadsReviewQueueBtn");
  const loadLeadsReviewDetailBtn = document.getElementById("loadLeadsReviewDetailBtn");
  const checkLeadsReviewDuplicatesBtn = document.getElementById("checkLeadsReviewDuplicatesBtn");
  const saveLeadsReviewBtn = document.getElementById("saveLeadsReviewBtn");
  const discardLeadsReviewBtn = document.getElementById("discardLeadsReviewBtn");
  const saveLeadsReviewQueueBtn = document.getElementById("saveLeadsReviewQueueBtn");
  const discardLeadsReviewQueueBtn = document.getElementById("discardLeadsReviewQueueBtn");
  const updateLeadsReviewDraftBtn = document.getElementById("updateLeadsReviewDraftBtn");
  const leadsReviewDraftSelect = document.getElementById("leadsReviewDraftSelect");
  const runLeadsCrmSyncBtn = document.getElementById("runLeadsCrmSyncBtn");
  const runLeadsRetryQueueBtn = document.getElementById("runLeadsRetryQueueBtn");
  const downloadLeadsExportBtn = document.getElementById("downloadLeadsExportBtn");
  const leadsInventoryView = document.getElementById("leadsInventoryView");
  const leadsListView = document.getElementById("leadsListView");
  const leadsPushPlanView = document.getElementById("leadsPushPlanView");
  const leadsPushResultView = document.getElementById("leadsPushResultView");
  const leadsQualityView = document.getElementById("leadsQualityView");
  const leadsDeliveryHistoryView = document.getElementById("leadsDeliveryHistoryView");
  const leadsReviewQueueView = document.getElementById("leadsReviewQueueView");
  const leadsReviewDetailView = document.getElementById("leadsReviewDetailView");
  const leadsReviewDuplicatesView = document.getElementById("leadsReviewDuplicatesView");
  const leadsReviewActionView = document.getElementById("leadsReviewActionView");
  const crmConnectors = document.getElementById("crmConnectors");
  const refreshCrmConnectorsBtn = document.getElementById("refreshCrmConnectorsBtn");
  const crmConnectorFilterProvider = document.getElementById("crmConnectorFilterProvider");
  const crmConnectorFilterStatus = document.getElementById("crmConnectorFilterStatus");
  const crmConnectorFilterSite = document.getElementById("crmConnectorFilterSite");
  const crmConnectorFilterRunMode = document.getElementById("crmConnectorFilterRunMode");
  const crmConnectorFilterSearch = document.getElementById("crmConnectorFilterSearch");
  const crmConnectorFilterPage = document.getElementById("crmConnectorFilterPage");
  const crmConnectorFilterLimit = document.getElementById("crmConnectorFilterLimit");
  const refreshCrmConnectorDetailBtn = document.getElementById("refreshCrmConnectorDetailBtn");
  const downloadCrmConnectorsExportBtn = document.getElementById("downloadCrmConnectorsExportBtn");
  const runCrmConnectorBulkUpdateBtn = document.getElementById("runCrmConnectorBulkUpdateBtn");
  const crmConnectorForm = document.getElementById("crmConnectorForm");
  const runCrmSyncBtn = document.getElementById("runCrmSyncBtn");
  const runRetryQueueBtn = document.getElementById("runRetryQueueBtn");
  const refreshCrmDeliverySummaryBtn = document.getElementById("refreshCrmDeliverySummaryBtn");
  const refreshCrmDeliveryDetailBtn = document.getElementById("refreshCrmDeliveryDetailBtn");
  const downloadCrmDeliveryExportBtn = document.getElementById("downloadCrmDeliveryExportBtn");
  const refreshCrmDeliveryWatchBtn = document.getElementById("refreshCrmDeliveryWatchBtn");
  const runCrmDeliveryWatchBtn = document.getElementById("runCrmDeliveryWatchBtn");
  const crmSyncResult = document.getElementById("crmSyncResult");
  const crmConnectorDetail = document.getElementById("crmConnectorDetail");
  const crmConnectorBulkResult = document.getElementById("crmConnectorBulkResult");
  const crmDeliverySummary = document.getElementById("crmDeliverySummary");
  const crmDeliveryConnectorDetail = document.getElementById("crmDeliveryConnectorDetail");
  const crmDeliveryWatchView = document.getElementById("crmDeliveryWatchView");
  const refreshCrmSyncLogBtn = document.getElementById("refreshCrmSyncLogBtn");
  const loadCrmSyncDetailBtn = document.getElementById("loadCrmSyncDetailBtn");
  const downloadCrmSyncExportBtn = document.getElementById("downloadCrmSyncExportBtn");
  const refreshCrmRetryQueueBtn = document.getElementById("refreshCrmRetryQueueBtn");
  const loadCrmRetryDetailBtn = document.getElementById("loadCrmRetryDetailBtn");
  const downloadCrmRetryExportBtn = document.getElementById("downloadCrmRetryExportBtn");
  const crmSyncLog = document.getElementById("crmSyncLog");
  const crmSyncDetail = document.getElementById("crmSyncDetail");
  const crmRetryQueue = document.getElementById("crmRetryQueue");
  const crmRetryDetail = document.getElementById("crmRetryDetail");
  const refreshCrmSmtpBtn = document.getElementById("refreshCrmSmtpBtn");
  const refreshCrmSmtpWatchBtn = document.getElementById("refreshCrmSmtpWatchBtn");
  const runCrmSmtpWatchBtn = document.getElementById("runCrmSmtpWatchBtn");
  const crmSmtpForm = document.getElementById("crmSmtpForm");
  const crmSmtpSummary = document.getElementById("crmSmtpSummary");
  const crmSmtpDetail = document.getElementById("crmSmtpDetail");
  const crmSmtpResult = document.getElementById("crmSmtpResult");
  const crmSmtpWatchView = document.getElementById("crmSmtpWatchView");
  const downloadCrmSmtpExportBtn = document.getElementById("downloadCrmSmtpExportBtn");
  const downloadCrmSmtpWatchExportBtn = document.getElementById("downloadCrmSmtpWatchExportBtn");
  const refreshCrmEmailTemplatesBtn = document.getElementById("refreshCrmEmailTemplatesBtn");
  const loadCrmEmailTemplateBtn = document.getElementById("loadCrmEmailTemplateBtn");
  const saveCrmEmailTemplateBtn = document.getElementById("saveCrmEmailTemplateBtn");
  const previewCrmEmailTemplateBtn = document.getElementById("previewCrmEmailTemplateBtn");
  const sendCrmEmailTemplateTestBtn = document.getElementById("sendCrmEmailTemplateTestBtn");
  const refreshCrmEmailTemplateLogBtn = document.getElementById("refreshCrmEmailTemplateLogBtn");
  const crmEmailTemplatesSummary = document.getElementById("crmEmailTemplatesSummary");
  const crmEmailTemplatesResult = document.getElementById("crmEmailTemplatesResult");
  const crmEmailTemplatePreview = document.getElementById("crmEmailTemplatePreview");
  const crmEmailTemplateTestLog = document.getElementById("crmEmailTemplateTestLog");
  const crmEmailTemplateLogDetail = document.getElementById("crmEmailTemplateLogDetail");
  const crmEmailTemplateSites = document.getElementById("crmEmailTemplateSites");
  const downloadCrmEmailTemplatesExportBtn = document.getElementById("downloadCrmEmailTemplatesExportBtn");
  const downloadCrmEmailTemplateLogExportBtn = document.getElementById("downloadCrmEmailTemplateLogExportBtn");
  const crmOperationsSnapshot = document.getElementById("crmOperationsSnapshot");
  const downloadCrmOperationsSnapshotBtn = document.getElementById("downloadCrmOperationsSnapshotBtn");
  const crmOperationsHistory = document.getElementById("crmOperationsHistory");
  const crmOperationsHistorySummary = document.getElementById("crmOperationsHistorySummary");
  const crmOperationsLatestCompare = document.getElementById("crmOperationsLatestCompare");
  const crmOperationsIssuesSummary = document.getElementById("crmOperationsIssuesSummary");
  const refreshSocialPlatformsBtn = document.getElementById("refreshSocialPlatformsBtn");
  const refreshSocialCapabilitiesBtn = document.getElementById("refreshSocialCapabilitiesBtn");
  const refreshSocialWatchBtn = document.getElementById("refreshSocialWatchBtn");
  const runSocialWatchBtn = document.getElementById("runSocialWatchBtn");
  const refreshSocialOpsSnapshotBtn = document.getElementById("refreshSocialOpsSnapshotBtn");
  const refreshSocialDraftsBtn = document.getElementById("refreshSocialDraftsBtn");
  const refreshSocialDraftListBtn = document.getElementById("refreshSocialDraftListBtn");
  const refreshSocialDraftDetailBtn = document.getElementById("refreshSocialDraftDetailBtn");
  const refreshSocialDraftRecommendationsBtn = document.getElementById("refreshSocialDraftRecommendationsBtn");
  const refreshSocialDraftVariantsBtn = document.getElementById("refreshSocialDraftVariantsBtn");
  const downloadSocialDraftVariantsExportBtn = document.getElementById("downloadSocialDraftVariantsExportBtn");
  const runSocialDraftScheduleBtn = document.getElementById("runSocialDraftScheduleBtn");
  const runSocialDraftBulkScheduleBtn = document.getElementById("runSocialDraftBulkScheduleBtn");
  const refreshSocialConnectorsBtn = document.getElementById("refreshSocialConnectorsBtn");
  const refreshSocialConnectorDetailBtn = document.getElementById("refreshSocialConnectorDetailBtn");
  const refreshSocialConnectorRuleAuditBtn = document.getElementById("refreshSocialConnectorRuleAuditBtn");
  const loadSocialConnectorRuleAuditDetailBtn = document.getElementById("loadSocialConnectorRuleAuditDetailBtn");
  const downloadSocialConnectorRuleAuditExportBtn = document.getElementById("downloadSocialConnectorRuleAuditExportBtn");
  const downloadSocialConnectorsExportBtn = document.getElementById("downloadSocialConnectorsExportBtn");
  const runSocialConnectorBulkUpdateBtn = document.getElementById("runSocialConnectorBulkUpdateBtn");
  const refreshSocialRetryQueueBtn = document.getElementById("refreshSocialRetryQueueBtn");
  const loadSocialRetryDetailBtn = document.getElementById("loadSocialRetryDetailBtn");
  const downloadSocialRetryExportBtn = document.getElementById("downloadSocialRetryExportBtn");
  const runSocialRetryBulkUpdateBtn = document.getElementById("runSocialRetryBulkUpdateBtn");
  const refreshSocialSyncLogBtn = document.getElementById("refreshSocialSyncLogBtn");
  const loadSocialSyncDetailBtn = document.getElementById("loadSocialSyncDetailBtn");
  const downloadSocialSyncExportBtn = document.getElementById("downloadSocialSyncExportBtn");
  const loadSocialActivityDetailBtn = document.getElementById("loadSocialActivityDetailBtn");
  const downloadSocialActivityExportBtn = document.getElementById("downloadSocialActivityExportBtn");
  const loadSocialPlatformDetailBtn = document.getElementById("loadSocialPlatformDetailBtn");
  const downloadSocialPlatformsExportBtn = document.getElementById("downloadSocialPlatformsExportBtn");
  const refreshSocialPlatformCoverageBtn = document.getElementById("refreshSocialPlatformCoverageBtn");
  const downloadSocialPlatformCoverageExportBtn = document.getElementById("downloadSocialPlatformCoverageExportBtn");
  const refreshSocialExecutionPreviewBtn = document.getElementById("refreshSocialExecutionPreviewBtn");
  const downloadSocialExecutionPreviewBtn = document.getElementById("downloadSocialExecutionPreviewBtn");
  const downloadSocialDraftsExportBtn = document.getElementById("downloadSocialDraftsExportBtn");
  const refreshSocialDraftValidationBtn = document.getElementById("refreshSocialDraftValidationBtn");
  const refreshSocialDraftPlanBtn = document.getElementById("refreshSocialDraftPlanBtn");
  const refreshSocialActivityBtn = document.getElementById("refreshSocialActivityBtn");
  const refreshSocialInboxBtn = document.getElementById("refreshSocialInboxBtn");
  const refreshSocialInboxWorkloadBtn = document.getElementById("refreshSocialInboxWorkloadBtn");
  const refreshSocialInboxWatchBtn = document.getElementById("refreshSocialInboxWatchBtn");
  const runSocialInboxWatchBtn = document.getElementById("runSocialInboxWatchBtn");
  const socialPlatforms = document.getElementById("socialPlatforms");
  const socialPlatformDetail = document.getElementById("socialPlatformDetail");
  const socialPlatformCoverage = document.getElementById("socialPlatformCoverage");
  const socialCapabilitiesSummary = document.getElementById("socialCapabilitiesSummary");
  const socialWatchView = document.getElementById("socialWatchView");
  const socialOpsSnapshotView = document.getElementById("socialOpsSnapshotView");
  const socialOpsHistoryView = document.getElementById("socialOpsHistoryView");
  const socialOpsHistorySummaryView = document.getElementById("socialOpsHistorySummaryView");
  const socialOpsLatestCompareView = document.getElementById("socialOpsLatestCompareView");
  const socialOpsIssuesSummaryView = document.getElementById("socialOpsIssuesSummaryView");
  const downloadSocialOpsSnapshotBtn = document.getElementById("downloadSocialOpsSnapshotBtn");
  const socialProviderProfile = document.getElementById("socialProviderProfile");
  const socialDraftsPreview = document.getElementById("socialDraftsPreview");
  const socialDraftList = document.getElementById("socialDraftList");
  const socialDraftDetail = document.getElementById("socialDraftDetail");
  const socialDraftRecommendations = document.getElementById("socialDraftRecommendations");
  const socialDraftVariants = document.getElementById("socialDraftVariants");
  const socialDraftScheduleResult = document.getElementById("socialDraftScheduleResult");
  const socialDraftBulkScheduleResult = document.getElementById("socialDraftBulkScheduleResult");
  const socialDraftValidationView = document.getElementById("socialDraftValidationView");
  const socialDraftPlanView = document.getElementById("socialDraftPlanView");
  const socialConnectors = document.getElementById("socialConnectors");
  const socialConnectorDetail = document.getElementById("socialConnectorDetail");
  const socialConnectorRuleAudit = document.getElementById("socialConnectorRuleAudit");
  const socialConnectorRuleAuditDetail = document.getElementById("socialConnectorRuleAuditDetail");
  const socialConnectorBulkResult = document.getElementById("socialConnectorBulkResult");
  const socialConnectorForm = document.getElementById("socialConnectorForm");
  const refreshSocialScheduleSummaryBtn = document.getElementById("refreshSocialScheduleSummaryBtn");
  const socialScheduleSummary = document.getElementById("socialScheduleSummary");
  const socialScheduleQueue = document.getElementById("socialScheduleQueue");
  const socialScheduleDetail = document.getElementById("socialScheduleDetail");
  const downloadSocialScheduleExportBtn = document.getElementById("downloadSocialScheduleExportBtn");
  const updateSocialScheduleBulkBtn = document.getElementById("updateSocialScheduleBulkBtn");
  const socialScheduleForm = document.getElementById("socialScheduleForm");
  const socialScheduleTargetValidation = document.getElementById("socialScheduleTargetValidation");
  const socialActivityFeed = document.getElementById("socialActivityFeed");
  const socialActivityDetail = document.getElementById("socialActivityDetail");
  const refreshSocialInboxSummaryBtn = document.getElementById("refreshSocialInboxSummaryBtn");
  const socialInboxSummary = document.getElementById("socialInboxSummary");
  const socialInboxWorkload = document.getElementById("socialInboxWorkload");
  const socialInboxWatchView = document.getElementById("socialInboxWatchView");
  const socialInboxThreads = document.getElementById("socialInboxThreads");
  const socialInboxThreadDetail = document.getElementById("socialInboxThreadDetail");
  const downloadSocialInboxExportBtn = document.getElementById("downloadSocialInboxExportBtn");
  const socialInboxReplyForm = document.getElementById("socialInboxReplyForm");
  const socialInboxUpdateForm = document.getElementById("socialInboxUpdateForm");
  const updateSocialInboxBulkBtn = document.getElementById("updateSocialInboxBulkBtn");
  const refreshWebopsTypesBtn = document.getElementById("refreshWebopsTypesBtn");
  const refreshWebopsIncidentsBtn = document.getElementById("refreshWebopsIncidentsBtn");
  const refreshWebopsActionsBtn = document.getElementById("refreshWebopsActionsBtn");
  const refreshWebopsPostureBtn = document.getElementById("refreshWebopsPostureBtn");
  const runWebopsActionsBtn = document.getElementById("runWebopsActionsBtn");
  const runSocialSyncBtn = document.getElementById("runSocialSyncBtn");
  const runSocialRetryQueueBtn = document.getElementById("runSocialRetryQueueBtn");
  const refreshSocialDeliverySummaryBtn = document.getElementById("refreshSocialDeliverySummaryBtn");
  const refreshSocialDeliveryDetailBtn = document.getElementById("refreshSocialDeliveryDetailBtn");
  const downloadSocialDeliveryExportBtn = document.getElementById("downloadSocialDeliveryExportBtn");
  const refreshSocialDeliveryWatchBtn = document.getElementById("refreshSocialDeliveryWatchBtn");
  const runSocialDeliveryWatchBtn = document.getElementById("runSocialDeliveryWatchBtn");
  const downloadSocialDeliveryWatchExportBtn = document.getElementById("downloadSocialDeliveryWatchExportBtn");
  const socialSyncResult = document.getElementById("socialSyncResult");
  const socialDeliverySummary = document.getElementById("socialDeliverySummary");
  const socialDeliveryConnectorDetail = document.getElementById("socialDeliveryConnectorDetail");
  const socialDeliveryWatchView = document.getElementById("socialDeliveryWatchView");
  const socialExecutionPreview = document.getElementById("socialExecutionPreview");
  const socialSyncLog = document.getElementById("socialSyncLog");
  const socialRetryQueue = document.getElementById("socialRetryQueue");
  const socialRetryDetail = document.getElementById("socialRetryDetail");
  const socialRetryBulkResult = document.getElementById("socialRetryBulkResult");
  const socialSyncDetail = document.getElementById("socialSyncDetail");
  const webopsMonitors = document.getElementById("webopsMonitors");
  const webopsTypes = document.getElementById("webopsTypes");
  const webopsMonitorForm = document.getElementById("webopsMonitorForm");
  const webopsIncidents = document.getElementById("webopsIncidents");
  const webopsIncidentForm = document.getElementById("webopsIncidentForm");
  const webopsActionsQueue = document.getElementById("webopsActionsQueue");
  const webopsActionForm = document.getElementById("webopsActionForm");
  const webopsActionsLog = document.getElementById("webopsActionsLog");
  const webopsPostureView = document.getElementById("webopsPostureView");
  const webopsOperationsSnapshotView = document.getElementById("webopsOperationsSnapshotView");
  const webopsOperationsHistoryView = document.getElementById("webopsOperationsHistoryView");
  const webopsOperationsHistorySummaryView = document.getElementById("webopsOperationsHistorySummaryView");
  const webopsOperationsIssuesSummaryView = document.getElementById("webopsOperationsIssuesSummaryView");
  const refreshWebopsOperationsSnapshotBtn = document.getElementById("refreshWebopsOperationsSnapshotBtn");
  const downloadWebopsOperationsSnapshotBtn = document.getElementById("downloadWebopsOperationsSnapshotBtn");
  const runWebopsBtn = document.getElementById("runWebopsBtn");
  const runWebopsRetryQueueBtn = document.getElementById("runWebopsRetryQueueBtn");
  const webopsResult = document.getElementById("webopsResult");
  const webopsLog = document.getElementById("webopsLog");
  const webopsRetryQueue = document.getElementById("webopsRetryQueue");
  const seoProjects = document.getElementById("seoProjects");
  const refreshSeoProjectsBtn = document.getElementById("refreshSeoProjectsBtn");
  const seoProjectFilterStatus = document.getElementById("seoProjectFilterStatus");
  const seoProjectFilterSearch = document.getElementById("seoProjectFilterSearch");
  const seoProjectFilterPage = document.getElementById("seoProjectFilterPage");
  const seoProjectFilterLimit = document.getElementById("seoProjectFilterLimit");
  const loadSeoProjectDetailBtn = document.getElementById("loadSeoProjectDetailBtn");
  const downloadSeoProjectsExportBtn = document.getElementById("downloadSeoProjectsExportBtn");
  let currentLeadsReviewDrafts = [];
  const seoProjectForm = document.getElementById("seoProjectForm");
  const seoResult = document.getElementById("seoResult");
  const refreshSeoIssuesBtn = document.getElementById("refreshSeoIssuesBtn");
  const downloadSeoIssuesExportBtn = document.getElementById("downloadSeoIssuesExportBtn");
  const refreshSeoHistoryBtn = document.getElementById("refreshSeoHistoryBtn");
  const downloadSeoHistoryExportBtn = document.getElementById("downloadSeoHistoryExportBtn");
  const refreshSeoExtensionSummaryBtn = document.getElementById("refreshSeoExtensionSummaryBtn");
  const downloadSeoExtensionSummaryExportBtn = document.getElementById("downloadSeoExtensionSummaryExportBtn");
  const refreshSeoAuditsBtn = document.getElementById("refreshSeoAuditsBtn");
  const refreshSeoExtensionEventsBtn = document.getElementById("refreshSeoExtensionEventsBtn");
  const refreshSeoExtensionSessionsBtn = document.getElementById("refreshSeoExtensionSessionsBtn");
  const downloadSeoAuditsExportBtn = document.getElementById("downloadSeoAuditsExportBtn");
  const downloadSeoExtensionEventsExportBtn = document.getElementById("downloadSeoExtensionEventsExportBtn");
  const downloadSeoExtensionSessionsExportBtn = document.getElementById("downloadSeoExtensionSessionsExportBtn");
  const downloadSeoReportBtn = document.getElementById("downloadSeoReportBtn");
  const refreshSeoProjectSnapshotBtn = document.getElementById("refreshSeoProjectSnapshotBtn");
  const downloadSeoProjectSnapshotExportBtn = document.getElementById("downloadSeoProjectSnapshotExportBtn");
  const refreshSeoActionPlanBtn = document.getElementById("refreshSeoActionPlanBtn");
  const downloadSeoActionPlanExportBtn = document.getElementById("downloadSeoActionPlanExportBtn");
  const refreshSeoOpportunitiesBtn = document.getElementById("refreshSeoOpportunitiesBtn");
  const downloadSeoOpportunitiesExportBtn = document.getElementById("downloadSeoOpportunitiesExportBtn");
  const refreshSeoRegressionsBtn = document.getElementById("refreshSeoRegressionsBtn");
  const runSeoRegressionsBtn = document.getElementById("runSeoRegressionsBtn");
  const downloadSeoRegressionsExportBtn = document.getElementById("downloadSeoRegressionsExportBtn");
  const refreshSeoUrlHistoryBtn = document.getElementById("refreshSeoUrlHistoryBtn");
  const downloadSeoUrlHistoryExportBtn = document.getElementById("downloadSeoUrlHistoryExportBtn");
  const refreshSeoCompareBtn = document.getElementById("refreshSeoCompareBtn");
  const downloadSeoCompareExportBtn = document.getElementById("downloadSeoCompareExportBtn");
  const seoIssuesSummary = document.getElementById("seoIssuesSummary");
  const seoHistorySummary = document.getElementById("seoHistorySummary");
  const seoExtensionSummary = document.getElementById("seoExtensionSummary");
  const seoAuditFilterProjectId = document.getElementById("seoAuditFilterProjectId");
  const seoAuditFilterSource = document.getElementById("seoAuditFilterSource");
  const seoAuditFilterSearch = document.getElementById("seoAuditFilterSearch");
  const seoAuditFilterMinScore = document.getElementById("seoAuditFilterMinScore");
  const seoAuditFilterMaxScore = document.getElementById("seoAuditFilterMaxScore");
  const seoAuditFilterPage = document.getElementById("seoAuditFilterPage");
  const seoAuditFilterLimit = document.getElementById("seoAuditFilterLimit");
  const seoAudits = document.getElementById("seoAudits");
  const loadSeoAuditDetailBtn = document.getElementById("loadSeoAuditDetailBtn");
  const seoAuditDetail = document.getElementById("seoAuditDetail");
  const seoExtensionEventFilterProjectId = document.getElementById("seoExtensionEventFilterProjectId");
  const seoExtensionEventFilterSessionId = document.getElementById("seoExtensionEventFilterSessionId");
  const seoExtensionEventFilterSearch = document.getElementById("seoExtensionEventFilterSearch");
  const seoExtensionEventFilterMinScore = document.getElementById("seoExtensionEventFilterMinScore");
  const seoExtensionEventFilterMaxScore = document.getElementById("seoExtensionEventFilterMaxScore");
  const seoExtensionEventFilterPage = document.getElementById("seoExtensionEventFilterPage");
  const seoExtensionEventFilterLimit = document.getElementById("seoExtensionEventFilterLimit");
  const seoExtensionEvents = document.getElementById("seoExtensionEvents");
  const loadSeoExtensionEventDetailBtn = document.getElementById("loadSeoExtensionEventDetailBtn");
  const seoExtensionEventDetail = document.getElementById("seoExtensionEventDetail");
  const seoExtensionSessionFilterProjectId = document.getElementById("seoExtensionSessionFilterProjectId");
  const seoExtensionSessionFilterStatus = document.getElementById("seoExtensionSessionFilterStatus");
  const seoExtensionSessionFilterSearch = document.getElementById("seoExtensionSessionFilterSearch");
  const seoExtensionSessionFilterPage = document.getElementById("seoExtensionSessionFilterPage");
  const seoExtensionSessionFilterLimit = document.getElementById("seoExtensionSessionFilterLimit");
  const seoExtensionSessions = document.getElementById("seoExtensionSessions");
  const loadSeoExtensionSessionDetailBtn = document.getElementById("loadSeoExtensionSessionDetailBtn");
  const seoExtensionSessionDetail = document.getElementById("seoExtensionSessionDetail");
  const seoExtensionSessionForm = document.getElementById("seoExtensionSessionForm");
  const seoProjectDetail = document.getElementById("seoProjectDetail");
  const seoProjectSnapshot = document.getElementById("seoProjectSnapshot");
  const seoActionPlanView = document.getElementById("seoActionPlanView");
  const seoOpportunitiesView = document.getElementById("seoOpportunitiesView");
  const seoRegressionView = document.getElementById("seoRegressionView");
  const seoUrlHistoryView = document.getElementById("seoUrlHistoryView");
  const seoCompareView = document.getElementById("seoCompareView");
  const seoOperationsSnapshotView = document.getElementById("seoOperationsSnapshotView");
  const downloadSeoOperationsSnapshotBtn = document.getElementById("downloadSeoOperationsSnapshotBtn");
  const seoOperationsHistoryView = document.getElementById("seoOperationsHistoryView");
  const seoOperationsHistorySummaryView = document.getElementById("seoOperationsHistorySummaryView");
  const seoOperationsIssuesSummaryView = document.getElementById("seoOperationsIssuesSummaryView");
  const refreshLaunchOperationsSnapshotBtn = document.getElementById("refreshLaunchOperationsSnapshotBtn");
  const downloadLaunchOperationsSnapshotBtn = document.getElementById("downloadLaunchOperationsSnapshotBtn");
  const downloadLaunchOperationsHistoryBtn = document.getElementById("downloadLaunchOperationsHistoryBtn");
  const downloadLaunchOperationsHistorySummaryBtn = document.getElementById("downloadLaunchOperationsHistorySummaryBtn");
  const downloadLaunchOperationsCompareBtn = document.getElementById("downloadLaunchOperationsCompareBtn");
  const downloadLaunchOperationsIssuesBtn = document.getElementById("downloadLaunchOperationsIssuesBtn");
  const downloadLaunchOperationsReviewBundleBtn = document.getElementById("downloadLaunchOperationsReviewBundleBtn");
  const launchOperationsSnapshotView = document.getElementById("launchOperationsSnapshotView");
  const launchOperationsHistoryView = document.getElementById("launchOperationsHistoryView");
  const launchOperationsHistoryDetailView = document.getElementById("launchOperationsHistoryDetailView");
  const launchOperationsHistorySummaryView = document.getElementById("launchOperationsHistorySummaryView");
  const launchOperationsLatestCompareView = document.getElementById("launchOperationsLatestCompareView");
  const launchOperationsIssuesSummaryView = document.getElementById("launchOperationsIssuesSummaryView");
  let lastNotificationToneKey = "";
  const releaseGateRunsFilterStorageKey = "lc_release_gate_runs_filters_v1";
  let socialPlatformCatalogItems = [];

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
      await loadReleaseGateOpsSnapshot(params);
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
      if (releaseGateOpsSnapshotView) {
        releaseGateOpsSnapshotView.textContent = "Failed to load gate operations snapshot.";
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

  async function loadReleaseGateOpsSnapshot(existingParams) {
    if (!releaseGateOpsSnapshotView) return;
    const params = existingParams && typeof existingParams === "object" ? existingParams : collectReleaseGateRunsFilters();
    try {
      const data = await apiGetWithParams("deployment.release.gate.operations.snapshot", params);
      releaseGateOpsSnapshotView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      releaseGateOpsSnapshotView.textContent = "Failed to load gate operations snapshot.";
    }
  }

  async function downloadReleaseGateRuns() {
    const params = collectReleaseGateRunsFilters();
    const data = await apiGetWithParams("deployment.release.gate.runs.export", params);
    const filename = data && data.filename ? String(data.filename) : ("release-gate-runs-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function downloadReleaseGateQuickstats() {
    const params = collectReleaseGateRunsFilters();
    const data = await apiGetWithParams("deployment.release.gate.runs.quickstats.export", params);
    const filename = data && data.filename ? String(data.filename) : ("release-gate-quickstats-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function downloadReleaseGateOpsSnapshot() {
    const params = collectReleaseGateRunsFilters();
    const data = await apiGetWithParams("deployment.release.gate.operations.snapshot.export", params);
    const filename = data && data.filename ? String(data.filename) : ("release-gate-operations-snapshot-" + Date.now() + ".json");
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

  async function loadLeadsInventory() {
    if (!leadsInventoryView) return;
    try {
      const data = await apiGetWithParams("leads.inventory", { limit: 8 });
      leadsInventoryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      leadsInventoryView.textContent = "Failed to load leads inventory.";
    }
  }

  function leadsListFilters() {
    const search = document.getElementById("leadsListSearch");
    const site = document.getElementById("leadsListSiteFilter");
    const status = document.getElementById("leadsListStatusFilter");
    const limit = document.getElementById("leadsListLimit");
    return {
      search: search && search.value ? search.value.trim() : "",
      site_id: site && site.value ? site.value.trim() : "",
      status: status && status.value ? status.value.trim() : "",
      limit: limit && limit.value ? limit.value.trim() : "20",
      page: 1,
    };
  }

  async function loadLeadsList() {
    if (!leadsListView) return;
    try {
      const data = await apiGetWithParams("leads.list", leadsListFilters());
      leadsListView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      leadsListView.textContent = "Failed to load leads list.";
    }
  }

  async function loadLeadsPushPlan() {
    if (!leadsPushPlanView) return;
    try {
      const data = await apiGetWithParams("leads.push.plan", { limit: 8 });
      leadsPushPlanView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      leadsPushPlanView.textContent = "Failed to load leads push plan.";
    }
  }

  async function loadLeadsQuality() {
    if (!leadsQualityView) return;
    try {
      const data = await apiGetWithParams("leads.quality", { limit: 8 });
      leadsQualityView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      leadsQualityView.textContent = "Failed to load leads quality.";
    }
  }

  async function loadLeadsDeliveryHistory() {
    if (!leadsDeliveryHistoryView) return;
    try {
      const data = await apiGetWithParams("leads.delivery.history", { limit: 8 });
      leadsDeliveryHistoryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      leadsDeliveryHistoryView.textContent = "Failed to load leads delivery history.";
    }
  }

  function leadsReviewQueueFilters() {
    const status = document.getElementById("leadsReviewStatusFilter");
    const site = document.getElementById("leadsReviewQueueSiteFilter");
    const page = document.getElementById("leadsReviewQueuePage");
    const limit = document.getElementById("leadsReviewQueueLimit");
    return {
      review_status: status && status.value ? status.value : "pending",
      site_id: site && site.value ? site.value.trim() : "",
      page: page && page.value ? page.value.trim() : "1",
      limit: limit && limit.value ? limit.value.trim() : "8",
    };
  }

  async function loadLeadsReviewQueue() {
    if (!leadsReviewQueueView) return;
    try {
      const data = await apiGetWithParams("leads.review.queue", leadsReviewQueueFilters());
      leadsReviewQueueView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      leadsReviewQueueView.textContent = "Failed to load leads review queue.";
    }
  }

  async function loadLeadsReviewDetail() {
    if (!leadsReviewDetailView) return;
    try {
      const siteId = document.getElementById("leadsReviewSiteId");
      const runId = document.getElementById("leadsReviewRunId");
      const data = await apiGetWithParams("leads.review.detail", {
        site_id: siteId && siteId.value ? siteId.value.trim() : "",
        run_id: runId && runId.value ? runId.value.trim() : "",
      });
      leadsReviewDetailView.textContent = JSON.stringify(data, null, 2);
      currentLeadsReviewDrafts = data && data.review && Array.isArray(data.review.draft_preview)
        ? data.review.draft_preview
        : [];
      populateLeadsReviewDraftSelect(currentLeadsReviewDrafts);
      await loadLeadsReviewDuplicates();
    } catch (err) {
      currentLeadsReviewDrafts = [];
      populateLeadsReviewDraftSelect([]);
      if (leadsReviewDuplicatesView) {
        leadsReviewDuplicatesView.textContent = "No duplicate check yet.";
      }
      leadsReviewDetailView.textContent = "Failed to load leads review detail.";
    }
  }

  async function loadLeadsReviewDuplicates() {
    if (!leadsReviewDuplicatesView) return;
    const siteId = document.getElementById("leadsReviewSiteId");
    const runId = document.getElementById("leadsReviewRunId");
    if (!siteId || !siteId.value || !runId || !runId.value) {
      leadsReviewDuplicatesView.textContent = "Site ID and Review Run ID are required for duplicate checks.";
      return;
    }
    try {
      const data = await apiGetWithParams("leads.review.duplicates", {
        site_id: siteId.value.trim(),
        run_id: runId.value.trim(),
        limit: 25,
      });
      leadsReviewDuplicatesView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      leadsReviewDuplicatesView.textContent = "Failed to load review duplicate check.";
    }
  }

  function setLeadsReviewDraftEditorValues(draft) {
    const draftId = document.getElementById("leadsReviewDraftId");
    const businessName = document.getElementById("leadsReviewDraftBusiness");
    const city = document.getElementById("leadsReviewDraftCity");
    const category = document.getElementById("leadsReviewDraftCategory");
    const website = document.getElementById("leadsReviewDraftWebsite");
    const phone = document.getElementById("leadsReviewDraftPhone");
    const email = document.getElementById("leadsReviewDraftEmail");
    const status = document.getElementById("leadsReviewDraftStatus");
    const notes = document.getElementById("leadsReviewDraftNotes");
    const item = draft && typeof draft === "object" ? draft : {};
    if (draftId) draftId.value = item.id ? String(item.id) : "";
    if (businessName) businessName.value = item.business_name || "";
    if (city) city.value = item.city || "";
    if (category) category.value = item.category || "";
    if (website) website.value = item.website || "";
    if (phone) phone.value = item.phone || "";
    if (email) email.value = item.email || "";
    if (status) status.value = item.status || "";
    if (notes) notes.value = item.notes || "";
  }

  function populateLeadsReviewDraftSelect(drafts) {
    if (!leadsReviewDraftSelect) return;
    const items = Array.isArray(drafts) ? drafts : [];
    leadsReviewDraftSelect.innerHTML = "";
    if (!items.length) {
      const emptyOption = document.createElement("option");
      emptyOption.value = "";
      emptyOption.textContent = "No review drafts loaded";
      leadsReviewDraftSelect.appendChild(emptyOption);
      setLeadsReviewDraftEditorValues(null);
      return;
    }
    const draftIdInput = document.getElementById("leadsReviewDraftId");
    const preferredId = draftIdInput && draftIdInput.value ? String(draftIdInput.value).trim() : "";
    let selectedId = "";
    items.forEach(function (draft, index) {
      const option = document.createElement("option");
      const draftId = draft && draft.id ? String(draft.id) : "";
      option.value = draftId;
      option.textContent = draftId
        ? "#" + draftId + " " + (draft.business_name || "(no business)") + " | " + (draft.city || "No city")
        : "Draft " + String(index + 1);
      leadsReviewDraftSelect.appendChild(option);
      if (preferredId !== "" && draftId === preferredId) {
        selectedId = draftId;
      }
    });
    if (selectedId === "" && items[0] && items[0].id) {
      selectedId = String(items[0].id);
    }
    leadsReviewDraftSelect.value = selectedId;
    const selectedDraft = items.find(function (draft) {
      return String(draft.id || "") === selectedId;
    }) || items[0];
    setLeadsReviewDraftEditorValues(selectedDraft);
  }

  async function runLeadsReviewAction(action) {
    const siteId = document.getElementById("leadsReviewSiteId");
    const runId = document.getElementById("leadsReviewRunId");
    const payload = {
      site_id: siteId && siteId.value ? siteId.value.trim() : "",
      run_id: runId && runId.value ? runId.value.trim() : "",
    };
    const endpoint = action === "save" ? "leads.review.save" : "leads.review.discard";
    const result = await apiPost(endpoint, payload);
    if (leadsReviewActionView) {
      leadsReviewActionView.textContent = JSON.stringify(result, null, 2);
    }
    await loadLeadsReviewQueue();
    await loadLeadsReviewDetail();
    await loadLeadsInventory();
    await loadLeadsList();
    await loadLeadsPushPlan();
    await loadLeadsQuality();
    await refreshLeadsAndCrmModuleCards();
  }

  async function runLeadsReviewQueueBulkAction(action) {
    const result = await apiPost("leads.review.queue.bulk_action", Object.assign({
      action: action,
    }, leadsReviewQueueFilters()));
    if (leadsReviewActionView) {
      leadsReviewActionView.textContent = JSON.stringify(result, null, 2);
    }
    await loadLeadsReviewQueue();
    await loadLeadsReviewDetail();
    await loadLeadsInventory();
    await loadLeadsList();
    await loadLeadsPushPlan();
    await loadLeadsQuality();
    await refreshLeadsAndCrmModuleCards();
  }

  async function runLeadsReviewDraftUpdate() {
    const siteId = document.getElementById("leadsReviewSiteId");
    const runId = document.getElementById("leadsReviewRunId");
    const draftId = document.getElementById("leadsReviewDraftId");
    const businessName = document.getElementById("leadsReviewDraftBusiness");
    const city = document.getElementById("leadsReviewDraftCity");
    const category = document.getElementById("leadsReviewDraftCategory");
    const website = document.getElementById("leadsReviewDraftWebsite");
    const phone = document.getElementById("leadsReviewDraftPhone");
    const email = document.getElementById("leadsReviewDraftEmail");
    const status = document.getElementById("leadsReviewDraftStatus");
    const notes = document.getElementById("leadsReviewDraftNotes");
    const payload = {
      site_id: siteId && siteId.value ? siteId.value.trim() : "",
      run_id: runId && runId.value ? runId.value.trim() : "",
      draft_id: draftId && draftId.value ? draftId.value.trim() : "",
      business_name: businessName && businessName.value ? businessName.value.trim() : "",
      city: city && city.value ? city.value.trim() : "",
      category: category && category.value ? category.value.trim() : "",
      website: website && website.value ? website.value.trim() : "",
      phone: phone && phone.value ? phone.value.trim() : "",
      email: email && email.value ? email.value.trim() : "",
      status: status && status.value ? status.value.trim() : "",
      notes: notes && notes.value ? notes.value.trim() : "",
    };
    const result = await apiPost("leads.review.draft.update", payload);
    if (leadsReviewActionView) {
      leadsReviewActionView.textContent = JSON.stringify(result, null, 2);
    }
    await loadLeadsReviewQueue();
    await loadLeadsReviewDetail();
  }

  async function refreshLeadsAndCrmModuleCards() {
    const leadsPanel = document.getElementById("modLeads");
    const crmPanel = document.getElementById("modCrm");
    try {
      if (leadsPanel) {
        const leadsData = await apiGet("leads.summary");
        leadsPanel.textContent = JSON.stringify(leadsData, null, 2);
      }
      if (crmPanel) {
        const crmData = await apiGet("crm.summary");
        crmPanel.textContent = JSON.stringify(crmData, null, 2);
      }
    } catch (err) {
      // Leave existing panel content intact on refresh failure.
    }
  }

  async function loadCrmConnectors() {
    if (!crmConnectors) return;
    try {
      const data = await apiGetWithParams("crm.connectors.list", {
        provider: crmConnectorFilterProvider && crmConnectorFilterProvider.value ? crmConnectorFilterProvider.value.trim() : "",
        status: crmConnectorFilterStatus && crmConnectorFilterStatus.value ? crmConnectorFilterStatus.value : "",
        site_id: crmConnectorFilterSite && crmConnectorFilterSite.value ? crmConnectorFilterSite.value.trim() : "",
        run_mode: crmConnectorFilterRunMode && crmConnectorFilterRunMode.value ? crmConnectorFilterRunMode.value : "",
        search: crmConnectorFilterSearch && crmConnectorFilterSearch.value ? crmConnectorFilterSearch.value.trim() : "",
        page: crmConnectorFilterPage && crmConnectorFilterPage.value ? crmConnectorFilterPage.value : 1,
        limit: crmConnectorFilterLimit && crmConnectorFilterLimit.value ? crmConnectorFilterLimit.value : 10,
      });
      crmConnectors.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmConnectors.textContent = "Failed to load CRM connectors.";
    }
  }

  async function loadCrmConnectorDetail() {
    if (!crmConnectorDetail) return;
    const connectorId = document.getElementById("crmConnectorDetailId");
    const limit = document.getElementById("crmConnectorDetailLimit");
    if (!connectorId || !connectorId.value) {
      crmConnectorDetail.textContent = "Enter a connector ID to load detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("crm.connectors.detail", {
        connector_id: connectorId.value.trim(),
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      crmConnectorDetail.textContent = JSON.stringify(data, null, 2);
      if (data && data.item) {
        const formId = document.getElementById("connectorId");
        const provider = document.getElementById("connectorProvider");
        const type = document.getElementById("connectorType");
        const status = document.getElementById("connectorStatus");
        const auth = document.getElementById("connectorAuth");
        const caps = document.getElementById("connectorCapabilities");
        const siteId = document.getElementById("connectorSiteId");
        const runMode = document.getElementById("connectorRunMode");
        const endpoint = document.getElementById("connectorEndpoint");
        const webhook = document.getElementById("connectorWebhook");
        if (formId) formId.value = data.item.connector_id || "";
        if (provider) provider.value = data.item.provider || "";
        if (type) type.value = data.item.type || "external_api";
        if (status) status.value = data.item.status || "planned";
        if (auth) auth.value = data.item.auth_mode || "api_key";
        if (caps) caps.value = Array.isArray(data.item.capabilities) ? data.item.capabilities.join(",") : "";
        if (siteId) siteId.value = data.item.site_id || "";
        if (runMode) runMode.value = (data.item.config && data.item.config.run_mode) ? data.item.config.run_mode : "dry_run";
        if (endpoint) endpoint.value = (data.item.config && data.item.config.endpoint_url) ? data.item.config.endpoint_url : "";
        if (webhook) webhook.value = (data.item.config && data.item.config.webhook_url) ? data.item.config.webhook_url : "";
      }
    } catch (err) {
      crmConnectorDetail.textContent = "Failed to load CRM connector detail.";
    }
  }

  async function downloadCrmConnectorsExport() {
    const detailId = document.getElementById("crmConnectorDetailId");
    const detailLimit = document.getElementById("crmConnectorDetailLimit");
    const connectorId = detailId && detailId.value ? detailId.value.trim() : "";
    const data = await apiGetWithParams("crm.connectors.export", {
      provider: crmConnectorFilterProvider && crmConnectorFilterProvider.value ? crmConnectorFilterProvider.value.trim() : "",
      status: crmConnectorFilterStatus && crmConnectorFilterStatus.value ? crmConnectorFilterStatus.value : "",
      site_id: crmConnectorFilterSite && crmConnectorFilterSite.value ? crmConnectorFilterSite.value.trim() : "",
      run_mode: crmConnectorFilterRunMode && crmConnectorFilterRunMode.value ? crmConnectorFilterRunMode.value : "",
      search: crmConnectorFilterSearch && crmConnectorFilterSearch.value ? crmConnectorFilterSearch.value.trim() : "",
      page: crmConnectorFilterPage && crmConnectorFilterPage.value ? crmConnectorFilterPage.value : 1,
      limit: crmConnectorFilterLimit && crmConnectorFilterLimit.value ? crmConnectorFilterLimit.value : 10,
      connector_id: connectorId,
      detail_limit: detailLimit && detailLimit.value ? detailLimit.value : 10,
      include_detail: connectorId ? 1 : "",
    });
    downloadJsonFile(data.filename || "crm-connectors-export.json", data.export || data);
    if (crmSyncResult) {
      crmSyncResult.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function runCrmConnectorBulkUpdate() {
    const result = await apiPost("crm.connectors.bulk_update", {
      provider: crmConnectorFilterProvider && crmConnectorFilterProvider.value ? crmConnectorFilterProvider.value.trim() : "",
      status: crmConnectorFilterStatus && crmConnectorFilterStatus.value ? crmConnectorFilterStatus.value : "",
      site_id: crmConnectorFilterSite && crmConnectorFilterSite.value ? crmConnectorFilterSite.value.trim() : "",
      run_mode: crmConnectorFilterRunMode && crmConnectorFilterRunMode.value ? crmConnectorFilterRunMode.value : "",
      search: crmConnectorFilterSearch && crmConnectorFilterSearch.value ? crmConnectorFilterSearch.value.trim() : "",
      page: crmConnectorFilterPage && crmConnectorFilterPage.value ? crmConnectorFilterPage.value : 1,
      limit: crmConnectorFilterLimit && crmConnectorFilterLimit.value ? crmConnectorFilterLimit.value : 10,
      bulk_status: document.getElementById("crmConnectorBulkStatus") && document.getElementById("crmConnectorBulkStatus").value ? document.getElementById("crmConnectorBulkStatus").value : "",
      bulk_site_id: document.getElementById("crmConnectorBulkSiteId") && document.getElementById("crmConnectorBulkSiteId").value ? document.getElementById("crmConnectorBulkSiteId").value.trim() : "",
      bulk_run_mode: document.getElementById("crmConnectorBulkRunMode") && document.getElementById("crmConnectorBulkRunMode").value ? document.getElementById("crmConnectorBulkRunMode").value : "",
    });
    if (crmConnectorBulkResult) {
      crmConnectorBulkResult.textContent = JSON.stringify(result, null, 2);
    }
    await loadCrmConnectors();
    const detailId = document.getElementById("crmConnectorDetailId");
    if (detailId && detailId.value) {
      await loadCrmConnectorDetail();
    }
  }

  async function loadCrmSyncLog() {
    if (!crmSyncLog) return;
    try {
      const data = await apiGetWithParams("crm.push.log", {
        status: document.getElementById("crmSyncFilterStatus") && document.getElementById("crmSyncFilterStatus").value ? document.getElementById("crmSyncFilterStatus").value.trim() : "",
        connector_id: document.getElementById("crmSyncFilterConnector") && document.getElementById("crmSyncFilterConnector").value ? document.getElementById("crmSyncFilterConnector").value.trim() : "",
        search: document.getElementById("crmSyncFilterSearch") && document.getElementById("crmSyncFilterSearch").value ? document.getElementById("crmSyncFilterSearch").value.trim() : "",
        page: document.getElementById("crmSyncFilterPage") && document.getElementById("crmSyncFilterPage").value ? document.getElementById("crmSyncFilterPage").value : 1,
        limit: document.getElementById("crmSyncFilterLimit") && document.getElementById("crmSyncFilterLimit").value ? document.getElementById("crmSyncFilterLimit").value : 10,
      });
      crmSyncLog.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmSyncLog.textContent = "Failed to load CRM sync log.";
    }
  }

  async function loadCrmSyncDetail() {
    if (!crmSyncDetail) return;
    const syncId = document.getElementById("crmSyncDetailId");
    if (!syncId || !syncId.value) {
      crmSyncDetail.textContent = "Enter a sync ID to load detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("crm.push.detail", {
        sync_id: syncId.value.trim(),
      });
      crmSyncDetail.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmSyncDetail.textContent = "Failed to load CRM sync detail.";
    }
  }

  async function downloadCrmSyncExport() {
    const syncId = document.getElementById("crmSyncDetailId");
    const data = await apiGetWithParams("crm.push.export", {
      status: document.getElementById("crmSyncFilterStatus") && document.getElementById("crmSyncFilterStatus").value ? document.getElementById("crmSyncFilterStatus").value.trim() : "",
      connector_id: document.getElementById("crmSyncFilterConnector") && document.getElementById("crmSyncFilterConnector").value ? document.getElementById("crmSyncFilterConnector").value.trim() : "",
      search: document.getElementById("crmSyncFilterSearch") && document.getElementById("crmSyncFilterSearch").value ? document.getElementById("crmSyncFilterSearch").value.trim() : "",
      page: document.getElementById("crmSyncFilterPage") && document.getElementById("crmSyncFilterPage").value ? document.getElementById("crmSyncFilterPage").value : 1,
      limit: document.getElementById("crmSyncFilterLimit") && document.getElementById("crmSyncFilterLimit").value ? document.getElementById("crmSyncFilterLimit").value : 10,
      sync_id: syncId && syncId.value ? syncId.value.trim() : "",
      include_detail: syncId && syncId.value ? 1 : "",
    });
    downloadJsonFile(data.filename || "crm-sync-export.json", data.export || data);
    if (crmSyncResult) {
      crmSyncResult.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadCrmDeliverySummary() {
    if (!crmDeliverySummary) return;
    try {
      const data = await apiGetWithParams("crm.delivery.summary", { limit: 20 });
      crmDeliverySummary.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmDeliverySummary.textContent = "Failed to load CRM delivery summary.";
    }
  }

  async function loadCrmDeliveryConnectorDetail() {
    if (!crmDeliveryConnectorDetail) return;
    const connectorId = document.getElementById("crmDeliveryConnectorId");
    const limit = document.getElementById("crmDeliveryConnectorLimit");
    const connector_id = connectorId && connectorId.value ? connectorId.value.trim() : "";
    if (!connector_id) {
      crmDeliveryConnectorDetail.textContent = "Enter a connector ID to load delivery detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("crm.delivery.connector_detail", {
        connector_id,
        limit: limit && limit.value ? limit.value.trim() : "10",
      });
      crmDeliveryConnectorDetail.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmDeliveryConnectorDetail.textContent = "Failed to load CRM connector delivery detail.";
    }
  }

  async function loadCrmDeliveryWatch() {
    if (!crmDeliveryWatchView) return;
    try {
      const data = await apiGet("crm.delivery.watch.summary");
      crmDeliveryWatchView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmDeliveryWatchView.textContent = "Failed to load CRM delivery watch.";
    }
  }

  async function downloadCrmDeliveryExport() {
    const connectorId = document.getElementById("crmDeliveryConnectorId");
    const limit = document.getElementById("crmDeliveryConnectorLimit");
    const params = {
      limit: limit && limit.value ? limit.value.trim() : "10",
    };
    if (connectorId && connectorId.value) {
      params.connector_id = connectorId.value.trim();
    }
    const data = await apiGetWithParams("crm.delivery.export", params);
    const filename = data && data.filename ? String(data.filename) : ("crm-delivery-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  function crmRetryFilters() {
    const status = document.getElementById("crmRetryFilterStatus");
    const connector = document.getElementById("crmRetryFilterConnector");
    const provider = document.getElementById("crmRetryFilterProvider");
    const errorCode = document.getElementById("crmRetryFilterErrorCode");
    const search = document.getElementById("crmRetryFilterSearch");
    const page = document.getElementById("crmRetryFilterPage");
    const limit = document.getElementById("crmRetryFilterLimit");
    return {
      status: status && status.value ? status.value.trim() : "",
      connector_id: connector && connector.value ? connector.value.trim() : "",
      provider: provider && provider.value ? provider.value.trim() : "",
      error_code: errorCode && errorCode.value ? errorCode.value.trim() : "",
      search: search && search.value ? search.value.trim() : "",
      page: page && page.value ? Number(page.value) : 1,
      limit: limit && limit.value ? Number(limit.value) : 10,
    };
  }

  async function loadRetryQueue() {
    if (!crmRetryQueue) return;
    try {
      const data = await apiGetWithParams("crm.retry.list", crmRetryFilters());
      crmRetryQueue.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmRetryQueue.textContent = "Failed to load retry queue.";
    }
  }

  async function loadCrmRetryDetail() {
    if (!crmRetryDetail) return;
    const retryId = document.getElementById("crmRetryDetailId");
    const limit = document.getElementById("crmRetryDetailLimit");
    if (!retryId || !retryId.value) {
      crmRetryDetail.textContent = "Enter a retry ID to load detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("crm.retry.detail", {
        retry_id: retryId.value.trim(),
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      crmRetryDetail.textContent = JSON.stringify(data, null, 2);
      if (data && data.item) {
        const connectorId = data.item.connector_id || "";
        const connectorFilter = document.getElementById("crmRetryFilterConnector");
        const connectorDetailId = document.getElementById("crmConnectorDetailId");
        const deliveryConnectorId = document.getElementById("crmDeliveryConnectorId");
        if (connectorFilter) connectorFilter.value = connectorId;
        if (connectorDetailId) connectorDetailId.value = connectorId;
        if (deliveryConnectorId) deliveryConnectorId.value = connectorId;
      }
    } catch (err) {
      crmRetryDetail.textContent = "Failed to load CRM retry detail.";
    }
  }

  async function downloadCrmRetryExport() {
    const params = crmRetryFilters();
    const retryId = document.getElementById("crmRetryDetailId");
    const detailLimit = document.getElementById("crmRetryDetailLimit");
    if (retryId && retryId.value) {
      params.retry_id = retryId.value.trim();
      params.detail_limit = detailLimit && detailLimit.value ? Number(detailLimit.value) : 10;
    }
    const data = await apiGetWithParams("crm.retry.export", params);
    const filename = data && data.filename ? String(data.filename) : ("crm-retry-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (crmSyncResult) {
      crmSyncResult.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadCrmSmtpSummary() {
    if (!crmSmtpSummary) return;
    try {
      const data = await apiGetWithParams("crm.smtp.summary", {
        site_id: document.getElementById("crmSmtpFilterSiteId") && document.getElementById("crmSmtpFilterSiteId").value ? document.getElementById("crmSmtpFilterSiteId").value.trim() : "",
        connection: document.getElementById("crmSmtpFilterConnection") && document.getElementById("crmSmtpFilterConnection").value ? document.getElementById("crmSmtpFilterConnection").value : "",
        confirmation: document.getElementById("crmSmtpFilterConfirmation") && document.getElementById("crmSmtpFilterConfirmation").value ? document.getElementById("crmSmtpFilterConfirmation").value : "",
        search: document.getElementById("crmSmtpFilterSearch") && document.getElementById("crmSmtpFilterSearch").value ? document.getElementById("crmSmtpFilterSearch").value.trim() : "",
        page: document.getElementById("crmSmtpFilterPage") && document.getElementById("crmSmtpFilterPage").value ? Number(document.getElementById("crmSmtpFilterPage").value) : 1,
        limit: document.getElementById("crmSmtpFilterLimit") && document.getElementById("crmSmtpFilterLimit").value ? Number(document.getElementById("crmSmtpFilterLimit").value) : 10,
      });
      crmSmtpSummary.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmSmtpSummary.textContent = "Failed to load CRM SMTP status.";
    }
  }

  async function loadCrmSmtpDetail() {
    if (!crmSmtpDetail) return;
    const siteId = document.getElementById("crmSmtpDetailSiteId");
    const limit = document.getElementById("crmSmtpDetailLimit");
    if (!siteId || !siteId.value) {
      crmSmtpDetail.textContent = "Enter a site ID to load SMTP detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("crm.smtp.detail", {
        site_id: siteId.value.trim(),
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      crmSmtpDetail.textContent = JSON.stringify(data, null, 2);
      if (data && data.site) {
        const smtpSiteId = document.getElementById("crmSmtpSiteId");
        const templateSiteId = document.getElementById("crmEmailTemplateSiteId");
        if (smtpSiteId) smtpSiteId.value = data.site.site_id || "";
        if (templateSiteId) templateSiteId.value = data.site.site_id || "";
      }
    } catch (err) {
      crmSmtpDetail.textContent = "Failed to load CRM SMTP detail.";
    }
  }

  async function downloadCrmSmtpExport() {
    const detailSiteId = document.getElementById("crmSmtpDetailSiteId");
    const detailLimit = document.getElementById("crmSmtpDetailLimit");
    const data = await apiGetWithParams("crm.smtp.export", {
      site_id: document.getElementById("crmSmtpFilterSiteId") && document.getElementById("crmSmtpFilterSiteId").value ? document.getElementById("crmSmtpFilterSiteId").value.trim() : "",
      connection: document.getElementById("crmSmtpFilterConnection") && document.getElementById("crmSmtpFilterConnection").value ? document.getElementById("crmSmtpFilterConnection").value : "",
      confirmation: document.getElementById("crmSmtpFilterConfirmation") && document.getElementById("crmSmtpFilterConfirmation").value ? document.getElementById("crmSmtpFilterConfirmation").value : "",
      search: document.getElementById("crmSmtpFilterSearch") && document.getElementById("crmSmtpFilterSearch").value ? document.getElementById("crmSmtpFilterSearch").value.trim() : "",
      page: document.getElementById("crmSmtpFilterPage") && document.getElementById("crmSmtpFilterPage").value ? Number(document.getElementById("crmSmtpFilterPage").value) : 1,
      limit: document.getElementById("crmSmtpFilterLimit") && document.getElementById("crmSmtpFilterLimit").value ? Number(document.getElementById("crmSmtpFilterLimit").value) : 10,
      detail_site_id: detailSiteId && detailSiteId.value ? detailSiteId.value.trim() : "",
      detail_limit: detailLimit && detailLimit.value ? Number(detailLimit.value) : 10,
    });
    const filename = data && data.filename ? String(data.filename) : ("crm-smtp-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (crmSmtpResult) {
      crmSmtpResult.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function downloadCrmSmtpWatchExport() {
    const data = await apiGet("crm.smtp.watch.export");
    const filename = data && data.filename ? String(data.filename) : ("crm-smtp-watch-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (crmSmtpResult) {
      crmSmtpResult.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadCrmSmtpWatch() {
    if (!crmSmtpWatchView) return;
    try {
      const data = await apiGet("crm.smtp.watch.summary");
      crmSmtpWatchView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      crmSmtpWatchView.textContent = "Failed to load CRM SMTP watch.";
    }
  }

  async function loadCrmEmailTemplatesSummary() {
    if (!crmEmailTemplatesSummary) return;
    try {
      const siteId = document.getElementById("crmEmailTemplateSiteId");
      const data = await apiGetWithParams("crm.email_templates.get", {
        site_id: siteId && siteId.value ? siteId.value.trim() : "",
      });
      crmEmailTemplatesSummary.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      crmEmailTemplatesSummary.textContent = "Failed to load CRM email templates.";
      return null;
    }
  }

  async function loadCrmEmailTemplateSites() {
    if (!crmEmailTemplateSites) return null;
    try {
      const data = await apiGetWithParams("crm.email_templates.sites", {
        site_id: document.getElementById("crmEmailTemplateFilterSiteId") && document.getElementById("crmEmailTemplateFilterSiteId").value ? document.getElementById("crmEmailTemplateFilterSiteId").value.trim() : "",
        bridge: document.getElementById("crmEmailTemplateFilterBridge") && document.getElementById("crmEmailTemplateFilterBridge").value ? document.getElementById("crmEmailTemplateFilterBridge").value : "",
        template_key: document.getElementById("crmEmailTemplateFilterKey") && document.getElementById("crmEmailTemplateFilterKey").value ? document.getElementById("crmEmailTemplateFilterKey").value.trim() : "",
        search: document.getElementById("crmEmailTemplateFilterSearch") && document.getElementById("crmEmailTemplateFilterSearch").value ? document.getElementById("crmEmailTemplateFilterSearch").value.trim() : "",
        page: document.getElementById("crmEmailTemplateFilterPage") && document.getElementById("crmEmailTemplateFilterPage").value ? Number(document.getElementById("crmEmailTemplateFilterPage").value) : 1,
        limit: document.getElementById("crmEmailTemplateFilterLimit") && document.getElementById("crmEmailTemplateFilterLimit").value ? Number(document.getElementById("crmEmailTemplateFilterLimit").value) : 10,
      });
      crmEmailTemplateSites.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      crmEmailTemplateSites.textContent = "Failed to load CRM email template sites.";
      return null;
    }
  }

  async function downloadCrmEmailTemplatesExport() {
    const detailSiteId = document.getElementById("crmEmailTemplateSiteId");
    const data = await apiGetWithParams("crm.email_templates.export", {
      site_id: document.getElementById("crmEmailTemplateFilterSiteId") && document.getElementById("crmEmailTemplateFilterSiteId").value ? document.getElementById("crmEmailTemplateFilterSiteId").value.trim() : "",
      bridge: document.getElementById("crmEmailTemplateFilterBridge") && document.getElementById("crmEmailTemplateFilterBridge").value ? document.getElementById("crmEmailTemplateFilterBridge").value : "",
      template_key: document.getElementById("crmEmailTemplateFilterKey") && document.getElementById("crmEmailTemplateFilterKey").value ? document.getElementById("crmEmailTemplateFilterKey").value.trim() : "",
      search: document.getElementById("crmEmailTemplateFilterSearch") && document.getElementById("crmEmailTemplateFilterSearch").value ? document.getElementById("crmEmailTemplateFilterSearch").value.trim() : "",
      page: document.getElementById("crmEmailTemplateFilterPage") && document.getElementById("crmEmailTemplateFilterPage").value ? Number(document.getElementById("crmEmailTemplateFilterPage").value) : 1,
      limit: document.getElementById("crmEmailTemplateFilterLimit") && document.getElementById("crmEmailTemplateFilterLimit").value ? Number(document.getElementById("crmEmailTemplateFilterLimit").value) : 10,
      detail_site_id: detailSiteId && detailSiteId.value ? detailSiteId.value.trim() : "",
      test_log_limit: 15,
    });
    const filename = data && data.filename ? String(data.filename) : ("crm-email-templates-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (crmEmailTemplatesResult) {
      crmEmailTemplatesResult.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadCrmEmailTemplateTestLog() {
    if (!crmEmailTemplateTestLog) return null;
    try {
      const siteId = document.getElementById("crmEmailTemplateSiteId");
      const filterSiteId = document.getElementById("crmEmailTemplateLogSiteId");
      const success = document.getElementById("crmEmailTemplateLogSuccess");
      const templateKey = document.getElementById("crmEmailTemplateLogKey");
      const errorCode = document.getElementById("crmEmailTemplateLogErrorCode");
      const search = document.getElementById("crmEmailTemplateLogSearch");
      const page = document.getElementById("crmEmailTemplateLogPage");
      const limit = document.getElementById("crmEmailTemplateLogLimit");
      const data = await apiGetWithParams("crm.email_templates.test_log", {
        site_id: filterSiteId && filterSiteId.value ? filterSiteId.value.trim() : (siteId && siteId.value ? siteId.value.trim() : ""),
        success: success && success.value ? success.value : "",
        template_key: templateKey && templateKey.value ? templateKey.value.trim() : "",
        error_code: errorCode && errorCode.value ? errorCode.value.trim() : "",
        search: search && search.value ? search.value.trim() : "",
        page: page && page.value ? Number(page.value) : 1,
        limit: limit && limit.value ? Number(limit.value) : 15,
      });
      crmEmailTemplateTestLog.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      crmEmailTemplateTestLog.textContent = "Failed to load CRM email template test log.";
      return null;
    }
  }

  async function loadCrmEmailTemplateLogDetail() {
    if (!crmEmailTemplateLogDetail) return null;
    const logId = document.getElementById("crmEmailTemplateLogDetailId");
    const siteId = document.getElementById("crmEmailTemplateLogSiteId");
    if (!logId || !logId.value) {
      crmEmailTemplateLogDetail.textContent = "Enter a log ID to load detail.";
      return null;
    }
    try {
      const data = await apiGetWithParams("crm.email_templates.test_log_detail", {
        log_id: logId.value.trim(),
        site_id: siteId && siteId.value ? siteId.value.trim() : "",
      });
      crmEmailTemplateLogDetail.textContent = JSON.stringify(data, null, 2);
      if (data && data.item) {
        const templateSiteId = document.getElementById("crmEmailTemplateSiteId");
        const templateKey = document.getElementById("crmEmailTemplateKey");
        const logSiteId = document.getElementById("crmEmailTemplateLogSiteId");
        if (templateSiteId) templateSiteId.value = data.item.site_id || "";
        if (logSiteId) logSiteId.value = data.item.site_id || "";
        if (templateKey && data.item.template_key) templateKey.value = data.item.template_key;
      }
      return data;
    } catch (err) {
      crmEmailTemplateLogDetail.textContent = "Failed to load CRM email template log detail.";
      return null;
    }
  }

  async function downloadCrmEmailTemplateLogExport() {
    const logId = document.getElementById("crmEmailTemplateLogDetailId");
    const logSiteId = document.getElementById("crmEmailTemplateLogSiteId");
    const data = await apiGetWithParams("crm.email_templates.test_log_export", {
      site_id: logSiteId && logSiteId.value ? logSiteId.value.trim() : "",
      success: document.getElementById("crmEmailTemplateLogSuccess") && document.getElementById("crmEmailTemplateLogSuccess").value ? document.getElementById("crmEmailTemplateLogSuccess").value : "",
      template_key: document.getElementById("crmEmailTemplateLogKey") && document.getElementById("crmEmailTemplateLogKey").value ? document.getElementById("crmEmailTemplateLogKey").value.trim() : "",
      error_code: document.getElementById("crmEmailTemplateLogErrorCode") && document.getElementById("crmEmailTemplateLogErrorCode").value ? document.getElementById("crmEmailTemplateLogErrorCode").value.trim() : "",
      search: document.getElementById("crmEmailTemplateLogSearch") && document.getElementById("crmEmailTemplateLogSearch").value ? document.getElementById("crmEmailTemplateLogSearch").value.trim() : "",
      page: document.getElementById("crmEmailTemplateLogPage") && document.getElementById("crmEmailTemplateLogPage").value ? Number(document.getElementById("crmEmailTemplateLogPage").value) : 1,
      limit: document.getElementById("crmEmailTemplateLogLimit") && document.getElementById("crmEmailTemplateLogLimit").value ? Number(document.getElementById("crmEmailTemplateLogLimit").value) : 15,
      log_id: logId && logId.value ? logId.value.trim() : "",
      detail_site_id: logSiteId && logSiteId.value ? logSiteId.value.trim() : "",
    });
    const filename = data && data.filename ? String(data.filename) : ("crm-email-template-test-log-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (crmEmailTemplatesResult) {
      crmEmailTemplatesResult.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadCrmOperationsSnapshot() {
    if (!crmOperationsSnapshot) return null;
    const limit = document.getElementById("crmOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("crm.operations.snapshot", {
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      crmOperationsSnapshot.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      crmOperationsSnapshot.textContent = "Failed to load CRM operations snapshot.";
      return null;
    }
  }

  async function loadCrmOperationsHistory() {
    if (!crmOperationsHistory) return null;
    const limit = document.getElementById("crmOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("crm.operations.history", {
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      crmOperationsHistory.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      crmOperationsHistory.textContent = "Failed to load CRM operations history.";
      return null;
    }
  }

  async function loadCrmOperationsHistorySummary() {
    if (!crmOperationsHistorySummary) return null;
    const limit = document.getElementById("crmOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("crm.operations.history_summary", {
        limit: limit && limit.value ? Number(limit.value) : 20,
      });
      crmOperationsHistorySummary.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      crmOperationsHistorySummary.textContent = "Failed to load CRM operations history summary.";
      return null;
    }
  }

  async function loadCrmOperationsLatestCompare() {
    if (!crmOperationsLatestCompare) return null;
    try {
      const data = await apiGet("crm.operations.latest_compare");
      crmOperationsLatestCompare.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      crmOperationsLatestCompare.textContent = "Failed to load CRM operations latest compare.";
      return null;
    }
  }

  async function loadCrmOperationsIssuesSummary() {
    if (!crmOperationsIssuesSummary) return null;
    const limit = document.getElementById("crmOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("crm.operations.issues_summary", {
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      crmOperationsIssuesSummary.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      crmOperationsIssuesSummary.textContent = "Failed to load CRM operations issues summary.";
      return null;
    }
  }

  async function downloadCrmOperationsSnapshot() {
    const limit = document.getElementById("crmOperationsSnapshotLimit");
    const data = await apiGetWithParams("crm.operations.export", {
      limit: limit && limit.value ? Number(limit.value) : 10,
    });
    const filename = data && data.filename ? String(data.filename) : ("crm-operations-snapshot-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (crmOperationsSnapshot) {
      crmOperationsSnapshot.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadSocialConnectors() {
    if (!socialConnectors) return;
    try {
      const data = await apiGetWithParams("social.connectors.list", socialConnectorFilters());
      socialConnectors.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialConnectors.textContent = "Failed to load social connectors.";
    }
  }

  async function loadSocialConnectorDetail() {
    if (!socialConnectorDetail) return;
    const connectorId = document.getElementById("socialConnectorDetailId");
    const limit = document.getElementById("socialConnectorDetailLimit");
    if (!connectorId || !connectorId.value) {
      socialConnectorDetail.textContent = "Enter a connector ID to load detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("social.connectors.detail", {
        connector_id: connectorId.value.trim(),
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      socialConnectorDetail.textContent = JSON.stringify(data, null, 2);
      if (data && data.item) {
        const formId = document.getElementById("socialConnectorId");
        const provider = document.getElementById("socialProvider");
        const accountLabel = document.getElementById("socialAccountLabel");
        const type = document.getElementById("socialType");
        const status = document.getElementById("socialStatus");
        const auth = document.getElementById("socialAuth");
        const caps = document.getElementById("socialCapabilities");
        const siteId = document.getElementById("socialSiteId");
        const runMode = document.getElementById("socialRunMode");
        const webhook = document.getElementById("socialWebhook");
        const expiresAt = document.getElementById("socialExpiresAt");
        const deliveryConnectorId = document.getElementById("socialDeliveryConnectorId");
        if (formId) formId.value = data.item.connector_id || "";
        if (provider) provider.value = data.item.provider || "";
        if (accountLabel) accountLabel.value = data.item.account_label || "";
        if (type) type.value = data.item.type || "external_api";
        if (status) status.value = data.item.status || "planned";
        if (auth) auth.value = data.item.auth_mode || "api_key";
        if (caps) caps.value = Array.isArray(data.item.capabilities) ? data.item.capabilities.join(",") : "";
        if (siteId) siteId.value = data.item.site_id || "";
        if (runMode) runMode.value = data.item.config && data.item.config.run_mode ? data.item.config.run_mode : "dry_run";
        if (webhook) webhook.value = data.item.config && data.item.config.webhook_url ? data.item.config.webhook_url : "";
        if (expiresAt) expiresAt.value = data.item.expires_at || "";
        if (deliveryConnectorId) deliveryConnectorId.value = data.item.connector_id || "";
      }
    } catch (err) {
      socialConnectorDetail.textContent = "Failed to load social connector detail.";
    }
  }

  async function loadSocialConnectorRuleAudit() {
    if (!socialConnectorRuleAudit) return;
    const filters = socialConnectorFilters();
    const draftLimit = document.getElementById("socialConnectorRuleAuditDraftLimit");
    try {
      const data = await apiGetWithParams("social.connectors.rule_audit", {
        provider: filters.provider,
        status: filters.status,
        site_id: filters.site_id,
        run_mode: filters.run_mode,
        search: filters.search,
        page: filters.page,
        limit: filters.limit,
        draft_limit: draftLimit && draftLimit.value ? Number(draftLimit.value) : 5,
      });
      socialConnectorRuleAudit.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialConnectorRuleAudit.textContent = "Failed to load social connector rule audit.";
    }
  }

  async function loadSocialConnectorRuleAuditDetail() {
    if (!socialConnectorRuleAuditDetail) return;
    const connectorId = document.getElementById("socialConnectorRuleAuditDetailId");
    const draftLimit = document.getElementById("socialConnectorRuleAuditDraftLimit");
    if (!connectorId || !connectorId.value) {
      socialConnectorRuleAuditDetail.textContent = "Enter a connector ID to load connector audit detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("social.connectors.rule_audit_detail", {
        connector_id: connectorId.value.trim(),
        draft_limit: draftLimit && draftLimit.value ? Number(draftLimit.value) : 5,
      });
      socialConnectorRuleAuditDetail.textContent = JSON.stringify(data, null, 2);
      const connectorDetailId = document.getElementById("socialConnectorDetailId");
      if (connectorDetailId) {
        connectorDetailId.value = connectorId.value.trim();
      }
    } catch (err) {
      socialConnectorRuleAuditDetail.textContent = "Failed to load social connector rule audit detail.";
    }
  }

  async function downloadSocialConnectorRuleAuditExport() {
    const filters = socialConnectorFilters();
    const connectorId = document.getElementById("socialConnectorRuleAuditDetailId");
    const draftLimit = document.getElementById("socialConnectorRuleAuditDraftLimit");
    const data = await apiGetWithParams("social.connectors.rule_audit.export", {
      provider: filters.provider,
      status: filters.status,
      site_id: filters.site_id,
      run_mode: filters.run_mode,
      search: filters.search,
      page: filters.page,
      limit: filters.limit,
      connector_id: connectorId && connectorId.value ? connectorId.value.trim() : "",
      draft_limit: draftLimit && draftLimit.value ? Number(draftLimit.value) : 5,
    });
    const filename = data && data.filename ? String(data.filename) : ("social-connector-rule-audit-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function downloadSocialConnectorsExport() {
    const connectorId = document.getElementById("socialConnectorDetailId");
    const limit = document.getElementById("socialConnectorDetailLimit");
    const params = socialConnectorFilters();
    if (connectorId && connectorId.value) {
      params.connector_id = connectorId.value.trim();
    }
    if (limit && limit.value) {
      params.limit = Number(limit.value);
    }
    const data = await apiGetWithParams("social.connectors.export", params);
    const filename = data && data.filename ? String(data.filename) : ("social-connectors-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function runSocialConnectorBulkUpdate() {
    if (!socialConnectorBulkResult) return;
    const filters = socialConnectorFilters();
    const status = document.getElementById("socialConnectorBulkStatus");
    const site = document.getElementById("socialConnectorBulkSite");
    const runMode = document.getElementById("socialConnectorBulkRunMode");
    const expiresAt = document.getElementById("socialConnectorBulkExpiresAt");
    const payload = {
      filter_provider: filters.provider,
      filter_status: filters.status,
      filter_site_id: filters.site_id,
      filter_run_mode: filters.run_mode,
      filter_search: filters.search,
      filter_page: filters.page,
      filter_limit: filters.limit,
      status: status && status.value ? status.value : "",
      site_id: site && site.value ? site.value.trim() : "",
      run_mode: runMode && runMode.value ? runMode.value : "",
      expires_at: expiresAt && expiresAt.value ? expiresAt.value : "",
    };
    const data = await apiPost("social.connectors.bulk_update", payload);
    socialConnectorBulkResult.textContent = JSON.stringify(data, null, 2);
    await loadSocialConnectors();
    await loadSocialWatch();
    await loadSocialOperationsSnapshot();
    await refreshSocialModuleSummary();
  }

  function socialConnectorFilters() {
    const provider = document.getElementById("socialConnectorFilterProvider");
    const status = document.getElementById("socialConnectorFilterStatus");
    const site = document.getElementById("socialConnectorFilterSite");
    const runMode = document.getElementById("socialConnectorFilterRunMode");
    const search = document.getElementById("socialConnectorFilterSearch");
    const page = document.getElementById("socialConnectorFilterPage");
    const limit = document.getElementById("socialConnectorFilterLimit");
    return {
      provider: provider && provider.value ? provider.value.trim() : "",
      status: status && status.value ? status.value : "",
      site_id: site && site.value ? site.value.trim() : "",
      run_mode: runMode && runMode.value ? runMode.value : "",
      search: search && search.value ? search.value.trim() : "",
      page: page && page.value ? Number(page.value) : 1,
      limit: limit && limit.value ? Number(limit.value) : 10,
    };
  }

  async function loadSocialScheduleQueue() {
    if (!socialScheduleQueue) return;
    try {
      const data = await apiGetWithParams("social.schedule.list", socialScheduleFilters());
      socialScheduleQueue.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialScheduleQueue.textContent = "Failed to load social schedule queue.";
    }
  }

  function socialScheduleFilters() {
    const status = document.getElementById("socialScheduleFilterStatus");
    const connector = document.getElementById("socialScheduleFilterConnector");
    const search = document.getElementById("socialScheduleFilterSearch");
    const page = document.getElementById("socialScheduleFilterPage");
    const limit = document.getElementById("socialScheduleFilterLimit");
    return {
      status: status && status.value ? status.value : "",
      connector_id: connector && connector.value ? connector.value.trim() : "",
      search: search && search.value ? search.value.trim() : "",
      page: page && page.value ? page.value.trim() : "1",
      limit: limit && limit.value ? limit.value.trim() : "10",
    };
  }

  async function loadSocialScheduleSummary() {
    if (!socialScheduleSummary) return;
    try {
      const data = await apiGetWithParams("social.schedule.summary", { limit: 8 });
      socialScheduleSummary.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialScheduleSummary.textContent = "Failed to load social schedule health.";
    }
  }

  async function loadSocialScheduleDetail() {
    if (!socialScheduleDetail) return;
    const scheduleIdInput = document.getElementById("socialScheduleDetailId");
    const scheduleId = scheduleIdInput && scheduleIdInput.value ? scheduleIdInput.value.trim() : "";
    if (!scheduleId) {
      socialScheduleDetail.textContent = "Enter a schedule ID to load detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("social.schedule.detail", { schedule_id: scheduleId });
      socialScheduleDetail.textContent = JSON.stringify(data, null, 2);
      const item = data && data.item ? data.item : null;
      if (item) {
        const id = document.getElementById("socialScheduleId");
        const title = document.getElementById("socialScheduleTitle");
        const message = document.getElementById("socialScheduleMessage");
        const url = document.getElementById("socialScheduleUrl");
        const connectorIds = document.getElementById("socialScheduleConnectorIds");
        const scheduledFor = document.getElementById("socialScheduleFor");
        if (id) id.value = item.schedule_id || "";
        if (title) title.value = item.title || "";
        if (message) message.value = item.message || "";
        if (url) url.value = item.url || "";
        if (connectorIds) {
          connectorIds.value = Array.isArray(item.connector_ids) ? item.connector_ids.join(",") : "";
        }
        if (scheduledFor) {
          const value = item.scheduled_for ? String(item.scheduled_for).replace("Z", "") : "";
          scheduledFor.value = value.length >= 16 ? value.slice(0, 16) : value;
        }
      }
    } catch (err) {
      socialScheduleDetail.textContent = "Failed to load social schedule detail.";
    }
  }

  async function downloadSocialScheduleExport() {
    const detailId = document.getElementById("socialScheduleDetailId");
    const params = socialScheduleFilters();
    if (detailId && detailId.value) {
      params.schedule_id = detailId.value.trim();
    }
    const data = await apiGetWithParams("social.schedule.export", params);
    const filename = data && data.filename ? String(data.filename) : ("social-schedule-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function runSocialScheduleBulkUpdate() {
    const filters = socialScheduleFilters();
    const payload = {
      filter_status: filters.status || "",
      filter_connector_id: filters.connector_id || "",
      filter_search: filters.search || "",
      filter_page: filters.page || "1",
      filter_limit: filters.limit || "10",
      status: document.getElementById("socialScheduleBulkStatus") && document.getElementById("socialScheduleBulkStatus").value
        ? document.getElementById("socialScheduleBulkStatus").value
        : "",
      scheduled_for: document.getElementById("socialScheduleBulkFor") && document.getElementById("socialScheduleBulkFor").value
        ? document.getElementById("socialScheduleBulkFor").value
        : "",
    };
    const result = await apiPost("social.schedule.bulk_update", payload);
    if (socialScheduleDetail) {
      socialScheduleDetail.textContent = JSON.stringify(result, null, 2);
    }
    await loadSocialScheduleQueue();
    await loadSocialScheduleSummary();
    await loadSocialActivityFeed();
    await refreshSocialModuleSummary();
  }

  async function validateSocialScheduleTargets() {
    if (!socialScheduleTargetValidation) return null;
    const connectorIds = document.getElementById("socialScheduleConnectorIds");
    const value = connectorIds && connectorIds.value ? connectorIds.value.trim() : "";
    try {
      const data = await apiGetWithParams("social.schedule.targets.validate", {
        connector_ids: value,
      });
      socialScheduleTargetValidation.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      socialScheduleTargetValidation.textContent = "Failed to validate social schedule targets.";
      return null;
    }
  }

  async function loadSocialActivityFeed() {
    if (!socialActivityFeed) return;
    try {
      const data = await apiGetWithParams("social.activity.list", socialActivityFilters());
      socialActivityFeed.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialActivityFeed.textContent = "Failed to load social activity feed.";
    }
  }

  function socialActivityFilters() {
    const type = document.getElementById("socialActivityFilterType");
    const ref = document.getElementById("socialActivityFilterRef");
    const search = document.getElementById("socialActivityFilterSearch");
    const page = document.getElementById("socialActivityFilterPage");
    const limit = document.getElementById("socialActivityFilterLimit");
    return {
      type: type && type.value ? type.value.trim() : "",
      ref: ref && ref.value ? ref.value.trim() : "",
      search: search && search.value ? search.value.trim() : "",
      page: page && page.value ? Number(page.value) : 1,
      limit: limit && limit.value ? Number(limit.value) : 10,
    };
  }

  async function loadSocialActivityDetail() {
    if (!socialActivityDetail) return;
    const activityId = document.getElementById("socialActivityDetailId");
    if (!activityId || !activityId.value) {
      socialActivityDetail.textContent = "Enter an activity ID to load detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("social.activity.detail", { activity_id: activityId.value.trim() });
      socialActivityDetail.textContent = JSON.stringify(data, null, 2);
      if (data && data.item) {
        const syncId = document.getElementById("socialSyncDetailId");
        const retryId = document.getElementById("socialRetryDetailId");
        const connectorId = document.getElementById("socialConnectorDetailId");
        if (syncId && data.item.meta && data.item.meta.sync_id) syncId.value = data.item.meta.sync_id;
        if (retryId && data.item.meta && data.item.meta.retry_id) retryId.value = data.item.meta.retry_id;
        if (connectorId && data.item.meta && data.item.meta.connector_id) connectorId.value = data.item.meta.connector_id;
      }
    } catch (err) {
      socialActivityDetail.textContent = "Failed to load social activity detail.";
    }
  }

  async function downloadSocialActivityExport() {
    const params = socialActivityFilters();
    const activityId = document.getElementById("socialActivityDetailId");
    if (activityId && activityId.value) {
      params.activity_id = activityId.value.trim();
    }
    const data = await apiGetWithParams("social.activity.export", params);
    const filename = data && data.filename ? String(data.filename) : ("social-activity-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  function socialInboxFilters() {
    const status = document.getElementById("socialInboxFilterStatus");
    const priority = document.getElementById("socialInboxFilterPriority");
    const provider = document.getElementById("socialInboxFilterProvider");
    const owner = document.getElementById("socialInboxFilterOwner");
    const search = document.getElementById("socialInboxFilterSearch");
    const page = document.getElementById("socialInboxFilterPage");
    const limit = document.getElementById("socialInboxFilterLimit");
    return {
      status: status && status.value ? status.value : "",
      priority: priority && priority.value ? priority.value : "",
      provider: provider && provider.value ? provider.value.trim() : "",
      owner: owner && owner.value ? owner.value.trim() : "",
      search: search && search.value ? search.value.trim() : "",
      page: page && page.value ? page.value.trim() : "1",
      limit: limit && limit.value ? limit.value.trim() : "10",
    };
  }

  async function loadSocialInboxThreads() {
    if (!socialInboxThreads) return;
    try {
      const data = await apiGetWithParams("social.inbox.list", socialInboxFilters());
      socialInboxThreads.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialInboxThreads.textContent = "Failed to load social inbox.";
    }
  }

  async function loadSocialInboxDetail() {
    if (!socialInboxThreadDetail) return;
    const detailThread = document.getElementById("socialInboxDetailThreadId");
    const threadId = detailThread && detailThread.value ? detailThread.value.trim() : "";
    if (!threadId) {
      socialInboxThreadDetail.textContent = "Enter a thread ID to load detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("social.inbox.detail", { thread_id: threadId });
      socialInboxThreadDetail.textContent = JSON.stringify(data, null, 2);
      const replyThread = document.getElementById("socialInboxThreadId");
      const updateThread = document.getElementById("socialInboxUpdateThreadId");
      const owner = document.getElementById("socialInboxOwner");
      const note = document.getElementById("socialInboxInternalNote");
      const status = document.getElementById("socialInboxStatus");
      const priority = document.getElementById("socialInboxPriority");
      const thread = data && data.thread ? data.thread : null;
      if (replyThread) replyThread.value = thread && thread.thread_id ? thread.thread_id : threadId;
      if (updateThread) updateThread.value = thread && thread.thread_id ? thread.thread_id : threadId;
      if (owner) owner.value = thread && thread.owner ? thread.owner : "";
      if (note) note.value = thread && thread.internal_note ? thread.internal_note : "";
      if (status) status.value = thread && thread.status ? thread.status : "";
      if (priority) priority.value = thread && thread.priority ? thread.priority : "";
    } catch (err) {
      socialInboxThreadDetail.textContent = "Failed to load social inbox thread detail.";
    }
  }

  async function runSocialInboxBulkUpdate() {
    const filters = socialInboxFilters();
    const payload = {
      filter_status: filters.status || "",
      filter_priority: filters.priority || "",
      filter_provider: filters.provider || "",
      filter_owner: filters.owner || "",
      filter_search: filters.search || "",
      filter_page: filters.page || "1",
      filter_limit: filters.limit || "10",
      status: document.getElementById("socialInboxBulkStatus") && document.getElementById("socialInboxBulkStatus").value
        ? document.getElementById("socialInboxBulkStatus").value
        : "",
      priority: document.getElementById("socialInboxBulkPriority") && document.getElementById("socialInboxBulkPriority").value
        ? document.getElementById("socialInboxBulkPriority").value
        : "",
      owner: document.getElementById("socialInboxBulkOwner") && document.getElementById("socialInboxBulkOwner").value
        ? document.getElementById("socialInboxBulkOwner").value.trim()
        : "",
      internal_note: document.getElementById("socialInboxBulkNote") && document.getElementById("socialInboxBulkNote").value
        ? document.getElementById("socialInboxBulkNote").value.trim()
        : "",
    };
    const result = await apiPost("social.inbox.bulk_update", payload);
    if (socialInboxThreadDetail) {
      socialInboxThreadDetail.textContent = JSON.stringify(result, null, 2);
    }
    await loadSocialInboxThreads();
    await loadSocialInboxSummary();
    await loadSocialInboxWorkload();
    await loadSocialInboxWatch();
    await loadSocialActivityFeed();
    await refreshSocialModuleSummary();
  }

  async function downloadSocialInboxExport() {
    const detailThread = document.getElementById("socialInboxDetailThreadId");
    const params = socialInboxFilters();
    if (detailThread && detailThread.value) {
      params.thread_id = detailThread.value.trim();
    }
    const data = await apiGetWithParams("social.inbox.export", params);
    const filename = data && data.filename ? String(data.filename) : ("social-inbox-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function loadSocialInboxSummary() {
    if (!socialInboxSummary) return;
    try {
      const data = await apiGetWithParams("social.inbox.summary", { limit: 8 });
      socialInboxSummary.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialInboxSummary.textContent = "Failed to load social inbox summary.";
    }
  }

  async function loadSocialInboxWorkload() {
    if (!socialInboxWorkload) return;
    try {
      const data = await apiGetWithParams("social.inbox.workload", { limit: 8 });
      socialInboxWorkload.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialInboxWorkload.textContent = "Failed to load social inbox workload.";
    }
  }

  async function loadSocialInboxWatch() {
    if (!socialInboxWatchView) return;
    try {
      const data = await apiGet("social.inbox.watch.summary");
      socialInboxWatchView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialInboxWatchView.textContent = "Failed to load social inbox watch.";
    }
  }

  async function refreshSocialModuleSummary() {
    const socialPanel = document.getElementById("modSocial");
    if (!socialPanel) return;
    try {
      const socialData = await apiGet("social.summary");
      socialPanel.textContent = JSON.stringify(socialData, null, 2);
    } catch (err) {
      socialPanel.textContent = "Failed to load social summary.";
    }
  }

  async function loadSocialPlatforms() {
    if (!socialPlatforms) return;
    try {
      const data = await apiGetWithParams("social.platforms.list", socialPlatformFilters());
      socialPlatformCatalogItems = data && Array.isArray(data.items) ? data.items : [];
      socialPlatforms.textContent = JSON.stringify(data, null, 2);
      const providerInput = document.getElementById("socialProvider");
      if (providerInput && providerInput.value) {
        applySocialProviderProfile(providerInput.value);
      }
    } catch (err) {
      socialPlatforms.textContent = "Failed to load social platforms.";
    }
  }

  function socialPlatformFilters() {
    const family = document.getElementById("socialPlatformFilterFamily");
    const authMode = document.getElementById("socialPlatformFilterAuthMode");
    const capability = document.getElementById("socialPlatformFilterCapability");
    const search = document.getElementById("socialPlatformFilterSearch");
    const page = document.getElementById("socialPlatformFilterPage");
    const limit = document.getElementById("socialPlatformFilterLimit");
    return {
      family: family && family.value ? family.value.trim() : "",
      auth_mode: authMode && authMode.value ? authMode.value.trim() : "",
      capability: capability && capability.value ? capability.value.trim() : "",
      search: search && search.value ? search.value.trim() : "",
      page: page && page.value ? Number(page.value) : 1,
      limit: limit && limit.value ? Number(limit.value) : 10,
    };
  }

  async function loadSocialPlatformDetail() {
    if (!socialPlatformDetail) return;
    const provider = document.getElementById("socialPlatformDetailProvider");
    if (!provider || !provider.value) {
      socialPlatformDetail.textContent = "Enter a provider to load platform detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("social.platforms.detail", { provider: provider.value.trim() });
      socialPlatformDetail.textContent = JSON.stringify(data, null, 2);
      if (data && data.item) {
        const providerInput = document.getElementById("socialProvider");
        if (providerInput) {
          providerInput.value = data.item.provider || "";
          applySocialProviderProfile(providerInput.value);
        }
      }
    } catch (err) {
      socialPlatformDetail.textContent = "Failed to load social platform detail.";
    }
  }

  async function loadSocialPlatformCoverage() {
    if (!socialPlatformCoverage) return;
    const family = document.getElementById("socialPlatformCoverageFamily");
    const search = document.getElementById("socialPlatformCoverageSearch");
    try {
      const data = await apiGetWithParams("social.platforms.coverage", {
        family: family && family.value ? family.value.trim() : "",
        search: search && search.value ? search.value.trim() : "",
      });
      socialPlatformCoverage.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialPlatformCoverage.textContent = "Failed to load social platform coverage.";
    }
  }

  async function downloadSocialPlatformCoverageExport() {
    const family = document.getElementById("socialPlatformCoverageFamily");
    const search = document.getElementById("socialPlatformCoverageSearch");
    const data = await apiGetWithParams("social.platforms.coverage.export", {
      family: family && family.value ? family.value.trim() : "",
      search: search && search.value ? search.value.trim() : "",
    });
    const filename = data && data.filename ? String(data.filename) : ("social-platform-coverage-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function downloadSocialPlatformsExport() {
    const params = socialPlatformFilters();
    const provider = document.getElementById("socialPlatformDetailProvider");
    if (provider && provider.value) {
      params.provider = provider.value.trim();
    }
    const data = await apiGetWithParams("social.platforms.export", params);
    const filename = data && data.filename ? String(data.filename) : ("social-platforms-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  const renderSocialProviderProfile = function (profile, message) {
    if (!socialProviderProfile) return;
    if (profile) {
      socialProviderProfile.textContent = JSON.stringify(profile, null, 2);
      return;
    }
    socialProviderProfile.textContent = message || "Select a provider to load defaults.";
  };

  const applySocialProviderProfile = function (providerValue) {
    const provider = providerValue ? providerValue.trim().toLowerCase() : "";
    if (!provider) {
      renderSocialProviderProfile(null, "Select a provider to load defaults.");
      return;
    }
    const profile = socialPlatformCatalogItems.find(function (item) {
      return item && item.provider === provider;
    });
    if (!profile) {
      renderSocialProviderProfile(null, "No catalog match. Manual setup is required for this provider.");
      return;
    }
    const type = document.getElementById("socialType");
    const auth = document.getElementById("socialAuth");
    const caps = document.getElementById("socialCapabilities");
    const accountLabel = document.getElementById("socialAccountLabel");
    if (type && profile.default_type) {
      type.value = profile.default_type;
    }
    if (auth && Array.isArray(profile.auth_modes) && profile.auth_modes.length > 0) {
      auth.value = profile.auth_modes[0];
    }
    if (caps && Array.isArray(profile.capabilities)) {
      caps.value = profile.capabilities.join(", ");
    }
    if (accountLabel && !accountLabel.value.trim() && profile.label) {
      accountLabel.value = profile.label;
    }
    renderSocialProviderProfile(profile);
  };

  async function loadSocialCapabilitiesSummary() {
    if (!socialCapabilitiesSummary) return;
    try {
      const data = await apiGet("social.capabilities.summary");
      socialCapabilitiesSummary.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialCapabilitiesSummary.textContent = "Failed to load social capability map.";
    }
  }

  async function loadSocialWatch() {
    if (!socialWatchView) return;
    try {
      const data = await apiGet("social.watch.summary");
      socialWatchView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialWatchView.textContent = "Failed to load social watch.";
    }
  }

  async function loadSocialOperationsSnapshot() {
    if (!socialOpsSnapshotView) return;
    try {
      const limit = document.getElementById("socialOperationsSnapshotLimit");
      const data = await apiGetWithParams("social.operations.snapshot", {
        limit: limit && limit.value ? Number(limit.value) : 5,
      });
      socialOpsSnapshotView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialOpsSnapshotView.textContent = "Failed to load social operations snapshot.";
    }
  }

  async function loadSocialOperationsHistory() {
    if (!socialOpsHistoryView) return null;
    const limit = document.getElementById("socialOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("social.operations.history", {
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      socialOpsHistoryView.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      socialOpsHistoryView.textContent = "Failed to load social operations history.";
      return null;
    }
  }

  async function loadSocialOperationsHistorySummary() {
    if (!socialOpsHistorySummaryView) return null;
    const limit = document.getElementById("socialOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("social.operations.history_summary", {
        limit: limit && limit.value ? Number(limit.value) : 20,
      });
      socialOpsHistorySummaryView.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      socialOpsHistorySummaryView.textContent = "Failed to load social operations history summary.";
      return null;
    }
  }

  async function loadSocialOperationsLatestCompare() {
    if (!socialOpsLatestCompareView) return null;
    try {
      const data = await apiGet("social.operations.latest_compare");
      socialOpsLatestCompareView.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      socialOpsLatestCompareView.textContent = "Failed to load social operations latest compare.";
      return null;
    }
  }

  async function loadSocialOperationsIssuesSummary() {
    if (!socialOpsIssuesSummaryView) return null;
    const limit = document.getElementById("socialOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("social.operations.issues_summary", {
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      socialOpsIssuesSummaryView.textContent = JSON.stringify(data, null, 2);
      return data;
    } catch (err) {
      socialOpsIssuesSummaryView.textContent = "Failed to load social operations issues summary.";
      return null;
    }
  }

  async function downloadSocialOperationsSnapshot() {
    const limit = document.getElementById("socialOperationsSnapshotLimit");
    const data = await apiGetWithParams("social.operations.export", {
      limit: limit && limit.value ? Number(limit.value) : 5,
    });
    const filename = data && data.filename ? String(data.filename) : ("social-operations-snapshot-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (socialOpsSnapshotView) {
      socialOpsSnapshotView.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadSocialDraftsPreview() {
    if (!socialDraftsPreview) return;
    try {
      const data = await apiGet("social.drafts.preview");
      socialDraftsPreview.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialDraftsPreview.textContent = "Failed to load social drafts preview.";
    }
  }

  function socialDraftFilters() {
    const site = document.getElementById("socialDraftFilterSite");
    const hasUrl = document.getElementById("socialDraftFilterHasUrl");
    const search = document.getElementById("socialDraftFilterSearch");
    const page = document.getElementById("socialDraftFilterPage");
    const limit = document.getElementById("socialDraftFilterLimit");
    return {
      source_site_id: site && site.value ? site.value.trim() : "",
      has_url: hasUrl && hasUrl.value ? hasUrl.value : "",
      search: search && search.value ? search.value.trim() : "",
      page: page && page.value ? Number(page.value) : 1,
      limit: limit && limit.value ? Number(limit.value) : 10,
    };
  }

  async function loadSocialDraftList() {
    if (!socialDraftList) return;
    try {
      const data = await apiGetWithParams("social.drafts.list", socialDraftFilters());
      socialDraftList.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialDraftList.textContent = "Failed to load social draft list.";
    }
  }

  async function loadSocialDraftDetail() {
    if (!socialDraftDetail) return;
    const draftIndex = document.getElementById("socialDraftDetailIndex");
    const leadId = document.getElementById("socialDraftDetailLeadId");
    const params = {};
    if (draftIndex && draftIndex.value !== "") {
      params.draft_index = Number(draftIndex.value);
    }
    if (leadId && leadId.value !== "") {
      params.lead_id = Number(leadId.value);
    }
    if (!Object.keys(params).length) {
      socialDraftDetail.textContent = "Enter a draft index or lead ID to load detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("social.drafts.detail", params);
      socialDraftDetail.textContent = JSON.stringify(data, null, 2);
      if (data && data.item) {
        const title = document.getElementById("socialScheduleTitle");
        const message = document.getElementById("socialScheduleMessage");
        const url = document.getElementById("socialScheduleUrl");
        const scheduleIndex = document.getElementById("socialDraftScheduleIndex");
        const scheduleLeadId = document.getElementById("socialDraftScheduleLeadId");
        if (title) title.value = data.item.title || "";
        if (message) message.value = data.item.message || "";
        if (url) url.value = data.item.url || "";
        if (scheduleIndex) scheduleIndex.value = data.item.draft_index != null ? String(data.item.draft_index) : "";
        if (scheduleLeadId) scheduleLeadId.value = data.item.lead_id != null ? String(data.item.lead_id) : "";
      }
    } catch (err) {
      socialDraftDetail.textContent = "Failed to load social draft detail.";
    }
  }

  function socialDraftRecommendationParams() {
    const params = socialDraftFilters();
    const draftIndex = document.getElementById("socialDraftDetailIndex");
    const leadId = document.getElementById("socialDraftDetailLeadId");
    if (draftIndex && draftIndex.value !== "") {
      params.draft_index = Number(draftIndex.value);
    }
    if (leadId && leadId.value !== "") {
      params.lead_id = Number(leadId.value);
    }
    return params;
  }

  async function loadSocialDraftRecommendations() {
    if (!socialDraftRecommendations) return;
    try {
      const data = await apiGetWithParams("social.drafts.recommendations", socialDraftRecommendationParams());
      socialDraftRecommendations.textContent = JSON.stringify(data, null, 2);
      if (data && data.summary && Number(data.summary.detail_mode) === 1 && Array.isArray(data.items) && data.items.length) {
        const connectorIds = document.getElementById("socialScheduleConnectorIds");
        const draftConnectorIds = document.getElementById("socialDraftScheduleConnectorIds");
        const recommendedValue = Array.isArray(data.items[0].recommended_connector_ids)
          ? data.items[0].recommended_connector_ids.join(",")
          : "";
        if (connectorIds) {
          connectorIds.value = recommendedValue;
        }
        if (draftConnectorIds) {
          draftConnectorIds.value = recommendedValue;
        }
      }
    } catch (err) {
      socialDraftRecommendations.textContent = "Failed to load social draft recommendations.";
    }
  }

  async function loadSocialDraftVariants() {
    if (!socialDraftVariants) return;
    const draftIndex = document.getElementById("socialDraftDetailIndex");
    const leadId = document.getElementById("socialDraftDetailLeadId");
    const connector = document.getElementById("socialDraftVariantConnector");
    const params = {};
    if (draftIndex && draftIndex.value !== "") {
      params.draft_index = Number(draftIndex.value);
    }
    if (leadId && leadId.value !== "") {
      params.lead_id = Number(leadId.value);
    }
    if (connector && connector.value) {
      params.connector_id = connector.value.trim();
    }
    if (!Object.keys(params).length || (params.draft_index === undefined && params.lead_id === undefined)) {
      socialDraftVariants.textContent = "Enter a draft index or lead ID before loading variants.";
      return;
    }
    try {
      const data = await apiGetWithParams("social.drafts.variants", params);
      socialDraftVariants.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialDraftVariants.textContent = "Failed to load social draft variants.";
    }
  }

  async function downloadSocialDraftVariantsExport() {
    const draftIndex = document.getElementById("socialDraftDetailIndex");
    const leadId = document.getElementById("socialDraftDetailLeadId");
    const connector = document.getElementById("socialDraftVariantConnector");
    const params = {};
    if (draftIndex && draftIndex.value !== "") {
      params.draft_index = Number(draftIndex.value);
    }
    if (leadId && leadId.value !== "") {
      params.lead_id = Number(leadId.value);
    }
    if (connector && connector.value) {
      params.connector_id = connector.value.trim();
    }
    if (params.draft_index === undefined && params.lead_id === undefined) {
      if (socialDraftVariants) {
        socialDraftVariants.textContent = "Enter a draft index or lead ID before exporting variants.";
      }
      return;
    }
    const data = await apiGetWithParams("social.drafts.variants.export", params);
    const filename = data && data.filename ? String(data.filename) : ("social-draft-variants-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function runSocialDraftSchedule() {
    if (!socialDraftScheduleResult) return;
    const draftIndex = document.getElementById("socialDraftScheduleIndex");
    const leadId = document.getElementById("socialDraftScheduleLeadId");
    const connectorIds = document.getElementById("socialDraftScheduleConnectorIds");
    const scheduledFor = document.getElementById("socialDraftScheduleFor");
    const payload = {
      draft_index: draftIndex && draftIndex.value !== "" ? Number(draftIndex.value) : undefined,
      lead_id: leadId && leadId.value !== "" ? Number(leadId.value) : undefined,
      connector_ids: connectorIds && connectorIds.value
        ? connectorIds.value.split(",").map(function (value) { return value.trim(); }).filter(Boolean)
        : [],
      scheduled_for: scheduledFor && scheduledFor.value ? scheduledFor.value : "",
    };
    if (payload.draft_index === undefined && payload.lead_id === undefined) {
      socialDraftScheduleResult.textContent = "Enter a draft index or lead ID before scheduling.";
      return;
    }
    const data = await apiPost("social.drafts.schedule", payload);
    socialDraftScheduleResult.textContent = JSON.stringify(data, null, 2);
    await loadSocialScheduleSummary();
    await loadSocialScheduleQueue();
    await loadSocialActivityFeed();
    await loadSocialOperationsSnapshot();
    await refreshSocialModuleSummary();
  }

  async function runSocialDraftBulkSchedule() {
    if (!socialDraftBulkScheduleResult) return;
    const connectorIds = document.getElementById("socialDraftBulkConnectorIds");
    const scheduledFor = document.getElementById("socialDraftBulkFor");
    const interval = document.getElementById("socialDraftBulkInterval");
    const filters = socialDraftFilters();
    const payload = {
      filter_source_site_id: filters.source_site_id,
      filter_has_url: filters.has_url,
      filter_search: filters.search,
      filter_page: filters.page,
      filter_limit: filters.limit,
      connector_ids: connectorIds && connectorIds.value
        ? connectorIds.value.split(",").map(function (value) { return value.trim(); }).filter(Boolean)
        : [],
      scheduled_for: scheduledFor && scheduledFor.value ? scheduledFor.value : "",
      interval_minutes: interval && interval.value ? Number(interval.value) : 10,
    };
    const data = await apiPost("social.drafts.bulk_schedule", payload);
    socialDraftBulkScheduleResult.textContent = JSON.stringify(data, null, 2);
    await loadSocialScheduleSummary();
    await loadSocialScheduleQueue();
    await loadSocialActivityFeed();
    await loadSocialOperationsSnapshot();
    await refreshSocialModuleSummary();
  }

  async function downloadSocialDraftsExport() {
    const data = await apiGetWithParams("social.drafts.export", { limit: 20 });
    const filename = data && data.filename ? String(data.filename) : ("social-drafts-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function loadSocialDraftValidation() {
    if (!socialDraftValidationView) return;
    try {
      const data = await apiGet("social.drafts.validation");
      socialDraftValidationView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialDraftValidationView.textContent = "Failed to load social draft validation.";
    }
  }

  async function loadSocialDraftPlan() {
    if (!socialDraftPlanView) return;
    try {
      const data = await apiGetWithParams("social.drafts.plan", { limit: 8 });
      socialDraftPlanView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialDraftPlanView.textContent = "Failed to load social draft plan.";
    }
  }

  async function loadSocialSyncLog() {
    if (!socialSyncLog) return;
    try {
      const data = await apiGetWithParams("social.push.log", socialSyncFilters());
      socialSyncLog.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialSyncLog.textContent = "Failed to load social sync log.";
    }
  }

  async function loadSocialExecutionPreview() {
    if (!socialExecutionPreview) return;
    const provider = document.getElementById("socialExecutionPreviewProvider");
    const connector = document.getElementById("socialExecutionPreviewConnector");
    const draftLimit = document.getElementById("socialExecutionPreviewDraftLimit");
    try {
      const data = await apiGetWithParams("social.execution.preview", {
        provider: provider && provider.value ? provider.value.trim() : "",
        connector_id: connector && connector.value ? connector.value.trim() : "",
        draft_limit: draftLimit && draftLimit.value ? Number(draftLimit.value) : 5,
      });
      socialExecutionPreview.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialExecutionPreview.textContent = "Failed to load social execution preview.";
    }
  }

  async function downloadSocialExecutionPreview() {
    const provider = document.getElementById("socialExecutionPreviewProvider");
    const connector = document.getElementById("socialExecutionPreviewConnector");
    const draftLimit = document.getElementById("socialExecutionPreviewDraftLimit");
    const data = await apiGetWithParams("social.execution.preview.export", {
      provider: provider && provider.value ? provider.value.trim() : "",
      connector_id: connector && connector.value ? connector.value.trim() : "",
      draft_limit: draftLimit && draftLimit.value ? Number(draftLimit.value) : 5,
    });
    const filename = data && data.filename ? String(data.filename) : ("social-execution-preview-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function loadSocialSyncDetail() {
    if (!socialSyncDetail) return;
    const syncId = document.getElementById("socialSyncDetailId");
    if (!syncId || !syncId.value) {
      socialSyncDetail.textContent = "Enter a sync ID to load detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("social.push.detail", { sync_id: syncId.value.trim() });
      socialSyncDetail.textContent = JSON.stringify(data, null, 2);
      if (data && data.item) {
        const retryId = document.getElementById("socialRetryDetailId");
        if (retryId && data.item.retry_id) retryId.value = data.item.retry_id;
      }
    } catch (err) {
      socialSyncDetail.textContent = "Failed to load social sync detail.";
    }
  }

  async function downloadSocialSyncExport() {
    const params = socialSyncFilters();
    const syncId = document.getElementById("socialSyncDetailId");
    if (syncId && syncId.value) {
      params.sync_id = syncId.value.trim();
    }
    const data = await apiGetWithParams("social.push.export", params);
    const filename = data && data.filename ? String(data.filename) : ("social-sync-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  function socialSyncFilters() {
    const status = document.getElementById("socialSyncFilterStatus");
    const source = document.getElementById("socialSyncFilterSource");
    const connector = document.getElementById("socialSyncFilterConnector");
    const search = document.getElementById("socialSyncFilterSearch");
    const page = document.getElementById("socialSyncFilterPage");
    const limit = document.getElementById("socialSyncFilterLimit");
    return {
      status: status && status.value ? status.value.trim() : "",
      source: source && source.value ? source.value.trim() : "",
      connector_id: connector && connector.value ? connector.value.trim() : "",
      search: search && search.value ? search.value.trim() : "",
      page: page && page.value ? Number(page.value) : 1,
      limit: limit && limit.value ? Number(limit.value) : 10,
    };
  }

  async function loadSocialRetryQueue() {
    if (!socialRetryQueue) return;
    try {
      const data = await apiGetWithParams("social.retry.list", socialRetryFilters());
      socialRetryQueue.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialRetryQueue.textContent = "Failed to load social retry queue.";
    }
  }

  async function loadSocialRetryDetail() {
    if (!socialRetryDetail) return;
    const retryId = document.getElementById("socialRetryDetailId");
    if (!retryId || !retryId.value) {
      socialRetryDetail.textContent = "Enter a retry ID to load detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("social.retry.detail", { retry_id: retryId.value.trim() });
      socialRetryDetail.textContent = JSON.stringify(data, null, 2);
      if (data && data.item) {
        const connectorId = document.getElementById("socialDeliveryConnectorId");
        const connectorDetailId = document.getElementById("socialConnectorDetailId");
        if (connectorId) connectorId.value = data.item.connector_id || "";
        if (connectorDetailId) connectorDetailId.value = data.item.connector_id || "";
      }
    } catch (err) {
      socialRetryDetail.textContent = "Failed to load social retry detail.";
    }
  }

  async function downloadSocialRetryExport() {
    const params = socialRetryFilters();
    const retryId = document.getElementById("socialRetryDetailId");
    if (retryId && retryId.value) {
      params.retry_id = retryId.value.trim();
    }
    const data = await apiGetWithParams("social.retry.export", params);
    const filename = data && data.filename ? String(data.filename) : ("social-retry-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function runSocialRetryBulkUpdate() {
    if (!socialRetryBulkResult) return;
    const filters = socialRetryFilters();
    const action = document.getElementById("socialRetryBulkAction");
    const payload = {
      filter_status: filters.status,
      filter_connector_id: filters.connector_id,
      filter_source: filters.source,
      filter_error_code: filters.error_code,
      filter_search: filters.search,
      filter_page: filters.page,
      filter_limit: filters.limit,
      action: action && action.value ? action.value : "reset",
    };
    const data = await apiPost("social.retry.bulk_update", payload);
    socialRetryBulkResult.textContent = JSON.stringify(data, null, 2);
    await loadSocialRetryQueue();
    await loadSocialActivityFeed();
    await loadSocialDeliverySummary();
    await refreshSocialModuleSummary();
  }

  function socialRetryFilters() {
    const status = document.getElementById("socialRetryFilterStatus");
    const connector = document.getElementById("socialRetryFilterConnector");
    const source = document.getElementById("socialRetryFilterSource");
    const errorCode = document.getElementById("socialRetryFilterErrorCode");
    const search = document.getElementById("socialRetryFilterSearch");
    const page = document.getElementById("socialRetryFilterPage");
    const limit = document.getElementById("socialRetryFilterLimit");
    return {
      status: status && status.value ? status.value : "",
      connector_id: connector && connector.value ? connector.value.trim() : "",
      source: source && source.value ? source.value.trim() : "",
      error_code: errorCode && errorCode.value ? errorCode.value.trim() : "",
      search: search && search.value ? search.value.trim() : "",
      page: page && page.value ? Number(page.value) : 1,
      limit: limit && limit.value ? Number(limit.value) : 10,
    };
  }

  async function loadSocialDeliverySummary() {
    if (!socialDeliverySummary) return;
    try {
      const data = await apiGetWithParams("social.delivery.summary", { limit: 20 });
      socialDeliverySummary.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialDeliverySummary.textContent = "Failed to load social delivery summary.";
    }
  }

  async function loadSocialDeliveryConnectorDetail() {
    if (!socialDeliveryConnectorDetail) return;
    const connectorId = document.getElementById("socialDeliveryConnectorId");
    const limit = document.getElementById("socialDeliveryConnectorLimit");
    const connector_id = connectorId && connectorId.value ? connectorId.value.trim() : "";
    if (!connector_id) {
      socialDeliveryConnectorDetail.textContent = "Enter a connector ID to load social delivery detail.";
      return;
    }
    try {
      const data = await apiGetWithParams("social.delivery.connector_detail", {
        connector_id,
        limit: limit && limit.value ? limit.value.trim() : "10",
      });
      socialDeliveryConnectorDetail.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialDeliveryConnectorDetail.textContent = "Failed to load social connector delivery detail.";
    }
  }

  async function downloadSocialDeliveryExport() {
    const connectorId = document.getElementById("socialDeliveryConnectorId");
    const limit = document.getElementById("socialDeliveryConnectorLimit");
    const params = {
      limit: limit && limit.value ? limit.value.trim() : "10",
    };
    if (connectorId && connectorId.value) {
      params.connector_id = connectorId.value.trim();
    }
    const data = await apiGetWithParams("social.delivery.export", params);
    const filename = data && data.filename ? String(data.filename) : ("social-delivery-export-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
  }

  async function loadSocialDeliveryWatch() {
    if (!socialDeliveryWatchView) return;
    try {
      const data = await apiGet("social.delivery.watch.summary");
      socialDeliveryWatchView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      socialDeliveryWatchView.textContent = "Failed to load social delivery watch.";
    }
  }

  async function downloadSocialDeliveryWatchExport() {
    const data = await apiGet("social.delivery.watch.export");
    const filename = data && data.filename ? String(data.filename) : ("social-delivery-watch-" + Date.now() + ".json");
    downloadJsonFile(filename, data);
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

  async function loadWebopsTypes() {
    if (!webopsTypes) return;
    try {
      const data = await apiGet("webops.types.list");
      webopsTypes.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsTypes.textContent = "Failed to load WebOps types.";
    }
  }

  async function loadWebopsIncidents() {
    if (!webopsIncidents) return;
    try {
      const data = await apiGet("webops.incidents.list");
      webopsIncidents.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsIncidents.textContent = "Failed to load WebOps incidents.";
    }
  }

  async function loadWebopsActionsQueue() {
    if (!webopsActionsQueue) return;
    try {
      const data = await apiGet("webops.actions.list");
      webopsActionsQueue.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsActionsQueue.textContent = "Failed to load WebOps actions.";
    }
  }

  async function loadWebopsActionsLog() {
    if (!webopsActionsLog) return;
    try {
      const data = await apiGet("webops.actions.log");
      webopsActionsLog.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsActionsLog.textContent = "Failed to load WebOps action log.";
    }
  }

  async function loadWebopsPosture() {
    if (!webopsPostureView) return;
    try {
      const data = await apiGet("webops.posture.snapshot");
      webopsPostureView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsPostureView.textContent = "Failed to load WebOps posture snapshot.";
    }
  }

  async function loadWebopsOperationsSnapshot() {
    if (!webopsOperationsSnapshotView) return;
    const limit = document.getElementById("webopsOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("webops.operations.snapshot", {
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      webopsOperationsSnapshotView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsOperationsSnapshotView.textContent = "Failed to load WebOps operations snapshot.";
    }
  }

  async function loadWebopsOperationsHistory() {
    if (!webopsOperationsHistoryView) return;
    const limit = document.getElementById("webopsOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("webops.operations.history", {
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      webopsOperationsHistoryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsOperationsHistoryView.textContent = "Failed to load WebOps operations history.";
    }
  }

  async function loadWebopsOperationsHistorySummary() {
    if (!webopsOperationsHistorySummaryView) return;
    const limit = document.getElementById("webopsOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("webops.operations.history_summary", {
        limit: limit && limit.value ? Number(limit.value) : 20,
      });
      webopsOperationsHistorySummaryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsOperationsHistorySummaryView.textContent = "Failed to load WebOps operations history summary.";
    }
  }

  async function loadWebopsOperationsIssuesSummary() {
    if (!webopsOperationsIssuesSummaryView) return;
    const limit = document.getElementById("webopsOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("webops.operations.issues_summary", {
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      webopsOperationsIssuesSummaryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      webopsOperationsIssuesSummaryView.textContent = "Failed to load WebOps operations issues summary.";
    }
  }

  async function downloadWebopsOperationsSnapshot() {
    const limit = document.getElementById("webopsOperationsSnapshotLimit");
    const data = await apiGetWithParams("webops.operations.export", {
      limit: limit && limit.value ? Number(limit.value) : 10,
    });
    const filename = data && data.filename ? String(data.filename) : ("webops-operations-snapshot-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (webopsOperationsSnapshotView) {
      webopsOperationsSnapshotView.textContent = JSON.stringify(data, null, 2);
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
      const data = await apiGetWithParams("seo.projects.list", {
        status: seoProjectFilterStatus && seoProjectFilterStatus.value ? seoProjectFilterStatus.value : "",
        search: seoProjectFilterSearch && seoProjectFilterSearch.value ? seoProjectFilterSearch.value.trim() : "",
        page: seoProjectFilterPage && seoProjectFilterPage.value ? seoProjectFilterPage.value : 1,
        limit: seoProjectFilterLimit && seoProjectFilterLimit.value ? seoProjectFilterLimit.value : 10,
      });
      seoProjects.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoProjects.textContent = "Failed to load SEO projects.";
    }
  }

  async function loadSeoProjectDetail() {
    if (!seoProjectDetail) return;
    try {
      const detailId = document.getElementById("seoProjectDetailId");
      const projectId = detailId && detailId.value ? detailId.value.trim() : "";
      const data = await apiGetWithParams("seo.projects.detail", { project_id: projectId });
      seoProjectDetail.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoProjectDetail.textContent = "Failed to load SEO project detail.";
    }
  }

  async function downloadSeoProjectsExport() {
    const detailId = document.getElementById("seoProjectDetailId");
    const projectId = detailId && detailId.value ? detailId.value.trim() : "";
    const data = await apiGetWithParams("seo.projects.export", {
      status: seoProjectFilterStatus && seoProjectFilterStatus.value ? seoProjectFilterStatus.value : "",
      search: seoProjectFilterSearch && seoProjectFilterSearch.value ? seoProjectFilterSearch.value.trim() : "",
      page: seoProjectFilterPage && seoProjectFilterPage.value ? seoProjectFilterPage.value : 1,
      limit: seoProjectFilterLimit && seoProjectFilterLimit.value ? seoProjectFilterLimit.value : 10,
      project_id: projectId,
      include_detail: projectId ? 1 : "",
    });
    downloadJsonFile(data.filename || "seo-projects-export.json", data.export || data);
    if (seoResult) {
      seoResult.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadSeoAudits() {
    if (!seoAudits) return;
    try {
      const data = await apiGetWithParams("seo.audits.list", {
        project_id: seoAuditFilterProjectId && seoAuditFilterProjectId.value ? seoAuditFilterProjectId.value.trim() : "",
        source: seoAuditFilterSource && seoAuditFilterSource.value ? seoAuditFilterSource.value.trim() : "",
        search: seoAuditFilterSearch && seoAuditFilterSearch.value ? seoAuditFilterSearch.value.trim() : "",
        min_score: seoAuditFilterMinScore && seoAuditFilterMinScore.value ? seoAuditFilterMinScore.value : "",
        max_score: seoAuditFilterMaxScore && seoAuditFilterMaxScore.value ? seoAuditFilterMaxScore.value : "",
        page: seoAuditFilterPage && seoAuditFilterPage.value ? seoAuditFilterPage.value : 1,
        limit: seoAuditFilterLimit && seoAuditFilterLimit.value ? seoAuditFilterLimit.value : 10,
      });
      seoAudits.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoAudits.textContent = "Failed to load SEO audits.";
    }
  }

  async function loadSeoAuditDetail() {
    if (!seoAuditDetail) return;
    try {
      const detailId = document.getElementById("seoAuditDetailId");
      const auditId = detailId && detailId.value ? detailId.value.trim() : "";
      const data = await apiGetWithParams("seo.audits.detail", { audit_id: auditId });
      seoAuditDetail.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoAuditDetail.textContent = "Failed to load SEO audit detail.";
    }
  }

  async function downloadSeoAuditsExport() {
    const detailId = document.getElementById("seoAuditDetailId");
    const auditId = detailId && detailId.value ? detailId.value.trim() : "";
    const data = await apiGetWithParams("seo.audits.export", {
      project_id: seoAuditFilterProjectId && seoAuditFilterProjectId.value ? seoAuditFilterProjectId.value.trim() : "",
      source: seoAuditFilterSource && seoAuditFilterSource.value ? seoAuditFilterSource.value.trim() : "",
      search: seoAuditFilterSearch && seoAuditFilterSearch.value ? seoAuditFilterSearch.value.trim() : "",
      min_score: seoAuditFilterMinScore && seoAuditFilterMinScore.value ? seoAuditFilterMinScore.value : "",
      max_score: seoAuditFilterMaxScore && seoAuditFilterMaxScore.value ? seoAuditFilterMaxScore.value : "",
      page: seoAuditFilterPage && seoAuditFilterPage.value ? seoAuditFilterPage.value : 1,
      limit: seoAuditFilterLimit && seoAuditFilterLimit.value ? seoAuditFilterLimit.value : 10,
      audit_id: auditId,
      include_detail: auditId ? 1 : "",
    });
    downloadJsonFile(data.filename || "seo-audits-export.json", data.export || data);
    if (seoResult) {
      seoResult.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadSeoIssuesSummary() {
    if (!seoIssuesSummary) return;
    try {
      const data = await apiGet("seo.issues.summary");
      seoIssuesSummary.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoIssuesSummary.textContent = "Failed to load SEO issue summary.";
    }
  }

  async function loadSeoHistorySummary() {
    if (!seoHistorySummary) return;
    try {
      const data = await apiGet("seo.history.summary");
      seoHistorySummary.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoHistorySummary.textContent = "Failed to load SEO history summary.";
    }
  }

  async function loadSeoExtensionSummary() {
    if (!seoExtensionSummary) return;
    try {
      const data = await apiGet("seo.extension.events.summary");
      seoExtensionSummary.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoExtensionSummary.textContent = "Failed to load SEO extension summary.";
    }
  }

  async function loadSeoExtensionEvents() {
    if (!seoExtensionEvents) return;
    try {
      const data = await apiGetWithParams("seo.extension.events.list", {
        project_id: seoExtensionEventFilterProjectId && seoExtensionEventFilterProjectId.value ? seoExtensionEventFilterProjectId.value.trim() : "",
        session_id: seoExtensionEventFilterSessionId && seoExtensionEventFilterSessionId.value ? seoExtensionEventFilterSessionId.value.trim() : "",
        search: seoExtensionEventFilterSearch && seoExtensionEventFilterSearch.value ? seoExtensionEventFilterSearch.value.trim() : "",
        min_score: seoExtensionEventFilterMinScore && seoExtensionEventFilterMinScore.value ? seoExtensionEventFilterMinScore.value : "",
        max_score: seoExtensionEventFilterMaxScore && seoExtensionEventFilterMaxScore.value ? seoExtensionEventFilterMaxScore.value : "",
        page: seoExtensionEventFilterPage && seoExtensionEventFilterPage.value ? seoExtensionEventFilterPage.value : 1,
        limit: seoExtensionEventFilterLimit && seoExtensionEventFilterLimit.value ? seoExtensionEventFilterLimit.value : 10,
      });
      seoExtensionEvents.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoExtensionEvents.textContent = "Failed to load extension events.";
    }
  }

  async function loadSeoExtensionEventDetail() {
    if (!seoExtensionEventDetail) return;
    try {
      const detailId = document.getElementById("seoExtensionEventDetailId");
      const eventId = detailId && detailId.value ? detailId.value.trim() : "";
      const data = await apiGetWithParams("seo.extension.events.detail", { event_id: eventId });
      seoExtensionEventDetail.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoExtensionEventDetail.textContent = "Failed to load SEO extension event detail.";
    }
  }

  async function downloadSeoExtensionEventsExport() {
    const detailId = document.getElementById("seoExtensionEventDetailId");
    const eventId = detailId && detailId.value ? detailId.value.trim() : "";
    const data = await apiGetWithParams("seo.extension.events.export", {
      project_id: seoExtensionEventFilterProjectId && seoExtensionEventFilterProjectId.value ? seoExtensionEventFilterProjectId.value.trim() : "",
      session_id: seoExtensionEventFilterSessionId && seoExtensionEventFilterSessionId.value ? seoExtensionEventFilterSessionId.value.trim() : "",
      search: seoExtensionEventFilterSearch && seoExtensionEventFilterSearch.value ? seoExtensionEventFilterSearch.value.trim() : "",
      min_score: seoExtensionEventFilterMinScore && seoExtensionEventFilterMinScore.value ? seoExtensionEventFilterMinScore.value : "",
      max_score: seoExtensionEventFilterMaxScore && seoExtensionEventFilterMaxScore.value ? seoExtensionEventFilterMaxScore.value : "",
      page: seoExtensionEventFilterPage && seoExtensionEventFilterPage.value ? seoExtensionEventFilterPage.value : 1,
      limit: seoExtensionEventFilterLimit && seoExtensionEventFilterLimit.value ? seoExtensionEventFilterLimit.value : 10,
      event_id: eventId,
      include_detail: eventId ? 1 : "",
    });
    downloadJsonFile(data.filename || "seo-extension-events-export.json", data.export || data);
    if (seoResult) {
      seoResult.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadSeoExtensionSessions() {
    if (!seoExtensionSessions) return;
    try {
      const data = await apiGetWithParams("seo.extension.sessions.list", {
        project_id: seoExtensionSessionFilterProjectId && seoExtensionSessionFilterProjectId.value ? seoExtensionSessionFilterProjectId.value.trim() : "",
        status: seoExtensionSessionFilterStatus && seoExtensionSessionFilterStatus.value ? seoExtensionSessionFilterStatus.value : "",
        search: seoExtensionSessionFilterSearch && seoExtensionSessionFilterSearch.value ? seoExtensionSessionFilterSearch.value.trim() : "",
        page: seoExtensionSessionFilterPage && seoExtensionSessionFilterPage.value ? seoExtensionSessionFilterPage.value : 1,
        limit: seoExtensionSessionFilterLimit && seoExtensionSessionFilterLimit.value ? seoExtensionSessionFilterLimit.value : 10,
      });
      seoExtensionSessions.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoExtensionSessions.textContent = "Failed to load SEO extension sessions.";
    }
  }

  async function loadSeoExtensionSessionDetail() {
    if (!seoExtensionSessionDetail) return;
    try {
      const detailId = document.getElementById("seoExtensionSessionDetailId");
      const sessionId = detailId && detailId.value ? detailId.value.trim() : "";
      const data = await apiGetWithParams("seo.extension.sessions.detail", { session_id: sessionId });
      seoExtensionSessionDetail.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoExtensionSessionDetail.textContent = "Failed to load SEO extension session detail.";
    }
  }

  async function downloadSeoExtensionSessionsExport() {
    const detailId = document.getElementById("seoExtensionSessionDetailId");
    const sessionId = detailId && detailId.value ? detailId.value.trim() : "";
    const data = await apiGetWithParams("seo.extension.sessions.export", {
      project_id: seoExtensionSessionFilterProjectId && seoExtensionSessionFilterProjectId.value ? seoExtensionSessionFilterProjectId.value.trim() : "",
      status: seoExtensionSessionFilterStatus && seoExtensionSessionFilterStatus.value ? seoExtensionSessionFilterStatus.value : "",
      search: seoExtensionSessionFilterSearch && seoExtensionSessionFilterSearch.value ? seoExtensionSessionFilterSearch.value.trim() : "",
      page: seoExtensionSessionFilterPage && seoExtensionSessionFilterPage.value ? seoExtensionSessionFilterPage.value : 1,
      limit: seoExtensionSessionFilterLimit && seoExtensionSessionFilterLimit.value ? seoExtensionSessionFilterLimit.value : 10,
      session_id: sessionId,
      include_detail: sessionId ? 1 : "",
    });
    downloadJsonFile(data.filename || "seo-extension-sessions-export.json", data.export || data);
    if (seoResult) {
      seoResult.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadSeoProjectSnapshot() {
    if (!seoProjectSnapshot) return;
    try {
      const projectId = document.getElementById("seoProjectId");
      const data = await apiGetWithParams("seo.project.snapshot", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
      });
      seoProjectSnapshot.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoProjectSnapshot.textContent = "Failed to load SEO project snapshot.";
    }
  }

  async function loadSeoActionPlan() {
    if (!seoActionPlanView) return;
    try {
      const projectId = document.getElementById("seoProjectId");
      const data = await apiGetWithParams("seo.actions.plan", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
      });
      seoActionPlanView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoActionPlanView.textContent = "Failed to load SEO action plan.";
    }
  }

  async function loadSeoUrlHistory() {
    if (!seoUrlHistoryView) return;
    try {
      const projectId = document.getElementById("seoProjectId");
      const urlFilter = document.getElementById("seoHistoryUrlFilter");
      const data = await apiGetWithParams("seo.url.history", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
        url: urlFilter && urlFilter.value ? urlFilter.value.trim() : "",
      });
      seoUrlHistoryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoUrlHistoryView.textContent = "Failed to load SEO URL history.";
    }
  }

  async function loadSeoOpportunities() {
    if (!seoOpportunitiesView) return;
    try {
      const projectId = document.getElementById("seoProjectId");
      const data = await apiGetWithParams("seo.opportunities.summary", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
      });
      seoOpportunitiesView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoOpportunitiesView.textContent = "Failed to load SEO opportunities.";
    }
  }

  async function loadSeoRegressions() {
    if (!seoRegressionView) return;
    try {
      const data = await apiGet("seo.regressions.summary");
      seoRegressionView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoRegressionView.textContent = "Failed to load SEO regression watch.";
    }
  }

  async function loadSeoCompare() {
    if (!seoCompareView) return;
    try {
      const projectId = document.getElementById("seoProjectId");
      const data = await apiGetWithParams("seo.compare.latest", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
      });
      seoCompareView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoCompareView.textContent = "Failed to load SEO compare view.";
    }
  }

  async function loadSeoOperationsSnapshot() {
    if (!seoOperationsSnapshotView) return;
    const limit = document.getElementById("seoOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("seo.operations.snapshot", {
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      seoOperationsSnapshotView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoOperationsSnapshotView.textContent = "Failed to load SEO operations snapshot.";
    }
  }

  async function loadSeoOperationsHistory() {
    if (!seoOperationsHistoryView) return;
    const limit = document.getElementById("seoOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("seo.operations.history", {
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      seoOperationsHistoryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoOperationsHistoryView.textContent = "Failed to load SEO operations history.";
    }
  }

  async function loadSeoOperationsHistorySummary() {
    if (!seoOperationsHistorySummaryView) return;
    const limit = document.getElementById("seoOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("seo.operations.history_summary", {
        limit: limit && limit.value ? Number(limit.value) : 20,
      });
      seoOperationsHistorySummaryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoOperationsHistorySummaryView.textContent = "Failed to load SEO operations history summary.";
    }
  }

  async function loadSeoOperationsIssuesSummary() {
    if (!seoOperationsIssuesSummaryView) return;
    const limit = document.getElementById("seoOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("seo.operations.issues_summary", {
        limit: limit && limit.value ? Number(limit.value) : 10,
      });
      seoOperationsIssuesSummaryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      seoOperationsIssuesSummaryView.textContent = "Failed to load SEO operations issues summary.";
    }
  }

  async function loadLaunchOperationsSnapshot() {
    if (!launchOperationsSnapshotView) return;
    const limit = document.getElementById("launchOperationsSnapshotLimit");
    const freshness = document.getElementById("launchOperationsFreshnessMinutes");
    try {
      const data = await apiGetWithParams("launch.operations.snapshot", {
        limit: limit && limit.value ? Number(limit.value) : 10,
        freshness_minutes: freshness && freshness.value ? Number(freshness.value) : 30,
      });
      launchOperationsSnapshotView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      launchOperationsSnapshotView.textContent = "Failed to load launch operations snapshot.";
    }
  }

  async function loadLaunchOperationsHistory() {
    if (!launchOperationsHistoryView) return;
    const limit = document.getElementById("launchOperationsSnapshotLimit");
    const source = document.getElementById("launchOperationsHistorySourceFilter");
    const launchState = document.getElementById("launchOperationsHistoryStateFilter");
    const search = document.getElementById("launchOperationsHistorySearch");
    const page = document.getElementById("launchOperationsHistoryPage");
    try {
      const data = await apiGetWithParams("launch.operations.history", {
        page: page && page.value ? Number(page.value) : 1,
        limit: limit && limit.value ? Number(limit.value) : 10,
        source: source && source.value ? source.value.trim() : "",
        launch_state: launchState && launchState.value ? launchState.value : "",
        search: search && search.value ? search.value.trim() : "",
      });
      launchOperationsHistoryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      launchOperationsHistoryView.textContent = "Failed to load launch operations history.";
    }
  }

  async function loadLaunchOperationsHistorySummary() {
    if (!launchOperationsHistorySummaryView) return;
    const limit = document.getElementById("launchOperationsSnapshotLimit");
    try {
      const data = await apiGetWithParams("launch.operations.history_summary", {
        limit: limit && limit.value ? Number(limit.value) : 20,
      });
      launchOperationsHistorySummaryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      launchOperationsHistorySummaryView.textContent = "Failed to load launch operations history summary.";
    }
  }

  async function loadLaunchOperationsHistoryDetail() {
    if (!launchOperationsHistoryDetailView) return;
    const snapshotId = document.getElementById("launchOperationsHistoryDetailId");
    const id = snapshotId && snapshotId.value ? snapshotId.value.trim() : "";
    if (!id) {
      launchOperationsHistoryDetailView.textContent = "Enter a launch history snapshot ID.";
      return;
    }
    try {
      const data = await apiGetWithParams("launch.operations.history_detail", {
        snapshot_id: id,
      });
      launchOperationsHistoryDetailView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      launchOperationsHistoryDetailView.textContent = "Failed to load launch operations history detail.";
    }
  }

  async function downloadLaunchOperationsHistoryDetail() {
    const snapshotId = document.getElementById("launchOperationsHistoryDetailId");
    const id = snapshotId && snapshotId.value ? snapshotId.value.trim() : "";
    if (!id) {
      if (launchOperationsHistoryDetailView) {
        launchOperationsHistoryDetailView.textContent = "Enter a launch history snapshot ID.";
      }
      return;
    }
    const data = await apiGetWithParams("launch.operations.history_detail_export", {
      snapshot_id: id,
    });
    const filename = data && data.filename ? String(data.filename) : ("launch-operations-history-detail-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (launchOperationsHistoryDetailView) {
      launchOperationsHistoryDetailView.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadLaunchOperationsLatestCompare() {
    if (!launchOperationsLatestCompareView) return;
    try {
      const data = await apiGet("launch.operations.latest_compare");
      launchOperationsLatestCompareView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      launchOperationsLatestCompareView.textContent = "Failed to load launch operations latest compare.";
    }
  }

  async function downloadLaunchOperationsLatestCompare() {
    const data = await apiGet("launch.operations.latest_compare_export");
    const filename = data && data.filename ? String(data.filename) : ("launch-operations-latest-compare-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (launchOperationsLatestCompareView) {
      launchOperationsLatestCompareView.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function downloadLaunchOperationsIssues() {
    const limit = document.getElementById("launchOperationsSnapshotLimit");
    const freshness = document.getElementById("launchOperationsFreshnessMinutes");
    const data = await apiGetWithParams("launch.operations.issues_export", {
      limit: limit && limit.value ? Number(limit.value) : 10,
      freshness_minutes: freshness && freshness.value ? Number(freshness.value) : 30,
    });
    const filename = data && data.filename ? String(data.filename) : ("launch-operations-issues-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (launchOperationsIssuesSummaryView) {
      launchOperationsIssuesSummaryView.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function downloadLaunchOperationsReviewBundle() {
    const limit = document.getElementById("launchOperationsSnapshotLimit");
    const freshness = document.getElementById("launchOperationsFreshnessMinutes");
    const data = await apiGetWithParams("launch.operations.review_bundle", {
      limit: limit && limit.value ? Number(limit.value) : 10,
      freshness_minutes: freshness && freshness.value ? Number(freshness.value) : 30,
    });
    const filename = data && data.filename ? String(data.filename) : ("launch-operations-review-bundle-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (launchOperationsSnapshotView) {
      launchOperationsSnapshotView.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function loadLaunchOperationsIssuesSummary() {
    if (!launchOperationsIssuesSummaryView) return;
    const limit = document.getElementById("launchOperationsSnapshotLimit");
    const freshness = document.getElementById("launchOperationsFreshnessMinutes");
    try {
      const data = await apiGetWithParams("launch.operations.issues_summary", {
        limit: limit && limit.value ? Number(limit.value) : 10,
        freshness_minutes: freshness && freshness.value ? Number(freshness.value) : 30,
      });
      launchOperationsIssuesSummaryView.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      launchOperationsIssuesSummaryView.textContent = "Failed to load launch operations issues summary.";
    }
  }

  async function downloadLaunchOperationsSnapshot() {
    const limit = document.getElementById("launchOperationsSnapshotLimit");
    const freshness = document.getElementById("launchOperationsFreshnessMinutes");
    const data = await apiGetWithParams("launch.operations.export", {
      limit: limit && limit.value ? Number(limit.value) : 10,
      freshness_minutes: freshness && freshness.value ? Number(freshness.value) : 30,
    });
    const filename = data && data.filename ? String(data.filename) : ("launch-operations-snapshot-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (launchOperationsSnapshotView) {
      launchOperationsSnapshotView.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function downloadLaunchOperationsHistory() {
    const limit = document.getElementById("launchOperationsSnapshotLimit");
    const source = document.getElementById("launchOperationsHistorySourceFilter");
    const launchState = document.getElementById("launchOperationsHistoryStateFilter");
    const search = document.getElementById("launchOperationsHistorySearch");
    const page = document.getElementById("launchOperationsHistoryPage");
    const data = await apiGetWithParams("launch.operations.history_export", {
      page: page && page.value ? Number(page.value) : 1,
      limit: limit && limit.value ? Number(limit.value) : 10,
      source: source && source.value ? source.value.trim() : "",
      launch_state: launchState && launchState.value ? launchState.value : "",
      search: search && search.value ? search.value.trim() : "",
    });
    const filename = data && data.filename ? String(data.filename) : ("launch-operations-history-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (launchOperationsHistoryView) {
      launchOperationsHistoryView.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function downloadLaunchOperationsHistorySummary() {
    const limit = document.getElementById("launchOperationsSnapshotLimit");
    const data = await apiGetWithParams("launch.operations.history_summary_export", {
      limit: limit && limit.value ? Number(limit.value) : 20,
    });
    const filename = data && data.filename ? String(data.filename) : ("launch-operations-history-summary-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (launchOperationsHistorySummaryView) {
      launchOperationsHistorySummaryView.textContent = JSON.stringify(data, null, 2);
    }
  }

  async function downloadSeoOperationsSnapshot() {
    const limit = document.getElementById("seoOperationsSnapshotLimit");
    const data = await apiGetWithParams("seo.operations.export", {
      limit: limit && limit.value ? Number(limit.value) : 10,
    });
    const filename = data && data.filename ? String(data.filename) : ("seo-operations-snapshot-" + Date.now() + ".json");
    downloadJsonFile(filename, data.export || data);
    if (seoOperationsSnapshotView) {
      seoOperationsSnapshotView.textContent = JSON.stringify(data, null, 2);
    }
  }

  (async function initCrm() {
    await loadLeadsInventory();
    await loadLeadsList();
    await loadLeadsPushPlan();
    await loadLeadsQuality();
    await loadLeadsDeliveryHistory();
    await loadLeadsReviewQueue();
    await loadCrmConnectors();
    await loadSocialPlatforms();
    await loadSocialPlatformCoverage();
    await loadSocialCapabilitiesSummary();
    await loadSocialWatch();
    await loadSocialOperationsSnapshot();
    await loadSocialOperationsHistory();
    await loadSocialOperationsHistorySummary();
    await loadSocialOperationsLatestCompare();
    await loadSocialOperationsIssuesSummary();
    await loadSocialDraftsPreview();
    await loadSocialDraftList();
    await loadSocialDraftRecommendations();
    await loadSocialDraftValidation();
    await loadSocialDraftPlan();
    await loadSocialDeliverySummary();
    await loadSocialDeliveryWatch();
    await loadSocialConnectorRuleAudit();
    await loadCrmDeliverySummary();
    await loadCrmDeliveryWatch();
    await loadCrmSyncLog();
    await loadRetryQueue();
    await loadCrmSmtpSummary();
    await loadCrmSmtpWatch();
    await loadCrmEmailTemplateSites();
    await loadCrmEmailTemplatesSummary();
    await loadCrmEmailTemplateTestLog();
    await loadCrmOperationsSnapshot();
    await loadCrmOperationsHistory();
    await loadCrmOperationsHistorySummary();
    await loadCrmOperationsLatestCompare();
    await loadCrmOperationsIssuesSummary();
    await loadSocialConnectors();
    await loadSocialScheduleSummary();
    await loadSocialScheduleQueue();
    await loadSocialActivityFeed();
    await loadSocialInboxSummary();
    await loadSocialInboxWorkload();
    await loadSocialInboxWatch();
    await loadSocialInboxThreads();
    await loadSocialSyncLog();
    await loadSocialRetryQueue();
    await loadWebopsTypes();
    await loadWebopsMonitors();
    await loadWebopsIncidents();
    await loadWebopsActionsQueue();
    await loadWebopsActionsLog();
    await loadWebopsPosture();
    await loadWebopsOperationsSnapshot();
    await loadWebopsOperationsHistory();
    await loadWebopsOperationsHistorySummary();
    await loadWebopsOperationsIssuesSummary();
    await loadWebopsLog();
    await loadWebopsRetryQueue();
    await loadSeoProjects();
    await loadSeoProjectDetail();
    await loadSeoIssuesSummary();
    await loadSeoHistorySummary();
    await loadSeoExtensionSummary();
    await loadSeoAudits();
    await loadSeoAuditDetail();
    await loadSeoExtensionEvents();
    await loadSeoExtensionEventDetail();
    await loadSeoExtensionSessions();
    await loadSeoExtensionSessionDetail();
    await loadSeoProjectSnapshot();
    await loadSeoActionPlan();
    await loadSeoOpportunities();
    await loadSeoRegressions();
    await loadSeoUrlHistory();
    await loadSeoCompare();
    await loadSeoOperationsSnapshot();
    await loadSeoOperationsHistory();
    await loadSeoOperationsHistorySummary();
    await loadSeoOperationsIssuesSummary();
    await loadLaunchOperationsSnapshot();
    await loadLaunchOperationsHistory();
    await loadLaunchOperationsHistorySummary();
    await loadLaunchOperationsLatestCompare();
    await loadLaunchOperationsIssuesSummary();
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

  if (refreshCrmConnectorsBtn) {
    refreshCrmConnectorsBtn.addEventListener("click", async function () {
      await loadCrmConnectors();
    });
  }

  if (downloadCrmConnectorsExportBtn) {
    downloadCrmConnectorsExportBtn.addEventListener("click", async function () {
      await downloadCrmConnectorsExport();
    });
  }

  if (runCrmConnectorBulkUpdateBtn) {
    runCrmConnectorBulkUpdateBtn.addEventListener("click", async function () {
      await runCrmConnectorBulkUpdate();
    });
  }

  if (refreshCrmConnectorDetailBtn) {
    refreshCrmConnectorDetailBtn.addEventListener("click", async function () {
      await loadCrmConnectorDetail();
    });
  }

  if (runCrmSyncBtn) {
    runCrmSyncBtn.addEventListener("click", async function () {
      const result = await apiPost("crm.push.sync", {});
      if (crmSyncResult) {
        crmSyncResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadCrmDeliverySummary();
      await loadCrmDeliveryWatch();
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
      await loadCrmDeliverySummary();
      await loadCrmDeliveryWatch();
      await loadRetryQueue();
      const crmData = await apiGet("crm.summary");
      const crmPanel = document.getElementById("modCrm");
      if (crmPanel) {
        crmPanel.textContent = JSON.stringify(crmData, null, 2);
      }
    });
  }

  if (refreshCrmDeliverySummaryBtn) {
    refreshCrmDeliverySummaryBtn.addEventListener("click", async function () {
      await loadCrmDeliverySummary();
    });
  }

  if (refreshCrmDeliveryDetailBtn) {
    refreshCrmDeliveryDetailBtn.addEventListener("click", async function () {
      await loadCrmDeliveryConnectorDetail();
    });
  }

  if (downloadCrmDeliveryExportBtn) {
    downloadCrmDeliveryExportBtn.addEventListener("click", async function () {
      await downloadCrmDeliveryExport();
    });
  }

  if (refreshCrmDeliveryWatchBtn) {
    refreshCrmDeliveryWatchBtn.addEventListener("click", async function () {
      await loadCrmDeliveryWatch();
    });
  }

  if (refreshCrmSyncLogBtn) {
    refreshCrmSyncLogBtn.addEventListener("click", async function () {
      await loadCrmSyncLog();
    });
  }

  if (refreshCrmRetryQueueBtn) {
    refreshCrmRetryQueueBtn.addEventListener("click", async function () {
      await loadRetryQueue();
    });
  }

  if (loadCrmRetryDetailBtn) {
    loadCrmRetryDetailBtn.addEventListener("click", async function () {
      await loadCrmRetryDetail();
    });
  }

  const loadCrmSmtpDetailBtn = document.getElementById("loadCrmSmtpDetailBtn");
  if (loadCrmSmtpDetailBtn) {
    loadCrmSmtpDetailBtn.addEventListener("click", async function () {
      await loadCrmSmtpDetail();
    });
  }

  if (downloadCrmSmtpExportBtn) {
    downloadCrmSmtpExportBtn.addEventListener("click", async function () {
      await downloadCrmSmtpExport();
    });
  }

  if (downloadCrmSmtpWatchExportBtn) {
    downloadCrmSmtpWatchExportBtn.addEventListener("click", async function () {
      await downloadCrmSmtpWatchExport();
    });
  }

  if (downloadCrmRetryExportBtn) {
    downloadCrmRetryExportBtn.addEventListener("click", async function () {
      await downloadCrmRetryExport();
    });
  }

  if (loadCrmSyncDetailBtn) {
    loadCrmSyncDetailBtn.addEventListener("click", async function () {
      await loadCrmSyncDetail();
    });
  }

  if (downloadCrmSyncExportBtn) {
    downloadCrmSyncExportBtn.addEventListener("click", async function () {
      await downloadCrmSyncExport();
    });
  }

  if (runCrmDeliveryWatchBtn) {
    runCrmDeliveryWatchBtn.addEventListener("click", async function () {
      const result = await apiPost("crm.delivery.watch.run", {});
      if (crmDeliveryWatchView) {
        crmDeliveryWatchView.textContent = JSON.stringify(result, null, 2);
      }
      await loadNotifications();
    });
  }

  if (refreshCrmSmtpBtn) {
    refreshCrmSmtpBtn.addEventListener("click", async function () {
      await loadCrmSmtpSummary();
    });
  }

  if (refreshCrmSmtpWatchBtn) {
    refreshCrmSmtpWatchBtn.addEventListener("click", async function () {
      await loadCrmSmtpWatch();
    });
  }

  if (runCrmSmtpWatchBtn) {
    runCrmSmtpWatchBtn.addEventListener("click", async function () {
      const result = await apiPost("crm.smtp.watch.run", {});
      if (crmSmtpResult) {
        crmSmtpResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadCrmSmtpSummary();
      await loadCrmSmtpWatch();
      const crmData = await apiGet("crm.summary");
      const crmPanel = document.getElementById("modCrm");
      if (crmPanel) {
        crmPanel.textContent = JSON.stringify(crmData, null, 2);
      }
    });
  }

  if (crmSmtpForm) {
    crmSmtpForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const siteIdInput = function () {
      const el = document.getElementById("crmSmtpSiteId");
      return el && el.value ? el.value.trim() : "";
    };
    const toEmailInput = function () {
      const el = document.getElementById("crmSmtpToEmail");
      return el && el.value ? el.value.trim() : "";
    };
    const runCrmSmtpAction = async function (action, payload) {
      const result = await apiPost(action, payload);
      if (crmSmtpResult) {
        crmSmtpResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadCrmSmtpSummary();
      await loadCrmSmtpWatch();
      const crmData = await apiGet("crm.summary");
      const crmPanel = document.getElementById("modCrm");
      if (crmPanel) {
        crmPanel.textContent = JSON.stringify(crmData, null, 2);
      }
    };
    const probeCrmSmtpBtn = document.getElementById("probeCrmSmtpBtn");
    if (probeCrmSmtpBtn) {
      probeCrmSmtpBtn.addEventListener("click", async function () {
        const site_id = siteIdInput();
        if (!site_id) {
          if (crmSmtpResult) crmSmtpResult.textContent = "Enter Site ID first.";
          return;
        }
        await runCrmSmtpAction("crm.smtp.probe", { site_id });
      });
    }
    const sendCrmSmtpTestBtn = document.getElementById("sendCrmSmtpTestBtn");
    if (sendCrmSmtpTestBtn) {
      sendCrmSmtpTestBtn.addEventListener("click", async function () {
        const site_id = siteIdInput();
        const to_email = toEmailInput();
        if (!site_id || !to_email) {
          if (crmSmtpResult) crmSmtpResult.textContent = "Enter Site ID and test email recipient.";
          return;
        }
        await runCrmSmtpAction("crm.smtp.send_test", { site_id, to_email });
      });
    }
    const confirmCrmSmtpYesBtn = document.getElementById("confirmCrmSmtpYesBtn");
    if (confirmCrmSmtpYesBtn) {
      confirmCrmSmtpYesBtn.addEventListener("click", async function () {
        const site_id = siteIdInput();
        if (!site_id) {
          if (crmSmtpResult) crmSmtpResult.textContent = "Enter Site ID first.";
          return;
        }
        await runCrmSmtpAction("crm.smtp.confirm", { site_id, to_email: toEmailInput(), received: 1 });
      });
    }
    const confirmCrmSmtpNoBtn = document.getElementById("confirmCrmSmtpNoBtn");
    if (confirmCrmSmtpNoBtn) {
      confirmCrmSmtpNoBtn.addEventListener("click", async function () {
        const site_id = siteIdInput();
        if (!site_id) {
          if (crmSmtpResult) crmSmtpResult.textContent = "Enter Site ID first.";
          return;
        }
        await runCrmSmtpAction("crm.smtp.confirm", { site_id, to_email: toEmailInput(), received: 0 });
      });
    }
  }

  const fillCrmEmailTemplateFields = function (data) {
    if (!data || !data.templates || !data.templates.templates) return;
    const templateKey = document.getElementById("crmEmailTemplateKey");
    const subject = document.getElementById("crmEmailTemplateSubject");
    const body = document.getElementById("crmEmailTemplateBody");
    const key = templateKey && templateKey.value ? templateKey.value : "reset";
    const template = data.templates.templates[key];
    if (!template) return;
    if (subject) subject.value = template.subject || "";
    if (body) body.value = template.body || "";
  };

  const renderCrmEmailTemplatePreview = function (preview) {
    if (!crmEmailTemplatePreview) return;
    if (!preview) {
      crmEmailTemplatePreview.textContent = "No preview yet.";
      return;
    }
    crmEmailTemplatePreview.textContent = JSON.stringify(preview, null, 2);
  };

  const crmEmailTemplatePayload = function () {
    const siteId = document.getElementById("crmEmailTemplateSiteId");
    const templateKey = document.getElementById("crmEmailTemplateKey");
    const toEmail = document.getElementById("crmEmailTemplateToEmail");
    const subject = document.getElementById("crmEmailTemplateSubject");
    const body = document.getElementById("crmEmailTemplateBody");
    return {
      site_id: siteId && siteId.value ? siteId.value.trim() : "",
      template_key: templateKey && templateKey.value ? templateKey.value : "",
      to_email: toEmail && toEmail.value ? toEmail.value.trim() : "",
      subject: subject ? subject.value : "",
      body: body ? body.value : "",
      vars: {},
    };
  };

  if (refreshCrmEmailTemplatesBtn) {
    refreshCrmEmailTemplatesBtn.addEventListener("click", async function () {
      await loadCrmEmailTemplateSites();
      const data = await loadCrmEmailTemplatesSummary();
      fillCrmEmailTemplateFields(data);
      await loadCrmEmailTemplateTestLog();
    });
  }

  if (downloadCrmEmailTemplatesExportBtn) {
    downloadCrmEmailTemplatesExportBtn.addEventListener("click", async function () {
      await downloadCrmEmailTemplatesExport();
    });
  }

  if (loadCrmEmailTemplateBtn) {
    loadCrmEmailTemplateBtn.addEventListener("click", async function () {
      const data = await loadCrmEmailTemplatesSummary();
      fillCrmEmailTemplateFields(data);
      await loadCrmEmailTemplateTestLog();
    });
  }

  const crmEmailTemplateKey = document.getElementById("crmEmailTemplateKey");
  if (crmEmailTemplateKey) {
    crmEmailTemplateKey.addEventListener("change", async function () {
      const data = await loadCrmEmailTemplatesSummary();
      fillCrmEmailTemplateFields(data);
      renderCrmEmailTemplatePreview(null);
    });
  }

  if (refreshCrmEmailTemplateLogBtn) {
    refreshCrmEmailTemplateLogBtn.addEventListener("click", async function () {
      await loadCrmEmailTemplateTestLog();
    });
  }

  const loadCrmEmailTemplateLogDetailBtn = document.getElementById("loadCrmEmailTemplateLogDetailBtn");
  if (loadCrmEmailTemplateLogDetailBtn) {
    loadCrmEmailTemplateLogDetailBtn.addEventListener("click", async function () {
      await loadCrmEmailTemplateLogDetail();
    });
  }

  if (downloadCrmEmailTemplateLogExportBtn) {
    downloadCrmEmailTemplateLogExportBtn.addEventListener("click", async function () {
      await downloadCrmEmailTemplateLogExport();
    });
  }

  const refreshCrmOperationsSnapshotBtn = document.getElementById("refreshCrmOperationsSnapshotBtn");
  if (refreshCrmOperationsSnapshotBtn) {
    refreshCrmOperationsSnapshotBtn.addEventListener("click", async function () {
      await loadCrmOperationsSnapshot();
      await loadCrmOperationsHistory();
      await loadCrmOperationsHistorySummary();
      await loadCrmOperationsLatestCompare();
      await loadCrmOperationsIssuesSummary();
    });
  }

  if (downloadCrmOperationsSnapshotBtn) {
    downloadCrmOperationsSnapshotBtn.addEventListener("click", async function () {
      await downloadCrmOperationsSnapshot();
    });
  }

  if (saveCrmEmailTemplateBtn) {
    saveCrmEmailTemplateBtn.addEventListener("click", async function () {
      const siteId = document.getElementById("crmEmailTemplateSiteId");
      const templateKey = document.getElementById("crmEmailTemplateKey");
      const subject = document.getElementById("crmEmailTemplateSubject");
      const body = document.getElementById("crmEmailTemplateBody");
      const site_id = siteId && siteId.value ? siteId.value.trim() : "";
      const key = templateKey && templateKey.value ? templateKey.value : "";
      if (!site_id || !key) {
        if (crmEmailTemplatesResult) crmEmailTemplatesResult.textContent = "Enter template site ID and choose a template key.";
        return;
      }
      const templates = {};
      templates[key] = {
        subject: subject ? subject.value : "",
        body: body ? body.value : "",
      };
      const result = await apiPost("crm.email_templates.save", { site_id, templates });
      if (crmEmailTemplatesResult) {
        crmEmailTemplatesResult.textContent = JSON.stringify(result, null, 2);
      }
      const data = await loadCrmEmailTemplatesSummary();
      fillCrmEmailTemplateFields(data);
      const crmData = await apiGet("crm.summary");
      const crmPanel = document.getElementById("modCrm");
      if (crmPanel) {
        crmPanel.textContent = JSON.stringify(crmData, null, 2);
      }
      await loadCrmEmailTemplateTestLog();
    });
  }

  if (previewCrmEmailTemplateBtn) {
    previewCrmEmailTemplateBtn.addEventListener("click", async function () {
      const payload = crmEmailTemplatePayload();
      if (!payload.site_id || !payload.template_key) {
        if (crmEmailTemplatesResult) crmEmailTemplatesResult.textContent = "Enter template site ID and choose a template key.";
        return;
      }
      const result = await apiPost("crm.email_templates.preview", payload);
      renderCrmEmailTemplatePreview(result && result.preview ? result.preview : null);
      if (crmEmailTemplatesResult) {
        crmEmailTemplatesResult.textContent = JSON.stringify(result, null, 2);
      }
    });
  }

  if (sendCrmEmailTemplateTestBtn) {
    sendCrmEmailTemplateTestBtn.addEventListener("click", async function () {
      const payload = crmEmailTemplatePayload();
      if (!payload.site_id || !payload.template_key) {
        if (crmEmailTemplatesResult) crmEmailTemplatesResult.textContent = "Enter template site ID and choose a template key.";
        return;
      }
      if (!payload.to_email) {
        if (crmEmailTemplatesResult) crmEmailTemplatesResult.textContent = "Enter a template test recipient email first.";
        return;
      }
      const result = await apiPost("crm.email_templates.send_test", payload);
      if (crmEmailTemplatesResult) {
        crmEmailTemplatesResult.textContent = JSON.stringify(result, null, 2);
      }
      const preview =
        result &&
        result.response &&
        result.response.data &&
        result.response.data.result &&
        result.response.data.result.preview
          ? result.response.data.result.preview
          : null;
      if (preview) {
        renderCrmEmailTemplatePreview(preview);
      }
      await loadCrmSmtpSummary();
      await loadCrmSmtpWatch();
      await loadCrmEmailTemplateTestLog();
    });
  }

  if (socialConnectorForm) {
    socialConnectorForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const socialProviderInput = document.getElementById("socialProvider");
    if (socialProviderInput) {
      const syncSocialProviderProfile = function () {
        applySocialProviderProfile(socialProviderInput.value);
      };
      socialProviderInput.addEventListener("change", syncSocialProviderProfile);
      socialProviderInput.addEventListener("blur", syncSocialProviderProfile);
    }
    const saveSocialBtn = document.getElementById("saveSocialConnectorBtn");
    if (saveSocialBtn) {
      saveSocialBtn.addEventListener("click", async function () {
        const connectorId = document.getElementById("socialConnectorId");
        const provider = document.getElementById("socialProvider");
        const accountLabel = document.getElementById("socialAccountLabel");
        const type = document.getElementById("socialType");
        const status = document.getElementById("socialStatus");
        const auth = document.getElementById("socialAuth");
        const caps = document.getElementById("socialCapabilities");
        const siteId = document.getElementById("socialSiteId");
        const runMode = document.getElementById("socialRunMode");
        const webhook = document.getElementById("socialWebhook");
        const expiresAt = document.getElementById("socialExpiresAt");
        const payload = {
          connector_id: connectorId && connectorId.value ? connectorId.value.trim() : "",
          provider: provider ? provider.value.trim() : "",
          account_label: accountLabel ? accountLabel.value.trim() : "",
          type: type ? type.value : "external_api",
          status: status ? status.value : "planned",
          auth_mode: auth ? auth.value : "api_key",
          site_id: siteId ? siteId.value.trim() : "",
          expires_at: expiresAt && expiresAt.value ? new Date(expiresAt.value).toISOString() : "",
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
        await loadSocialCapabilitiesSummary();
        await loadSocialWatch();
        await loadSocialOperationsSnapshot();
        await loadSocialDraftsPreview();
        await loadSocialDraftValidation();
        await loadSocialDraftPlan();
        await loadSocialActivityFeed();
        await loadSocialInboxThreads();
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
        await loadSocialCapabilitiesSummary();
        await loadSocialWatch();
        await loadSocialOperationsSnapshot();
        await loadSocialDraftsPreview();
        await loadSocialDraftValidation();
        await loadSocialDraftPlan();
        await loadSocialActivityFeed();
        await loadSocialInboxThreads();
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
        await loadSocialWatch();
      });
    }
  }

  if (socialScheduleForm) {
    socialScheduleForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const saveScheduleBtn = document.getElementById("saveSocialScheduleBtn");
    const validateTargetsBtn = document.getElementById("validateSocialScheduleTargetsBtn");
    if (validateTargetsBtn) {
      validateTargetsBtn.addEventListener("click", async function () {
        await validateSocialScheduleTargets();
      });
    }
    if (saveScheduleBtn) {
      saveScheduleBtn.addEventListener("click", async function () {
        const scheduleId = document.getElementById("socialScheduleId");
        const title = document.getElementById("socialScheduleTitle");
        const message = document.getElementById("socialScheduleMessage");
        const url = document.getElementById("socialScheduleUrl");
        const connectorIds = document.getElementById("socialScheduleConnectorIds");
        const scheduledFor = document.getElementById("socialScheduleFor");
        const payload = {
          schedule_id: scheduleId && scheduleId.value ? scheduleId.value.trim() : "",
          title: title ? title.value.trim() : "",
          message: message ? message.value.trim() : "",
          url: url ? url.value.trim() : "",
          connector_ids: (connectorIds && connectorIds.value ? connectorIds.value.split(",") : []).map(function (value) {
            return value.trim();
          }).filter(Boolean),
          scheduled_for: scheduledFor && scheduledFor.value ? new Date(scheduledFor.value).toISOString() : "",
        };
        const result = await apiPost("social.schedule.save", payload);
        if (socialSyncResult) {
          socialSyncResult.textContent = JSON.stringify(result, null, 2);
        }
        await validateSocialScheduleTargets();
        await loadSocialScheduleSummary();
        await loadSocialScheduleQueue();
        await loadSocialActivityFeed();
      });
    }

    const deleteScheduleBtn = document.getElementById("deleteSocialScheduleBtn");
    if (deleteScheduleBtn) {
      deleteScheduleBtn.addEventListener("click", async function () {
        const scheduleId = document.getElementById("socialScheduleId");
        const id = scheduleId && scheduleId.value ? scheduleId.value.trim() : "";
        if (!id) {
          if (socialSyncResult) {
            socialSyncResult.textContent = "Enter Schedule ID to delete.";
          }
          return;
        }
        const result = await apiPost("social.schedule.delete", { schedule_id: id });
        if (socialSyncResult) {
          socialSyncResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadSocialScheduleSummary();
        await loadSocialScheduleQueue();
        await loadSocialActivityFeed();
      });
    }

    const runScheduleBtn = document.getElementById("runSocialScheduleBtn");
    if (runScheduleBtn) {
      runScheduleBtn.addEventListener("click", async function () {
        const result = await apiPost("social.schedule.run", {});
        if (socialSyncResult) {
          socialSyncResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadSocialScheduleSummary();
        await loadSocialScheduleQueue();
        await loadSocialSyncLog();
        await loadSocialRetryQueue();
        await loadSocialActivityFeed();
        const socialData = await apiGet("social.summary");
        const socialPanel = document.getElementById("modSocial");
        if (socialPanel) {
          socialPanel.textContent = JSON.stringify(socialData, null, 2);
        }
      });
    }
  }

  if (socialInboxReplyForm) {
    socialInboxReplyForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const replyBtn = document.getElementById("replySocialInboxBtn");
    if (replyBtn) {
      replyBtn.addEventListener("click", async function () {
        const threadId = document.getElementById("socialInboxThreadId");
        const message = document.getElementById("socialInboxReplyMessage");
        const id = threadId && threadId.value ? threadId.value.trim() : "";
        const text = message && message.value ? message.value.trim() : "";
        if (!id || !text) {
          if (socialSyncResult) {
            socialSyncResult.textContent = "Enter Thread ID and reply message.";
          }
          return;
        }
        const result = await apiPost("social.inbox.reply", {
          thread_id: id,
          message: text,
        });
        if (socialSyncResult) {
          socialSyncResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadSocialInboxSummary();
        await loadSocialInboxWorkload();
        await loadSocialInboxWatch();
        await loadSocialInboxThreads();
        await loadSocialActivityFeed();
        await refreshSocialModuleSummary();
      });
    }
  }

  if (socialInboxUpdateForm) {
    socialInboxUpdateForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const updateBtn = document.getElementById("updateSocialInboxThreadBtn");
    if (updateBtn) {
      updateBtn.addEventListener("click", async function () {
        const threadId = document.getElementById("socialInboxUpdateThreadId");
        const status = document.getElementById("socialInboxStatus");
        const priority = document.getElementById("socialInboxPriority");
        const owner = document.getElementById("socialInboxOwner");
        const internalNote = document.getElementById("socialInboxInternalNote");
        const id = threadId && threadId.value ? threadId.value.trim() : "";
        if (!id) {
          if (socialSyncResult) {
            socialSyncResult.textContent = "Enter Thread ID before updating the inbox thread.";
          }
          return;
        }
        const payload = {
          thread_id: id,
        };
        if (status && status.value) {
          payload.status = status.value;
        }
        if (priority && priority.value) {
          payload.priority = priority.value;
        }
        if (owner && owner.value !== "") {
          payload.owner = owner.value.trim();
        }
        if (internalNote && internalNote.value !== "") {
          payload.internal_note = internalNote.value.trim();
        }
        if (Object.keys(payload).length === 1) {
          if (socialSyncResult) {
            socialSyncResult.textContent = "Choose at least one inbox field to update.";
          }
          return;
        }
        const result = await apiPost("social.inbox.update", payload);
        if (socialSyncResult) {
          socialSyncResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadSocialInboxSummary();
        await loadSocialInboxWorkload();
        await loadSocialInboxWatch();
        await loadSocialInboxThreads();
        await loadSocialActivityFeed();
        await refreshSocialModuleSummary();
      });
    }
  }

  if (refreshSocialPlatformsBtn) {
    refreshSocialPlatformsBtn.addEventListener("click", async function () {
      await loadSocialPlatforms();
    });
  }

  if (loadSocialPlatformDetailBtn) {
    loadSocialPlatformDetailBtn.addEventListener("click", async function () {
      await loadSocialPlatformDetail();
    });
  }

  if (refreshSocialPlatformCoverageBtn) {
    refreshSocialPlatformCoverageBtn.addEventListener("click", async function () {
      await loadSocialPlatformCoverage();
    });
  }

  if (downloadSocialPlatformCoverageExportBtn) {
    downloadSocialPlatformCoverageExportBtn.addEventListener("click", async function () {
      await downloadSocialPlatformCoverageExport();
    });
  }

  if (downloadSocialPlatformsExportBtn) {
    downloadSocialPlatformsExportBtn.addEventListener("click", async function () {
      await downloadSocialPlatformsExport();
    });
  }

  if (refreshSocialExecutionPreviewBtn) {
    refreshSocialExecutionPreviewBtn.addEventListener("click", async function () {
      await loadSocialExecutionPreview();
    });
  }

  if (downloadSocialExecutionPreviewBtn) {
    downloadSocialExecutionPreviewBtn.addEventListener("click", async function () {
      await downloadSocialExecutionPreview();
    });
  }

  if (refreshSocialCapabilitiesBtn) {
    refreshSocialCapabilitiesBtn.addEventListener("click", async function () {
      await loadSocialCapabilitiesSummary();
    });
  }

  if (refreshSocialWatchBtn) {
    refreshSocialWatchBtn.addEventListener("click", async function () {
      await loadSocialWatch();
      await loadSocialOperationsSnapshot();
    });
  }

  if (refreshSocialOpsSnapshotBtn) {
    refreshSocialOpsSnapshotBtn.addEventListener("click", async function () {
      await loadSocialOperationsSnapshot();
      await loadSocialOperationsHistory();
      await loadSocialOperationsHistorySummary();
      await loadSocialOperationsLatestCompare();
      await loadSocialOperationsIssuesSummary();
    });
  }

  if (downloadSocialOpsSnapshotBtn) {
    downloadSocialOpsSnapshotBtn.addEventListener("click", async function () {
      await downloadSocialOperationsSnapshot();
    });
  }

  if (runSocialWatchBtn) {
    runSocialWatchBtn.addEventListener("click", async function () {
      const result = await apiPost("social.watch.run", {});
      if (socialSyncResult) {
        socialSyncResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadSocialWatch();
    });
  }

  if (refreshSocialDraftsBtn) {
    refreshSocialDraftsBtn.addEventListener("click", async function () {
      await loadSocialDraftsPreview();
    });
  }

  if (refreshSocialDraftListBtn) {
    refreshSocialDraftListBtn.addEventListener("click", async function () {
      await loadSocialDraftList();
    });
  }

  if (refreshSocialDraftDetailBtn) {
    refreshSocialDraftDetailBtn.addEventListener("click", async function () {
      await loadSocialDraftDetail();
      await loadSocialDraftRecommendations();
      await loadSocialDraftVariants();
    });
  }

  if (refreshSocialDraftRecommendationsBtn) {
    refreshSocialDraftRecommendationsBtn.addEventListener("click", async function () {
      await loadSocialDraftRecommendations();
    });
  }

  if (refreshSocialDraftVariantsBtn) {
    refreshSocialDraftVariantsBtn.addEventListener("click", async function () {
      await loadSocialDraftVariants();
    });
  }

  if (downloadSocialDraftVariantsExportBtn) {
    downloadSocialDraftVariantsExportBtn.addEventListener("click", async function () {
      await downloadSocialDraftVariantsExport();
    });
  }

  if (runSocialDraftScheduleBtn) {
    runSocialDraftScheduleBtn.addEventListener("click", async function () {
      await runSocialDraftSchedule();
    });
  }

  if (runSocialDraftBulkScheduleBtn) {
    runSocialDraftBulkScheduleBtn.addEventListener("click", async function () {
      await runSocialDraftBulkSchedule();
    });
  }

  if (refreshSocialConnectorsBtn) {
    refreshSocialConnectorsBtn.addEventListener("click", async function () {
      await loadSocialConnectors();
    });
  }

  if (refreshSocialConnectorDetailBtn) {
    refreshSocialConnectorDetailBtn.addEventListener("click", async function () {
      await loadSocialConnectorDetail();
    });
  }

  if (refreshSocialConnectorRuleAuditBtn) {
    refreshSocialConnectorRuleAuditBtn.addEventListener("click", async function () {
      await loadSocialConnectorRuleAudit();
    });
  }

  if (loadSocialConnectorRuleAuditDetailBtn) {
    loadSocialConnectorRuleAuditDetailBtn.addEventListener("click", async function () {
      await loadSocialConnectorRuleAuditDetail();
    });
  }

  if (downloadSocialConnectorRuleAuditExportBtn) {
    downloadSocialConnectorRuleAuditExportBtn.addEventListener("click", async function () {
      await downloadSocialConnectorRuleAuditExport();
    });
  }

  if (downloadSocialConnectorsExportBtn) {
    downloadSocialConnectorsExportBtn.addEventListener("click", async function () {
      await downloadSocialConnectorsExport();
    });
  }

  if (runSocialConnectorBulkUpdateBtn) {
    runSocialConnectorBulkUpdateBtn.addEventListener("click", async function () {
      await runSocialConnectorBulkUpdate();
    });
  }

  if (refreshSocialRetryQueueBtn) {
    refreshSocialRetryQueueBtn.addEventListener("click", async function () {
      await loadSocialRetryQueue();
    });
  }

  if (loadSocialRetryDetailBtn) {
    loadSocialRetryDetailBtn.addEventListener("click", async function () {
      await loadSocialRetryDetail();
    });
  }

  if (downloadSocialRetryExportBtn) {
    downloadSocialRetryExportBtn.addEventListener("click", async function () {
      await downloadSocialRetryExport();
    });
  }

  if (runSocialRetryBulkUpdateBtn) {
    runSocialRetryBulkUpdateBtn.addEventListener("click", async function () {
      await runSocialRetryBulkUpdate();
    });
  }

  if (refreshSocialSyncLogBtn) {
    refreshSocialSyncLogBtn.addEventListener("click", async function () {
      await loadSocialSyncLog();
    });
  }

  if (loadSocialSyncDetailBtn) {
    loadSocialSyncDetailBtn.addEventListener("click", async function () {
      await loadSocialSyncDetail();
    });
  }

  if (downloadSocialSyncExportBtn) {
    downloadSocialSyncExportBtn.addEventListener("click", async function () {
      await downloadSocialSyncExport();
    });
  }

  if (downloadSocialDraftsExportBtn) {
    downloadSocialDraftsExportBtn.addEventListener("click", async function () {
      await downloadSocialDraftsExport();
    });
  }

  if (refreshSocialDraftValidationBtn) {
    refreshSocialDraftValidationBtn.addEventListener("click", async function () {
      await loadSocialDraftValidation();
    });
  }

  if (refreshSocialDraftPlanBtn) {
    refreshSocialDraftPlanBtn.addEventListener("click", async function () {
      await loadSocialDraftPlan();
    });
  }

  if (refreshSocialScheduleSummaryBtn) {
    refreshSocialScheduleSummaryBtn.addEventListener("click", async function () {
      await loadSocialScheduleSummary();
    });
  }

  if (downloadSocialScheduleExportBtn) {
    downloadSocialScheduleExportBtn.addEventListener("click", async function () {
      await downloadSocialScheduleExport();
    });
  }

  const loadSocialScheduleDetailBtn = document.getElementById("loadSocialScheduleDetailBtn");
  if (loadSocialScheduleDetailBtn) {
    loadSocialScheduleDetailBtn.addEventListener("click", async function () {
      await loadSocialScheduleDetail();
    });
  }

  if (updateSocialScheduleBulkBtn) {
    updateSocialScheduleBulkBtn.addEventListener("click", async function () {
      await runSocialScheduleBulkUpdate();
    });
  }

  if (refreshSocialActivityBtn) {
    refreshSocialActivityBtn.addEventListener("click", async function () {
      await loadSocialActivityFeed();
    });
  }

  if (loadSocialActivityDetailBtn) {
    loadSocialActivityDetailBtn.addEventListener("click", async function () {
      await loadSocialActivityDetail();
    });
  }

  if (downloadSocialActivityExportBtn) {
    downloadSocialActivityExportBtn.addEventListener("click", async function () {
      await downloadSocialActivityExport();
    });
  }

  if (refreshSocialInboxBtn) {
    refreshSocialInboxBtn.addEventListener("click", async function () {
      await loadSocialInboxThreads();
    });
  }

  if (downloadSocialInboxExportBtn) {
    downloadSocialInboxExportBtn.addEventListener("click", async function () {
      await downloadSocialInboxExport();
    });
  }

  const loadSocialInboxDetailBtn = document.getElementById("loadSocialInboxDetailBtn");
  if (loadSocialInboxDetailBtn) {
    loadSocialInboxDetailBtn.addEventListener("click", async function () {
      await loadSocialInboxDetail();
    });
  }

  if (updateSocialInboxBulkBtn) {
    updateSocialInboxBulkBtn.addEventListener("click", async function () {
      await runSocialInboxBulkUpdate();
    });
  }

  if (refreshSocialInboxSummaryBtn) {
    refreshSocialInboxSummaryBtn.addEventListener("click", async function () {
      await loadSocialInboxSummary();
    });
  }

  if (refreshSocialInboxWorkloadBtn) {
    refreshSocialInboxWorkloadBtn.addEventListener("click", async function () {
      await loadSocialInboxWorkload();
    });
  }

  if (refreshSocialInboxWatchBtn) {
    refreshSocialInboxWatchBtn.addEventListener("click", async function () {
      await loadSocialInboxWatch();
    });
  }

  if (runSocialInboxWatchBtn) {
    runSocialInboxWatchBtn.addEventListener("click", async function () {
      const result = await apiPost("social.inbox.watch.run", {});
      if (socialSyncResult) {
        socialSyncResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadSocialInboxWatch();
      await loadSocialInboxWorkload();
      await loadSocialInboxSummary();
      await loadSocialActivityFeed();
      await loadSocialOperationsSnapshot();
      await refreshSocialModuleSummary();
    });
  }

  if (runSocialSyncBtn) {
    runSocialSyncBtn.addEventListener("click", async function () {
      const result = await apiPost("social.push.sync", {});
      if (socialSyncResult) {
        socialSyncResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadSocialDeliverySummary();
      await loadSocialDeliveryWatch();
      await loadSocialSyncLog();
      await loadSocialRetryQueue();
      await loadSocialActivityFeed();
      await refreshSocialModuleSummary();
    });
  }

  if (runSocialRetryQueueBtn) {
    runSocialRetryQueueBtn.addEventListener("click", async function () {
      const result = await apiPost("social.retry.run", {});
      if (socialSyncResult) {
        socialSyncResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadSocialDeliverySummary();
      await loadSocialDeliveryWatch();
      await loadSocialRetryQueue();
      await loadSocialActivityFeed();
      await refreshSocialModuleSummary();
    });
  }

  if (refreshSocialDeliverySummaryBtn) {
    refreshSocialDeliverySummaryBtn.addEventListener("click", async function () {
      await loadSocialDeliverySummary();
    });
  }

  if (refreshSocialDeliveryDetailBtn) {
    refreshSocialDeliveryDetailBtn.addEventListener("click", async function () {
      await loadSocialDeliveryConnectorDetail();
    });
  }

  if (downloadSocialDeliveryExportBtn) {
    downloadSocialDeliveryExportBtn.addEventListener("click", async function () {
      await downloadSocialDeliveryExport();
    });
  }

  if (refreshSocialDeliveryWatchBtn) {
    refreshSocialDeliveryWatchBtn.addEventListener("click", async function () {
      await loadSocialDeliveryWatch();
    });
  }

  if (runSocialDeliveryWatchBtn) {
    runSocialDeliveryWatchBtn.addEventListener("click", async function () {
      const result = await apiPost("social.delivery.watch.run", {});
      if (socialDeliveryWatchView) {
        socialDeliveryWatchView.textContent = JSON.stringify(result, null, 2);
      }
      await loadNotifications();
    });
  }

  if (downloadSocialDeliveryWatchExportBtn) {
    downloadSocialDeliveryWatchExportBtn.addEventListener("click", async function () {
      await downloadSocialDeliveryWatchExport();
    });
  }

  if (refreshWebopsTypesBtn) {
    refreshWebopsTypesBtn.addEventListener("click", async function () {
      await loadWebopsTypes();
    });
  }

  if (refreshWebopsIncidentsBtn) {
    refreshWebopsIncidentsBtn.addEventListener("click", async function () {
      await loadWebopsIncidents();
    });
  }

  if (refreshWebopsActionsBtn) {
    refreshWebopsActionsBtn.addEventListener("click", async function () {
      await loadWebopsActionsQueue();
    });
  }

  if (refreshWebopsPostureBtn) {
    refreshWebopsPostureBtn.addEventListener("click", async function () {
      await loadWebopsPosture();
    });
  }

  if (refreshWebopsOperationsSnapshotBtn) {
    refreshWebopsOperationsSnapshotBtn.addEventListener("click", async function () {
      await loadWebopsOperationsSnapshot();
      await loadWebopsOperationsHistory();
      await loadWebopsOperationsHistorySummary();
      await loadWebopsOperationsIssuesSummary();
    });
  }

  if (downloadWebopsOperationsSnapshotBtn) {
    downloadWebopsOperationsSnapshotBtn.addEventListener("click", async function () {
      await downloadWebopsOperationsSnapshot();
    });
  }

  if (runWebopsActionsBtn) {
    runWebopsActionsBtn.addEventListener("click", async function () {
      const result = await apiPost("webops.actions.run", {});
      if (webopsResult) {
        webopsResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadWebopsActionsQueue();
      await loadWebopsActionsLog();
      await loadWebopsPosture();
      const data = await apiGet("webops.summary");
      const panel = document.getElementById("modWebops");
      if (panel) {
        panel.textContent = JSON.stringify(data, null, 2);
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
        await loadWebopsIncidents();
      });
    }
  }

  if (webopsIncidentForm) {
    webopsIncidentForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const resolveWebopsIncidentBtn = document.getElementById("resolveWebopsIncidentBtn");
    if (resolveWebopsIncidentBtn) {
      resolveWebopsIncidentBtn.addEventListener("click", async function () {
        const incidentId = document.getElementById("webopsIncidentId");
        const note = document.getElementById("webopsIncidentNote");
        const id = incidentId && incidentId.value ? incidentId.value.trim() : "";
        if (!id) {
          if (webopsResult) webopsResult.textContent = "Enter Incident ID to resolve.";
          return;
        }
        const result = await apiPost("webops.incidents.resolve", {
          incident_id: id,
          note: note ? note.value.trim() : "",
        });
        if (webopsResult) {
          webopsResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadWebopsIncidents();
        const data = await apiGet("webops.summary");
        const panel = document.getElementById("modWebops");
        if (panel) {
          panel.textContent = JSON.stringify(data, null, 2);
        }
      });
    }
  }

  if (webopsActionForm) {
    webopsActionForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const enqueueWebopsActionBtn = document.getElementById("enqueueWebopsActionBtn");
    if (enqueueWebopsActionBtn) {
      enqueueWebopsActionBtn.addEventListener("click", async function () {
        const siteId = document.getElementById("webopsActionSiteId");
        const actionType = document.getElementById("webopsActionType");
        const pluginFile = document.getElementById("webopsActionPluginFile");
        const desiredState = document.getElementById("webopsActionDesiredState");
        const runMode = document.getElementById("webopsActionRunMode");
        const payload = {
          site_id: siteId ? siteId.value.trim() : "",
          action_type: actionType ? actionType.value : "",
          plugin_file: pluginFile ? pluginFile.value.trim() : "",
          desired_state: desiredState ? desiredState.value : "",
          run_mode: runMode ? runMode.value : "dry_run",
        };
        const result = await apiPost("webops.actions.enqueue", payload);
        if (webopsResult) {
          webopsResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadWebopsActionsQueue();
        await loadWebopsActionsLog();
        await loadWebopsPosture();
        const data = await apiGet("webops.summary");
        const panel = document.getElementById("modWebops");
        if (panel) {
          panel.textContent = JSON.stringify(data, null, 2);
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
      await loadWebopsIncidents();
      await loadWebopsPosture();
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
      await loadWebopsIncidents();
      await loadWebopsPosture();
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
      await loadLaunchOperationsHistory();
      await loadLaunchOperationsHistorySummary();
      await loadLaunchOperationsLatestCompare();
      await loadLaunchOperationsIssuesSummary();
      await loadLaunchOperationsSnapshot();
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
      await loadLaunchOperationsHistory();
      await loadLaunchOperationsHistorySummary();
      await loadLaunchOperationsLatestCompare();
      await loadLaunchOperationsIssuesSummary();
      await loadLaunchOperationsSnapshot();
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

  if (refreshLeadsInventoryBtn) {
    refreshLeadsInventoryBtn.addEventListener("click", async function () {
      await loadLeadsInventory();
    });
  }

  if (refreshLeadsListBtn) {
    refreshLeadsListBtn.addEventListener("click", async function () {
      await loadLeadsList();
    });
  }

  if (refreshLeadsPushPlanBtn) {
    refreshLeadsPushPlanBtn.addEventListener("click", async function () {
      await loadLeadsPushPlan();
    });
  }

  if (refreshLeadsQualityBtn) {
    refreshLeadsQualityBtn.addEventListener("click", async function () {
      await loadLeadsQuality();
    });
  }

  if (refreshLeadsDeliveryHistoryBtn) {
    refreshLeadsDeliveryHistoryBtn.addEventListener("click", async function () {
      await loadLeadsDeliveryHistory();
    });
  }

  if (refreshLeadsReviewQueueBtn) {
    refreshLeadsReviewQueueBtn.addEventListener("click", async function () {
      await loadLeadsReviewQueue();
    });
  }

  if (downloadLeadsReviewQueueBtn) {
    downloadLeadsReviewQueueBtn.addEventListener("click", async function () {
      const data = await apiGetWithParams("leads.review.queue.export", leadsReviewQueueFilters());
      if (data && data.ok && data.export) {
        downloadJsonFile(data.filename || "leads-review-queue.json", data.export);
      }
    });
  }

  if (loadLeadsReviewDetailBtn) {
    loadLeadsReviewDetailBtn.addEventListener("click", async function () {
      await loadLeadsReviewDetail();
    });
  }

  if (checkLeadsReviewDuplicatesBtn) {
    checkLeadsReviewDuplicatesBtn.addEventListener("click", async function () {
      await loadLeadsReviewDuplicates();
    });
  }

  if (saveLeadsReviewBtn) {
    saveLeadsReviewBtn.addEventListener("click", async function () {
      await runLeadsReviewAction("save");
    });
  }

  if (discardLeadsReviewBtn) {
    discardLeadsReviewBtn.addEventListener("click", async function () {
      await runLeadsReviewAction("discard");
    });
  }

  if (saveLeadsReviewQueueBtn) {
    saveLeadsReviewQueueBtn.addEventListener("click", async function () {
      await runLeadsReviewQueueBulkAction("save");
    });
  }

  if (discardLeadsReviewQueueBtn) {
    discardLeadsReviewQueueBtn.addEventListener("click", async function () {
      await runLeadsReviewQueueBulkAction("discard");
    });
  }

  if (updateLeadsReviewDraftBtn) {
    updateLeadsReviewDraftBtn.addEventListener("click", async function () {
      await runLeadsReviewDraftUpdate();
    });
  }

  if (leadsReviewDraftSelect) {
    leadsReviewDraftSelect.addEventListener("change", function () {
      const selectedId = leadsReviewDraftSelect.value ? String(leadsReviewDraftSelect.value) : "";
      const draft = currentLeadsReviewDrafts.find(function (item) {
        return String(item && item.id ? item.id : "") === selectedId;
      });
      setLeadsReviewDraftEditorValues(draft || null);
    });
  }

  if (runLeadsCrmSyncBtn) {
    runLeadsCrmSyncBtn.addEventListener("click", async function () {
      const result = await apiPost("crm.push.sync", {});
      if (leadsPushResultView) {
        leadsPushResultView.textContent = JSON.stringify(result, null, 2);
      }
      if (crmSyncResult) {
        crmSyncResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadLeadsPushPlan();
      await loadLeadsInventory();
      await loadLeadsList();
      await loadLeadsQuality();
      await loadLeadsDeliveryHistory();
      await loadCrmDeliverySummary();
      await loadCrmSyncLog();
      await loadRetryQueue();
      await refreshLeadsAndCrmModuleCards();
    });
  }

  if (runLeadsRetryQueueBtn) {
    runLeadsRetryQueueBtn.addEventListener("click", async function () {
      const result = await apiPost("crm.retry.run", {});
      if (leadsPushResultView) {
        leadsPushResultView.textContent = JSON.stringify(result, null, 2);
      }
      if (crmSyncResult) {
        crmSyncResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadLeadsPushPlan();
      await loadLeadsQuality();
      await loadLeadsDeliveryHistory();
      await loadCrmDeliverySummary();
      await loadCrmSyncLog();
      await loadRetryQueue();
      await refreshLeadsAndCrmModuleCards();
    });
  }

  if (downloadLeadsExportBtn) {
    downloadLeadsExportBtn.addEventListener("click", async function () {
      const data = await apiGetWithParams("leads.export", leadsListFilters());
      if (data && data.ok && data.export) {
        downloadJsonFile(data.filename || "leads-export.json", data.export);
      }
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

  if (refreshReleaseGateOpsSnapshotBtn) {
    refreshReleaseGateOpsSnapshotBtn.addEventListener("click", async function () {
      await loadReleaseGateOpsSnapshot();
    });
  }

  if (downloadReleaseGateRunsBtn) {
    downloadReleaseGateRunsBtn.addEventListener("click", async function () {
      await downloadReleaseGateRuns();
    });
  }

  if (downloadReleaseGateQuickstatsBtn) {
    downloadReleaseGateQuickstatsBtn.addEventListener("click", async function () {
      await downloadReleaseGateQuickstats();
    });
  }

  if (downloadReleaseGateOpsSnapshotBtn) {
    downloadReleaseGateOpsSnapshotBtn.addEventListener("click", async function () {
      await downloadReleaseGateOpsSnapshot();
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
        await loadSeoIssuesSummary();
        await loadSeoHistorySummary();
        await loadSeoExtensionSummary();
        await loadSeoProjectSnapshot();
        await loadSeoActionPlan();
        await loadSeoOpportunities();
        await loadSeoRegressions();
        await loadSeoUrlHistory();
        await loadSeoCompare();
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
        await loadSeoIssuesSummary();
        await loadSeoHistorySummary();
        await loadSeoExtensionSummary();
        await loadSeoProjectSnapshot();
        await loadSeoActionPlan();
        await loadSeoOpportunities();
        await loadSeoRegressions();
        await loadSeoUrlHistory();
        await loadSeoCompare();
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
        await loadSeoIssuesSummary();
        await loadSeoHistorySummary();
        await loadSeoExtensionSummary();
        await loadSeoAudits();
        await loadSeoProjectSnapshot();
        await loadSeoActionPlan();
        await loadSeoOpportunities();
        await loadSeoRegressions();
        await loadSeoUrlHistory();
        await loadSeoCompare();
        const data = await apiGet("seo.summary");
        const panel = document.getElementById("modSeo");
        if (panel) {
          panel.textContent = JSON.stringify(data, null, 2);
        }
      });
    }
  }

  if (refreshSeoIssuesBtn) {
    refreshSeoIssuesBtn.addEventListener("click", async function () {
      await loadSeoIssuesSummary();
    });
  }

  if (downloadSeoIssuesExportBtn) {
    downloadSeoIssuesExportBtn.addEventListener("click", async function () {
      const projectId = document.getElementById("seoProjectId");
      const data = await apiGetWithParams("seo.issues.summary.export", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
      });
      downloadJsonFile(data.filename || "seo-issues-summary-export.json", data.export || data);
      if (seoResult) {
        seoResult.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (refreshSeoProjectsBtn) {
    refreshSeoProjectsBtn.addEventListener("click", async function () {
      await loadSeoProjects();
    });
  }

  if (loadSeoProjectDetailBtn) {
    loadSeoProjectDetailBtn.addEventListener("click", async function () {
      await loadSeoProjectDetail();
    });
  }

  if (downloadSeoProjectsExportBtn) {
    downloadSeoProjectsExportBtn.addEventListener("click", async function () {
      await downloadSeoProjectsExport();
    });
  }

  if (refreshSeoHistoryBtn) {
    refreshSeoHistoryBtn.addEventListener("click", async function () {
      await loadSeoHistorySummary();
    });
  }

  if (downloadSeoHistoryExportBtn) {
    downloadSeoHistoryExportBtn.addEventListener("click", async function () {
      const projectId = document.getElementById("seoProjectId");
      const data = await apiGetWithParams("seo.history.summary.export", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
      });
      downloadJsonFile(data.filename || "seo-history-summary-export.json", data.export || data);
      if (seoResult) {
        seoResult.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (refreshSeoExtensionSummaryBtn) {
    refreshSeoExtensionSummaryBtn.addEventListener("click", async function () {
      await loadSeoExtensionSummary();
    });
  }

  if (downloadSeoExtensionSummaryExportBtn) {
    downloadSeoExtensionSummaryExportBtn.addEventListener("click", async function () {
      const projectId = document.getElementById("seoProjectId");
      const data = await apiGetWithParams("seo.extension.events.summary.export", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
      });
      downloadJsonFile(data.filename || "seo-extension-summary-export.json", data.export || data);
      if (seoResult) {
        seoResult.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (refreshSeoAuditsBtn) {
    refreshSeoAuditsBtn.addEventListener("click", async function () {
      await loadSeoAudits();
    });
  }

  if (refreshSeoExtensionEventsBtn) {
    refreshSeoExtensionEventsBtn.addEventListener("click", async function () {
      await loadSeoExtensionEvents();
    });
  }

  if (refreshSeoExtensionSessionsBtn) {
    refreshSeoExtensionSessionsBtn.addEventListener("click", async function () {
      await loadSeoExtensionSessions();
    });
  }

  if (loadSeoExtensionSessionDetailBtn) {
    loadSeoExtensionSessionDetailBtn.addEventListener("click", async function () {
      await loadSeoExtensionSessionDetail();
    });
  }

  if (downloadSeoExtensionSessionsExportBtn) {
    downloadSeoExtensionSessionsExportBtn.addEventListener("click", async function () {
      await downloadSeoExtensionSessionsExport();
    });
  }

  if (loadSeoExtensionEventDetailBtn) {
    loadSeoExtensionEventDetailBtn.addEventListener("click", async function () {
      await loadSeoExtensionEventDetail();
    });
  }

  if (downloadSeoExtensionEventsExportBtn) {
    downloadSeoExtensionEventsExportBtn.addEventListener("click", async function () {
      await downloadSeoExtensionEventsExport();
    });
  }

  if (loadSeoAuditDetailBtn) {
    loadSeoAuditDetailBtn.addEventListener("click", async function () {
      await loadSeoAuditDetail();
    });
  }

  if (downloadSeoAuditsExportBtn) {
    downloadSeoAuditsExportBtn.addEventListener("click", async function () {
      await downloadSeoAuditsExport();
    });
  }

  if (downloadSeoReportBtn) {
    downloadSeoReportBtn.addEventListener("click", async function () {
      const projectId = document.getElementById("seoProjectId");
      const id = projectId && projectId.value ? projectId.value.trim() : "";
      const data = await apiGetWithParams("seo.report.export", {
        project_id: id,
      });
      if (data && data.ok && data.export) {
        downloadJsonFile(data.filename || "seo-report.json", data.export);
      }
      if (seoResult) {
        seoResult.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (refreshSeoProjectSnapshotBtn) {
    refreshSeoProjectSnapshotBtn.addEventListener("click", async function () {
      await loadSeoProjectSnapshot();
    });
  }

  const refreshSeoOperationsSnapshotBtn = document.getElementById("refreshSeoOperationsSnapshotBtn");
  if (refreshSeoOperationsSnapshotBtn) {
    refreshSeoOperationsSnapshotBtn.addEventListener("click", async function () {
      await loadSeoOperationsSnapshot();
      await loadSeoOperationsHistory();
      await loadSeoOperationsHistorySummary();
      await loadSeoOperationsIssuesSummary();
    });
  }

  if (downloadSeoOperationsSnapshotBtn) {
    downloadSeoOperationsSnapshotBtn.addEventListener("click", async function () {
      await downloadSeoOperationsSnapshot();
    });
  }

  if (refreshLaunchOperationsSnapshotBtn) {
    refreshLaunchOperationsSnapshotBtn.addEventListener("click", async function () {
      await loadLaunchOperationsSnapshot();
      await loadLaunchOperationsHistory();
      await loadLaunchOperationsHistorySummary();
      await loadLaunchOperationsLatestCompare();
      await loadLaunchOperationsIssuesSummary();
    });
  }

  if (downloadLaunchOperationsSnapshotBtn) {
    downloadLaunchOperationsSnapshotBtn.addEventListener("click", async function () {
      await downloadLaunchOperationsSnapshot();
    });
  }

  if (downloadLaunchOperationsHistoryBtn) {
    downloadLaunchOperationsHistoryBtn.addEventListener("click", async function () {
      await downloadLaunchOperationsHistory();
    });
  }

  const loadLaunchOperationsHistoryDetailBtn = document.getElementById("loadLaunchOperationsHistoryDetailBtn");
  if (loadLaunchOperationsHistoryDetailBtn) {
    loadLaunchOperationsHistoryDetailBtn.addEventListener("click", async function () {
      await loadLaunchOperationsHistoryDetail();
    });
  }

  const downloadLaunchOperationsHistoryDetailBtn = document.getElementById("downloadLaunchOperationsHistoryDetailBtn");
  if (downloadLaunchOperationsHistoryDetailBtn) {
    downloadLaunchOperationsHistoryDetailBtn.addEventListener("click", async function () {
      await downloadLaunchOperationsHistoryDetail();
    });
  }

  if (downloadLaunchOperationsHistorySummaryBtn) {
    downloadLaunchOperationsHistorySummaryBtn.addEventListener("click", async function () {
      await downloadLaunchOperationsHistorySummary();
    });
  }

  if (downloadLaunchOperationsCompareBtn) {
    downloadLaunchOperationsCompareBtn.addEventListener("click", async function () {
      await downloadLaunchOperationsLatestCompare();
    });
  }

  if (downloadLaunchOperationsIssuesBtn) {
    downloadLaunchOperationsIssuesBtn.addEventListener("click", async function () {
      await downloadLaunchOperationsIssues();
    });
  }

  if (downloadLaunchOperationsReviewBundleBtn) {
    downloadLaunchOperationsReviewBundleBtn.addEventListener("click", async function () {
      await downloadLaunchOperationsReviewBundle();
    });
  }

  if (downloadSeoProjectSnapshotExportBtn) {
    downloadSeoProjectSnapshotExportBtn.addEventListener("click", async function () {
      const projectId = document.getElementById("seoProjectId");
      const data = await apiGetWithParams("seo.project.snapshot.export", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
      });
      downloadJsonFile(data.filename || "seo-project-snapshot-export.json", data.export || data);
      if (seoResult) {
        seoResult.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (refreshSeoActionPlanBtn) {
    refreshSeoActionPlanBtn.addEventListener("click", async function () {
      await loadSeoActionPlan();
    });
  }

  if (downloadSeoActionPlanExportBtn) {
    downloadSeoActionPlanExportBtn.addEventListener("click", async function () {
      const projectId = document.getElementById("seoProjectId");
      const data = await apiGetWithParams("seo.actions.plan.export", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
      });
      downloadJsonFile(data.filename || "seo-action-plan-export.json", data.export || data);
      if (seoResult) {
        seoResult.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (refreshSeoOpportunitiesBtn) {
    refreshSeoOpportunitiesBtn.addEventListener("click", async function () {
      await loadSeoOpportunities();
    });
  }

  if (downloadSeoOpportunitiesExportBtn) {
    downloadSeoOpportunitiesExportBtn.addEventListener("click", async function () {
      const projectId = document.getElementById("seoProjectId");
      const data = await apiGetWithParams("seo.opportunities.export", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
      });
      downloadJsonFile(data.filename || "seo-opportunities-export.json", data.export || data);
      if (seoResult) {
        seoResult.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (refreshSeoRegressionsBtn) {
    refreshSeoRegressionsBtn.addEventListener("click", async function () {
      await loadSeoRegressions();
    });
  }

  if (runSeoRegressionsBtn) {
    runSeoRegressionsBtn.addEventListener("click", async function () {
      const result = await apiPost("seo.regressions.run", {});
      if (seoResult) {
        seoResult.textContent = JSON.stringify(result, null, 2);
      }
      await loadSeoRegressions();
    });
  }

  if (downloadSeoRegressionsExportBtn) {
    downloadSeoRegressionsExportBtn.addEventListener("click", async function () {
      const data = await apiGet("seo.regressions.export");
      downloadJsonFile(data.filename || "seo-regressions-export.json", data.export || data);
      if (seoResult) {
        seoResult.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (refreshSeoUrlHistoryBtn) {
    refreshSeoUrlHistoryBtn.addEventListener("click", async function () {
      await loadSeoUrlHistory();
    });
  }

  if (downloadSeoUrlHistoryExportBtn) {
    downloadSeoUrlHistoryExportBtn.addEventListener("click", async function () {
      const projectId = document.getElementById("seoProjectId");
      const urlFilter = document.getElementById("seoHistoryUrlFilter");
      const data = await apiGetWithParams("seo.url.history.export", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
        url: urlFilter && urlFilter.value ? urlFilter.value.trim() : "",
      });
      downloadJsonFile(data.filename || "seo-url-history-export.json", data.export || data);
      if (seoResult) {
        seoResult.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (refreshSeoCompareBtn) {
    refreshSeoCompareBtn.addEventListener("click", async function () {
      await loadSeoCompare();
    });
  }

  if (downloadSeoCompareExportBtn) {
    downloadSeoCompareExportBtn.addEventListener("click", async function () {
      const projectId = document.getElementById("seoProjectId");
      const data = await apiGetWithParams("seo.compare.latest.export", {
        project_id: projectId && projectId.value ? projectId.value.trim() : "",
      });
      downloadJsonFile(data.filename || "seo-compare-export.json", data.export || data);
      if (seoResult) {
        seoResult.textContent = JSON.stringify(data, null, 2);
      }
    });
  }

  if (seoExtensionSessionForm) {
    seoExtensionSessionForm.addEventListener("submit", function (event) {
      event.preventDefault();
    });
    const createSeoExtensionSessionBtn = document.getElementById("createSeoExtensionSessionBtn");
    if (createSeoExtensionSessionBtn) {
      createSeoExtensionSessionBtn.addEventListener("click", async function () {
        const projectId = document.getElementById("seoExtensionSessionProjectId");
        const label = document.getElementById("seoExtensionSessionLabel");
        const payload = {
          project_id: projectId && projectId.value ? projectId.value.trim() : "",
          label: label ? label.value.trim() : "",
        };
        const result = await apiPost("seo.extension.session.create", payload);
        if (seoResult) {
          seoResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadSeoExtensionSessions();
        await loadSeoProjectSnapshot();
        await loadSeoActionPlan();
        await loadSeoOpportunities();
        await loadSeoRegressions();
        await loadSeoUrlHistory();
        await loadSeoCompare();
      });
    }

    const revokeSeoExtensionSessionBtn = document.getElementById("revokeSeoExtensionSessionBtn");
    if (revokeSeoExtensionSessionBtn) {
      revokeSeoExtensionSessionBtn.addEventListener("click", async function () {
        const sessionId = document.getElementById("seoExtensionSessionId");
        const id = sessionId && sessionId.value ? sessionId.value.trim() : "";
        if (!id) {
          if (seoResult) seoResult.textContent = "Enter Extension Session ID to revoke.";
          return;
        }
        const result = await apiPost("seo.extension.session.revoke", { session_id: id });
        if (seoResult) {
          seoResult.textContent = JSON.stringify(result, null, 2);
        }
        await loadSeoExtensionSessions();
        await loadSeoProjectSnapshot();
        await loadSeoActionPlan();
        await loadSeoOpportunities();
        await loadSeoRegressions();
        await loadSeoUrlHistory();
        await loadSeoCompare();
      });
    }
  }
})();
