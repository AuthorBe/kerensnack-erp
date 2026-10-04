@echo off
setlocal
title Keren One - DB Replication Engine (Supabase -^> Local)

echo ====================================================================
echo  KEREN ONE ERP - SINKRONISASI DATABASE 100%% KE LOCAL LARAGON
echo ====================================================================
echo.

set PHP_BIN=D:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe

if not exist "%PHP_BIN%" (
    set PHP_BIN=php
)

"%PHP_BIN%" "%~dp0sync_db.php" %*

echo.
pause
