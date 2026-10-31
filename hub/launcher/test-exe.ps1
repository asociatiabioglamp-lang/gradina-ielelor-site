$p = Start-Process -FilePath 'D:\gradina ielelor hub\GradinaIelelor-HQ.exe' -WorkingDirectory 'D:\gradina ielelor hub' -PassThru
Start-Sleep -Seconds 16
$ports = Get-NetTCPConnection -State Listen -ErrorAction SilentlyContinue | Where-Object { $_.LocalPort -in 7100,8787 } | Select-Object -ExpandProperty LocalPort -Unique
Write-Output ('PORTURI ACTIVE: ' + ($ports -join ','))
taskkill /PID $p.Id /T /F | Out-Null
Start-Sleep -Seconds 2
$left = Get-NetTCPConnection -State Listen -ErrorAction SilentlyContinue | Where-Object { $_.LocalPort -in 7100,8787 }
Write-Output ('RAMAS_DUPA_OPRIRE: ' + ($left.Count -eq 0))
