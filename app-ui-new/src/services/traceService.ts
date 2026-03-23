import type { TraceReport, ChatMessage } from '@/types/trace'
import reportsData from '@/assets/mock-data/trace-reports-data.json'

// WebSocket mock - simuluje odpověď AI
export const sendChatMessage = async (message: string): Promise<ChatMessage> => {
  // Simulace delay pro WebSocket odpověď
  await new Promise(resolve => setTimeout(resolve, 1500))
  
  // Mock odpověď - v produkci bude nahrazeno WebSocket komunikací
  const mockResponse = `I received your message: "${message}". This is a mock response from the AI assistant. In production, this will be replaced with actual WebSocket communication.`
  
  return {
    id: `msg-${Date.now()}`,
    role: 'assistant',
    content: `<p>${mockResponse}</p>`,
    timestamp: new Date(),
    status: 'sent',
    canSave: true // Všechny assistant odpovědi mohou být uloženy jako report
  }
}

// Reports management
export const fetchReports = async (): Promise<TraceReport[]> => {
  await new Promise(resolve => setTimeout(resolve, 300))
  
  // Konverze timestamp stringů na Date objekty
  return reportsData.map(report => ({
    ...report,
    timestamp: new Date(report.timestamp)
  })) as TraceReport[]
}

export const saveReport = async (report: Omit<TraceReport, 'id'>): Promise<TraceReport> => {
  await new Promise(resolve => setTimeout(resolve, 300))
  
  const newReport: TraceReport = {
    id: `report-${Date.now()}`,
    ...report
  }
  
  return newReport
}

export const updateReportTitle = async (id: string, title: string): Promise<void> => {
  await new Promise(resolve => setTimeout(resolve, 300))
}

export const deleteReport = async (id: string): Promise<void> => {
  await new Promise(resolve => setTimeout(resolve, 300))
}

