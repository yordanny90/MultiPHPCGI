@echo off
del "%~dp0..\TrayIcon.exe"
C:\Windows\Microsoft.NET\Framework64\v4.0.30319\csc.exe /t:winexe "/out:%~dp0..\TrayIcon.exe" "%~dp0TrayIcon.cs"