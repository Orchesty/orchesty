import io from 'socket.io-client'
import axios from 'axios'
import { config } from '../config'

const STREAM_TOKEN = 'stream-token'

class Stream {
  constructor(url, namespace, refreshTime, reconnectTime, debug = false) {
    this.url = url
    this.namespace = namespace
    this.refreshTime = refreshTime || 30
    this.reconnectTime = reconnectTime || 10
    this.debug = debug
    this.interval = null
    this.groups = new Set()
    this.callbacks = new Map()
    this.apiClient = axios.create({
      baseURL: url,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json; charset=utf-8',
      },
    })
    Stream.removeStreamToken()
  }

  addGroup(group) {
    this.groups.add(group)
  }

  removeGroup(group) {
    this.groups.delete(group)
  }

  addCallback(key, callback) {
    this.callbacks.set(key, callback)
  }

  static getStreamToken() {
    return localStorage.getItem(STREAM_TOKEN)
  }

  static setStreamToken(token) {
    localStorage.setItem(STREAM_TOKEN, token)
  }

  static removeStreamToken() {
    localStorage.removeItem(STREAM_TOKEN)
  }

  connect(userId = null) {
    this.io = io.connect(this.url + this.namespace, {
      reconnection: true,
      reconnectionDelay: 1000 * this.reconnectTime,
      reconnectionDelayMax: 5000,
      reconnectionAttempts: Infinity,
    })

    this.io.on('connect', () => {
      const token = Stream.getStreamToken()
      if (userId) {
        this.addGroup(userId)
        if (token) {
          this.emitSubscription()
        } else {
          this.subscribe(userId)
        }
      } else {
        this.disconnect()
      }
    })

    this.io.on('error', (e) => {
      // eslint-disable-next-line
      console.error(e)
    })

    this.io.on('connect_error', () => {
      // eslint-disable-next-line
      this.stopRefresh()
    })

    if (this.debug) {
      this.io.on('info', (e) => {
        // eslint-disable-next-line
        console.info(e)
      })
    }

    this.io.on('invalid_token', () => {
      this.stopRefresh()
      Stream.removeStreamToken()
      this.subscribe(userId)
    })

    this.callbacks.forEach((callback, key) => {
      this.io.on(key, callback)
    })
  }

  disconnect() {
    this.unsubscribe()
  }

  stopRefresh() {
    if (this.interval) {
      clearInterval(this.interval)
    }
  }

  emitSubscription() {
    const token = Stream.getStreamToken()
    if (token) {
      this.io.emit('subscribe', { content: { token, groups: [...this.groups] } })

      if (!this.interval) {
        this.interval = setInterval(() => {
          this.emitSubscription()
        }, 1000 * this.refreshTime)
      }
    }
  }

  emitUnSubscription() {
    const token = Stream.getStreamToken()

    if (token) {
      this.io.emit('unsubscribe', { content: { token, groups: [...this.groups] } })
      this.stopRefresh()
    }
  }

  subscribe(userId) {
    this.apiClient.post('/v1/user/login', { id: userId, groups: this.groups }).then((res) => {
      const { token } = res.data
      Stream.setStreamToken(token)
      this.emitSubscription()
    })
  }

  unsubscribe() {
    const token = Stream.getStreamToken()
    if (token) {
      this.apiClient.post('/v1/user/logout', {
        token,
      })
      Stream.removeStreamToken()
    }

    this.stopRefresh()

    if (this.io) {
      this.io.close()
    }
  }
}

export default new Stream(config.stream.url, config.stream.namespace, config.stream.refreshTime, 10, true)
