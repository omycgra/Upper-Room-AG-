; Upper Room Assembly Church Management System Installer
; Inno Setup Script

[Setup]
AppName=Upper Room Assembly CMS
AppVersion=1.0
AppPublisher=Upper Room Assembly
AppPublisherURL=http://localhost/AG
AppSupportURL=http://localhost/AG
AppUpdatesURL=http://localhost/AG
DefaultDirName={pf}\Upper Room Assembly CMS
DefaultGroupName=Upper Room Assembly CMS
AllowNoIcons=yes
LicenseFile=..\LICENSE
OutputBaseFilename=URA-CMS-Installer
Compression=lzma
SolidCompression=yes
WizardStyle=modern
WizardImageFile=compiler:WizModernImage-IS.bmp
WizardSmallImageFile=compiler:WizModernSmallImage-IS.bmp
SetupIconFile=..\installer\assets\church-logo.ico
UninstallDisplayIcon={app}\logo.ico

[Files]
Source: "..\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs; Excludes: "*.git*","installer\*","*.md"

[Icons]
Name: "{group}\Upper Room Assembly CMS"; Filename: "{app}\URA-CMS.bat"; WorkingDir: "{app}"; IconFilename: "{app}\logo.ico"
Name: "{group}\Uninstall Upper Room Assembly CMS"; Filename: "{uninstallexe}"
Name: "{commondesktop}\Upper Room Assembly CMS"; Filename: "{app}\URA-CMS.bat"; WorkingDir: "{app}"; IconFilename: "{app}\logo.ico"

[Run]
Filename: "{app}\URA-CMS.bat"; Description: "Launch Upper Room Assembly CMS"; Flags: nowait postinstall skipifsilent
