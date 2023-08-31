import Vue from 'vue'
import Router from 'vue-router'
import { ROUTES, SECURITY } from '../enums/routerEnums'
import { withNamespace } from '../../store/utils'
import { AUTH } from '../../store/modules/auth/types'
import { config } from '../../config'
import routes from './routes'
import { events } from '@/services/utils/events'
import { EVENT_TYPES } from '@/services/enums/eventsEnums'

Vue.use(Router)

const router = new Router({
  mode: 'history',
  base: config.router.baseUrl,
  linkActiveClass: 'active',
  linkExactActiveClass: 'active',
  routes,
})

let timer

export const beforeEach = (store) => {
  return (to, from, next) => {
    const isLogged = store.getters[withNamespace(AUTH.NAMESPACE, AUTH.GETTERS.IS_LOGGED)]
    const isChecked = store.getters[withNamespace(AUTH.NAMESPACE, AUTH.GETTERS.IS_CHECKED)]

    if (!isChecked) {
      clearInterval(timer)
    }

    if (to.meta.auth && to.meta.auth === SECURITY.PUBLIC) {
      next()
      return
    }

    // PRIVATE ROUTE
    const notLogged = () => {
      clearInterval(timer)

      next({ name: ROUTES.LOGIN })
    }

    const checkLogged = () => {
      store.dispatch(withNamespace(AUTH.NAMESPACE, AUTH.ACTIONS.CHECK_LOGGED_REQUEST)).then((res) => {
        if (!res) {
          notLogged()
        }
      })
    }

    if (!isChecked) {
      store.dispatch(withNamespace(AUTH.NAMESPACE, AUTH.ACTIONS.CHECK_LOGGED_REQUEST)).then((res) => {
        if (res) {
          timer = setInterval(checkLogged, config.checkLogged.refreshTime * 1000)

          next()
          return
        }

        notLogged()
      })
    }

    if (isChecked) {
      if (isLogged) {
        if (from.name === ROUTES.EDITOR && !to.params.forceRoute) {
          events.emit(EVENT_TYPES.EDITOR.COMPARE_XML, next)
          return
        }

        next()
        return
      }

      notLogged()
    }
  }
}

export default router
