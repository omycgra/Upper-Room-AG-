@echo off
setlocal

set "APP_DIR=%~dp0"
if "%APP_DIR:~-1%"=="\" set "APP_DIR=%APP_DIR:~0,-1%"
for %%I in ("%APP_DIR%") do set "APP_NAME=%%~nxI"
for %%I in ("%APP_DIR%\..\..") do set "XAMPP_DIR=%%~fI"

set "MYSQL_START=%XAMPP_DIR%\mysql_start.bat"
set "APACHE_START=%XAMPP_DIR%\apache_start.bat"
set "LOCAL_URL=http://localhost/%APP_NAME%/"
set "APP_WINDOW_SCRIPT=%APP_DIR%\open-local-app-window.ps1"

if exist "%MYSQL_START%" call "%MYSQL_START%"
if exist "%APACHE_START%" call "%APACHE_START%"

if exist "%APP_WINDOW_SCRIPT%" (
    powershell -ExecutionPolicy Bypass -File "%APP_WINDOW_SCRIPT%" -Url "%LOCAL_URL%"
) else (
    start "" "%LOCAL_URL%"
)
endlocal
