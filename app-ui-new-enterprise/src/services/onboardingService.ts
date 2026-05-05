import api from '@/services/api'

export interface OnboardingState {
  // ISO-8601 timestamp of when the user dismissed the dashboard welcome
  // modal, or null if it has never been dismissed.
  welcomeSeenAt: string | null
}

export async function getOnboardingState(): Promise<OnboardingState> {
  const response = await api.get<OnboardingState>('/api/onboarding/state')
  return response.data
}

export async function markWelcomeSeen(): Promise<OnboardingState> {
  const response = await api.post<OnboardingState>('/api/onboarding/welcome-seen')
  return response.data
}
