$desktop = [Environment]::GetFolderPath('Desktop')
Write-Output ('Desktop real: ' + $desktop)
$ws = New-Object -ComObject WScript.Shell
$sc = $ws.CreateShortcut("$desktop\Gradina Ielelor HQ.lnk")
$sc.TargetPath = 'D:\gradina ielelor hub\GradinaIelelor-HQ.exe'
$sc.WorkingDirectory = 'D:\gradina ielelor hub'
$sc.Description = 'Lanseaza aplicatia Grădina Ielelor HQ'
$sc.Save()
Write-Output 'Scurtatura creata.'
