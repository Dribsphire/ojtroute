@echo off
REM =====================================================
REM Windows Task Scheduler Script
REM Run Missing Timeout Detection
REM =====================================================

REM Set PHP executable path (adjust if needed)
SET PHP_PATH=C:\xampp\php\php.exe

REM Set script path
SET SCRIPT_PATH=C:\xampp\htdocs\ojtlast\app\cron\detect_missing_timeouts.php

REM Run the script
"%PHP_PATH%" "%SCRIPT_PATH%"

REM Optional: Log the execution
echo [%date% %time%] Missing timeout detection executed >> C:\xampp\htdocs\ojtlast\storage\logs\cron_execution.log
