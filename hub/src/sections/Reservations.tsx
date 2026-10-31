import { useEffect, useState } from 'react'
import { Plus, CalendarCheck, Banknote, AlertTriangle, Star } from 'lucide-react'
import { api, post, patch } from '@/lib/api'
import type { Reservation } from '@/types'
import { card, cardMuted, chip, btnPrimary, btnGhost, input, label } from '@/lib/ui'

const SOURCES = ['Airbnb', '5starDesk', 'Site propriu', 'Booking.com', 'Telefon'] as const

const STATUS_STYLE: Record<string, [string, string, string]> = {
  confirmata: ['#1e3329', '#8ab8e6', 'confirmată'],
  finalizata: ['#2f6b4d55', '#7ec8a2', 'finalizată'],
  anulata: ['#2a2f2a', '#7d9588', 'anulată'],
  'necesita-atentie': ['#6b3a3a', '#e6a1a1', '⚠ de acționat'],
}

export default function Reservations() {
  const [items, setItems] = useState<Reservation[]>([])
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ guest: '', unit: 'Glamping', checkin: '', checkout: '', source: '5starDesk' as string, amount: '', note: '' })

  const load = () => api<Reservation[]>('/api/reservations').then(setItems)
  useEffect(() => { load() }, [])

  const add = async () => {
    if (!form.guest) return
    await post('/api/reservations', {
      guest: form.guest, unit: form.unit, checkin: form.checkin, checkout: form.checkout,
      source: form.source, amount: form.amount ? Number(form.amount) : null, note: form.note,
    })
    setForm({ guest: '', unit: 'Glamping', checkin: '', checkout: '', source: '5starDesk', amount: '', note: '' })
    setShowForm(false); load()
  }

  const setStatus = async (r: Reservation, status: Reservation['status']) => {
    await patch(`/api/reservations/${r.id}`, { status }); load()
  }

  const income = items.reduce((s, r) => s + (r.amount || 0), 0)
  const pending = items.filter((r) => r.status === 'necesita-atentie').length
  const bySource = SOURCES.map((s) => ({ s, n: items.filter((r) => r.source === s).length })).filter((x) => x.n > 0)

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold text-[#e9f2ec]">Rezervări</h2>
        <button className={btnPrimary} onClick={() => setShowForm(!showForm)}><Plus size={14} /> Adaugă rezervare</button>
      </div>

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div className={card}>
          <div className="flex items-center justify-between"><span className="text-xs uppercase tracking-wide text-[#8fa79a]">Total</span><CalendarCheck size={16} className="text-[#8ab8e6]" /></div>
          <div className="mt-1 text-2xl font-bold text-[#e9f2ec]">{items.length}</div>
          <div className="mt-1 text-xs text-[#8fa79a]">{bySource.map((x) => `${x.s}: ${x.n}`).join(' · ')}</div>
        </div>
        <div className={card}>
          <div className="flex items-center justify-between"><span className="text-xs uppercase tracking-wide text-[#8fa79a]">Încasări</span><Banknote size={16} className="text-[#7ec8a2]" /></div>
          <div className="mt-1 text-2xl font-bold text-[#e9f2ec]">{income.toLocaleString('ro-RO', { maximumFractionDigits: 0 })} lei</div>
          <div className="mt-1 text-xs text-[#8fa79a]">din rezervările cu sumă trecută</div>
        </div>
        <div className={card}>
          <div className="flex items-center justify-between"><span className="text-xs uppercase tracking-wide text-[#8fa79a]">De acționat</span><AlertTriangle size={16} className="text-[#e6a1a1]" /></div>
          <div className="mt-1 text-2xl font-bold text-[#e6a1a1]">{pending}</div>
          <div className="mt-1 text-xs text-[#8fa79a]">necesită atenție</div>
        </div>
        <div className={card}>
          <div className="flex items-center justify-between"><span className="text-xs uppercase tracking-wide text-[#8fa79a]">Media recenzii</span><Star size={16} className="text-[#e6c37a]" /></div>
          <div className="mt-1 text-2xl font-bold text-[#e9f2ec]">4,3 ★</div>
          <div className="mt-1 text-xs text-[#8fa79a]">din notificările Airbnb</div>
        </div>
      </div>

      {pending > 0 && (
        <div className={`${cardMuted} border-l-2 border-l-[#e6a1a1]`}>
          <p className="text-sm text-[#e6c37a]">
            ⚠ <b>{pending} lucruri de rezolvat:</b> recenzia de 2 stele de pe Airbnb (4 oct) nu are răspuns, iar recenziile tale către oaspeți sunt în așteptare.
            Verifică și detaliile de găzduire din contul Airbnb — erau marcate „urgent" (posibil să blocheze încasările).
          </p>
        </div>
      )}

      {showForm && (
        <div className={`${card} space-y-3`}>
          <div className="grid gap-3 sm:grid-cols-3">
            <div><label className={label}>Nume oaspete</label><input className={input} value={form.guest} onChange={(e) => setForm({ ...form, guest: e.target.value })} /></div>
            <div><label className={label}>Cazare</label><select className={input} value={form.unit} onChange={(e) => setForm({ ...form, unit: e.target.value })}>{['Glamping', 'Bungalow', 'Cort', 'Rulotă', '—'].map((u) => <option key={u}>{u}</option>)}</select></div>
            <div><label className={label}>Sursă</label><select className={input} value={form.source} onChange={(e) => setForm({ ...form, source: e.target.value })}>{SOURCES.map((s) => <option key={s}>{s}</option>)}</select></div>
            <div><label className={label}>Check-in</label><input type="date" className={input} value={form.checkin} onChange={(e) => setForm({ ...form, checkin: e.target.value })} /></div>
            <div><label className={label}>Check-out</label><input type="date" className={input} value={form.checkout} onChange={(e) => setForm({ ...form, checkout: e.target.value })} /></div>
            <div><label className={label}>Sumă (lei, opțional)</label><input type="number" className={input} value={form.amount} onChange={(e) => setForm({ ...form, amount: e.target.value })} /></div>
          </div>
          <div><label className={label}>Notiță</label><input className={input} value={form.note} onChange={(e) => setForm({ ...form, note: e.target.value })} /></div>
          <div className="flex gap-2"><button className={btnPrimary} onClick={add}>Salvează</button><button className={btnGhost} onClick={() => setShowForm(false)}>Anulează</button></div>
        </div>
      )}

      <div className="space-y-2">
        {items.map((r) => {
          const [bg, fg, lbl] = STATUS_STYLE[r.status] || STATUS_STYLE.confirmata
          return (
            <div key={r.id} className={`${cardMuted} ${r.status === 'necesita-atentie' ? 'border-l-2 border-l-[#e6a1a1]' : ''}`}>
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div className="min-w-0">
                  <div className="flex flex-wrap items-center gap-2">
                    <span className="font-medium text-[#e9f2ec]">{r.guest}</span>
                    <span className={chip} style={{ backgroundColor: '#1e3329', color: '#8fa79a' }}>{r.source}</span>
                    {r.unit && r.unit !== '—' && <span className={chip} style={{ backgroundColor: '#1e3329', color: '#8fa79a' }}>{r.unit}</span>}
                    <span className={chip} style={{ backgroundColor: bg, color: fg }}>{lbl}</span>
                  </div>
                  <p className="mt-1 text-xs text-[#8fa79a]">
                    {r.checkin && `${new Date(r.checkin).toLocaleDateString('ro-RO')} → ${r.checkout ? new Date(r.checkout).toLocaleDateString('ro-RO') : '?'}`}
                    {r.amount != null && ` · ${r.amount.toLocaleString('ro-RO')} lei`}
                  </p>
                  {r.note && <p className="mt-1 text-sm text-[#a8bfb2]">{r.note}</p>}
                </div>
                {r.status === 'necesita-atentie' && (
                  <button className={btnGhost} onClick={() => setStatus(r, 'finalizata')}>Marchează rezolvat</button>
                )}
              </div>
            </div>
          )
        })}
        {!items.length && <div className={cardMuted}><p className="text-sm text-[#8fa79a]">Nicio rezervare înregistrată încă.</p></div>}
      </div>

      <div className={cardMuted}>
        <p className="text-xs leading-relaxed text-[#8fa79a]">
          <b className="text-[#a8bfb2]">5starDesk:</b> rezervările din 5starDesk nu ajung automat aici — se adaugă manual cu butonul de sus, sau (pasul următor) ne conectăm direct la platformă dacă îmi dai acces la contul de 5starDesk.
          Rezervările Airbnb de mai sus au fost extrase automat din emailuri.
        </p>
      </div>
    </div>
  )
}
