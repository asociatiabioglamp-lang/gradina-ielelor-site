import { useEffect, useState } from 'react'
import { Plug, KeyRound, ExternalLink, Save, TestTube2, ChevronDown, HardDrive } from 'lucide-react'
import { api, post } from '@/lib/api'
import { card, cardMuted, btnPrimary, btnGhost, input, label } from '@/lib/ui'

interface Cfg {
  mode: 'oauth' | 'password' | 'outlook-local'
  user: string
  clientId: string
  tenantId: string
  pass: string
  imapHost: string
  filterTo: string
}

const EMPTY: Cfg = { mode: 'outlook-local', user: '', clientId: '', tenantId: '', pass: '', imapHost: 'outlook.office365.com', filterTo: '' }

export default function Settings() {
  const [office, setOffice] = useState<Cfg>(EMPTY)
  const [rezervari, setRezervari] = useState<Cfg>(EMPTY)
  const [active, setActive] = useState<'office' | 'rezervari'>('office')
  const [saved, setSaved] = useState<Record<string, boolean>>({})
  const [message, setMessage] = useState('')
  const [showGuide, setShowGuide] = useState(false)
  const [showGmail, setShowGmail] = useState(true)

  const cfg = active === 'office' ? office : rezervari
  const setCfg = active === 'office' ? setOffice : setRezervari

  useEffect(() => {
    api<{ office: any; rezervari: any }>('/api/email/config').then((c) => {
      if (c.office) setOffice({ ...EMPTY, ...c.office, pass: '' })
      if (c.rezervari) setRezervari({ ...EMPTY, ...c.rezervari, pass: '' })
    }).catch(() => {})
    const p = new URLSearchParams(window.location.search)
    const oauth = p.get('oauth')
    if (oauth === 'ok') setMessage(`✅ Contul ${p.get('mailbox')}@gradina-ielelor.ro a fost conectat cu succes!`)
    if (oauth === 'error') setMessage(`❌ Eroare la conectare: ${p.get('msg')}`)
  }, [])

  const save = async () => {
    const body: any = { mailbox: active, mode: cfg.mode, user: cfg.user || (cfg.mode === 'outlook-local' ? `${active}@gradina-ielelor.ro` : ''), clientId: cfg.clientId, tenantId: cfg.tenantId, imapHost: cfg.imapHost, filterTo: cfg.filterTo }
    if (cfg.pass) body.pass = cfg.pass
    await post('/api/email/config', body)
    setSaved({ ...saved, [active]: true })
    setTimeout(() => setSaved({ ...saved, [active]: false }), 2500)
  }

  const oauthAction = async (kind: 'login' | 'consent') => {
    await save()
    const res = await api<{ url: string }>(`/api/email/oauth-${kind === 'login' ? 'url' : 'consent-url'}?mailbox=${active}`)
    window.open(res.url, '_blank')
  }

  const test = async () => {
    setMessage('Se testează conexiunea…')
    try {
      const r = await post<{ connected: boolean; count: number }>('/api/email/test', { mailbox: active })
      setMessage(`✅ Conexiune reușită! ${r.count} emailuri preluate.`)
    } catch (e: any) {
      setMessage(`❌ Conexiune eșuată: ${e.message}`)
    }
  }

  return (
    <div className="space-y-4">
      <h2 className="text-lg font-semibold text-[#e9f2ec]">Setări — conectare Outlook</h2>

      {message && <div className={`${cardMuted} text-sm text-[#dce8df]`}>{message}</div>}

      <div className="flex gap-2">
        {(['office', 'rezervari'] as const).map((m) => (
          <button key={m} onClick={() => setActive(m)}
            className={`rounded-lg px-4 py-2 text-sm font-medium ${active === m ? 'bg-[#c9a96a] text-[#122117]' : 'border border-[#2a4536] text-[#c9d8ce]'}`}>
            {m}@gradina-ielelor.ro
          </button>
        ))}
      </div>

      <div className={card}>
        <div className="mb-4">
          <label className={label}>Metodă de conectare</label>
          <div className="flex flex-wrap gap-2">
            <button onClick={() => setCfg({ ...cfg, mode: 'outlook-local' })}
              className={`flex items-center gap-2 rounded-lg px-4 py-2 text-sm ${cfg.mode === 'outlook-local' ? 'bg-[#c9a96a] text-[#122117]' : 'border border-[#2a4536] text-[#c9d8ce]'}`}>
              <HardDrive size={14} /> Outlook local — fără parolă ⭐
            </button>
            <button onClick={() => setCfg({ ...cfg, mode: 'oauth' })}
              className={`flex items-center gap-2 rounded-lg px-4 py-2 text-sm ${cfg.mode === 'oauth' ? 'bg-[#c9a96a] text-[#122117]' : 'border border-[#2a4536] text-[#c9d8ce]'}`}>
              <Plug size={14} /> OAuth2 Microsoft
            </button>
            <button onClick={() => setCfg({ ...cfg, mode: 'password' })}
              className={`flex items-center gap-2 rounded-lg px-4 py-2 text-sm ${cfg.mode === 'password' ? 'bg-[#c9a96a] text-[#122117]' : 'border border-[#2a4536] text-[#c9d8ce]'}`}>
              <KeyRound size={14} /> Gmail / IMAP cu parolă
            </button>
          </div>
        </div>

        {cfg.mode === 'outlook-local' ? (
          <div className={`${cardMuted} mt-4`}>
            <p className="text-sm leading-relaxed text-[#dce8df]">
              Citește emailurile <b>direct din programul Outlook de pe acest PC</b> — fără parole, fără Gmail, fără reguli.
              Singura condiție: Outlook să fie instalat aici cu căsuța <b>{active}@gradina-ielelor.ro</b> configurată (ea este deja).
              Apasă <b>Salvează</b>, apoi <b>Testează conexiunea</b>.
            </p>
          </div>
        ) : (
        <div className="grid gap-3 sm:grid-cols-2">
          <div>
            <label className={label}>Adresa de email</label>
            <input className={input} value={cfg.user} onChange={(e) => setCfg({ ...cfg, user: e.target.value })} placeholder={`${active}@gradina-ielelor.ro`} />
          </div>
          {cfg.mode === 'password' ? (
            <>
              <div>
                <label className={label}>Server email (IMAP)</label>
                <select className={input} value={cfg.imapHost} onChange={(e) => setCfg({ ...cfg, imapHost: e.target.value })}>
                  <option value="outlook.office365.com">outlook.office365.com (Outlook / Microsoft 365)</option>
                  <option value="imap.gmail.com">imap.gmail.com (Gmail — varianta cu redirecționare)</option>
                </select>
              </div>
              <div>
                <label className={label}>Parolă (pentru Gmail: parola de aplicație, nu cea obișnuită)</label>
                <input type="password" className={input} value={cfg.pass} onChange={(e) => setCfg({ ...cfg, pass: e.target.value })} placeholder="••••••••" />
              </div>
              <div className="sm:col-span-2">
                <label className={label}>Afișează doar mesajele trimise către (opțional — pentru varianta Gmail)</label>
                <input className={input} value={cfg.filterTo} onChange={(e) => setCfg({ ...cfg, filterTo: e.target.value })} placeholder={`${active}@gradina-ielelor.ro`} />
              </div>
            </>
          ) : (
            <>
              <div>
                <label className={label}>Client ID (din Microsoft Entra)</label>
                <input className={input} value={cfg.clientId} onChange={(e) => setCfg({ ...cfg, clientId: e.target.value })} placeholder="00000000-0000-0000-0000-000000000000" />
              </div>
              <div>
                <label className={label}>Tenant ID (lasă gol pentru „common")</label>
                <input className={input} value={cfg.tenantId} onChange={(e) => setCfg({ ...cfg, tenantId: e.target.value })} placeholder="common" />
              </div>
            </>
          )}
        </div>
        )}

        <div className="mt-4 flex flex-wrap gap-2">
          <button className={btnGhost} onClick={save}><Save size={14} /> {saved[active] ? 'Salvat ✓' : 'Salvează'}</button>
          <button className={btnPrimary} onClick={test}><TestTube2 size={14} /> Testează conexiunea</button>
          {cfg.mode === 'oauth' && cfg.clientId && (
            <>
              <button className={btnPrimary} onClick={() => oauthAction('consent')}><ExternalLink size={14} /> 1. Consimțământ admin</button>
              <button className={btnPrimary} onClick={() => oauthAction('login')}><Plug size={14} /> 2. Conectează contul</button>
            </>
          )}
        </div>
      </div>

      <div className={card}>
        <button className="flex w-full items-center justify-between text-sm font-medium text-[#e6c37a]" onClick={() => setShowGmail(!showGmail)}>
          <span>⭐ Varianta recomandată acum: Gmail + redirecționare din Outlook (fără Azure)</span>
          <ChevronDown size={16} className={showGmail ? 'rotate-180 transition-transform' : 'transition-transform'} />
        </button>
        {showGmail && (
          <ol className="mt-3 list-decimal space-y-2 pl-5 text-sm text-[#a8bfb2]">
            <li>Creează o adresă Gmail nouă, dedicată (ex. <i>gradina.ielelor.hub@gmail.com</i>) — de preferat de pe PC-ul unde rulează aplicația.</li>
            <li>În Gmail → <b className="text-[#dce8df]">Setări → Vezi toate setările → Transfer și POP/IMAP</b> → activează <b className="text-[#dce8df]">IMAP</b> → Salvează.</li>
            <li>Activează verificarea în 2 pași pe contul Google (Setări cont → Securitate), apoi generează o <b className="text-[#dce8df]">parolă de aplicație</b> (Securitate → Parole de aplicație) — o să ți-o ceară aplicația.</li>
            <li>În Outlook (fiecare căsuță): <b className="text-[#dce8df]">Setări → Mail → Reguli → Adaugă regulă</b> → „Aplică la TOATE mesajele" → acțiune: <b className="text-[#dce8df]">Redirecționează către</b> adresa Gmail → Salvează. Repetă pentru ambele căsuțe.</li>
            <li>Aici, în Setări: pentru fiecare tab (office / rezervări) alege metoda <b className="text-[#dce8df]">„Parolă"</b>, serverul <b className="text-[#dce8df]">imap.gmail.com</b>, pune adresa Gmail + parola de aplicație, iar la „Afișează doar mesajele trimise către" pune <code className="rounded bg-[#0b1410] px-1.5 py-0.5 text-[#c9a96a]">{active}@gradina-ielelor.ro</code> (pentru fiecare tab câte una).</li>
            <li>Apasă <b className="text-[#dce8df]">Salvează</b>, apoi <b className="text-[#dce8df]">Testează conexiunea</b>.</li>
            <li>Gata — emailurile din ambele căsuțe ajung în dashboard, pe taburi separate, sincronizate la 5 minute.</li>
          </ol>
        )}
      </div>

      <div className={card}>
        <button className="flex w-full items-center justify-between text-sm font-medium text-[#dce8df]" onClick={() => setShowGuide(!showGuide)}>
          <span>Cum obții Client ID-ul din Microsoft (10 minute, o singură dată)</span>
          <ChevronDown size={16} className={showGuide ? 'rotate-180 transition-transform' : 'transition-transform'} />
        </button>
        {showGuide && (
          <ol className="mt-3 list-decimal space-y-2 pl-5 text-sm text-[#a8bfb2]">
            <li>Intră pe <b className="text-[#dce8df]">portal.azure.com</b> cu contul de administrator al firmei (office@ sau rezervari@).</li>
            <li>Mergi la <b className="text-[#dce8df]">Microsoft Entra ID → App registrations → New registration</b>.</li>
            <li>Nume: <i>GradinaIelelor-HQ</i>. La „Supported account types" alege <i>Accounts in this organizational directory only</i>.</li>
            <li>La „Redirect URI" alege <i>Web</i> și lipește exact: <code className="rounded bg-[#0b1410] px-1.5 py-0.5 text-[#c9a96a]">http://localhost:8787/api/oauth/callback</code></li>
            <li>După înregistrare, copiază <b className="text-[#dce8df]">Application (client) ID</b> și lipește-l mai sus. Tenant ID e tot pe aceeași pagină (opțional).</li>
            <li>Înapoi în aplicație: apasă <b className="text-[#dce8df]">„1. Consimțământ admin"</b> (se deschide pagina Microsoft unde accepți permisiunea de citire email — o dată pentru tot contul).</li>
            <li>Apoi apasă <b className="text-[#dce8df]">„2. Conectează contul"</b> și autentifică-te cu fiecare căsuță (office, apoi rezervari).</li>
            <li>Gata — emailurile se sincronizează automat la fiecare 5 minute.</li>
          </ol>
        )}
      </div>

      <div className={cardMuted}>
        <p className="text-xs leading-relaxed text-[#8fa79a]">
          <b className="text-[#a8bfb2]">Notă:</b> Conexiunea prin parolă simplă funcționează doar dacă administratorul Microsoft 365 a activat accesul IMAP cu autentificare de bază pentru căsuță (în general dezactivat implicit la Microsoft 365). De aceea recomandăm OAuth2. Toate datele rămân local, pe PC-ul tău — în <code className="text-[#c9a96a]">server/data/db.json</code>.
        </p>
      </div>
    </div>
  )
}
