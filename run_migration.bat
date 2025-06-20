@echo off
setlocal

REM Find PHP executable
for %%i in (php.exe) do set PHP_EXE=%%~$PATH:i

if "%PHP_EXE%"=="" (
    echo PHP not found in PATH
    echo Searching for PHP in common locations...

    if exist "C:\xampp\php\php.exe" (
        set PHP_EXE=C:\xampp\php\php.exe
    ) else if exist "C:\wamp64\bin\php\php*\php.exe" (
        for /d %%i in (C:\wamp64\bin\php\php*) do set PHP_EXE=%%i\php.exe
    ) else if exist "C:\laragon\bin\php\php*\php.exe" (
        for /d %%i in (C:\laragon\bin\php\php*) do set PHP_EXE=%%i\php.exe
    )
)

if "%PHP_EXE%"=="" (
    echo PHP not found. Please make sure PHP is installed and in your PATH.
    exit /b 1
)

echo Using PHP from: %PHP_EXE%
echo.

echo Running migration...
"%PHP_EXE%" artisan migrate

echo.
echo Done!
pause
