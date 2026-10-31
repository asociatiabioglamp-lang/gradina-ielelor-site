[Console]::OutputEncoding = New-Object System.Text.UTF8Encoding
$outlook = New-Object -ComObject Outlook.Application
$ns = $outlook.GetNamespace("MAPI")
$found = @()
foreach ($store in $ns.Stores) {
  if ($store.DisplayName -notmatch "rezervari") { continue }
  $root = $store.GetRootFolder()
  $inbox = $null
  foreach ($f in $root.Folders) { if ($f.Name -eq "Inbox") { $inbox = $f; break } }
  if ($null -eq $inbox) { continue }
  $items = $inbox.Items
  for ($i = 1; $i -le $items.Count; $i++) {
    try {
      $m = $items.Item($i)
      if ($m.Subject -match "recenzie de [23] stele") {
        $script:found += [pscustomobject]@{
          subject = $m.Subject
          date    = $m.ReceivedTime.ToString("o")
          body    = $m.Body
        }
      }
    } catch {}
  }
}
$found | ConvertTo-Json -Depth 3 -Compress
