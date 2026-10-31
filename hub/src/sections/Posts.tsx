import { useEffect, useState } from 'react'
import { Plus, Check } from 'lucide-react'
import { api, post, patch } from '@/lib/api'
import type { Post } from '@/types'
import { card, cardMuted, chip, btnPrimary, btnGhost, input, label } from '@/lib/ui'

export default function Posts() {
  const [posts, setPosts] = useState<Post[]>([])
  const [showForm, setShowForm] = useState(false)
  const [form, setForm] = useState({ platform: 'Instagram + Facebook', date: '', title: '', content: '' })

  const load = () => api<Post[]>('/api/posts').then(setPosts)
  useEffect(() => { load() }, [])

  const add = async () => {
    if (!form.title || !form.date) return
    await post('/api/posts', form)
    setForm({ platform: 'Instagram + Facebook', date: '', title: '', content: '' })
    setShowForm(false)
    load()
  }

  const toggle = async (p: Post) => {
    await patch(`/api/posts/${p.id}`, { status: p.status === 'publicat' ? 'planificat' : 'publicat' })
    load()
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold text-[#e9f2ec]">Postări</h2>
        <button className={btnPrimary} onClick={() => setShowForm(!showForm)}><Plus size={14} /> Adaugă postare</button>
      </div>

      {showForm && (
        <div className={`${card} space-y-3`}>
          <div className="grid gap-3 sm:grid-cols-2">
            <div>
              <label className={label}>Titlu</label>
              <input className={input} value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} placeholder="ex: Anunț deschidere sezon" />
            </div>
            <div>
              <label className={label}>Platformă</label>
              <select className={input} value={form.platform} onChange={(e) => setForm({ ...form, platform: e.target.value })}>
                {['Instagram + Facebook', 'Instagram', 'Facebook', 'TikTok + Reels', 'TikTok', 'YouTube', 'Blog'].map((p) => <option key={p}>{p}</option>)}
              </select>
            </div>
          </div>
          <div>
            <label className={label}>Data publicării</label>
            <input type="date" className={input} value={form.date} onChange={(e) => setForm({ ...form, date: e.target.value })} />
          </div>
          <div>
            <label className={label}>Conținut / idee</label>
            <textarea rows={2} className={input} value={form.content} onChange={(e) => setForm({ ...form, content: e.target.value })} placeholder="Descriere scurtă a postării" />
          </div>
          <div className="flex gap-2">
            <button className={btnPrimary} onClick={add}>Salvează</button>
            <button className={btnGhost} onClick={() => setShowForm(false)}>Anulează</button>
          </div>
        </div>
      )}

      <div className="space-y-2">
        {posts.map((p) => (
          <div key={p.id} className={`${cardMuted} flex items-start justify-between gap-4`}>
            <div className="min-w-0">
              <div className="flex flex-wrap items-center gap-2">
                <span className="font-medium text-[#e9f2ec]">{p.title}</span>
                <span className={chip} style={{ backgroundColor: '#1e3329', color: '#8fa79a' }}>{p.platform}</span>
                <span className={chip} style={{ backgroundColor: p.status === 'publicat' ? '#2f6b4d55' : '#6b5a3a55', color: p.status === 'publicat' ? '#7ec8a2' : '#e6c37a' }}>
                  {p.status}
                </span>
              </div>
              <p className="mt-1 text-xs text-[#8fa79a]">{p.date ? new Date(p.date).toLocaleDateString('ro-RO') : ''}</p>
              {p.content && <p className="mt-1 text-sm text-[#a8bfb2]">{p.content}</p>}
            </div>
            <button className={btnGhost} onClick={() => toggle(p)}>
              {p.status === 'publicat' ? '↺' : <><Check size={14} /> Publicat</>}
            </button>
          </div>
        ))}
        {!posts.length && <div className={cardMuted}><p className="text-sm text-[#8fa79a]">Nicio postare adăugată.</p></div>}
      </div>
    </div>
  )
}
