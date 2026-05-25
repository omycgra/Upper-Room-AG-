$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$envPath = Join-Path $projectRoot ".env"
$appBaseUrl = "/AG"

if (Test-Path $envPath) {
    $baseLine = Get-Content $envPath | Where-Object { $_ -match '^\s*APP_BASE_URL\s*=' } | Select-Object -First 1
    if ($baseLine) {
        $appBaseUrl = (($baseLine -split '=', 2)[1]).Trim()
        if ([string]::IsNullOrWhiteSpace($appBaseUrl)) {
            $appBaseUrl = "/"
        }
    }
}

if (-not $appBaseUrl.StartsWith("/")) {
    $appBaseUrl = "/" + $appBaseUrl
}

$addresses = Get-NetIPAddress -AddressFamily IPv4 |
    Where-Object {
        $_.IPAddress -notlike "127.*" -and
        $_.IPAddress -notlike "169.254.*" -and
        $_.PrefixOrigin -ne "WellKnown"
    } |
    Sort-Object InterfaceMetric, IPAddress

if (-not $addresses) {
    Write-Host "No LAN IPv4 address was found." -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Open the app from another PC using one of these URLs:" -ForegroundColor Green
Write-Host ""

foreach ($address in $addresses) {
    $url = "http://{0}{1}" -f $address.IPAddress, $appBaseUrl
    Write-Host $url
}

Write-Host ""
Write-Host "Make sure Apache is running and Windows Firewall allows private-network access to port 80." -ForegroundColor Yellow
