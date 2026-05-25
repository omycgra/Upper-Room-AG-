@echo off
setlocal

for %%I in ("%~dp0\..\..") do set "XAMPP_DIR=%%~fI"

if exist "%XAMPP_DIR%\apache_stop.bat" call "%XAMPP_DIR%\apache_stop.bat"
if exist "%XAMPP_DIR%\mysql_stop.bat" call "%XAMPP_DIR%\mysql_stop.bat"

echo Local services stopped.
pause
endlocal
