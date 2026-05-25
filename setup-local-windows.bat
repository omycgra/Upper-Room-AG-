@echo off
setlocal

set "APP_DIR=%~dp0"
if "%APP_DIR:~-1%"=="\" set "APP_DIR=%APP_DIR:~0,-1%"
for %%I in ("%APP_DIR%") do set "APP_NAME=%%~nxI"
for %%I in ("%APP_DIR%\..\..") do set "XAMPP_DIR=%%~fI"

set "PHP_EXE=%XAMPP_DIR%\php\php.exe"
set "MYSQL_START=%XAMPP_DIR%\mysql_start.bat"
set "APACHE_START=%XAMPP_DIR%\apache_start.bat"

echo ============================================
echo   Church Management Local Setup
echo ============================================
echo.

if not exist "%PHP_EXE%" (
    echo XAMPP PHP was not found.
    echo Expected: %PHP_EXE%
    echo Put this project inside C:\xampp\htdocs\%APP_NAME% or edit the script.
    pause
    exit /b 1
)

if not exist "%APP_DIR%\.env" (
    if exist "%APP_DIR%\.env.local-software.example" (
        copy /Y "%APP_DIR%\.env.local-software.example" "%APP_DIR%\.env" >nul
        echo Created local .env file.
    ) else (
        echo Missing .env.local-software.example
        pause
        exit /b 1
    )
)

if exist "%MYSQL_START%" call "%MYSQL_START%"
if exist "%APACHE_START%" call "%APACHE_START%"

echo.
echo Starting first-time setup page...
echo Open this in your browser to choose church name, logo, and theme:
echo http://localhost/%APP_NAME%/setup.php

echo.
echo Creating desktop shortcut...
powershell -ExecutionPolicy Bypass -File "%APP_DIR%\create-local-shortcut.ps1" -AppDir "%APP_DIR%" -AppName "Church Management System"

echo.
echo Finish the installer in your browser, then login from the app shortcut.
start "" "http://localhost/%APP_NAME%/setup.php"
pause
endlocal
