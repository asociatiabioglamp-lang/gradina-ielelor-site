import { useEffect, useState } from 'react'
import { Check } from 'lucide-react'
import { api, patch } from '@/lib/api'
import type { CalendarEvent } from '@/types'
import { card, cardMuted, chip } from '@/lib/ui'

export default function CalendarTab() {
  const [events, setEvents] = useState<CalendarEvent[]>([])
  const load = () => api<CalendarEvent[]>('/api/events').then(setEvents)
  useEffect(() => { load() }, [])

  const toggle = async (e: CalendarEvent) => {
    await patch(`/api/events/${e.id}`, { status: e.status === 'publicat' ? 'planificat' : 'publicat' })
    load()
  }

  const weeks = [...new Set(events.map((e) => e.week))]

  return (
    <div className="space-y-5">
      <h2 className="text-lg font-semibold text-[#e9f2ec]">Calendar conținut — Luna 1 (19 oct – 15 nov)</h2>
      {weeks.map((w) => {
        const theme = events.find((e) => e.week === w)?.theme
        return (
          <div key={w} className={card}>
            <div className="mb-3 flex items-baseline justify-between">
              <h3 className="font-semibold text-[#e9f2ec]">{w}</h3>
              <span className={chip} style={{ backgroundColor: '#c9a96a22', color: '#e6c37a' }}>{theme}</span>
            </div>
            <div className="space-y-2">
              {events.filter((e) => e.week === w).map((e) => (
                <div key={e.id} className={`${cardMuted} flex items-center justify-between gap-4`}>
                  <div className="flex items-center gap-3">
                    <button
                      onClick={() => toggle(e)}
                      className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-md border ${e.status === 'publicat' ? 'border-[#7ec8a2] bg-[#2f6b4d]' : 'border-[#2a4536] hover:border-[#c9a96a]'}`}
                      title={e.status === 'publicat' ? 'Marchează nepublicat' : 'Marchează publicat'}>
                      {e.status === 'publicat' && <Check size={14} className="text-[#e9f2ec]" />}
                    </button>
                    <div>
                      <p className={`text-sm ${e.status === 'publicat' ? 'text-[#7d9588] line-through' : 'text-[#dce8df]'}`}>{e.title}</p>
                      <p className="text-xs text-[#8fa79a]">{e.platform} · {new Date(e.date).toLocaleDateString('ro-RO')}</p>
                    </div>
                  </div>
                  <span className={chip} style={{ backgroundColor: e.status === 'publicat' ? '#2f6b4d55' : '#6b5a3a55', color: e.status === 'publicat' ? '#7ec8a2' : '#e6c37a' }}>
                    {e.status === 'publicat' ? 'publicat' : 'planificat'}
                  </span>
                </div>
              ))}
            </div>
          </div>
        )
      })}
    </div>
  )
}
