param(
    [string]$AppDir = $PSScriptRoot
)

if ([string]::IsNullOrWhiteSpace($AppDir)) {
    $AppDir = $PSScriptRoot
}

$AppDir = [System.IO.Path]::GetFullPath($AppDir)
$appName = Split-Path -Path $AppDir -Leaf
$xamppDir = [System.IO.Path]::GetFullPath((Join-Path $AppDir '..\..'))
$mysqlStart = Join-Path $xamppDir 'mysql_start.bat'
$apacheStart = Join-Path $xamppDir 'apache_start.bat'
$windowScript = Join-Path $AppDir 'open-local-app-window.ps1'
$localUrl = "http://localhost/$appName/"
$cmdExe = Join-Path $env:SystemRoot 'System32\cmd.exe'

function Start-HiddenBatch {
    param(
        [string]$BatchPath
    )

    if (-not (Test-Path $BatchPath)) {
        return
    }

    Start-Process -FilePath $cmdExe -ArgumentList '/c', "`"$BatchPath`"" -WindowStyle Hidden
}

Start-HiddenBatch -BatchPath $mysqlStart
Start-HiddenBatch -BatchPath $apacheStart

Start-Sleep -Seconds 2

if (Test-Path $windowScript) {
    & $windowScript -Url $localUrl
} else {
    Start-Process $localUrl
}
