param(
    [Parameter(Mandatory = $true)]
    [string]$AppDir,

    [Parameter(Mandatory = $true)]
    [string]$AppName
)

$shell = New-Object -ComObject WScript.Shell
$desktop = [Environment]::GetFolderPath('Desktop')
$shortcutPath = Join-Path $desktop ($AppName + '.lnk')
$launcherVbs = Join-Path $AppDir 'launch-local-app.vbs'
$targetPath = if (Test-Path $launcherVbs) { $launcherVbs } else { Join-Path $AppDir 'start-local-app.bat' }
$iconPath = Join-Path $AppDir 'installer\assets\church-logo.ico'

$shortcut = $shell.CreateShortcut($shortcutPath)
$shortcut.TargetPath = $targetPath
$shortcut.WorkingDirectory = $AppDir
$shortcut.IconLocation = if (Test-Path $iconPath) { $iconPath } else { 'C:\xampp\xampp-control.exe,0' }
$shortcut.Description = 'Launch the local Church Management System'
$shortcut.Save()
