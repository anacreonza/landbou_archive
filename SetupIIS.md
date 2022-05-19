Install-WindowsFeature -name Web-Server -IncludeManagementTools
choco install git
choco install pandoc
https://getcomposer.org/Composer-Setup.exe
New-WebHandler -Name "PHP-FastCGI" -Path "*.php" -Verb "*" -Modules "FastCgiModule" -ScriptProcessor "c:\php\php-cgi.exe" -ResourceType File
https://www.iis.net/downloads/microsoft/url-rewrite?msclkid=93baca59d12311ecb717de63b6458dd1
elasticsearch-7.9.1