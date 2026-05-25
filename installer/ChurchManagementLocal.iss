; Compile with Inno Setup after confirming XAMPP is installed in C:\xampp

#define AppName "Upper Room Assembly CMS"
#define AppFullName "Upper Room Assembly Mampong Church Management System"
#define AppVersion "1.0.0"
#define AppPublisher "Upper Room Assembly Mampong"
#define AppExeName "start-local-app.bat"
#define SourceDir "C:\xampp\htdocs\AG"
#define InstallDir "C:\xampp\htdocs\AG"
#define AssetsDir "C:\xampp\htdocs\AG\installer\assets"

[Setup]
AppId={{8A26C470-6C06-4C8D-8FA1-F61283E72E31}
AppName={#AppName}
AppVerName={#AppFullName} {#AppVersion}
AppVersion={#AppVersion}
AppPublisher={#AppPublisher}
VersionInfoCompany={#AppPublisher}
VersionInfoProductName={#AppFullName}
VersionInfoProductVersion={#AppVersion}
VersionInfoDescription={#AppFullName}
DefaultDirName={#InstallDir}
DisableDirPage=yes
DefaultGroupName={#AppName}
OutputDir={#SourceDir}\installer\output
OutputBaseFilename=UpperRoomAssemblyCMSSetup
Compression=lzma
SolidCompression=yes
WizardStyle=modern
SetupIconFile={#AssetsDir}\church-logo.ico
UninstallDisplayIcon={#InstallDir}\installer\assets\church-logo.ico
WizardImageFile={#AssetsDir}\church-logo.bmp
PrivilegesRequired=admin

[Files]
Source: "{#SourceDir}\*"; DestDir: "{#InstallDir}"; Flags: ignoreversion recursesubdirs createallsubdirs

[Icons]
Name: "{group}\{#AppName}"; Filename: "{#InstallDir}\{#AppExeName}"; IconFilename: "{#InstallDir}\installer\assets\church-logo.ico"
Name: "{autodesktop}\{#AppName}"; Filename: "{#InstallDir}\{#AppExeName}"; IconFilename: "{#InstallDir}\installer\assets\church-logo.ico"

[Run]
Filename: "{#InstallDir}\setup-local-windows.bat"; Description: "Run first-time setup for Upper Room Assembly CMS"; Flags: postinstall shellexec

[Messages]
WelcomeLabel1=Welcome to the [name] Setup Wizard
WelcomeLabel2=This installer prepares the Upper Room Assembly Mampong Church Management System for local Windows use on this computer.
FinishedHeadingLabel=Setup Completed
FinishedLabel=Upper Room Assembly CMS is now installed. Click Finish to run the first-time local setup and create the desktop shortcut.
