import { EVENT_TYPES } from './types'

const eventEmitter = {
  events: {},
  listen: (eventType, callback) => {
    if (!eventEmitter.events[eventType]) eventEmitter.events[eventType] = []
    eventEmitter.events[eventType].push(callback)
  },
  emit: (eventType, payload) => {
    if (!eventEmitter.events[eventType]) return
    eventEmitter.events[eventType].forEach((callback) => callback(payload))
  },
}

export { eventEmitter as events, EVENT_TYPES as EVENTS }
