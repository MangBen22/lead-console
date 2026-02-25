$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$pluginFile = Join-Path $root "lead-console.php"

if (-not (Test-Path $pluginFile)) {
    throw "Plugin file not found: $pluginFile"
}

$content = Get-Content -Raw $pluginFile

$versionMatch = [regex]::Match($content, "Version:\s*([0-9]+)\.([0-9]+)\.([0-9]+)")
if (-not $versionMatch.Success) {
    throw "Could not find plugin header version in lead-console.php"
}

$major = [int]$versionMatch.Groups[1].Value
$minor = [int]$versionMatch.Groups[2].Value
$patch = [int]$versionMatch.Groups[3].Value + 1
$newVersion = "$major.$minor.$patch"
$oldVersion = "$major.$minor.$($patch - 1)"

$content = [regex]::Replace($content, "Version:\s*[0-9]+\.[0-9]+\.[0-9]+", "Version: $newVersion", 1)
$content = [regex]::Replace($content, "define\('LC_PLUGIN_VERSION',\s*'[0-9]+\.[0-9]+\.[0-9]+'\);", "define('LC_PLUGIN_VERSION', '$newVersion');", 1)

[System.IO.File]::WriteAllText($pluginFile, $content)

Write-Output "Bumped version: $oldVersion -> $newVersion"
