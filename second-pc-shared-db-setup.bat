@echo off
setlocal

set "APP_DIR=%~dp0"
if "%APP_DIR:~-1%"=="\" set "APP_DIR=%APP_DIR:~0,-1%"
for %%I in ("%APP_DIR%") do set "APP_NAME=%%~nxI"
for %%I in ("%APP_DIR%\..\..") do set "XAMPP_DIR=%%~fI"

set "PHP_EXE=%XAMPP_DIR%\php\php.exe"
set "PHP_INI=%XAMPP_DIR%\php\php.ini"
set "APACHE_START=%XAMPP_DIR%\apache_start.bat"
set "ENV_FILE=%APP_DIR%\.env"
set "ENV_TEMPLATE=%APP_DIR%\.env.supabase.example"
set "LOGIN_URL=http://localhost/%APP_NAME%/login"
set "GUIDE_URL=file:///%APP_DIR:\=/%/SHARED_DATABASE_MULTI_PC.md"

echo ===============================================
echo   Church Management Shared DB Setup
echo ===============================================
echo.

if not exist "%PHP_EXE%" (
    echo XAMPP PHP was not found.
    echo Expected: %PHP_EXE%
    echo.
    echo Install XAMPP in C:\xampp and keep this project in:
    echo C:\xampp\htdocs\%APP_NAME%
    pause
    exit /b 1
)

if not exist "%ENV_FILE%" (
    if exist "%ENV_TEMPLATE%" (
        copy /Y "%ENV_TEMPLATE%" "%ENV_FILE%" >nul
        echo Created .env from .env.supabase.example
        echo.
        echo IMPORTANT:
        echo Update DB_PASS in .env with your real Supabase database password.
        echo You can also replace the DB_HOST and DB_USER values with your known session pooler values if needed.
        echo.
    ) else (
        echo Missing .env and .env.supabase.example
        pause
        exit /b 1
    )
) else (
    echo Existing .env found. Shared database settings will be reused.
    echo.
)

if exist "%PHP_INI%" (
    findstr /R /I /C:"^[ ]*extension=pdo_pgsql" "%PHP_INI%" >nul
    if errorlevel 1 (
        echo WARNING: extension=pdo_pgsql is not enabled in:
        echo %PHP_INI%
        echo.
    ) else (
        echo PHP extension check: pdo_pgsql is enabled.
    )

    findstr /R /I /C:"^[ ]*extension=pgsql" "%PHP_INI%" >nul
    if errorlevel 1 (
        echo WARNING: extension=pgsql is not enabled in:
        echo %PHP_INI%
        echo.
    ) else (
        echo PHP extension check: pgsql is enabled.
    )
) else (
    echo WARNING: php.ini was not found at:
    echo %PHP_INI%
    echo.
)

echo Starting Apache...
if exist "%APACHE_START%" (
    call "%APACHE_START%"
) else (
    echo Apache start script not found at:
    echo %APACHE_START%
    echo Start Apache manually from XAMPP.
)

echo.
echo Creating desktop shortcut...
powershell -ExecutionPolicy Bypass -File "%APP_DIR%\create-local-shortcut.ps1" -AppDir "%APP_DIR%" -AppName "Church Management System"

echo.
echo Shared-database guide:
echo %APP_DIR%\SHARED_DATABASE_MULTI_PC.md
echo.
echo Local login URL:
echo %LOGIN_URL%
echo.
echo If you just created .env from the template, edit it before login:
echo %ENV_FILE%
echo.

start "" "%LOGIN_URL%"
pause
endlocal
