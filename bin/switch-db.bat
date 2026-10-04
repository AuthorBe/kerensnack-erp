@echo off
setlocal
title Keren One - 1-Click Database Switcher

set PHP_BIN=D:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

if not exist "%PHP_BIN%" (
    set PHP_BIN=php
)

"%PHP_BIN%" "%~dp0switch_db.php" %*

echo.
pause
