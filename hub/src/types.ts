export interface Review {
  id: string
  author: string
  rating: number
  source: string
  date: string
  status: 'new' | 'replied'
  text: string
  reply: string
}

export interface Post {
  id: string
  platform: string
  date: string
  title: string
  status: 'planificat' | 'publicat'
  content: string
}

export interface CalendarEvent {
  id: string
  week: string
  theme: string
  title: string
  platform: string
  date: string
  status: 'planificat' | 'publicat'
}

export interface EmailMessage {
  uid: number
  fromName: string
  from: string
  subject: string
  date: string
  seen: boolean
  snippet: string
  category?: string
}

export interface Reservation {
  id: string
  guest: string
  unit: string
  checkin: string
  checkout: string
  source: 'Airbnb' | '5starDesk' | 'Site propriu' | 'Booking.com' | 'Telefon'
  amount: number | null
  status: 'confirmata' | 'finalizata' | 'anulata' | 'necesita-atentie'
  note: string
}

export interface Overview {
  avgRating: number
  reviewCount: number
  newReviews: number
  unreadEmails: number
  unreadOffice: number
  unreadRez: number
  emailConfigured: { office: boolean; rezervari: boolean }
  lastFetch: { office: string | null; rezervari: string | null }
  plannedPosts: number
  eventsDone: number
  eventsTotal: number
}

export interface EmailStatus {
  configured: boolean
  mode: 'password' | 'oauth' | null
  user: string | null
  connected: boolean
  lastFetch: string | null
  count: number
  spamCount?: number
}
