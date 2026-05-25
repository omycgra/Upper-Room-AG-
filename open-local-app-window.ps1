param(
    [Parameter(Mandatory = $true)]
    [string]$Url
)

$edgePaths = @(
    "$Env:ProgramFiles(x86)\Microsoft\Edge\Application\msedge.exe",
    "$Env:ProgramFiles\Microsoft\Edge\Application\msedge.exe"
)

$chromePaths = @(
    "$Env:ProgramFiles\Google\Chrome\Application\chrome.exe",
    "$Env:ProgramFiles(x86)\Google\Chrome\Application\chrome.exe",
    "$Env:LocalAppData\Google\Chrome\Application\chrome.exe"
)

function Open-InAppWindow {
    param(
        [string[]]$Candidates,
        [string]$LaunchUrl
    )

    foreach ($candidate in $Candidates) {
        if (Test-Path $candidate) {
            Start-Process -FilePath $candidate -ArgumentList "--app=$LaunchUrl", "--new-window"
            return $true
        }
    }

    return $false
}

if (Open-InAppWindow -Candidates $edgePaths -LaunchUrl $Url) {
    exit 0
}

if (Open-InAppWindow -Candidates $chromePaths -LaunchUrl $Url) {
    exit 0
}

Start-Process $Url
