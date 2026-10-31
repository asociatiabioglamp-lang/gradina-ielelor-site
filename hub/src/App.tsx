import { useState } from 'react'
import { LayoutDashboard, Star, Inbox, Megaphone, CalendarDays, Settings as SettingsIcon, Moon, BedDouble } from 'lucide-react'
import Dashboard from '@/sections/Dashboard'
import Reviews from '@/sections/Reviews'
import Emails from '@/sections/Emails'
import Posts from '@/sections/Posts'
import CalendarTab from '@/sections/Calendar'
import Settings from '@/sections/Settings'
import Reservations from '@/sections/Reservations'

const TABS = [
  { id: 'panou', label: 'Panou', icon: LayoutDashboard },
  { id: 'email', label: 'Email-uri', icon: Inbox },
  { id: 'rezervari', label: 'Rezervări', icon: BedDouble },
  { id: 'reviews', label: 'Review-uri', icon: Star },
  { id: 'postari', label: 'Postări', icon: Megaphone },
  { id: 'calendar', label: 'Calendar', icon: CalendarDays },
  { id: 'setari', label: 'Setări', icon: SettingsIcon },
] as const

type TabId = (typeof TABS)[number]['id']

export default function App() {
  const params = new URLSearchParams(window.location.search)
  const initial = (params.get('tab') as TabId) || 'panou'
  const [tab, setTab] = useState<TabId>(TABS.some((t) => t.id === initial) ? initial : 'panou')

  return (
    <div className="min-h-screen bg-[#0b1410] text-[#dce8df]">
      <header className="border-b border-[#1e3329] bg-[#0d1a13]">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-5 py-4">
          <div className="flex items-center gap-3">
            <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#1d3a2a] to-[#0f2419] text-[#c9a96a]">
              <Moon size={20} />
            </span>
            <div>
              <h1 className="font-serif text-lg font-bold tracking-wide text-[#e9f2ec]">GRĂDINA IELELOR</h1>
              <p className="text-xs text-[#8fa79a]">HQ · Sâmbăta de Sus, Brașov · sezon 2027</p>
            </div>
          </div>
          <span className="hidden rounded-full border border-[#2a4536] px-3 py-1 text-xs text-[#8fa79a] sm:block">
            🌿 extrasezon — pregătim vara
          </span>
        </div>
        <nav className="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-5 pb-0">
          {TABS.map((t) => (
            <button key={t.id} onClick={() => setTab(t.id)}
              className={`flex items-center gap-2 rounded-t-lg px-4 py-2.5 text-sm font-medium transition-colors ${
                tab === t.id ? 'bg-[#0b1410] text-[#e6c37a]' : 'text-[#8fa79a] hover:text-[#dce8df]'
              }`}>
              <t.icon size={15} /> {t.label}
            </button>
          ))}
        </nav>
      </header>

      <main className="mx-auto max-w-6xl px-5 py-6">
        {tab === 'panou' && <Dashboard />}
        {tab === 'email' && <Emails goSettings={() => setTab('setari')} />}
        {tab === 'rezervari' && <Reservations />}
        {tab === 'reviews' && <Reviews />}
        {tab === 'postari' && <Posts />}
        {tab === 'calendar' && <CalendarTab />}
        {tab === 'setari' && <Settings />}
      </main>

      <footer className="border-t border-[#1e3329] py-4 text-center text-xs text-[#5f7a6b]">
        Grădina Ielelor HQ · datele stau local, pe PC-ul tău · sincronizare automată la 5 minute
      </footer>
    </div>
  )
}
