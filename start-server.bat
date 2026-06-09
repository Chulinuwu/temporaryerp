@echo off
setlocal
title PEGASUS ERP Server

echo ============================================
echo   PEGASUS ERP v3.0 - Development Server
echo ============================================
echo.

where php >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP not found. Please install PHP 8.2+ and add it to PATH.
    echo https://windows.php.net/download/
    pause
    exit /b 1
)

echo PHP Version:
for /f "delims=" %%v in ('php -r "echo PHP_VERSION;"') do echo   %%v
echo.

set PORT=8080
if not "%~1"=="" set PORT=%~1

echo Starting server on http://localhost:%PORT%
echo Document root: %~dp0public
echo Press Ctrl+C to stop.
echo.

cd /d "%~dp0"
php -S localhost:%PORT% -t public

pause
endlocal
