param(
    [Parameter(Mandatory = $true)]
    [string]$BaseUrl,
    [Parameter(Mandatory = $false)]
    [string]$Email = "admin@5n2digital.com",
    [Parameter(Mandatory = $false)]
    [string]$Password = "change-me"
)

$ErrorActionPreference = "Stop"
$root = $BaseUrl.TrimEnd("/")
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

function Get-Json {
    param([string]$Url)
    $res = Invoke-WebRequest -Method Get -Uri $Url -WebSession $session -UseBasicParsing -TimeoutSec 20
    $obj = $null
    try {
        $obj = $res.Content | ConvertFrom-Json -Depth 20
    } catch {
        $obj = @{ parse_error = $true; raw = $res.Content }
    }
    return @{
        http = [int]$res.StatusCode
        body = $obj
    }
}

function Post-Json {
    param(
        [string]$Url,
        [hashtable]$Body,
        [string]$CsrfToken
    )
    $json = $Body | ConvertTo-Json -Depth 20
    $headers = @{
        "Content-Type" = "application/json"
        "X-CSRF-Token" = $CsrfToken
    }
    $res = Invoke-WebRequest -Method Post -Uri $Url -Headers $headers -Body $json -WebSession $session -UseBasicParsing -TimeoutSec 20
    $obj = $null
    try {
        $obj = $res.Content | ConvertFrom-Json -Depth 20
    } catch {
        $obj = @{ parse_error = $true; raw = $res.Content }
    }
    return @{
        http = [int]$res.StatusCode
        body = $obj
    }
}

Write-Host "Opening app landing page..."
$landing = Invoke-WebRequest -Method Get -Uri "$root/" -WebSession $session -UseBasicParsing -TimeoutSec 20

Write-Host "Signing in..."
$loginBody = @{
    action = "login"
    email = $Email
    password = $Password
}
$login = Invoke-WebRequest -Method Post -Uri "$root/" -Body $loginBody -WebSession $session -UseBasicParsing -MaximumRedirection 5 -TimeoutSec 20
$dashboard = Invoke-WebRequest -Method Get -Uri "$root/" -WebSession $session -UseBasicParsing -TimeoutSec 20

if ($dashboard.Content -match "Invalid credentials") {
    Write-Error "Login failed: invalid credentials."
}
if ($dashboard.Content -notmatch "window\.appCsrfToken") {
    Write-Error "Login failed: dashboard CSRF token not found."
}

$csrf = ""
if ($dashboard.Content -match 'window\.appCsrfToken\s*=\s*"([^"]+)"') {
    $csrf = $Matches[1]
}
if ([string]::IsNullOrWhiteSpace($csrf)) {
    Write-Error "Could not extract CSRF token from dashboard."
}

$checks = @(
    @{ name = "deployment.guard.status"; url = "$root/api/index.php?action=deployment.guard.status"; method = "GET" },
    @{ name = "deployment.pipeline.runs"; url = "$root/api/index.php?action=deployment.pipeline.runs"; method = "GET" },
    @{ name = "deployment.incident.reports"; url = "$root/api/index.php?action=deployment.incident.reports"; method = "GET" },
    @{ name = "deployment.incident.summary"; url = "$root/api/index.php?action=deployment.incident.summary"; method = "GET" },
    @{ name = "deployment.incident.sla.runs"; url = "$root/api/index.php?action=deployment.incident.sla.runs"; method = "GET" },
    @{ name = "deployment.guard.preview"; url = "$root/api/index.php?action=deployment.guard.preview"; method = "POST"; body = @{
            enforced = 1
            launch_window_enabled = 0
            launch_window_start = ""
            launch_window_end = ""
            checklist = @{
                backup_verified = 1
                cron_configured = 1
                rollback_plan_ready = 1
                dns_domain_ready = 1
            }
        }
    }
)

$results = @()
foreach ($c in $checks) {
    Write-Host ("Checking {0}..." -f $c.name)
    if ($c.method -eq "POST") {
        $resp = Post-Json -Url $c.url -Body $c.body -CsrfToken $csrf
    } else {
        $resp = Get-Json -Url $c.url
    }
    $ok = $true
    if ($resp.http -ge 500) {
        $ok = $false
    }
    if ($resp.body -and $resp.body.ok -eq $false) {
        $ok = $false
    }
    $results += @{
        endpoint = $c.name
        method = $c.method
        http = $resp.http
        ok = $ok
    }
}

$failed = @($results | Where-Object { -not $_.ok })
$reportPayload = @{
    smoke_type = "auth"
    ok = if ($failed.Count -gt 0) { 0 } else { 1 }
    source = "scripts_smoke_auth"
    note = if ($failed.Count -gt 0) { "Authenticated smoke script detected failures." } else { "Authenticated smoke script passed." }
    details = @{
        endpoint_count = $results.Count
        failed_count = $failed.Count
        checks = $results
    }
}

try {
    Write-Host "Recording smoke result..."
    $report = Post-Json -Url "$root/api/index.php?action=deployment.smoke.report" -Body $reportPayload -CsrfToken $csrf
    Write-Host ("Smoke result recorded: http={0}" -f $report.http)
} catch {
    Write-Warning ("Unable to record smoke result: {0}" -f $_.Exception.Message)
}

Write-Host ""
Write-Host "Smoke Auth Summary"
$results | ForEach-Object {
    Write-Host ("- {0} [{1}]: http={2} ok={3}" -f $_.endpoint, $_.method, $_.http, $_.ok)
}

if ($failed.Count -gt 0) {
    Write-Error ("Authenticated smoke check failed on {0} endpoint(s)." -f $failed.Count)
}

Write-Host "Authenticated smoke check passed."
