param(
    [Parameter(Mandatory = $true)]
    [string]$BaseUrl
)

$ErrorActionPreference = "Stop"

function Invoke-JsonGet {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Url
    )
    $res = Invoke-WebRequest -Method Get -Uri $Url -UseBasicParsing -TimeoutSec 20
    $json = $null
    try {
        $json = $res.Content | ConvertFrom-Json -Depth 10
    } catch {
        $json = @{ parse_error = $true; raw = $res.Content }
    }
    return @{
        http = [int]$res.StatusCode
        body = $json
    }
}

$root = $BaseUrl.TrimEnd("/")
$targets = @(
    @{ name = "status"; url = "$root/api/index.php?action=status" },
    @{ name = "healthcheck"; url = "$root/api/index.php?action=healthcheck" },
    @{ name = "install.check"; url = "$root/api/index.php?action=install.check" },
    @{ name = "deployment.preflight"; url = "$root/api/index.php?action=deployment.preflight" }
)

$results = @()
foreach ($t in $targets) {
    Write-Host "Checking $($t.name) ..."
    $result = Invoke-JsonGet -Url $t.url
    $body = $result.body
    $ok = $true
    if ($result.http -ge 500) {
        $ok = $false
    }
    if ($body -and $body.ok -eq $false) {
        $ok = $false
    }
    $results += @{
        endpoint = $t.name
        url = $t.url
        http = $result.http
        ok = $ok
        status = if ($body -and $body.status) { [string]$body.status } else { "" }
    }
}

$failed = @($results | Where-Object { -not $_.ok })

Write-Host ""
Write-Host "Smoke Deploy Summary"
$results | ForEach-Object {
    Write-Host ("- {0}: http={1} ok={2} status={3}" -f $_.endpoint, $_.http, $_.ok, $_.status)
}

if ($failed.Count -gt 0) {
    Write-Error ("Smoke deploy check failed on {0} endpoint(s)." -f $failed.Count)
}

Write-Host "Smoke deploy check passed."
