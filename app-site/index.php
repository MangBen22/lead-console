<?php
session_start();

$defaultConfig = [
    'app_name' => '5N2 App Control Center',
    'environment' => 'development',
    'demo_admin_email' => 'admin@5n2digital.com',
    'demo_admin_password' => 'change-me',
];

$config = file_exists(__DIR__ . '/config.php')
    ? array_merge($defaultConfig, require __DIR__ . '/config.php')
    : $defaultConfig;

$error = '';

if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    if ($email === strtolower($config['demo_admin_email']) && $password === $config['demo_admin_password']) {
        $_SESSION['app_user'] = [
            'email' => $email,
            'name' => '5N2 Admin',
            'role' => 'owner',
        ];
        header('Location: /');
        exit;
    }
    $error = 'Invalid credentials.';
}

if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    unset($_SESSION['app_user']);
    header('Location: /');
    exit;
}

$user = $_SESSION['app_user'] ?? null;
if (!isset($_SESSION['app_csrf_token']) || !is_string($_SESSION['app_csrf_token']) || $_SESSION['app_csrf_token'] === '') {
    $_SESSION['app_csrf_token'] = bin2hex(random_bytes(16));
}
$csrfToken = (string) $_SESSION['app_csrf_token'];

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($config['app_name'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<?php if (!$user): ?>
    <main class="auth-shell">
        <section class="auth-card">
            <p class="eyebrow">5N2 DIGITAL PLATFORM</p>
            <h1>Sign In</h1>
            <p>Control center for Leads, CRM/Email, Social, WebOps, and SEO.</p>
            <?php if ($error !== ''): ?>
                <div class="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="action" value="login">
                <label>Email</label>
                <input type="email" name="email" required>
                <label>Password</label>
                <input type="password" name="password" required>
                <button type="submit">Sign In</button>
            </form>
            <p class="hint">Demo credentials come from <code>config.php</code>.</p>
        </section>
    </main>
<?php else: ?>
    <div class="layout">
        <aside class="sidebar">
            <h2>5N2 App</h2>
            <nav>
                <a class="active" href="#">Dashboard</a>
                <a href="#">Leads</a>
                <a href="#">CRM + Email</a>
                <a href="#">Social + Forums</a>
                <a href="#">WebOps Security</a>
                <a href="#">SEO Suite</a>
                <a href="#">Settings</a>
            </nav>
            <form method="post" class="logout">
                <input type="hidden" name="action" value="logout">
                <button type="submit">Logout</button>
            </form>
        </aside>
        <main class="content">
            <header class="topbar">
                <div>
                    <h1>Main Platform Dashboard</h1>
                    <p>Environment: <?php echo htmlspecialchars($config['environment'], ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <button id="notifyToggle" class="notify-btn">Notifications</button>
            </header>

            <section class="cards">
                <article class="card">
                    <h3>Leads Engine</h3>
                    <p>Queue runs, review candidates, save approved leads, and push to connectors.</p>
                    <span class="badge active">Active</span>
                </article>
                <article class="card">
                    <h3>CRM + Email</h3>
                    <p>Connector routing, SMTP health, template automation, and delivery diagnostics.</p>
                    <span class="badge planning">Planning</span>
                </article>
                <article class="card">
                    <h3>Social + Forums</h3>
                    <p>Per-platform publishing, account sync, inbox operations, and capability gates.</p>
                    <span class="badge planning">Planning</span>
                </article>
                <article class="card">
                    <h3>WebOps Security</h3>
                    <p>Uptime checks, SSL alerts, remote plugin actions, and site health incidents.</p>
                    <span class="badge planning">Planning</span>
                </article>
                <article class="card">
                    <h3>SEO Suite</h3>
                    <p>Audit intelligence, project reports, and extension-based quick analysis.</p>
                    <span class="badge planning">Planning</span>
                </article>
            </section>

            <section class="api-status">
                <h2>API Status</h2>
                <pre id="apiStatus">Loading /api/index.php?action=status ...</pre>
            </section>

            <section class="api-status">
                <h2>Automation Runner</h2>
                <div class="actions">
                    <button id="runAutomationBtn" type="button">Run All Automation</button>
                    <button id="markNotificationsReadBtn" type="button">Mark Notifications Read</button>
                    <button id="runSchedulerTickBtn" type="button">Run Scheduler Tick (Test)</button>
                </div>
                <pre id="automationResult">No automation run yet.</pre>
                <h3>Automation Runs</h3>
                <pre id="automationRuns">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Automation Settings</h2>
                <form id="automationSettingsForm" class="inline-form">
                    <div class="form-row">
                        <label for="automationEnabled">Automation enabled</label>
                        <select id="automationEnabled">
                            <option value="1">enabled</option>
                            <option value="0">disabled</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="automationInterval">Interval (minutes)</label>
                        <input id="automationInterval" type="number" min="5" max="1440" value="30">
                    </div>
                    <div class="form-row">
                        <label for="autoModuleCrm">Run CRM module</label>
                        <select id="autoModuleCrm">
                            <option value="1">yes</option>
                            <option value="0">no</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="autoModuleSocial">Run Social module</label>
                        <select id="autoModuleSocial">
                            <option value="1">yes</option>
                            <option value="0">no</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="autoModuleWebops">Run WebOps module</label>
                        <select id="autoModuleWebops">
                            <option value="1">yes</option>
                            <option value="0">no</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="autoModuleSeo">Run SEO module</label>
                        <select id="autoModuleSeo">
                            <option value="1">yes</option>
                            <option value="0">no</option>
                        </select>
                    </div>
                    <button id="saveAutomationSettingsBtn" type="button">Save Automation Settings</button>
                </form>
                <pre id="automationSettingsView">Loading...</pre>
                <h3>Scheduler Status</h3>
                <pre id="schedulerStatusView">Loading...</pre>
                <h3>Cron Helper</h3>
                <pre id="cronHelpView">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Notification Settings</h2>
                <form id="notificationSettingsForm" class="inline-form">
                    <div class="form-row">
                        <label for="notifSoundEnabled">Sound enabled</label>
                        <select id="notifSoundEnabled">
                            <option value="1">enabled</option>
                            <option value="0">disabled</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="notifSoundMode">Sound mode</label>
                        <select id="notifSoundMode">
                            <option value="critical_only">critical_only</option>
                            <option value="all">all</option>
                            <option value="off">off</option>
                        </select>
                    </div>
                    <button id="saveNotificationSettingsBtn" type="button">Save Notification Settings</button>
                </form>
                <pre id="notificationSettingsView">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Module Bootstrap</h2>
                <div class="module-grid">
                    <article>
                        <h3>Leads</h3>
                        <pre id="modLeads">Loading...</pre>
                    </article>
                    <article>
                        <h3>CRM + Email</h3>
                        <pre id="modCrm">Loading...</pre>
                    </article>
                    <article>
                        <h3>Social + Forums</h3>
                        <pre id="modSocial">Loading...</pre>
                    </article>
                    <article>
                        <h3>WebOps Security</h3>
                        <pre id="modWebops">Loading...</pre>
                    </article>
                    <article>
                        <h3>SEO Suite</h3>
                        <pre id="modSeo">Loading...</pre>
                    </article>
                </div>
            </section>

            <section class="api-status">
                <h2>Bridge Site Status</h2>
                <pre id="bridgeSites">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>CRM Connectors</h2>
                <pre id="crmConnectors">Loading...</pre>
                <form id="crmConnectorForm" class="inline-form">
                    <div class="form-row">
                        <label for="connectorId">Connector ID (optional for update)</label>
                        <input id="connectorId" type="text" placeholder="connector_abc123">
                    </div>
                    <div class="form-row">
                        <label for="connectorProvider">Provider</label>
                        <input id="connectorProvider" type="text" placeholder="fluentcrm" required>
                    </div>
                    <div class="form-row">
                        <label for="connectorType">Type</label>
                        <select id="connectorType">
                            <option value="wordpress_plugin">wordpress_plugin</option>
                            <option value="external_api">external_api</option>
                            <option value="csv_export_bridge">csv_export_bridge</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="connectorStatus">Status</label>
                        <select id="connectorStatus">
                            <option value="active">active</option>
                            <option value="planned">planned</option>
                            <option value="paused">paused</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="connectorAuth">Auth</label>
                        <select id="connectorAuth">
                            <option value="api_key">api_key</option>
                            <option value="oauth2">oauth2</option>
                            <option value="token">token</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="connectorCapabilities">Capabilities (comma)</label>
                        <input id="connectorCapabilities" type="text" placeholder="create_contact,update_contact">
                    </div>
                    <div class="form-row">
                        <label for="connectorSiteId">Site ID (for wordpress_plugin)</label>
                        <input id="connectorSiteId" type="text" placeholder="hq-main">
                    </div>
                    <div class="form-row">
                        <label for="connectorRunMode">Run mode</label>
                        <select id="connectorRunMode">
                            <option value="dry_run">dry_run</option>
                            <option value="live">live</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="connectorAccessToken">Access token (HubSpot)</label>
                        <input id="connectorAccessToken" type="password" placeholder="token">
                    </div>
                    <div class="form-row">
                        <label for="connectorEndpoint">Endpoint URL (optional)</label>
                        <input id="connectorEndpoint" type="text" placeholder="https://api.hubapi.com/crm/v3/objects/contacts">
                    </div>
                    <div class="form-row">
                        <label for="connectorWebhook">Webhook URL (custom_webhook)</label>
                        <input id="connectorWebhook" type="text" placeholder="https://example.com/webhook">
                    </div>
                    <button id="saveConnectorBtn" type="button">Save Connector</button>
                    <button id="deleteConnectorBtn" type="button">Delete Connector ID</button>
                    <button id="testConnectorBtn" type="button">Test Connector ID</button>
                </form>
            </section>

            <section class="api-status">
                <h2>CRM Push Pipeline</h2>
                <div class="actions">
                    <button id="runCrmSyncBtn" type="button">Run CRM Sync</button>
                    <button id="runRetryQueueBtn" type="button">Run Retry Queue</button>
                </div>
                <pre id="crmSyncResult">No sync yet.</pre>
                <h3>Sync Log</h3>
                <pre id="crmSyncLog">Loading...</pre>
                <h3>Retry Queue</h3>
                <pre id="crmRetryQueue">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Social Connectors</h2>
                <pre id="socialConnectors">Loading...</pre>
                <form id="socialConnectorForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialConnectorId">Connector ID (optional for update)</label>
                        <input id="socialConnectorId" type="text" placeholder="social_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialProvider">Provider</label>
                        <input id="socialProvider" type="text" placeholder="wordpress_social_bridge" required>
                    </div>
                    <div class="form-row">
                        <label for="socialType">Type</label>
                        <select id="socialType">
                            <option value="wordpress_plugin">wordpress_plugin</option>
                            <option value="external_api">external_api</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialStatus">Status</label>
                        <select id="socialStatus">
                            <option value="active">active</option>
                            <option value="planned">planned</option>
                            <option value="paused">paused</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialAuth">Auth</label>
                        <select id="socialAuth">
                            <option value="api_key">api_key</option>
                            <option value="oauth2">oauth2</option>
                            <option value="token">token</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialCapabilities">Capabilities (comma)</label>
                        <input id="socialCapabilities" type="text" placeholder="can_publish_post,can_schedule">
                    </div>
                    <div class="form-row">
                        <label for="socialSiteId">Site ID (for wordpress_plugin)</label>
                        <input id="socialSiteId" type="text" placeholder="hq-main">
                    </div>
                    <div class="form-row">
                        <label for="socialRunMode">Run mode</label>
                        <select id="socialRunMode">
                            <option value="dry_run">dry_run</option>
                            <option value="live">live</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialWebhook">Webhook URL (social_webhook)</label>
                        <input id="socialWebhook" type="text" placeholder="https://example.com/social-webhook">
                    </div>
                    <button id="saveSocialConnectorBtn" type="button">Save Social Connector</button>
                    <button id="deleteSocialConnectorBtn" type="button">Delete Social Connector</button>
                    <button id="testSocialConnectorBtn" type="button">Test Social Connector</button>
                </form>
            </section>

            <section class="api-status">
                <h2>Social Push Pipeline</h2>
                <div class="actions">
                    <button id="runSocialSyncBtn" type="button">Run Social Sync</button>
                    <button id="runSocialRetryQueueBtn" type="button">Run Social Retry Queue</button>
                </div>
                <pre id="socialSyncResult">No social sync yet.</pre>
                <h3>Social Sync Log</h3>
                <pre id="socialSyncLog">Loading...</pre>
                <h3>Social Retry Queue</h3>
                <pre id="socialRetryQueue">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>WebOps Monitors</h2>
                <pre id="webopsMonitors">Loading...</pre>
                <form id="webopsMonitorForm" class="inline-form">
                    <div class="form-row">
                        <label for="webopsMonitorId">Monitor ID (optional for update)</label>
                        <input id="webopsMonitorId" type="text" placeholder="monitor_abc123">
                    </div>
                    <div class="form-row">
                        <label for="webopsMonitorName">Name</label>
                        <input id="webopsMonitorName" type="text" placeholder="Main Site Uptime" required>
                    </div>
                    <div class="form-row">
                        <label for="webopsMonitorType">Type</label>
                        <select id="webopsMonitorType">
                            <option value="uptime_http">uptime_http</option>
                            <option value="bridge_site_health">bridge_site_health</option>
                            <option value="webhook_check">webhook_check</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="webopsMonitorStatus">Status</label>
                        <select id="webopsMonitorStatus">
                            <option value="active">active</option>
                            <option value="paused">paused</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="webopsMonitorTarget">Target URL</label>
                        <input id="webopsMonitorTarget" type="text" placeholder="https://example.com">
                    </div>
                    <div class="form-row">
                        <label for="webopsBridgeSiteId">Bridge Site ID (for bridge_site_health)</label>
                        <input id="webopsBridgeSiteId" type="text" placeholder="hq-main">
                    </div>
                    <div class="form-row">
                        <label for="webopsRunMode">Run mode</label>
                        <select id="webopsRunMode">
                            <option value="live">live</option>
                            <option value="dry_run">dry_run</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="webopsWebhookUrl">Webhook URL (for webhook_check)</label>
                        <input id="webopsWebhookUrl" type="text" placeholder="https://example.com/ping">
                    </div>
                    <button id="saveWebopsMonitorBtn" type="button">Save Monitor</button>
                    <button id="deleteWebopsMonitorBtn" type="button">Delete Monitor</button>
                    <button id="testWebopsMonitorBtn" type="button">Test Monitor</button>
                </form>
            </section>

            <section class="api-status">
                <h2>WebOps Run Pipeline</h2>
                <div class="actions">
                    <button id="runWebopsBtn" type="button">Run WebOps Checks</button>
                    <button id="runWebopsRetryQueueBtn" type="button">Run WebOps Retry Queue</button>
                </div>
                <pre id="webopsResult">No WebOps run yet.</pre>
                <h3>WebOps Log</h3>
                <pre id="webopsLog">Loading...</pre>
                <h3>WebOps Retry Queue</h3>
                <pre id="webopsRetryQueue">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>SEO Projects</h2>
                <pre id="seoProjects">Loading...</pre>
                <form id="seoProjectForm" class="inline-form">
                    <div class="form-row">
                        <label for="seoProjectId">Project ID (optional for update)</label>
                        <input id="seoProjectId" type="text" placeholder="seo_project_abc123">
                    </div>
                    <div class="form-row">
                        <label for="seoProjectName">Project name</label>
                        <input id="seoProjectName" type="text" placeholder="5N2 Main Website" required>
                    </div>
                    <div class="form-row">
                        <label for="seoProjectDomain">Domain or URL</label>
                        <input id="seoProjectDomain" type="text" placeholder="https://5n2digital.com">
                    </div>
                    <div class="form-row">
                        <label for="seoProjectStatus">Status</label>
                        <select id="seoProjectStatus">
                            <option value="active">active</option>
                            <option value="paused">paused</option>
                        </select>
                    </div>
                    <button id="saveSeoProjectBtn" type="button">Save SEO Project</button>
                    <button id="deleteSeoProjectBtn" type="button">Delete SEO Project</button>
                    <button id="runSeoAuditBtn" type="button">Run SEO Audit</button>
                </form>
            </section>

            <section class="api-status">
                <h2>SEO Audit Pipeline</h2>
                <pre id="seoResult">No SEO action yet.</pre>
                <h3>SEO Audits</h3>
                <pre id="seoAudits">Loading...</pre>
                <h3>Extension Events</h3>
                <pre id="seoExtensionEvents">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Backup + Audit</h2>
                <div class="actions">
                    <button id="exportBackupBtn" type="button">Export Backup</button>
                    <button id="importBackupBtn" type="button">Import Backup</button>
                    <button id="refreshAuditBtn" type="button">Refresh Audit Log</button>
                </div>
                <textarea id="backupPayload" class="large-text code" rows="8" placeholder="Backup payload JSON appears here for export/import..."></textarea>
                <pre id="backupResult">No backup action yet.</pre>
                <h3>Audit Log</h3>
                <pre id="auditLogView">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Go-Live Status</h2>
                <label>Release gate freshness window (minutes)<input id="releaseGateFreshnessInput" type="number" min="5" max="1440" value="30" /></label>
                <div class="actions">
                    <button id="refreshGoLiveStatusBtn" type="button">Refresh Go-Live Status</button>
                    <button id="refreshReleaseGateBtn" type="button">Refresh Release Gate</button>
                </div>
                <pre id="goLiveStatusView">Loading...</pre>
                <pre id="releaseGateView">Loading release gate...</pre>
            </section>

            <section class="api-status">
                <h2>Release Candidate</h2>
                <textarea id="releaseCandidateNote" class="large-text" rows="3" placeholder="Optional release note (what changed for this candidate)..."></textarea>
                <div class="actions">
                    <button id="generateReleaseCandidateBtn" type="button">Generate Release Candidate</button>
                    <button id="downloadArtifactManifestBtn" type="button">Download Artifact Manifest</button>
                </div>
                <pre id="releaseCandidateView">No release candidate yet.</pre>
                <h3>Artifact Manifest</h3>
                <pre id="artifactManifestView">No artifact manifest yet.</pre>
                <h3>Verify Uploaded Artifacts</h3>
                <textarea id="artifactBaselineInput" class="large-text code" rows="6" placeholder="Paste baseline manifest JSON here (from your source build) then click Verify Uploaded Artifacts..."></textarea>
                <div class="actions">
                    <button id="verifyArtifactManifestBtn" type="button">Verify Uploaded Artifacts</button>
                </div>
                <pre id="artifactVerifyView">No artifact verification yet.</pre>
                <h3>Release Log</h3>
                <pre id="releaseLogView">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Deployment Preflight</h2>
                <div class="actions">
                    <button id="runPreflightBtn" type="button">Run Deployment Preflight</button>
                    <button id="downloadDeployReportBtn" type="button">Download Deployment Report</button>
                </div>
                <pre id="preflightView">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Hosting Cutover Toolkit</h2>
                <div class="actions">
                    <button id="runInstallCheckBtn" type="button">Run Install Check</button>
                    <button id="runDeploymentVerifyBtn" type="button">Run Post-Deploy Verify</button>
                    <button id="downloadHandoffBundleBtn" type="button">Download Handoff Bundle</button>
                    <button id="runCutoverPipelineBtn" type="button">Run Full Cutover Check</button>
                </div>
                <h3>Cutover Readiness</h3>
                <label>Smoke check note<input id="cutoverSmokeNote" type="text" placeholder="Optional note for smoke check records..." /></label>
                <div class="actions">
                    <button id="refreshCutoverReadinessBtn" type="button">Refresh Readiness</button>
                    <button id="runSmokeSuiteBtn" type="button">Run Full Smoke Suite</button>
                    <button id="downloadSmokeHistoryBtn" type="button">Download Smoke History</button>
                    <button id="recordPublicSmokePassBtn" type="button">Record Public Smoke Pass</button>
                    <button id="recordPublicSmokeFailBtn" type="button">Record Public Smoke Fail</button>
                    <button id="recordAuthSmokePassBtn" type="button">Record Auth Smoke Pass</button>
                    <button id="recordAuthSmokeFailBtn" type="button">Record Auth Smoke Fail</button>
                </div>
                <pre id="cutoverReadinessView">Loading readiness...</pre>
                <pre id="cutoverSmokeHistoryView">Loading smoke history...</pre>
                <pre id="cutoverSmokeRecordView">No smoke record action yet.</pre>
                <h3>Install Check</h3>
                <pre id="installCheckView">Loading...</pre>
                <h3>Post-Deploy Verify</h3>
                <pre id="deploymentVerifyView">No verification run yet.</pre>
                <h3>Cutover Pipeline</h3>
                <textarea id="cutoverPipelineNote" class="large-text" rows="2" placeholder="Optional note for this full cutover check..."></textarea>
                <pre id="cutoverPipelineView">No cutover pipeline run yet.</pre>
                <h3>Cutover Pipeline History</h3>
                <pre id="cutoverPipelineRunsView">Loading...</pre>
                <h3>Deployment Guard</h3>
                <form id="deploymentGuardForm">
                    <label><input id="guardEnforced" type="checkbox" value="1" checked /> Enforce deployment guard on risky writes</label>
                    <label><input id="guardLaunchWindowEnabled" type="checkbox" value="1" /> Enable launch window (UTC)</label>
                    <label>Launch window start (UTC, YYYY-MM-DDTHH:MM)<input id="guardLaunchWindowStart" type="text" placeholder="2026-03-02T18:00" /></label>
                    <label>Launch window end (UTC, YYYY-MM-DDTHH:MM)<input id="guardLaunchWindowEnd" type="text" placeholder="2026-03-02T20:00" /></label>
                    <label><input id="guardBackupVerified" type="checkbox" value="1" /> Backup verified</label>
                    <label><input id="guardCronConfigured" type="checkbox" value="1" /> Cron configured</label>
                    <label><input id="guardRollbackReady" type="checkbox" value="1" /> Rollback plan ready</label>
                    <label><input id="guardDnsReady" type="checkbox" value="1" /> DNS/domain ready</label>
                    <div class="actions">
                        <button id="saveDeploymentGuardBtn" type="button">Save Guard Checklist</button>
                        <button id="previewDeploymentGuardBtn" type="button">Preview Guard Result</button>
                        <button id="unlockDeploymentGuardBtn" type="button">Unlock Guard</button>
                        <button id="lockDeploymentGuardBtn" type="button">Lock Guard</button>
                    </div>
                </form>
                <h4>Emergency Bypass (Time-Limited)</h4>
                <label>Bypass reason (required)<input id="guardBypassReason" type="text" placeholder="Explain why emergency bypass is needed..." /></label>
                <label>Duration minutes (5-240)<input id="guardBypassDuration" type="number" min="5" max="240" value="30" /></label>
                <div class="actions">
                    <button id="enableGuardBypassBtn" type="button">Enable Emergency Bypass</button>
                    <button id="extendGuardBypassBtn" type="button">Extend Emergency Bypass</button>
                    <button id="disableGuardBypassBtn" type="button">Disable Emergency Bypass</button>
                    <button id="refreshBypassLogBtn" type="button">Refresh Bypass Log</button>
                    <button id="downloadIncidentReportBtn" type="button">Download Incident Report</button>
                    <button id="refreshIncidentReportsBtn" type="button">Refresh Incident Reports</button>
                </div>
                <textarea id="incidentReportNote" class="large-text" rows="2" placeholder="Optional incident report note..."></textarea>
                <label>Incident report ID<input id="incidentReportIdInput" type="text" placeholder="incident_YYYYMMDD_HHMMSS_xxxxxx" /></label>
                <label>Incident status note<input id="incidentStatusNoteInput" type="text" placeholder="Resolution/reopen note..." /></label>
                <div class="actions">
                    <button id="resolveIncidentBtn" type="button">Mark Resolved</button>
                    <button id="reopenIncidentBtn" type="button">Reopen Incident</button>
                </div>
                <pre id="deploymentGuardView">Loading...</pre>
                <pre id="deploymentGuardPreviewView">No preview yet.</pre>
                <pre id="deploymentBypassLogView">Loading bypass log...</pre>
                <pre id="incidentStatusActionView">No incident lifecycle action yet.</pre>
                <pre id="incidentReportsView">Loading incident reports...</pre>
                <div class="actions">
                    <button id="refreshIncidentSummaryBtn" type="button">Refresh Incident Summary</button>
                </div>
                <pre id="incidentSummaryView">Loading incident summary...</pre>
                <label>SLA threshold minutes<input id="incidentSlaThresholdInput" type="number" min="5" max="10080" value="120" /></label>
                <label>SLA alert cooldown minutes<input id="incidentSlaCooldownInput" type="number" min="1" max="1440" value="30" /></label>
                <div class="actions">
                    <button id="refreshIncidentSlaBtn" type="button">Refresh Incident SLA</button>
                    <button id="runIncidentSlaCheckBtn" type="button">Run Incident SLA Check</button>
                    <button id="refreshIncidentSlaRunsBtn" type="button">Refresh SLA Check Runs</button>
                </div>
                <pre id="incidentSlaView">Loading incident SLA...</pre>
                <pre id="incidentSlaCheckView">No SLA check run yet.</pre>
                <pre id="incidentSlaRunsView">Loading SLA check runs...</pre>
            </section>
        </main>
        <aside id="notifyPanel" class="notifications hidden" aria-live="polite">
            <h3>Notifications</h3>
            <ul id="notifyList">
                <li>System initialized for Phase 1 main website scaffold.</li>
            </ul>
        </aside>
    </div>
    <script>window.appCsrfToken = "<?php echo esc_js($csrfToken); ?>";</script>
    <script src="/assets/app.js"></script>
<?php endif; ?>
</body>
</html>
