@echo off
echo This script register the phpstorm protocol handler in your registry
pause

reg add HKEY_CLASSES_ROOT\phpstorm /t REG_SZ /d "" /f
reg add HKEY_CLASSES_ROOT\phpstorm /v "URL Protocol" /t REG_SZ /d "" /f
reg add HKEY_CLASSES_ROOT\phpstorm\shell /f
reg add HKEY_CLASSES_ROOT\phpstorm\shell\open /f
reg add HKEY_CLASSES_ROOT\phpstorm\shell\open\command /t REG_SZ /d "\"%~dp0phpstorm-protocol-handler.bat\" \"%%1\"" /f
pause
exit