// Message types
export type MessageRole = 'user' | 'assistant'
export type MessageStatus = 'sending' | 'sent' | 'error'

export interface ChatMessage {
  id: string
  role: MessageRole
  content: string
  timestamp: Date
  status?: MessageStatus
  canSave?: boolean  // Only assistant messages with reports can be saved
}

// Report types
export interface TraceReport {
  id: string
  title: string
  content: string
  timestamp: Date
  messageId: string  // Reference to original chat message
}

export interface ReportsByDate {
  date: string  // Format: YYYY-MM-DD
  reports: TraceReport[]
}

