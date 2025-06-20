@echo off
echo Looking for PHP installation...

REM Try common XAMPP location
if exist "C:\xampp\php\php.exe" (
    echo Found PHP in XAMPP
    "C:\xampp\php\php.exe" -S localhost:8000 -t public
    goto :eof
)

REM Try common WAMP location
if exist "C:\wamp64\bin\php\php7.4.9\php.exe" (
    echo Found PHP in WAMP
    "C:\wamp64\bin\php\php7.4.9\php.exe" -S localhost:8000 -t public
    goto :eof
)

REM Try common Laragon location
if exist "C:\laragon\bin\php\php-7.4.19\php.exe" (
    echo Found PHP in Laragon
    "C:\laragon\bin\php\php-7.4.19\php.exe" -S localhost:8000 -t public
    goto :eof
)

REM Try to find any PHP installation
for /r C:\ %%i in (php.exe) do (
    echo Found PHP at: %%i
    "%%i" -S localhost:8000 -t public
    goto :eof
)

echo PHP not found. Please install PHP or check your installation.
pause
