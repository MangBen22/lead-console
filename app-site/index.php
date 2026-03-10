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
                <h3>Scheduler Gate Summary</h3>
                <pre id="schedulerGateSummaryView">Loading...</pre>
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
                <h2>Leads Inventory</h2>
                <div class="actions">
                    <button id="refreshLeadsInventoryBtn" type="button">Refresh Leads Inventory</button>
                    <button id="refreshLeadsListBtn" type="button">Refresh Leads List</button>
                    <button id="refreshLeadsPushPlanBtn" type="button">Refresh Push Plan</button>
                    <button id="runLeadsCrmSyncBtn" type="button">Run Leads Push</button>
                    <button id="runLeadsRetryQueueBtn" type="button">Run Leads Retry Queue</button>
                    <button id="downloadLeadsExportBtn" type="button">Download Leads Export</button>
                </div>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="leadsListSearch">Search</label>
                        <input id="leadsListSearch" type="text" placeholder="Business, city, category, email...">
                    </div>
                    <div class="form-row">
                        <label for="leadsListSiteFilter">Site ID</label>
                        <input id="leadsListSiteFilter" type="text" placeholder="Optional site filter">
                    </div>
                    <div class="form-row">
                        <label for="leadsListStatusFilter">Status</label>
                        <input id="leadsListStatusFilter" type="text" placeholder="Ready or Verified">
                    </div>
                    <div class="form-row">
                        <label for="leadsListLimit">Limit</label>
                        <input id="leadsListLimit" type="number" min="1" max="100" value="20">
                    </div>
                </div>
                <pre id="leadsInventoryView">Loading...</pre>
                <pre id="leadsListView">Loading...</pre>
                <pre id="leadsPushPlanView">Loading...</pre>
                <pre id="leadsPushResultView">No leads push action yet.</pre>
                <div class="actions">
                    <button id="refreshLeadsQualityBtn" type="button">Refresh Leads Quality</button>
                    <button id="refreshLeadsDeliveryHistoryBtn" type="button">Refresh Delivery History</button>
                </div>
                <pre id="leadsQualityView">Loading...</pre>
                <pre id="leadsDeliveryHistoryView">Loading...</pre>
                <div class="actions">
                    <button id="refreshLeadsReviewQueueBtn" type="button">Refresh Review Queue</button>
                    <button id="downloadLeadsReviewQueueBtn" type="button">Download Review Queue</button>
                    <button id="loadLeadsReviewDetailBtn" type="button">Load Review Detail</button>
                    <button id="checkLeadsReviewDuplicatesBtn" type="button">Check Review Duplicates</button>
                    <button id="saveLeadsReviewBtn" type="button">Save Review Drafts</button>
                    <button id="discardLeadsReviewBtn" type="button">Discard Review Drafts</button>
                    <button id="saveLeadsReviewQueueBtn" type="button">Save Queue Page</button>
                    <button id="discardLeadsReviewQueueBtn" type="button">Discard Queue Page</button>
                </div>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="leadsReviewStatusFilter">Review Status Filter</label>
                        <select id="leadsReviewStatusFilter">
                            <option value="pending">pending</option>
                            <option value="all">all</option>
                            <option value="saved">saved</option>
                            <option value="discarded">discarded</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewQueueSiteFilter">Queue Site Filter</label>
                        <input id="leadsReviewQueueSiteFilter" type="text" placeholder="Optional site filter">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewQueuePage">Queue Page</label>
                        <input id="leadsReviewQueuePage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewQueueLimit">Queue Limit</label>
                        <input id="leadsReviewQueueLimit" type="number" min="1" max="100" value="8">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewSiteId">Review Site ID</label>
                        <input id="leadsReviewSiteId" type="text" placeholder="hq-main">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewRunId">Review Run ID</label>
                        <input id="leadsReviewRunId" type="number" min="1" placeholder="123">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewDraftId">Review Draft ID</label>
                        <input id="leadsReviewDraftId" type="number" min="1" placeholder="456">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewDraftSelect">Review Draft Selector</label>
                        <select id="leadsReviewDraftSelect">
                            <option value="">Load review detail first</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewDraftBusiness">Draft Business Name</label>
                        <input id="leadsReviewDraftBusiness" type="text" placeholder="Business name">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewDraftCity">Draft City</label>
                        <input id="leadsReviewDraftCity" type="text" placeholder="City">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewDraftCategory">Draft Category</label>
                        <input id="leadsReviewDraftCategory" type="text" placeholder="Category">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewDraftWebsite">Draft Website</label>
                        <input id="leadsReviewDraftWebsite" type="text" placeholder="https://example.com">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewDraftPhone">Draft Phone</label>
                        <input id="leadsReviewDraftPhone" type="text" placeholder="Phone">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewDraftEmail">Draft Email</label>
                        <input id="leadsReviewDraftEmail" type="email" placeholder="name@example.com">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewDraftStatus">Draft Status</label>
                        <input id="leadsReviewDraftStatus" type="text" placeholder="New">
                    </div>
                    <div class="form-row">
                        <label for="leadsReviewDraftNotes">Draft Notes</label>
                        <textarea id="leadsReviewDraftNotes" rows="3" placeholder="Notes"></textarea>
                    </div>
                </div>
                <div class="actions">
                    <button id="updateLeadsReviewDraftBtn" type="button">Update Review Draft</button>
                </div>
                <pre id="leadsReviewQueueView">Loading...</pre>
                <pre id="leadsReviewDetailView">No review detail loaded.</pre>
                <pre id="leadsReviewDuplicatesView">No duplicate check yet.</pre>
                <pre id="leadsReviewActionView">No review action yet.</pre>
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
                    <button id="refreshCrmDeliverySummaryBtn" type="button">Refresh Delivery Summary</button>
                    <button id="downloadCrmDeliveryExportBtn" type="button">Download Delivery Export</button>
                    <button id="refreshCrmDeliveryWatchBtn" type="button">Refresh Delivery Watch</button>
                    <button id="runCrmDeliveryWatchBtn" type="button">Run Delivery Watch</button>
                </div>
                <pre id="crmSyncResult">No sync yet.</pre>
                <h3>Delivery Summary</h3>
                <pre id="crmDeliverySummary">Loading...</pre>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="crmDeliveryConnectorId">Delivery Connector ID</label>
                        <input id="crmDeliveryConnectorId" type="text" placeholder="connector_abc123">
                    </div>
                    <div class="form-row">
                        <label for="crmDeliveryConnectorLimit">Connector Detail Limit</label>
                        <input id="crmDeliveryConnectorLimit" type="number" min="1" max="50" value="10">
                    </div>
                    <button id="refreshCrmDeliveryDetailBtn" type="button">Refresh Connector Detail</button>
                </div>
                <h3>Delivery Connector Detail</h3>
                <pre id="crmDeliveryConnectorDetail">No connector detail loaded.</pre>
                <h3>Delivery Watch</h3>
                <pre id="crmDeliveryWatchView">Loading...</pre>
                <h3>Sync Log</h3>
                <pre id="crmSyncLog">Loading...</pre>
                <h3>Retry Queue</h3>
                <pre id="crmRetryQueue">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>CRM SMTP Operations</h2>
                <div class="actions">
                    <button id="refreshCrmSmtpBtn" type="button">Refresh SMTP Status</button>
                    <button id="refreshCrmSmtpWatchBtn" type="button">Refresh SMTP Watch</button>
                    <button id="runCrmSmtpWatchBtn" type="button">Run SMTP Watch</button>
                </div>
                <pre id="crmSmtpSummary">Loading...</pre>
                <form id="crmSmtpForm" class="inline-form">
                    <div class="form-row">
                        <label for="crmSmtpSiteId">Site ID</label>
                        <input id="crmSmtpSiteId" type="text" placeholder="hq-main">
                    </div>
                    <div class="form-row">
                        <label for="crmSmtpToEmail">Test email recipient</label>
                        <input id="crmSmtpToEmail" type="email" placeholder="name@example.com">
                    </div>
                    <button id="probeCrmSmtpBtn" type="button">Test Connection</button>
                    <button id="sendCrmSmtpTestBtn" type="button">Send Test Email</button>
                    <button id="confirmCrmSmtpYesBtn" type="button">Confirm Received</button>
                    <button id="confirmCrmSmtpNoBtn" type="button">Confirm Not Received</button>
                </form>
                <pre id="crmSmtpResult">No SMTP action yet.</pre>
                <h3>SMTP Watch</h3>
                <pre id="crmSmtpWatchView">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>CRM Email Templates</h2>
                <div class="actions">
                    <button id="refreshCrmEmailTemplatesBtn" type="button">Refresh Templates</button>
                    <button id="loadCrmEmailTemplateBtn" type="button">Load Selected Template</button>
                    <button id="saveCrmEmailTemplateBtn" type="button">Save Selected Template</button>
                    <button id="previewCrmEmailTemplateBtn" type="button">Preview Template</button>
                    <button id="sendCrmEmailTemplateTestBtn" type="button">Send Template Test</button>
                    <button id="refreshCrmEmailTemplateLogBtn" type="button">Refresh Test Log</button>
                </div>
                <label>Template site ID<input id="crmEmailTemplateSiteId" type="text" placeholder="hq-main"></label>
                <label>Template key
                    <select id="crmEmailTemplateKey">
                        <option value="reset">reset</option>
                        <option value="password_changed">password_changed</option>
                        <option value="admin_password_changed">admin_password_changed</option>
                        <option value="registration_received">registration_received</option>
                        <option value="registration_admin">registration_admin</option>
                        <option value="registration_approved">registration_approved</option>
                        <option value="registration_rejected">registration_rejected</option>
                    </select>
                </label>
                <label>Template test recipient<input id="crmEmailTemplateToEmail" type="email" placeholder="name@example.com"></label>
                <label>Template subject<input id="crmEmailTemplateSubject" type="text" placeholder="Email subject"></label>
                <label>Template body<textarea id="crmEmailTemplateBody" class="large-text code" rows="8" placeholder="Email body"></textarea></label>
                <pre id="crmEmailTemplatesSummary">Loading...</pre>
                <pre id="crmEmailTemplatesResult">No email template action yet.</pre>
                <pre id="crmEmailTemplatePreview">No preview yet.</pre>
                <pre id="crmEmailTemplateTestLog">Loading test log...</pre>
            </section>

            <section class="api-status">
                <h2>Social Platforms</h2>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialPlatformFilterFamily">Platform Family</label>
                        <input id="socialPlatformFilterFamily" type="text" placeholder="meta">
                    </div>
                    <div class="form-row">
                        <label for="socialPlatformFilterAuthMode">Auth Mode</label>
                        <input id="socialPlatformFilterAuthMode" type="text" placeholder="oauth2">
                    </div>
                    <div class="form-row">
                        <label for="socialPlatformFilterCapability">Capability</label>
                        <input id="socialPlatformFilterCapability" type="text" placeholder="can_schedule">
                    </div>
                    <div class="form-row">
                        <label for="socialPlatformFilterSearch">Platform Search</label>
                        <input id="socialPlatformFilterSearch" type="text" placeholder="provider or label">
                    </div>
                    <div class="form-row">
                        <label for="socialPlatformFilterPage">Platform Page</label>
                        <input id="socialPlatformFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="socialPlatformFilterLimit">Platform Limit</label>
                        <input id="socialPlatformFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                    <button id="refreshSocialPlatformsBtn" type="button">Refresh Social Platforms</button>
                    <button id="downloadSocialPlatformsExportBtn" type="button">Download Platform Export</button>
                </div>
                <pre id="socialPlatforms">Loading...</pre>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialPlatformDetailProvider">Platform Provider</label>
                        <input id="socialPlatformDetailProvider" type="text" placeholder="facebook">
                    </div>
                    <button id="loadSocialPlatformDetailBtn" type="button">Load Platform Detail</button>
                </div>
                <pre id="socialPlatformDetail">No social platform detail loaded.</pre>
                <form id="socialPlatformCoverageForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialPlatformCoverageFamily">Coverage Family</label>
                        <input id="socialPlatformCoverageFamily" type="text" placeholder="meta">
                    </div>
                    <div class="form-row">
                        <label for="socialPlatformCoverageSearch">Coverage Search</label>
                        <input id="socialPlatformCoverageSearch" type="text" placeholder="facebook">
                    </div>
                </form>
                <div class="actions">
                    <button id="refreshSocialPlatformCoverageBtn" type="button">Refresh Platform Coverage</button>
                    <button id="downloadSocialPlatformCoverageExportBtn" type="button">Download Platform Coverage</button>
                </div>
                <pre id="socialPlatformCoverage">No social platform coverage loaded.</pre>
            </section>

            <section class="api-status">
                <h2>Social Capability Map</h2>
                <div class="actions">
                    <button id="refreshSocialCapabilitiesBtn" type="button">Refresh Social Capability Map</button>
                </div>
                <pre id="socialCapabilitiesSummary">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Social Watch</h2>
                <div class="actions">
                    <button id="refreshSocialWatchBtn" type="button">Refresh Social Watch</button>
                    <button id="runSocialWatchBtn" type="button">Run Social Watch</button>
                </div>
                <pre id="socialWatchView">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Social Operations Snapshot</h2>
                <div class="actions">
                    <button id="refreshSocialOpsSnapshotBtn" type="button">Refresh Social Snapshot</button>
                </div>
                <pre id="socialOpsSnapshotView">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Social Draft Preview</h2>
                <div class="actions">
                    <button id="refreshSocialDraftsBtn" type="button">Refresh Social Drafts</button>
                    <button id="downloadSocialDraftsExportBtn" type="button">Download Draft Export</button>
                </div>
                <pre id="socialDraftsPreview">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Social Draft List</h2>
                <form id="socialDraftFilterForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialDraftFilterSite">Draft Site ID</label>
                        <input id="socialDraftFilterSite" type="text" placeholder="hq-main">
                    </div>
                    <div class="form-row">
                        <label for="socialDraftFilterHasUrl">Has URL</label>
                        <select id="socialDraftFilterHasUrl">
                            <option value="">any</option>
                            <option value="yes">yes</option>
                            <option value="no">no</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialDraftFilterSearch">Draft Search</label>
                        <input id="socialDraftFilterSearch" type="text" placeholder="title, message, lead id">
                    </div>
                    <div class="form-row">
                        <label for="socialDraftFilterPage">Draft Page</label>
                        <input id="socialDraftFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="socialDraftFilterLimit">Draft Limit</label>
                        <input id="socialDraftFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                </form>
                <div class="actions">
                    <button id="refreshSocialDraftListBtn" type="button">Refresh Draft List</button>
                </div>
                <pre id="socialDraftList">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Social Draft Detail</h2>
                <form id="socialDraftDetailForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialDraftDetailIndex">Draft Index</label>
                        <input id="socialDraftDetailIndex" type="number" min="0" placeholder="0">
                    </div>
                    <div class="form-row">
                        <label for="socialDraftDetailLeadId">Lead ID</label>
                        <input id="socialDraftDetailLeadId" type="number" min="1" placeholder="1001">
                    </div>
                </form>
                <div class="actions">
                    <button id="refreshSocialDraftDetailBtn" type="button">Load Draft Detail</button>
                </div>
                <pre id="socialDraftDetail">No social draft detail loaded.</pre>
            </section>

            <section class="api-status">
                <h2>Social Draft Recommendations</h2>
                <div class="actions">
                    <button id="refreshSocialDraftRecommendationsBtn" type="button">Refresh Draft Recommendations</button>
                </div>
                <pre id="socialDraftRecommendations">No social draft recommendations loaded.</pre>
            </section>

            <section class="api-status">
                <h2>Social Draft Variants</h2>
                <form id="socialDraftVariantForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialDraftVariantConnector">Connector ID Filter (optional)</label>
                        <input id="socialDraftVariantConnector" type="text" placeholder="social_abc123">
                    </div>
                </form>
                <div class="actions">
                    <button id="refreshSocialDraftVariantsBtn" type="button">Refresh Draft Variants</button>
                    <button id="downloadSocialDraftVariantsExportBtn" type="button">Download Draft Variants</button>
                </div>
                <pre id="socialDraftVariants">No social draft variants loaded.</pre>
            </section>

            <section class="api-status">
                <h2>Schedule Draft</h2>
                <form id="socialDraftScheduleForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialDraftScheduleIndex">Draft Index</label>
                        <input id="socialDraftScheduleIndex" type="number" min="0" placeholder="0">
                    </div>
                    <div class="form-row">
                        <label for="socialDraftScheduleLeadId">Lead ID</label>
                        <input id="socialDraftScheduleLeadId" type="number" min="1" placeholder="1001">
                    </div>
                    <div class="form-row">
                        <label for="socialDraftScheduleConnectorIds">Connector IDs (optional)</label>
                        <input id="socialDraftScheduleConnectorIds" type="text" placeholder="social_abc123,social_xyz789">
                    </div>
                    <div class="form-row">
                        <label for="socialDraftScheduleFor">Schedule For (optional)</label>
                        <input id="socialDraftScheduleFor" type="datetime-local">
                    </div>
                </form>
                <div class="actions">
                    <button id="runSocialDraftScheduleBtn" type="button">Create Schedule From Draft</button>
                </div>
                <pre id="socialDraftScheduleResult">No draft schedule action yet.</pre>
            </section>

            <section class="api-status">
                <h2>Bulk Schedule Draft Page</h2>
                <form id="socialDraftBulkScheduleForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialDraftBulkConnectorIds">Connector IDs (optional)</label>
                        <input id="socialDraftBulkConnectorIds" type="text" placeholder="social_abc123,social_xyz789">
                    </div>
                    <div class="form-row">
                        <label for="socialDraftBulkFor">Bulk Schedule Start (optional)</label>
                        <input id="socialDraftBulkFor" type="datetime-local">
                    </div>
                    <div class="form-row">
                        <label for="socialDraftBulkInterval">Interval Minutes</label>
                        <input id="socialDraftBulkInterval" type="number" min="0" max="1440" value="10">
                    </div>
                </form>
                <div class="actions">
                    <button id="runSocialDraftBulkScheduleBtn" type="button">Bulk Schedule Draft Page</button>
                </div>
                <pre id="socialDraftBulkScheduleResult">No bulk draft schedule action yet.</pre>
            </section>

            <section class="api-status">
                <h2>Social Draft Validation</h2>
                <div class="actions">
                    <button id="refreshSocialDraftValidationBtn" type="button">Refresh Draft Validation</button>
                </div>
                <pre id="socialDraftValidationView">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Social Draft Delivery Plan</h2>
                <div class="actions">
                    <button id="refreshSocialDraftPlanBtn" type="button">Refresh Draft Plan</button>
                </div>
                <pre id="socialDraftPlanView">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>Social Connectors</h2>
                <form id="socialConnectorFilterForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialConnectorFilterProvider">Provider</label>
                        <input id="socialConnectorFilterProvider" type="text" placeholder="facebook">
                    </div>
                    <div class="form-row">
                        <label for="socialConnectorFilterStatus">Status</label>
                        <select id="socialConnectorFilterStatus">
                            <option value="">any</option>
                            <option value="active">active</option>
                            <option value="planned">planned</option>
                            <option value="paused">paused</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialConnectorFilterSite">Site ID</label>
                        <input id="socialConnectorFilterSite" type="text" placeholder="hq-main">
                    </div>
                    <div class="form-row">
                        <label for="socialConnectorFilterRunMode">Run Mode</label>
                        <select id="socialConnectorFilterRunMode">
                            <option value="">any</option>
                            <option value="dry_run">dry_run</option>
                            <option value="live">live</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialConnectorFilterSearch">Search</label>
                        <input id="socialConnectorFilterSearch" type="text" placeholder="connector id or label">
                    </div>
                    <div class="form-row">
                        <label for="socialConnectorFilterPage">Page</label>
                        <input id="socialConnectorFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="socialConnectorFilterLimit">Limit</label>
                        <input id="socialConnectorFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                </form>
                <div class="actions">
                    <button id="refreshSocialConnectorsBtn" type="button">Refresh Social Connectors</button>
                    <button id="downloadSocialConnectorsExportBtn" type="button">Download Connector Export</button>
                </div>
                <pre id="socialConnectors">Loading...</pre>
                <form id="socialConnectorDetailForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialConnectorDetailId">Connector ID</label>
                        <input id="socialConnectorDetailId" type="text" placeholder="social_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialConnectorDetailLimit">Detail Limit</label>
                        <input id="socialConnectorDetailLimit" type="number" min="1" max="50" value="10">
                    </div>
                </form>
                <div class="actions">
                    <button id="refreshSocialConnectorDetailBtn" type="button">Load Connector Detail</button>
                </div>
                <pre id="socialConnectorDetail">No social connector detail loaded.</pre>
                <form id="socialConnectorRuleAuditForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialConnectorRuleAuditDraftLimit">Rule Audit Draft Limit</label>
                        <input id="socialConnectorRuleAuditDraftLimit" type="number" min="1" max="20" value="5">
                    </div>
                </form>
                <div class="actions">
                    <button id="refreshSocialConnectorRuleAuditBtn" type="button">Refresh Connector Rule Audit</button>
                    <button id="downloadSocialConnectorRuleAuditExportBtn" type="button">Download Connector Rule Audit</button>
                </div>
                <pre id="socialConnectorRuleAudit">No social connector rule audit loaded.</pre>
                <form id="socialConnectorRuleAuditDetailForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialConnectorRuleAuditDetailId">Connector ID</label>
                        <input id="socialConnectorRuleAuditDetailId" type="text" placeholder="social_abc123">
                    </div>
                </form>
                <div class="actions">
                    <button id="loadSocialConnectorRuleAuditDetailBtn" type="button">Load Audit Detail</button>
                </div>
                <pre id="socialConnectorRuleAuditDetail">No social connector rule audit detail loaded.</pre>
                <form id="socialConnectorBulkUpdateForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialConnectorBulkStatus">Bulk Status</label>
                        <select id="socialConnectorBulkStatus">
                            <option value="">leave as-is</option>
                            <option value="active">active</option>
                            <option value="planned">planned</option>
                            <option value="paused">paused</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialConnectorBulkSite">Bulk Site ID</label>
                        <input id="socialConnectorBulkSite" type="text" placeholder="hq-main">
                    </div>
                    <div class="form-row">
                        <label for="socialConnectorBulkRunMode">Bulk Run Mode</label>
                        <select id="socialConnectorBulkRunMode">
                            <option value="">leave as-is</option>
                            <option value="dry_run">dry_run</option>
                            <option value="live">live</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialConnectorBulkExpiresAt">Bulk Expiry</label>
                        <input id="socialConnectorBulkExpiresAt" type="datetime-local">
                    </div>
                </form>
                <div class="actions">
                    <button id="runSocialConnectorBulkUpdateBtn" type="button">Bulk Update Connectors</button>
                </div>
                <pre id="socialConnectorBulkResult">No social connector bulk update yet.</pre>
                <form id="socialConnectorForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialConnectorId">Connector ID (optional for update)</label>
                        <input id="socialConnectorId" type="text" placeholder="social_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialProvider">Provider</label>
                        <input id="socialProvider" type="text" placeholder="wordpress_social_bridge" required>
                    </div>
                    <pre id="socialProviderProfile">Select a provider to load defaults.</pre>
                    <div class="form-row">
                        <label for="socialAccountLabel">Account label</label>
                        <input id="socialAccountLabel" type="text" placeholder="5N2 Facebook Page">
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
                    <div class="form-row">
                        <label for="socialExpiresAt">Credential expiry (optional)</label>
                        <input id="socialExpiresAt" type="datetime-local">
                    </div>
                    <button id="saveSocialConnectorBtn" type="button">Save Social Connector</button>
                    <button id="deleteSocialConnectorBtn" type="button">Delete Social Connector</button>
                    <button id="testSocialConnectorBtn" type="button">Test Social Connector</button>
                </form>
            </section>

            <section class="api-status">
                <h2>Social Schedule Queue</h2>
                <div class="actions">
                    <button id="refreshSocialScheduleSummaryBtn" type="button">Refresh Schedule Health</button>
                    <button id="downloadSocialScheduleExportBtn" type="button">Download Schedule Export</button>
                </div>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialScheduleFilterStatus">Schedule Status</label>
                        <select id="socialScheduleFilterStatus">
                            <option value="">all</option>
                            <option value="queued">queued</option>
                            <option value="sent">sent</option>
                            <option value="failed">failed</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialScheduleFilterConnector">Connector ID</label>
                        <input id="socialScheduleFilterConnector" type="text" placeholder="social_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialScheduleFilterSearch">Schedule Search</label>
                        <input id="socialScheduleFilterSearch" type="text" placeholder="title or message">
                    </div>
                    <div class="form-row">
                        <label for="socialScheduleFilterPage">Schedule Page</label>
                        <input id="socialScheduleFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="socialScheduleFilterLimit">Schedule Limit</label>
                        <input id="socialScheduleFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                </div>
                <pre id="socialScheduleSummary">Loading...</pre>
                <pre id="socialScheduleQueue">Loading...</pre>
                <form id="socialScheduleDetailForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialScheduleDetailId">Detail Schedule ID</label>
                        <input id="socialScheduleDetailId" type="text" placeholder="social_schedule_abc123">
                    </div>
                    <button id="loadSocialScheduleDetailBtn" type="button">Load Schedule Detail</button>
                </form>
                <pre id="socialScheduleDetail">No schedule detail loaded.</pre>
                <form id="socialScheduleBulkForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialScheduleBulkStatus">Bulk Status</label>
                        <select id="socialScheduleBulkStatus">
                            <option value="">Keep current</option>
                            <option value="queued">queued</option>
                            <option value="sent">sent</option>
                            <option value="failed">failed</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialScheduleBulkFor">Bulk Schedule For</label>
                        <input id="socialScheduleBulkFor" type="datetime-local">
                    </div>
                    <button id="updateSocialScheduleBulkBtn" type="button">Update Schedule Page</button>
                </form>
                <form id="socialScheduleForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialScheduleId">Schedule ID (optional for update)</label>
                        <input id="socialScheduleId" type="text" placeholder="social_schedule_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialScheduleTitle">Title</label>
                        <input id="socialScheduleTitle" type="text" placeholder="Spotlight Post">
                    </div>
                    <div class="form-row">
                        <label for="socialScheduleMessage">Message</label>
                        <textarea id="socialScheduleMessage" rows="4" placeholder="Write the scheduled social post..." required></textarea>
                    </div>
                    <div class="form-row">
                        <label for="socialScheduleUrl">URL</label>
                        <input id="socialScheduleUrl" type="text" placeholder="https://example.com/post">
                    </div>
                    <div class="form-row">
                        <label for="socialScheduleConnectorIds">Connector IDs (comma, optional)</label>
                        <input id="socialScheduleConnectorIds" type="text" placeholder="social_abc123,social_xyz789">
                    </div>
                    <div class="form-row">
                        <label for="socialScheduleFor">Schedule for</label>
                        <input id="socialScheduleFor" type="datetime-local">
                    </div>
                    <button id="validateSocialScheduleTargetsBtn" type="button">Validate Schedule Targets</button>
                    <button id="saveSocialScheduleBtn" type="button">Save Scheduled Post</button>
                    <button id="deleteSocialScheduleBtn" type="button">Delete Scheduled Post</button>
                    <button id="runSocialScheduleBtn" type="button">Run Due Scheduled Posts</button>
                </form>
                <pre id="socialScheduleTargetValidation">No schedule target validation yet.</pre>
            </section>

            <section class="api-status">
                <h2>Social Activity Feed</h2>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialActivityFilterType">Activity Type</label>
                        <input id="socialActivityFilterType" type="text" placeholder="push_sync">
                    </div>
                    <div class="form-row">
                        <label for="socialActivityFilterRef">Activity Ref</label>
                        <input id="socialActivityFilterRef" type="text" placeholder="social_sync_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialActivityFilterSearch">Activity Search</label>
                        <input id="socialActivityFilterSearch" type="text" placeholder="message or meta">
                    </div>
                    <div class="form-row">
                        <label for="socialActivityFilterPage">Activity Page</label>
                        <input id="socialActivityFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="socialActivityFilterLimit">Activity Limit</label>
                        <input id="socialActivityFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                    <button id="refreshSocialActivityBtn" type="button">Refresh Social Activity</button>
                    <button id="downloadSocialActivityExportBtn" type="button">Download Activity Export</button>
                </div>
                <pre id="socialActivityFeed">Loading...</pre>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialActivityDetailId">Activity ID</label>
                        <input id="socialActivityDetailId" type="text" placeholder="social_activity_abc123">
                    </div>
                    <button id="loadSocialActivityDetailBtn" type="button">Load Activity Detail</button>
                </div>
                <pre id="socialActivityDetail">No social activity detail loaded.</pre>
            </section>

            <section class="api-status">
                <h2>Social Inbox</h2>
                <div class="actions">
                    <button id="refreshSocialInboxSummaryBtn" type="button">Refresh Inbox Summary</button>
                    <button id="refreshSocialInboxWorkloadBtn" type="button">Refresh Inbox Workload</button>
                    <button id="refreshSocialInboxWatchBtn" type="button">Refresh Inbox Watch</button>
                    <button id="runSocialInboxWatchBtn" type="button">Run Inbox Watch</button>
                    <button id="refreshSocialInboxBtn" type="button">Refresh Social Inbox</button>
                    <button id="downloadSocialInboxExportBtn" type="button">Download Inbox Export</button>
                </div>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialInboxFilterStatus">Inbox Status</label>
                        <select id="socialInboxFilterStatus">
                            <option value="">all</option>
                            <option value="open">open</option>
                            <option value="pending">pending</option>
                            <option value="replied">replied</option>
                            <option value="closed">closed</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialInboxFilterPriority">Inbox Priority</label>
                        <select id="socialInboxFilterPriority">
                            <option value="">all</option>
                            <option value="low">low</option>
                            <option value="normal">normal</option>
                            <option value="high">high</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialInboxFilterProvider">Inbox Provider</label>
                        <input id="socialInboxFilterProvider" type="text" placeholder="facebook">
                    </div>
                    <div class="form-row">
                        <label for="socialInboxFilterOwner">Inbox Owner</label>
                        <input id="socialInboxFilterOwner" type="text" placeholder="Ops team">
                    </div>
                    <div class="form-row">
                        <label for="socialInboxFilterSearch">Inbox Search</label>
                        <input id="socialInboxFilterSearch" type="text" placeholder="subject, name, thread id">
                    </div>
                    <div class="form-row">
                        <label for="socialInboxFilterPage">Inbox Page</label>
                        <input id="socialInboxFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="socialInboxFilterLimit">Inbox Limit</label>
                        <input id="socialInboxFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                </div>
                <pre id="socialInboxSummary">Loading...</pre>
                <pre id="socialInboxWorkload">Loading...</pre>
                <pre id="socialInboxWatchView">Loading...</pre>
                <pre id="socialInboxThreads">Loading...</pre>
                <form id="socialInboxDetailForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialInboxDetailThreadId">Detail Thread ID</label>
                        <input id="socialInboxDetailThreadId" type="text" placeholder="social_thread_abc123">
                    </div>
                    <button id="loadSocialInboxDetailBtn" type="button">Load Thread Detail</button>
                </form>
                <pre id="socialInboxThreadDetail">No thread detail loaded.</pre>
                <form id="socialInboxReplyForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialInboxThreadId">Thread ID</label>
                        <input id="socialInboxThreadId" type="text" placeholder="social_thread_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialInboxReplyMessage">Reply message</label>
                        <textarea id="socialInboxReplyMessage" rows="4" placeholder="Type the reply to send..."></textarea>
                    </div>
                    <button id="replySocialInboxBtn" type="button">Reply to Thread</button>
                </form>
                <form id="socialInboxUpdateForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialInboxUpdateThreadId">Thread ID</label>
                        <input id="socialInboxUpdateThreadId" type="text" placeholder="social_thread_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialInboxStatus">Status</label>
                        <select id="socialInboxStatus">
                            <option value="">Keep current</option>
                            <option value="open">open</option>
                            <option value="pending">pending</option>
                            <option value="replied">replied</option>
                            <option value="closed">closed</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialInboxPriority">Priority</label>
                        <select id="socialInboxPriority">
                            <option value="">Keep current</option>
                            <option value="low">low</option>
                            <option value="normal">normal</option>
                            <option value="high">high</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialInboxOwner">Owner</label>
                        <input id="socialInboxOwner" type="text" placeholder="Ops team">
                    </div>
                    <div class="form-row">
                        <label for="socialInboxInternalNote">Internal note</label>
                        <textarea id="socialInboxInternalNote" rows="4" placeholder="Internal handling note..."></textarea>
                    </div>
                    <button id="updateSocialInboxThreadBtn" type="button">Update Thread</button>
                </form>
                <form id="socialInboxBulkUpdateForm" class="inline-form">
                    <div class="form-row">
                        <label for="socialInboxBulkStatus">Bulk Status</label>
                        <select id="socialInboxBulkStatus">
                            <option value="">Keep current</option>
                            <option value="open">open</option>
                            <option value="pending">pending</option>
                            <option value="replied">replied</option>
                            <option value="closed">closed</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialInboxBulkPriority">Bulk Priority</label>
                        <select id="socialInboxBulkPriority">
                            <option value="">Keep current</option>
                            <option value="low">low</option>
                            <option value="normal">normal</option>
                            <option value="high">high</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialInboxBulkOwner">Bulk Owner</label>
                        <input id="socialInboxBulkOwner" type="text" placeholder="Ops team">
                    </div>
                    <div class="form-row">
                        <label for="socialInboxBulkNote">Bulk Internal Note</label>
                        <textarea id="socialInboxBulkNote" rows="3" placeholder="Bulk note to apply..."></textarea>
                    </div>
                    <button id="updateSocialInboxBulkBtn" type="button">Update Inbox Page</button>
                </form>
            </section>

            <section class="api-status">
                <h2>Social Push Pipeline</h2>
                <div class="actions">
                    <button id="runSocialSyncBtn" type="button">Run Social Sync</button>
                    <button id="runSocialRetryQueueBtn" type="button">Run Social Retry Queue</button>
                    <button id="refreshSocialDeliverySummaryBtn" type="button">Refresh Delivery Summary</button>
                    <button id="downloadSocialDeliveryExportBtn" type="button">Download Delivery Export</button>
                    <button id="refreshSocialDeliveryWatchBtn" type="button">Refresh Delivery Watch</button>
                    <button id="runSocialDeliveryWatchBtn" type="button">Run Delivery Watch</button>
                    <button id="downloadSocialDeliveryWatchExportBtn" type="button">Download Delivery Watch</button>
                </div>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialExecutionPreviewProvider">Preview Provider</label>
                        <input id="socialExecutionPreviewProvider" type="text" placeholder="facebook">
                    </div>
                    <div class="form-row">
                        <label for="socialExecutionPreviewConnector">Preview Connector ID</label>
                        <input id="socialExecutionPreviewConnector" type="text" placeholder="social_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialExecutionPreviewDraftLimit">Preview Draft Limit</label>
                        <input id="socialExecutionPreviewDraftLimit" type="number" min="1" max="20" value="5">
                    </div>
                    <button id="refreshSocialExecutionPreviewBtn" type="button">Refresh Execution Preview</button>
                    <button id="downloadSocialExecutionPreviewBtn" type="button">Download Execution Preview</button>
                </div>
                <pre id="socialSyncResult">No social sync yet.</pre>
                <h3>Social Execution Preview</h3>
                <pre id="socialExecutionPreview">No social execution preview loaded.</pre>
                <h3>Social Delivery Summary</h3>
                <pre id="socialDeliverySummary">Loading...</pre>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialDeliveryConnectorId">Delivery Connector ID</label>
                        <input id="socialDeliveryConnectorId" type="text" placeholder="social_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialDeliveryConnectorLimit">Connector Detail Limit</label>
                        <input id="socialDeliveryConnectorLimit" type="number" min="1" max="50" value="10">
                    </div>
                    <button id="refreshSocialDeliveryDetailBtn" type="button">Refresh Connector Detail</button>
                </div>
                <h3>Social Delivery Connector Detail</h3>
                <pre id="socialDeliveryConnectorDetail">No connector detail loaded.</pre>
                <h3>Social Delivery Watch</h3>
                <pre id="socialDeliveryWatchView">Loading...</pre>
                <h3>Social Sync Log</h3>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialSyncFilterStatus">Sync Status</label>
                        <input id="socialSyncFilterStatus" type="text" placeholder="queued_to_connectors">
                    </div>
                    <div class="form-row">
                        <label for="socialSyncFilterSource">Sync Source</label>
                        <input id="socialSyncFilterSource" type="text" placeholder="automation_retry">
                    </div>
                    <div class="form-row">
                        <label for="socialSyncFilterConnector">Sync Connector ID</label>
                        <input id="socialSyncFilterConnector" type="text" placeholder="social_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialSyncFilterSearch">Sync Search</label>
                        <input id="socialSyncFilterSearch" type="text" placeholder="sync id or retry id">
                    </div>
                    <div class="form-row">
                        <label for="socialSyncFilterPage">Sync Page</label>
                        <input id="socialSyncFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="socialSyncFilterLimit">Sync Limit</label>
                        <input id="socialSyncFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                    <button id="refreshSocialSyncLogBtn" type="button">Refresh Sync Log</button>
                    <button id="downloadSocialSyncExportBtn" type="button">Download Sync Export</button>
                </div>
                <pre id="socialSyncLog">Loading...</pre>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialSyncDetailId">Sync ID</label>
                        <input id="socialSyncDetailId" type="text" placeholder="social_sync_abc123">
                    </div>
                    <button id="loadSocialSyncDetailBtn" type="button">Load Sync Detail</button>
                </div>
                <pre id="socialSyncDetail">No social sync detail loaded.</pre>
                <h3>Social Retry Queue</h3>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialRetryFilterStatus">Retry Status</label>
                        <select id="socialRetryFilterStatus">
                            <option value="">all</option>
                            <option value="queued">queued</option>
                            <option value="failed_missing_connector">failed_missing_connector</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="socialRetryFilterConnector">Retry Connector ID</label>
                        <input id="socialRetryFilterConnector" type="text" placeholder="social_abc123">
                    </div>
                    <div class="form-row">
                        <label for="socialRetryFilterSource">Retry Source</label>
                        <input id="socialRetryFilterSource" type="text" placeholder="push_sync">
                    </div>
                    <div class="form-row">
                        <label for="socialRetryFilterErrorCode">Retry Error Code</label>
                        <input id="socialRetryFilterErrorCode" type="text" placeholder="missing_webhook_url">
                    </div>
                    <div class="form-row">
                        <label for="socialRetryFilterSearch">Retry Search</label>
                        <input id="socialRetryFilterSearch" type="text" placeholder="retry id or schedule id">
                    </div>
                    <div class="form-row">
                        <label for="socialRetryFilterPage">Retry Page</label>
                        <input id="socialRetryFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="socialRetryFilterLimit">Retry Limit</label>
                        <input id="socialRetryFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                    <div class="form-row">
                        <label for="socialRetryBulkAction">Bulk Action</label>
                        <select id="socialRetryBulkAction">
                            <option value="reset">reset queued state</option>
                            <option value="delete">delete items</option>
                        </select>
                    </div>
                    <button id="refreshSocialRetryQueueBtn" type="button">Refresh Retry Queue</button>
                    <button id="downloadSocialRetryExportBtn" type="button">Download Retry Export</button>
                    <button id="runSocialRetryBulkUpdateBtn" type="button">Apply Retry Bulk Action</button>
                </div>
                <pre id="socialRetryQueue">Loading...</pre>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="socialRetryDetailId">Retry ID</label>
                        <input id="socialRetryDetailId" type="text" placeholder="social_retry_abc123">
                    </div>
                    <button id="loadSocialRetryDetailBtn" type="button">Load Retry Detail</button>
                </div>
                <pre id="socialRetryDetail">No social retry detail loaded.</pre>
                <pre id="socialRetryBulkResult">No social retry bulk action yet.</pre>
            </section>

            <section class="api-status">
                <h2>WebOps Monitor Types</h2>
                <div class="actions">
                    <button id="refreshWebopsTypesBtn" type="button">Refresh WebOps Types</button>
                </div>
                <pre id="webopsTypes">Loading...</pre>
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
                            <option value="ssl_expiry">ssl_expiry</option>
                            <option value="dns_resolution">dns_resolution</option>
                            <option value="wp_heartbeat">wp_heartbeat</option>
                            <option value="update_health">update_health</option>
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
                <h2>WebOps Incidents</h2>
                <div class="actions">
                    <button id="refreshWebopsIncidentsBtn" type="button">Refresh WebOps Incidents</button>
                </div>
                <pre id="webopsIncidents">Loading...</pre>
                <form id="webopsIncidentForm" class="inline-form">
                    <div class="form-row">
                        <label for="webopsIncidentId">Incident ID</label>
                        <input id="webopsIncidentId" type="text" placeholder="webops_incident_abc123">
                    </div>
                    <div class="form-row">
                        <label for="webopsIncidentNote">Resolution note</label>
                        <textarea id="webopsIncidentNote" rows="3" placeholder="Why was this incident resolved?"></textarea>
                    </div>
                    <button id="resolveWebopsIncidentBtn" type="button">Resolve Incident</button>
                </form>
            </section>

            <section class="api-status">
                <h2>WebOps Action Queue</h2>
                <div class="actions">
                    <button id="refreshWebopsActionsBtn" type="button">Refresh WebOps Actions</button>
                    <button id="runWebopsActionsBtn" type="button">Run WebOps Actions</button>
                </div>
                <pre id="webopsActionsQueue">Loading...</pre>
                <form id="webopsActionForm" class="inline-form">
                    <div class="form-row">
                        <label for="webopsActionSiteId">Bridge Site ID</label>
                        <input id="webopsActionSiteId" type="text" placeholder="hq-main">
                    </div>
                    <div class="form-row">
                        <label for="webopsActionType">Action Type</label>
                        <select id="webopsActionType">
                            <option value="plugin_toggle">plugin_toggle</option>
                            <option value="update_check">update_check</option>
                            <option value="maintenance_mode">maintenance_mode</option>
                            <option value="rollback_prepare">rollback_prepare</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="webopsActionPluginFile">Plugin File (for plugin_toggle)</label>
                        <input id="webopsActionPluginFile" type="text" placeholder="hello-dolly/hello.php">
                    </div>
                    <div class="form-row">
                        <label for="webopsActionDesiredState">Desired State</label>
                        <select id="webopsActionDesiredState">
                            <option value="deactivate">deactivate</option>
                            <option value="activate">activate</option>
                            <option value="check">check</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="webopsActionRunMode">Run Mode</label>
                        <select id="webopsActionRunMode">
                            <option value="dry_run">dry_run</option>
                            <option value="live">live</option>
                        </select>
                    </div>
                    <button id="enqueueWebopsActionBtn" type="button">Queue WebOps Action</button>
                </form>
                <h3>WebOps Action Log</h3>
                <pre id="webopsActionsLog">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>WebOps Posture Snapshot</h2>
                <div class="actions">
                    <button id="refreshWebopsPostureBtn" type="button">Refresh WebOps Posture</button>
                </div>
                <pre id="webopsPostureView">Loading...</pre>
            </section>

            <section class="api-status">
                <h2>SEO Projects</h2>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="seoProjectFilterStatus">Project Status</label>
                        <select id="seoProjectFilterStatus">
                            <option value="">all</option>
                            <option value="active">active</option>
                            <option value="paused">paused</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="seoProjectFilterSearch">Project Search</label>
                        <input id="seoProjectFilterSearch" type="text" placeholder="project id, name, domain">
                    </div>
                    <div class="form-row">
                        <label for="seoProjectFilterPage">Project Page</label>
                        <input id="seoProjectFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="seoProjectFilterLimit">Project Limit</label>
                        <input id="seoProjectFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                    <button id="refreshSeoProjectsBtn" type="button">Refresh SEO Projects</button>
                    <button id="downloadSeoProjectsExportBtn" type="button">Download SEO Projects</button>
                </div>
                <pre id="seoProjects">Loading...</pre>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="seoProjectDetailId">Detail Project ID</label>
                        <input id="seoProjectDetailId" type="text" placeholder="seo_project_abc123">
                    </div>
                    <button id="loadSeoProjectDetailBtn" type="button">Load Project Detail</button>
                </div>
                <pre id="seoProjectDetail">No SEO project detail loaded.</pre>
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
                <div class="actions">
                    <button id="refreshSeoIssuesBtn" type="button">Refresh SEO Issues</button>
                    <button id="refreshSeoHistoryBtn" type="button">Refresh SEO History</button>
                    <button id="refreshSeoExtensionSummaryBtn" type="button">Refresh Extension Summary</button>
                    <button id="downloadSeoReportBtn" type="button">Download SEO Report</button>
                </div>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="seoAuditFilterProjectId">Audit Project ID</label>
                        <input id="seoAuditFilterProjectId" type="text" placeholder="seo_project_abc123">
                    </div>
                    <div class="form-row">
                        <label for="seoAuditFilterSource">Audit Source</label>
                        <input id="seoAuditFilterSource" type="text" placeholder="manual|browser_extension">
                    </div>
                    <div class="form-row">
                        <label for="seoAuditFilterSearch">Audit Search</label>
                        <input id="seoAuditFilterSearch" type="text" placeholder="audit id, url, project">
                    </div>
                    <div class="form-row">
                        <label for="seoAuditFilterMinScore">Min Score</label>
                        <input id="seoAuditFilterMinScore" type="number" min="0" max="100" placeholder="0">
                    </div>
                    <div class="form-row">
                        <label for="seoAuditFilterMaxScore">Max Score</label>
                        <input id="seoAuditFilterMaxScore" type="number" min="0" max="100" placeholder="100">
                    </div>
                    <div class="form-row">
                        <label for="seoAuditFilterPage">Audit Page</label>
                        <input id="seoAuditFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="seoAuditFilterLimit">Audit Limit</label>
                        <input id="seoAuditFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                    <button id="refreshSeoAuditsBtn" type="button">Refresh SEO Audits</button>
                    <button id="downloadSeoAuditsExportBtn" type="button">Download SEO Audits</button>
                </div>
                <h3>Issue Summary</h3>
                <pre id="seoIssuesSummary">Loading...</pre>
                <h3>History Summary</h3>
                <pre id="seoHistorySummary">Loading...</pre>
                <h3>Extension Summary</h3>
                <pre id="seoExtensionSummary">Loading...</pre>
                <h3>SEO Audits</h3>
                <pre id="seoAudits">Loading...</pre>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="seoAuditDetailId">Audit Detail ID</label>
                        <input id="seoAuditDetailId" type="text" placeholder="seo_audit_abc123">
                    </div>
                    <button id="loadSeoAuditDetailBtn" type="button">Load Audit Detail</button>
                </div>
                <pre id="seoAuditDetail">No SEO audit detail loaded.</pre>
                <h3>Extension Events</h3>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="seoExtensionEventFilterProjectId">Event Project ID</label>
                        <input id="seoExtensionEventFilterProjectId" type="text" placeholder="seo_project_abc123">
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionEventFilterSessionId">Event Session ID</label>
                        <input id="seoExtensionEventFilterSessionId" type="text" placeholder="seo_ext_session_abc123">
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionEventFilterSearch">Event Search</label>
                        <input id="seoExtensionEventFilterSearch" type="text" placeholder="event id, url, title">
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionEventFilterMinScore">Min Score</label>
                        <input id="seoExtensionEventFilterMinScore" type="number" min="0" max="100" placeholder="0">
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionEventFilterMaxScore">Max Score</label>
                        <input id="seoExtensionEventFilterMaxScore" type="number" min="0" max="100" placeholder="100">
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionEventFilterPage">Event Page</label>
                        <input id="seoExtensionEventFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionEventFilterLimit">Event Limit</label>
                        <input id="seoExtensionEventFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                    <button id="refreshSeoExtensionEventsBtn" type="button">Refresh Extension Events</button>
                    <button id="downloadSeoExtensionEventsExportBtn" type="button">Download Extension Events</button>
                </div>
                <pre id="seoExtensionEvents">Loading...</pre>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="seoExtensionEventDetailId">Event Detail ID</label>
                        <input id="seoExtensionEventDetailId" type="text" placeholder="seo_ext_abc123">
                    </div>
                    <button id="loadSeoExtensionEventDetailBtn" type="button">Load Event Detail</button>
                </div>
                <pre id="seoExtensionEventDetail">No SEO extension event detail loaded.</pre>
            </section>

            <section class="api-status">
                <h2>SEO Extension Sessions</h2>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="seoExtensionSessionFilterProjectId">Session Project ID</label>
                        <input id="seoExtensionSessionFilterProjectId" type="text" placeholder="seo_project_abc123">
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionSessionFilterStatus">Session Status</label>
                        <select id="seoExtensionSessionFilterStatus">
                            <option value="">all</option>
                            <option value="active">active</option>
                            <option value="revoked">revoked</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionSessionFilterSearch">Session Search</label>
                        <input id="seoExtensionSessionFilterSearch" type="text" placeholder="session id, label, project">
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionSessionFilterPage">Session Page</label>
                        <input id="seoExtensionSessionFilterPage" type="number" min="1" value="1">
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionSessionFilterLimit">Session Limit</label>
                        <input id="seoExtensionSessionFilterLimit" type="number" min="1" max="100" value="10">
                    </div>
                    <button id="refreshSeoExtensionSessionsBtn" type="button">Refresh Sessions</button>
                    <button id="downloadSeoExtensionSessionsExportBtn" type="button">Download Sessions</button>
                </div>
                <pre id="seoExtensionSessions">Loading...</pre>
                <div class="inline-form">
                    <div class="form-row">
                        <label for="seoExtensionSessionDetailId">Session Detail ID</label>
                        <input id="seoExtensionSessionDetailId" type="text" placeholder="seo_ext_session_abc123">
                    </div>
                    <button id="loadSeoExtensionSessionDetailBtn" type="button">Load Session Detail</button>
                </div>
                <pre id="seoExtensionSessionDetail">No SEO extension session detail loaded.</pre>
                <form id="seoExtensionSessionForm" class="inline-form">
                    <div class="form-row">
                        <label for="seoExtensionSessionProjectId">Project ID (optional)</label>
                        <input id="seoExtensionSessionProjectId" type="text" placeholder="seo_project_abc123">
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionSessionLabel">Session Label</label>
                        <input id="seoExtensionSessionLabel" type="text" placeholder="Chrome Laptop Extension">
                    </div>
                    <div class="form-row">
                        <label for="seoExtensionSessionId">Session ID (for revoke)</label>
                        <input id="seoExtensionSessionId" type="text" placeholder="seo_ext_session_abc123">
                    </div>
                    <button id="createSeoExtensionSessionBtn" type="button">Create Extension Session</button>
                    <button id="revokeSeoExtensionSessionBtn" type="button">Revoke Extension Session</button>
                </form>
            </section>

            <section class="api-status">
                <h2>SEO Project Snapshot</h2>
                <div class="actions">
                    <button id="refreshSeoProjectSnapshotBtn" type="button">Refresh Project Snapshot</button>
                    <button id="refreshSeoActionPlanBtn" type="button">Refresh Action Plan</button>
                    <button id="refreshSeoOpportunitiesBtn" type="button">Refresh Opportunities</button>
                    <button id="refreshSeoRegressionsBtn" type="button">Refresh Regressions</button>
                    <button id="runSeoRegressionsBtn" type="button">Run Regression Watch</button>
                    <button id="downloadSeoRegressionsExportBtn" type="button">Download Regressions</button>
                    <button id="refreshSeoUrlHistoryBtn" type="button">Refresh URL History</button>
                    <button id="refreshSeoCompareBtn" type="button">Refresh Audit Compare</button>
                </div>
                <label>URL history filter<input id="seoHistoryUrlFilter" type="text" placeholder="https://example.com/service-page"></label>
                <pre id="seoProjectSnapshot">Loading...</pre>
                <h3>Action Plan</h3>
                <pre id="seoActionPlanView">Loading...</pre>
                <h3>Opportunities</h3>
                <pre id="seoOpportunitiesView">Loading...</pre>
                <h3>Regression Watch</h3>
                <pre id="seoRegressionView">Loading...</pre>
                <h3>URL History</h3>
                <pre id="seoUrlHistoryView">Loading...</pre>
                <h3>Latest Audit Compare</h3>
                <pre id="seoCompareView">Loading...</pre>
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
                <label><input id="releaseGateRequireReadinessInput" type="checkbox" value="1" checked /> Require cutover readiness = ready</label>
                <label><input id="releaseGateRequirePublicSmokeInput" type="checkbox" value="1" checked /> Require recent passing public smoke</label>
                <label><input id="releaseGateRequireAuthSmokeInput" type="checkbox" value="1" checked /> Require recent passing auth smoke</label>
                <label><input id="releaseGateRequireCutoverSignoffInput" type="checkbox" value="1" /> Require recent cutover signoff</label>
                <label><input id="releaseGateRequireSignoffIntegrityInput" type="checkbox" value="1" checked /> Require active signoff integrity valid</label>
                <label><input id="releaseGateRequireSignoffIntegrityWatchInput" type="checkbox" value="1" /> Require recent valid signoff integrity watch</label>
                <label><input id="releaseGateRequirePolicyBaselineMatchInput" type="checkbox" value="1" /> Require watchdogs policy baseline match</label>
                <label><input id="releaseGateRequirePolicyBaselineCheckInput" type="checkbox" value="1" /> Require recent watchdogs baseline check</label>
                <div class="actions">
                    <button id="refreshGoLiveStatusBtn" type="button">Refresh Go-Live Status</button>
                    <button id="refreshReleaseGateBtn" type="button">Refresh Release Gate</button>
                    <button id="saveReleaseGateSettingsBtn" type="button">Save Release Gate Settings</button>
                    <button id="runReleaseGateWatchBtn" type="button">Run Gate Watch</button>
                    <button id="refreshReleaseGateRunsBtn" type="button">Refresh Gate Runs</button>
                    <button id="refreshWatchdogsStatusBtn" type="button">Refresh Watchdogs</button>
                    <button id="runWatchdogsCheckBtn" type="button">Run Watchdogs Check</button>
                    <button id="refreshWatchdogsRunsBtn" type="button">Refresh Watchdogs Runs</button>
                    <button id="refreshWatchdogsIncidentBtn" type="button">Refresh Watchdogs Incident</button>
                    <button id="refreshWatchdogsIncidentSummaryBtn" type="button">Refresh Watchdogs Incident Summary</button>
                    <button id="resolveWatchdogsIncidentBtn" type="button">Resolve Watchdogs Incident</button>
                    <button id="reopenWatchdogsIncidentBtn" type="button">Reopen Watchdogs Incident</button>
                </div>
                <div class="inline-form">
                    <label>Gate runs limit<input id="releaseGateRunsLimitInput" type="number" min="1" max="400" value="200" /></label>
                    <label>Gate runs window<input id="releaseGateRunsWindowInput" type="number" min="0" max="400" value="0" /></label>
                    <label>Gate status filter
                        <select id="releaseGateRunsAllowedFilter">
                            <option value="all">all</option>
                            <option value="allowed">allowed</option>
                            <option value="blocked">blocked</option>
                        </select>
                    </label>
                    <label>Sustained filter
                        <select id="releaseGateRunsSustainedFilter">
                            <option value="all">all</option>
                            <option value="active">active</option>
                            <option value="clear">clear</option>
                        </select>
                    </label>
                    <label>Sustained alert filter
                        <select id="releaseGateRunsSustainedAlertFilter">
                            <option value="all">all</option>
                            <option value="sent">sent</option>
                            <option value="not_sent">not_sent</option>
                        </select>
                    </label>
                    <label>Status change filter
                        <select id="releaseGateRunsStatusChangeFilter">
                            <option value="all">all</option>
                            <option value="changed">changed</option>
                            <option value="stable">stable</option>
                        </select>
                    </label>
                    <label>Transition to filter
                        <select id="releaseGateRunsTransitionToFilter">
                            <option value="all">all</option>
                            <option value="to_blocked">to_blocked</option>
                            <option value="to_allowed">to_allowed</option>
                        </select>
                    </label>
                    <label>Source group filter
                        <select id="releaseGateRunsSourceGroupFilter">
                            <option value="all">all</option>
                            <option value="scheduler">scheduler</option>
                            <option value="manual">manual</option>
                        </select>
                    </label>
                    <label>Gate source filter<input id="releaseGateRunsSourceFilter" type="text" placeholder="Optional source (e.g. scheduler_tick_run)" /></label>
                    <label>Source contains<input id="releaseGateRunsSourceContainsFilter" type="text" placeholder="Optional partial source text" /></label>
                    <label>Gate failed item filter<input id="releaseGateRunsFailedItemFilter" type="text" placeholder="Optional failed item (e.g. readiness_ready)" /></label>
                    <label>Failed item mode
                        <select id="releaseGateRunsFailedItemMode">
                            <option value="exact">exact</option>
                            <option value="contains">contains</option>
                        </select>
                    </label>
                    <label>Reason count min<input id="releaseGateRunsReasonMinInput" type="number" min="0" max="50" placeholder="Optional min" /></label>
                    <label>Reason count max<input id="releaseGateRunsReasonMaxInput" type="number" min="0" max="50" placeholder="Optional max" /></label>
                    <label>Recent ratio min %<input id="releaseGateRunsRecentRatioMinInput" type="number" min="0" max="100" step="0.1" placeholder="Optional min ratio" /></label>
                    <label>Sustained transitions<input id="releaseGateRunsTransitionLimitInput" type="number" min="1" max="50" value="12" /></label>
                </div>
                <div class="actions">
                    <button id="applyReleaseGateRunsFilterBtn" type="button">Apply Gate Filters</button>
                    <button id="clearReleaseGateRunsFilterBtn" type="button">Clear Gate Filters</button>
                    <button id="refreshReleaseGateRunsMetaBtn" type="button">Refresh Gate Meta</button>
                    <button id="refreshReleaseGateQuickstatsBtn" type="button">Refresh Quickstats</button>
                    <button id="downloadReleaseGateQuickstatsBtn" type="button">Download Quickstats</button>
                    <button id="refreshReleaseGateOpsSnapshotBtn" type="button">Refresh Ops Snapshot</button>
                    <button id="downloadReleaseGateOpsSnapshotBtn" type="button">Download Ops Snapshot</button>
                    <button id="downloadReleaseGateRunsBtn" type="button">Download Gate Runs</button>
                    <button id="downloadReleaseGateBlockerReportBtn" type="button">Download Blocker Report</button>
                </div>
                <div class="actions">
                    <button id="applyGatePresetBaselineMatchBtn" type="button">Preset: Baseline Match</button>
                    <button id="applyGatePresetBaselineCheckBtn" type="button">Preset: Baseline Check</button>
                    <button id="applyGatePresetSignoffWatchBtn" type="button">Preset: Signoff Watch</button>
                    <button id="applyGatePresetSchedulerBlockedBtn" type="button">Preset: Scheduler Blocked</button>
                    <button id="applyGatePresetManualBlockedBtn" type="button">Preset: Manual Blocked</button>
                    <button id="applyGatePresetSustainedActiveBtn" type="button">Preset: Sustained Active</button>
                    <button id="applyGatePresetSustainedClearBtn" type="button">Preset: Sustained Clear</button>
                    <button id="applyGatePresetSustainedAlertedBtn" type="button">Preset: Sustained Alerted</button>
                    <button id="applyGatePresetStatusChangedBtn" type="button">Preset: Status Changed</button>
                    <button id="applyGatePresetSchedulerGroupBtn" type="button">Preset: Scheduler Group</button>
                    <button id="applyGatePresetManualGroupBtn" type="button">Preset: Manual Group</button>
                    <button id="applyGatePresetHighNoiseBtn" type="button">Preset: High Noise</button>
                    <button id="applyGatePresetLowNoiseBtn" type="button">Preset: Low Noise</button>
                    <button id="applyGatePresetToBlockedBtn" type="button">Preset: To Blocked</button>
                    <button id="applyGatePresetToAllowedBtn" type="button">Preset: To Allowed</button>
                    <button id="applyGatePresetSevereRatioBtn" type="button">Preset: Severe Ratio</button>
                </div>
                <label>Watchdogs incident note<input id="watchdogsIncidentNoteInput" type="text" placeholder="Optional note for resolve/reopen watchdog incident actions..." /></label>
                <label>Auto-incident threshold (critical streak)<input id="watchdogsAutoIncidentThresholdInput" type="number" min="1" max="10" value="2" /></label>
                <label>Auto-resolve threshold (OK streak)<input id="watchdogsAutoResolveThresholdInput" type="number" min="1" max="10" value="2" /></label>
                <label>Policy history ID (optional for restore)<input id="watchdogsPolicyHistoryIdInput" type="text" placeholder="watchdogs_policy_YYYYMMDD_HHMMSS_xxxxxx" /></label>
                <label>Restore mode
                    <select id="watchdogsPolicyRestoreMode">
                        <option value="previous">previous</option>
                        <option value="current">current</option>
                    </select>
                </label>
                <div class="actions">
                    <button id="saveWatchdogsPolicyBtn" type="button">Save Watchdogs Policy</button>
                    <button id="previewWatchdogsPolicyRestoreBtn" type="button">Preview Restore Diff</button>
                    <button id="restoreWatchdogsPolicyBtn" type="button">Restore Watchdogs Policy</button>
                    <button id="refreshWatchdogsPolicyHistoryBtn" type="button">Refresh Watchdogs Policy History</button>
                </div>
                <div class="actions">
                    <button id="saveWatchdogsPolicyBaselineBtn" type="button">Save Policy Baseline</button>
                    <button id="refreshWatchdogsPolicyBaselineBtn" type="button">Refresh Baseline Drift</button>
                    <button id="clearWatchdogsPolicyBaselineBtn" type="button">Clear Policy Baseline</button>
                    <button id="runWatchdogsPolicyBaselineCheckBtn" type="button">Run Baseline Check</button>
                    <button id="refreshWatchdogsPolicyBaselineRunsBtn" type="button">Refresh Baseline Checks</button>
                </div>
                <pre id="goLiveStatusView">Loading...</pre>
                <pre id="releaseGateSettingsView">Loading release gate settings...</pre>
                <pre id="releaseGateView">Loading release gate...</pre>
                <pre id="releaseGateWatchView">No gate watch run yet.</pre>
                <pre id="releaseGateQuickstatsView">Loading gate quickstats...</pre>
                <pre id="releaseGateOpsSnapshotView">Loading gate operations snapshot...</pre>
                <pre id="releaseGateRunsView">Loading gate watch runs...</pre>
                <pre id="releaseGateRunSummaryView">Loading gate watch summary...</pre>
                <pre id="releaseGateRunDigestView">Loading gate watch digest...</pre>
                <pre id="releaseGateRunsMetaView">Loading gate filter metadata...</pre>
                <pre id="releaseGateSustainedView">Loading sustained gate trend...</pre>
                <pre id="watchdogsStatusView">Loading watchdogs status...</pre>
                <pre id="watchdogsCheckView">No watchdogs check run yet.</pre>
                <pre id="watchdogsRunsView">Loading watchdogs check runs...</pre>
                <pre id="watchdogsIncidentView">Loading watchdogs incident...</pre>
                <pre id="watchdogsIncidentSummaryView">Loading watchdogs incident summary...</pre>
                <pre id="watchdogsPolicyView">Loading watchdogs policy...</pre>
                <pre id="watchdogsPolicyPreviewView">No restore preview yet.</pre>
                <pre id="watchdogsPolicyBaselineView">Loading watchdogs policy baseline...</pre>
                <pre id="watchdogsPolicyBaselineCheckView">Loading watchdogs policy baseline checks...</pre>
                <pre id="watchdogsPolicyHistoryView">Loading watchdogs policy history...</pre>
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
                <label>Signoff note<input id="cutoverSignoffNote" type="text" placeholder="Optional note for cutover signoff record..." /></label>
                <label>Signoff ID<input id="cutoverSignoffIdInput" type="text" placeholder="signoff_YYYYMMDD_HHMMSS_xxxxxx" /></label>
                <label>Signoff action reason<input id="cutoverSignoffReasonInput" type="text" placeholder="Reason for signoff revoke..." /></label>
                <div class="actions">
                    <button id="refreshCutoverReadinessBtn" type="button">Refresh Readiness</button>
                    <button id="runSmokeSuiteBtn" type="button">Run Full Smoke Suite</button>
                    <button id="downloadSmokeHistoryBtn" type="button">Download Smoke History</button>
                    <button id="downloadCutoverEvidenceBtn" type="button">Download Cutover Evidence</button>
                    <button id="createCutoverSignoffBtn" type="button">Create Cutover Signoff</button>
                    <button id="refreshCutoverSignoffsBtn" type="button">Refresh Signoffs</button>
                    <button id="downloadLatestSignoffBtn" type="button">Download Latest Signoff</button>
                    <button id="refreshActiveSignoffBtn" type="button">Refresh Active Signoff</button>
                    <button id="activateSignoffBtn" type="button">Activate Signoff</button>
                    <button id="revokeSignoffBtn" type="button">Revoke Signoff</button>
                    <button id="verifyLatestSignoffBtn" type="button">Verify Latest Signoff</button>
                    <button id="verifyAllSignoffsBtn" type="button">Verify All Signoffs</button>
                    <button id="runSignoffIntegrityWatchBtn" type="button">Run Signoff Integrity Watch</button>
                    <button id="refreshSignoffIntegrityRunsBtn" type="button">Refresh Integrity Watch Runs</button>
                    <button id="recordPublicSmokePassBtn" type="button">Record Public Smoke Pass</button>
                    <button id="recordPublicSmokeFailBtn" type="button">Record Public Smoke Fail</button>
                    <button id="recordAuthSmokePassBtn" type="button">Record Auth Smoke Pass</button>
                    <button id="recordAuthSmokeFailBtn" type="button">Record Auth Smoke Fail</button>
                </div>
                <pre id="cutoverReadinessView">Loading readiness...</pre>
                <pre id="cutoverSmokeHistoryView">Loading smoke history...</pre>
                <pre id="cutoverSmokeRecordView">No smoke record action yet.</pre>
                <pre id="cutoverSignoffResultView">No cutover signoff action yet.</pre>
                <pre id="cutoverSignoffListView">Loading cutover signoffs...</pre>
                <pre id="cutoverActiveSignoffView">Loading active cutover signoff...</pre>
                <pre id="cutoverSignoffVerifyView">No signoff verification yet.</pre>
                <pre id="cutoverSignoffIntegrityWatchView">No signoff integrity watch run yet.</pre>
                <pre id="cutoverSignoffIntegrityRunsView">Loading signoff integrity watch runs...</pre>
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
