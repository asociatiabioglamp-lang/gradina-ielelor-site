import { useEffect, useState } from 'react'
import { Star, Mail, CalendarDays, MessageSquareQuote, RefreshCw, Inbox } from 'lucide-react'
import { api } from '@/lib/api'
import type { Overview } from '@/types'
import { card, cardMuted, chip, btnGhost } from '@/lib/ui'

function Stat({ icon, label, value, sub, accent }: { icon: React.ReactNode; label: string; value: string; sub?: string; accent: string }) {
  return (
    <div className={card}>
      <div className="flex items-center justify-between">
        <span className="text-xs font-medium uppercase tracking-wide text-[#8fa79a]">{label}</span>
        <span className="rounded-lg p-2" style={{ backgroundColor: accent + '22', color: accent }}>{icon}</span>
      </div>
      <div className="mt-2 text-3xl font-bold text-[#e9f2ec]">{value}</div>
      {sub && <div className="mt-1 text-xs text-[#8fa79a]">{sub}</div>}
    </div>
  )
}

export default function Dashboard() {
  const [ov, setOv] = useState<Overview | null>(null)
  const [err, setErr] = useState('')

  const load = () => api<Overview>('/api/overview').then(setOv).catch((e) => setErr(e.message))
  useEffect(() => { load() }, [])

  if (err) return <div className={card}><p className="text-sm text-[#e2a1a1]">Serverul local nu răspunde. Pornește-l cu <code className="text-[#c9a96a]">npm run dev</code> din folderul proiectului.</p></div>
  if (!ov) return <div className={card}><p className="text-sm text-[#8fa79a]">Se încarcă…</p></div>

  const mailSub = ov.emailConfigured.office || ov.emailConfigured.rezervari
    ? `office: ${ov.unreadOffice} necitite · rezervări: ${ov.unreadRez} necitite`
    : 'Email neconfigurat — vezi tabul Setări'

  return (
    <div className="space-y-5">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold text-[#e9f2ec]">Panou general</h2>
        <button className={btnGhost} onClick={load}><RefreshCw size={14} /> Reîmprospătează</button>
      </div>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Stat icon={<Star size={18} />} label="Rating Google" value={ov.avgRating.toFixed(1)} sub={`din ${ov.reviewCount} recenzii · ${ov.newReviews} fără răspuns`} accent="#e6c37a" />
        <Stat icon={<Inbox size={18} />} label="Email necitite" value={String(ov.unreadEmails)} sub={mailSub} accent="#7ec8a2" />
        <Stat icon={<CalendarDays size={18} />} label="Calendar conținut" value={`${ov.eventsDone}/${ov.eventsTotal}`} sub={`${ov.plannedPosts} de publicat`} accent="#8ab8e6" />
        <Stat icon={<MessageSquareQuote size={18} />} label="Review-uri noi" value={String(ov.newReviews)} sub="necesită răspuns" accent="#d695c0" />
      </div>

      <div className="grid gap-4 lg:grid-cols-2">
        <div className={card}>
          <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-[#8fa79a]">Stare conectare email</h3>
          <div className="space-y-2">
            {(['office', 'rezervari'] as const).map((mb) => {
              const cfg = ov.emailConfigured[mb]
              const last = ov.lastFetch[mb as 'office' | 'rezervari']
              return (
                <div key={mb} className={cardMuted}>
                  <div className="flex items-center justify-between">
                    <span className="text-sm font-medium text-[#dce8df]">{mb}@gradina-ielelor.ro</span>
                    <span className={chip} style={{ backgroundColor: cfg ? '#2f6b4d55' : '#6b3a3a55', color: cfg ? '#7ec8a2' : '#e2a1a1' }}>
                      <Mail size={11} /> {cfg ? 'configurat' : 'neconfigurat'}
                    </span>
                  </div>
                  {last && <p className="mt-1 text-xs text-[#8fa79a]">Ultima sincronizare: {new Date(last).toLocaleString('ro-RO')}</p>}
                </div>
              )
            })}
          </div>
        </div>

        <div className={card}>
          <h3 className="mb-3 text-sm font-semibold uppercase tracking-wide text-[#8fa79a]">Acum în Grădină</h3>
          <ul className="space-y-2 text-sm text-[#c9d8ce]">
            <li className="flex gap-2"><span className="text-[#c9a96a]">→</span> Sezonul 2026 s-a încheiat — focus pe early-booking 2027</li>
            <li className="flex gap-2"><span className="text-[#c9a96a]">→</span> 31 octombrie: „Noaptea Ielelor" — video de semnătură de filmat</li>
            <li className="flex gap-2"><span className="text-[#c9a96a]">→</span> 2 noiembrie: deschiderea rezervărilor pentru 2027</li>
            <li className="flex gap-2"><span className="text-[#c9a96a]">→</span> Blog: 1 articol SEO pe săptămână</li>
          </ul>
        </div>
      </div>
    </div>
  )
}
