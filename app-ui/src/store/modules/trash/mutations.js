import createState from './state'
import { resetState } from '../../utils'
import { TRASH } from '@/store/modules/trash/types'

export default {
  [TRASH.MUTATIONS.TRASH_GET]: (state, data) => {
    state.trash = data
  },
  [TRASH.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
