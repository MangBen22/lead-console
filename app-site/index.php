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
                </form>
            </section>

            <section class="api-status">
                <h2>CRM Push Pipeline</h2>
                <div class="actions">
                    <button id="runCrmSyncBtn" type="button">Run CRM Sync</button>
                </div>
                <pre id="crmSyncResult">No sync yet.</pre>
                <h3>Sync Log</h3>
                <pre id="crmSyncLog">Loading...</pre>
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
