import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory'
import cmdHelper from 'bpmn-js-properties-panel/lib/helper/CmdHelper'
import { getBusinessObject, is } from 'bpmn-js/lib/util/ModelUtil'
import { LOCAL_STORAGE } from '@/services/enums/localStorageEnums'

export default function (group, element, translate) {
  var businessObject = getBusinessObject(element)
  if (is(element, 'bpmn:Process') || (is(element, 'bpmn:Participant') && businessObject.get('processRef'))) {
    return
  }
  if (is(element, 'bpmn:Collaboration')) {
    return
  }

  let modelProperty = 'name'

  if (is(element, 'bpmn:TextAnnotation')) {
    modelProperty = 'text'
  }

  const pipesType = getBusinessObject(element).pipesType
  const implementationTypesNames = JSON.parse(localStorage.getItem(LOCAL_STORAGE.IMPLEMENTATIONS))?.items || []

  if (!['bpmn:Event', 'bpmn:Process'].includes(element.type) && pipesType !== 'user') {
    group.entries.push(
      entryFactory.selectBox(translate, {
        id: 'sdkHost',
        label: 'Service',
        selectOptions: implementationTypesNames.map(({ name, url }) => ({ name, value: url })),
        modelProperty: 'sdkHost',
        getProperty(element) {
          return getBusinessObject(element).sdkHost
        },
        setProperty(element, properties) {
          return cmdHelper.updateProperties(element, properties)
        },
      })
    )
  }

  if (['connector', 'batch', 'custom'].includes(pipesType)) {
    group.entries.push(
      entryFactory.selectBox(translate, {
        id: 'name',
        label: 'Name',
        selectOptions: getBusinessObject(element).$attrs.sdkHostOptions,
        modelProperty,
        validate: (element, values) => {
          if (element.type === 'bpmn:Process') {
            return {}
          }

          if (!values.name || values.name === 'Custom') {
            return { name: 'Name must not be empty.' }
          }

          return {}
        },
        getProperty(element) {
          return getBusinessObject(element).name
        },
        setProperty(element, properties) {
          return cmdHelper.updateProperties(element, properties)
        },
      })
    )
  } else {
    group.entries.push(
      entryFactory.validationAwareTextField(translate, {
        id: 'name',
        label: 'Name',
        modelProperty,
        validate: (element, values) => {
          if (element.type === 'bpmn:Process') {
            return {}
          }

          if (/\s/.test(values.name)) {
            return { name: 'Name must not contain spaces.' }
          }

          if (!values.name) {
            return { name: 'Name must not be empty.' }
          }

          return {}
        },
        getProperty(element) {
          return getBusinessObject(element).name
        },
        setProperty(element, properties) {
          return cmdHelper.updateProperties(element, properties)
        },
      })
    )
  }
}
