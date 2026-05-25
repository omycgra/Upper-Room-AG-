param(
    [string]$OutputDir = ""
)

$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$timestamp = Get-Date -Format "yyyyMMdd-HHmmss"

if ([string]::IsNullOrWhiteSpace($OutputDir)) {
    $OutputDir = Join-Path $projectRoot "installer\output"
}

if (-not (Test-Path $OutputDir)) {
    New-Item -ItemType Directory -Path $OutputDir -Force | Out-Null
}

$packageName = "AG-shared-db-package-$timestamp.zip"
$packagePath = Join-Path $OutputDir $packageName
$stagingRoot = Join-Path $env:TEMP "AG-shared-db-package-$timestamp"

if (Test-Path $stagingRoot) {
    Remove-Item -Path $stagingRoot -Recurse -Force
}

New-Item -ItemType Directory -Path $stagingRoot -Force | Out-Null

$robocopySource = $projectRoot
$robocopyDestination = Join-Path $stagingRoot "AG"

$excludeDirs = @(
    ".git",
    "node_modules",
    "vendor",
    ".trae",
    "installer\output"
)

$excludeFiles = @(
    "*.zip",
    "unins000.dat",
    "unins000.exe"
)

$robocopyArgs = @(
    $robocopySource,
    $robocopyDestination,
    "/E",
    "/R:1",
    "/W:1",
    "/NFL",
    "/NDL",
    "/NJH",
    "/NJS",
    "/NP",
    "/XD"
) + $excludeDirs + @("/XF") + $excludeFiles

& robocopy @robocopyArgs | Out-Null
$robocopyExitCode = $LASTEXITCODE
if ($robocopyExitCode -ge 8) {
    throw "Packaging failed during file copy. Robocopy exit code: $robocopyExitCode"
}

if (Test-Path $packagePath) {
    Remove-Item -Path $packagePath -Force
}

Compress-Archive -Path (Join-Path $stagingRoot "AG") -DestinationPath $packagePath -Force

Remove-Item -Path $stagingRoot -Recurse -Force

Write-Host ""
Write-Host "Package created successfully:" -ForegroundColor Green
Write-Host $packagePath
Write-Host ""
Write-Host "Use this zip on another PC, then extract it into C:\xampp\htdocs\" -ForegroundColor Yellow
