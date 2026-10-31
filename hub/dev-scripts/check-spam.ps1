$e = Invoke-RestMethod 'http://localhost:8787/api/emails?mailbox=office'
"office:    vizibile=$($e.data.emails.Count)  spamCount=$($e.data.spamCount)"
$e2 = Invoke-RestMethod 'http://localhost:8787/api/emails?mailbox=rezervari'
"rezervari: vizibile=$($e2.data.emails.Count)  spamCount=$($e2.data.spamCount)"
$o = Invoke-RestMethod 'http://localhost:8787/api/overview'
"dashboard necitite=$($o.data.unreadEmails)  (office=$($o.data.unreadOffice) rezervari=$($o.data.unreadRez))"
try { $v = Invoke-WebRequest -UseBasicParsing http://localhost:7100 -TimeoutSec 8; "interfata 7100: HTTP $($v.StatusCode)" } catch { "interfata: NU raspunde" }
