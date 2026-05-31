@echo off
echo Starting Upper Room Assembly Church Management System...
echo.
echo Checking for existing server...
echo.

REM Try to detect if XAMPP is running
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo Server already running! Opening app...
    start "" "http://localhost/AG/"
) else (
    echo.
    echo ==============================================
    echo IMPORTANT: This app requires a web server!
    echo ==============================================
    echo.
    echo Options:
    echo 1. If you have XAMPP installed: Start Apache and MySQL first!
    echo 2. For standalone desktop version: Use our PHP Desktop package
    echo.
    echo Opening app in browser...
    start "" "http://localhost/AG/"
)

timeout /t 3 >nul
