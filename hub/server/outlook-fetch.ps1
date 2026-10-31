param(
  [string]$Match = "gradina-ielelor"
)
[Console]::OutputEncoding = New-Object System.Text.UTF8Encoding
$ErrorActionPreference = "Continue"
$results = @()
try {
  $outlook = New-Object -ComObject Outlook.Application
  $ns = $outlook.GetNamespace("MAPI")
  foreach ($store in $ns.Stores) {
    $name = $store.DisplayName
    if ($name -notmatch [regex]::Escape($Match)) { continue }
    try {
      $root = $store.GetRootFolder()
      $inbox = $null
      foreach ($f in $root.Folders) { if ($f.Name -eq "Inbox") { $inbox = $f; break } }
      if ($null -eq $inbox) { continue }
      $items = $inbox.Items
      $items.Sort("[ReceivedTime]", $true)
      $total = $items.Count
      if ($total -gt 50) { $total = 50 }
      for ($i = 1; $i -le $total; $i++) {
        try {
          $m = $items.Item($i)
          if ($null -eq $m) { continue }
          $body = ""
          try { $body = ($m.Body -replace "\s+", " ").Trim() } catch {}
          if ($body.Length -gt 300) { $body = $body.Substring(0, 300) }
          $results += [pscustomobject]@{
            store     = $name
            fromName  = $m.SenderName
            from      = $m.SenderEmailAddress
            subject   = $m.Subject
            date      = $m.ReceivedTime.ToString("o")
            seen      = (-not [bool]$m.UnRead)
            snippet   = $body
            entryId   = $m.EntryID
          }
        } catch {}
      }
    } catch {}
  }
} catch {
  [Console]::Error.WriteLine("OUTLOOK_ERROR: " + $_.Exception.Message)
  exit 1
}
$results | ConvertTo-Json -Depth 3 -Compress
