import { useEffect, useState } from 'react'
import { Mail, RefreshCw, MailOpen, AlertCircle, Settings2, ShieldOff, ShieldCheck } from 'lucide-react'
import { api, post } from '@/lib/api'
import type { EmailMessage, EmailStatus } from '@/types'
import { card, cardMuted, chip, btnPrimary, btnGhost } from '@/lib/ui'

const MAILBOXES = [
  { id: 'office', label: 'office@gradina-ielelor.ro', desc: 'mesaje generale' },
  { id: 'rezervari', label: 'rezervari@gradina-ielelor.ro', desc: 'cereri de rezervare' },
] as const

export default function Emails({ goSettings }: { goSettings: () => void }) {
  const [mailbox, setMailbox] = useState<'office' | 'rezervari'>('office')
  const [emails, setEmails] = useState<EmailMessage[]>([])
  const [status, setStatus] = useState<EmailStatus | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')
  const [serverOff, setServerOff] = useState(false)
  const [showSpam, setShowSpam] = useState(false)

  const load = async () => {
    try {
      setServerOff(false)
      const [st, em] = await Promise.all([
        api<EmailStatus>(`/api/email/status?mailbox=${mailbox}`),
        api<{ emails: EmailMessage[]; spamCount?: number }>(`/api/emails?mailbox=${mailbox}${showSpam ? '&includeSpam=1' : ''}`),
      ])
      setStatus(st)
      setEmails(em.emails)
      setError('')
    } catch {
      setServerOff(true)
    }
  }

  const fetchNow = async () => {
    setLoading(true); setError('')
    try { await post('/api/email/fetch', { mailbox }); await load() }
    catch (e: any) { setError(e.message) }
    finally { setLoading(false) }
  }

  useEffect(() => { load() }, [mailbox, showSpam])

  if (serverOff) return <div className={card}><p className="text-sm text-[#e2a1a1]">Serverul local nu răspunde — pornește aplicația cu <code className="text-[#c9a96a]">npm run dev</code>.</p></div>

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <h2 className="text-lg font-semibold text-[#e9f2ec]">Email-uri</h2>
        <button className={btnPrimary} onClick={fetchNow} disabled={loading || !status?.configured}>
          <RefreshCw size={14} className={loading ? 'animate-spin' : ''} /> {loading ? 'Se sincronizează…' : 'Preia acum'}
        </button>
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <div className="flex gap-2">
        {MAILBOXES.map((m) => (
          <button key={m.id} onClick={() => setMailbox(m.id)}
            className={`rounded-lg px-4 py-2 text-left text-sm ${mailbox === m.id ? 'bg-[#c9a96a] text-[#122117]' : 'border border-[#2a4536] text-[#c9d8ce] hover:border-[#c9a96a]'}`}>
            <div className="font-medium">{m.label}</div>
            <div className={`text-xs ${mailbox === m.id ? 'text-[#4a3d22]' : 'text-[#8fa79a]'}`}>{m.desc}</div>
          </button>
        ))}
        </div>
        <button onClick={() => setShowSpam(!showSpam)}
          title="Reclamele și mesajele nesolicitate sunt ascunse automat din listă"
          className={`ml-auto flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs ${showSpam ? 'bg-[#2a2f2a] text-[#c9d8ce] border border-[#2a4536]' : 'border border-[#2a4536] text-[#8fa79a] hover:border-[#c9a96a]'}`}>
          {showSpam ? <ShieldCheck size={13} /> : <ShieldOff size={13} />}
          {showSpam ? 'Ascunde spamul' : `Spam oprit (${status?.spamCount ?? 0} ascunse)`}
        </button>
      </div>

      {status && !status.connected && (
        <div className={`${cardMuted} flex items-start gap-3`}>
          <AlertCircle size={18} className="mt-0.5 shrink-0 text-[#e2a1a1]" />
          <div className="flex-1">
            <p className="text-sm text-[#dce8df]">Căsuța <b>{mailbox}@gradina-ielelor.ro</b> nu este conectată încă.</p>
            <p className="mt-1 text-xs text-[#8fa79a]">După conectare, emailurile se aduc automat la fiecare 5 minute.</p>
          </div>
          <button className={btnGhost} onClick={goSettings}><Settings2 size={14} /> Configurează</button>
        </div>
      )}

      {status?.connected && (
        <p className="text-xs text-[#8fa79a]">
          Ultima sincronizare: {status.lastFetch ? new Date(status.lastFetch).toLocaleString('ro-RO') : 'niciodată'} · {status.count - (status.spamCount ?? 0)} mesaje · {status.spamCount ?? 0} în spam (ascunse)
        </p>
      )}

      {error && <div className={`${cardMuted} text-sm text-[#e2a1a1]`}>Eroare: {error}</div>}

      <div className="space-y-2">
        {emails.map((e) => (
          <div key={e.uid} className={`${cardMuted} ${!e.seen ? 'border-l-2 border-l-[#c9a96a]' : ''}`}>
            <div className="flex items-start justify-between gap-3">
              <div className="min-w-0">
                <div className="flex items-center gap-2">
                  {!e.seen && <span className="h-2 w-2 shrink-0 rounded-full bg-[#c9a96a]" />}
                  <span className={`truncate text-sm ${e.seen ? 'text-[#a8bfb2]' : 'font-semibold text-[#e9f2ec]'}`}>
                    {e.fromName || e.from}
                  </span>
                  <span className={chip} style={{ backgroundColor: '#1e3329', color: '#8fa79a' }}>
                    {e.seen ? <><MailOpen size={10} /> citit</> : <><Mail size={10} /> necitit</>}
                  </span>
                </div>
                <p className={`mt-0.5 truncate text-sm ${e.seen ? 'text-[#8fa79a]' : 'text-[#dce8df]'}`}>{e.subject}</p>
                <div className="mt-1 flex flex-wrap items-center gap-1.5">
                  {e.category && (() => {
                    const map: Record<string, [string, string]> = {
                      important: ['#6b3a3a', '#e6a1a1'],
                      suspicios: ['#6b4a2a', '#e6c37a'],
                      spam: ['#2a2f2a', '#7d9588'],
                      contracte: ['#3a2a6b', '#c0aee6'],
                      bani: ['#2a6b3f', '#8fe0ae'],
                      notificare: ['#1e3329', '#8fa79a'],
                    }
                    const [bg, fg] = map[e.category] || map.notificare
                    const label = { important: '⚠ de acționat', suspicios: '⚠ suspect', spam: 'spam', contracte: 'contract', bani: 'bani', notificare: 'notificare' }[e.category] || e.category
                    return <span className={chip} style={{ backgroundColor: bg, color: fg }}>{label}</span>
                  })()}
                </div>
                {e.snippet && <p className="mt-1 line-clamp-2 text-xs text-[#7d9588]">{e.snippet}</p>}
              </div>
              <span className="shrink-0 text-xs text-[#8fa79a]">{e.date ? new Date(e.date).toLocaleString('ro-RO', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) : ''}</span>
            </div>
          </div>
        ))}
        {status?.connected && !emails.length && !loading && (
          <div className={cardMuted}><p className="text-sm text-[#8fa79a]">Niciun email preluat încă — apasă „Preia acum".</p></div>
        )}
      </div>
    </div>
  )
}
