schtasks /delete /tn 'GCR' /F
schtasks /create /sc hourly /mo 12 /tn 'GCR' /tr C:\xampp\htdocs\cms\backups\cron.bat
