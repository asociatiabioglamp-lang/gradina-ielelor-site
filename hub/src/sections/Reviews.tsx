import { useEffect, useState } from 'react'
import { Star, Check, Sparkles } from 'lucide-react'
import { api, patch } from '@/lib/api'
import type { Review } from '@/types'
import { card, cardMuted, chip, btnPrimary, btnGhost } from '@/lib/ui'

const replySuggestions = (r: Review) => {
  const base = `Mulțumim frumos, ${r.author.split(' ')[0]}!`
  if (/furnic/i.test(r.text)) return `${base} Ne bucurăm că v-ați simțit bine — Furnica are și ea fan club acum și o îmbrățișăm din partea voastră. 😊 Vă așteptăm cu drag la vară!`
  if (/copil/i.test(r.text)) return `${base} Familiile cu copii sunt oaspeții noștri preferați — ne bucurăm că ați avut parte de o ședere frumoasă. Vă mai așteptăm în Grădină!`
  if (/curat|curăț/i.test(r.text)) return `${base} Curățenia e cartea noastră de vizită — muncim zilnic pentru asta. Vă mai așteptăm cu drag la poalele Făgărașului!`
  return `${base} Ne bucurăm că v-ați simțit bine în Grădina Ielelor. Priveliștea e mereu aici — vă așteptăm cu drag la vară! 🌿`
}

export default function Reviews() {
  const [reviews, setReviews] = useState<Review[]>([])
  const [filter, setFilter] = useState<'all' | 'new' | 'replied'>('all')
  const [expanded, setExpanded] = useState<string | null>(null)
  const [drafts, setDrafts] = useState<Record<string, string>>({})

  const load = () => api<Review[]>('/api/reviews').then(setReviews)
  useEffect(() => { load() }, [])

  const visible = reviews.filter((r) => (filter === 'all' ? true : r.status === filter))

  const save = async (r: Review, status: 'new' | 'replied') => {
    await patch(`/api/reviews/${r.id}`, { reply: drafts[r.id] ?? r.reply, status })
    setExpanded(null)
    load()
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold text-[#e9f2ec]">Review-uri Google</h2>
        <div className="flex gap-2">
          {(['all', 'new', 'replied'] as const).map((f) => (
            <button key={f} onClick={() => setFilter(f)}
              className={`rounded-lg px-3 py-1.5 text-xs font-medium ${filter === f ? 'bg-[#c9a96a] text-[#122117]' : 'border border-[#2a4536] text-[#8fa79a] hover:text-[#dce8df]'}`}>
              {f === 'all' ? 'Toate' : f === 'new' ? 'Fără răspuns' : 'Cu răspuns'}
            </button>
          ))}
        </div>
      </div>

      {visible.map((r) => (
        <div key={r.id} className={card}>
          <div className="flex items-start justify-between gap-4">
            <div>
              <div className="flex items-center gap-2">
                <span className="font-semibold text-[#e9f2ec]">{r.author}</span>
                <span className="flex gap-0.5">
                  {Array.from({ length: r.rating }).map((_, i) => <Star key={i} size={13} className="fill-[#e6c37a] text-[#e6c37a]" />)}
                </span>
                <span className={chip} style={{ backgroundColor: r.status === 'new' ? '#6b3a3a55' : '#2f6b4d55', color: r.status === 'new' ? '#e2a1a1' : '#7ec8a2' }}>
                  {r.status === 'new' ? 'fără răspuns' : 'răspuns trimis'}
                </span>
              </div>
              <p className="mt-0.5 text-xs text-[#8fa79a]">{r.source} · {new Date(r.date).toLocaleDateString('ro-RO')}</p>
            </div>
          </div>
          <p className="mt-2 text-sm leading-relaxed text-[#c9d8ce]">{r.text}</p>
          {r.reply && <p className="mt-2 rounded-lg border-l-2 border-[#c9a96a] bg-[#0d1a13] p-3 text-xs text-[#a8bfb2]"><span className="font-semibold text-[#c9a96a]">Răspunsul vostru:</span> {r.reply}</p>}

          {expanded === r.id ? (
            <div className="mt-3 space-y-2">
              <textarea rows={3} className="w-full rounded-lg border border-[#2a4536] bg-[#0b1410] px-3 py-2 text-sm text-[#dce8df] outline-none focus:border-[#c9a96a]"
                value={drafts[r.id] ?? r.reply} onChange={(e) => setDrafts({ ...drafts, [r.id]: e.target.value })} />
              <div className="flex gap-2">
                <button className={btnGhost} onClick={() => setDrafts({ ...drafts, [r.id]: replySuggestions(r) })}>
                  <Sparkles size={14} /> Sugestie de răspuns
                </button>
                <button className={btnPrimary} onClick={() => save(r, 'replied')}><Check size={14} /> Marchează răspuns</button>
              </div>
            </div>
          ) : (
            <button className={`${btnGhost} mt-3`} onClick={() => { setExpanded(r.id); if (!drafts[r.id] && !r.reply) setDrafts({ ...drafts, [r.id]: replySuggestions(r) }) }}>
              {r.status === 'new' ? 'Răspunde' : 'Editează răspuns'}
            </button>
          )}
        </div>
      ))}
      {!visible.length && <div className={cardMuted}><p className="text-sm text-[#8fa79a]">Nicio recenzie în această categorie.</p></div>}
    </div>
  )
}
