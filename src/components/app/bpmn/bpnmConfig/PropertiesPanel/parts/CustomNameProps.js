import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory'
import cmdHelper from 'bpmn-js-properties-panel/lib/helper/CmdHelper'
import { getBusinessObject, is } from 'bpmn-js/lib/util/ModelUtil'
// import apiGatewayServer from 'services/apiGatewayServer'

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

  // apiGatewayServer(() => {}, 'GET', '/nodes/list/name', null).then((response) => {
  //   localStorage.setItem('pipes-nodes-list', JSON.stringify(response))
  // })

  const pipesType = getBusinessObject(element).pipesType
  const implementationTypesNames111 = JSON.parse(localStorage.getItem('pipes'))
  let implementationTypesNames = implementationTypesNames111 ? implementationTypesNames111.items : []

  if (element.type !== 'bpmn:Process' && pipesType !== 'user') {
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

  if (['connector', 'batch', 'custom', 'user'].includes(pipesType)) {
    // const implementationTypesNames = JSON.parse(localStorage.getItem('pipes-nodes-list'))
    // const sdkServiceName = getBusinessObject(element).$attrs.sdkHostName
    // const options = implementationTypesNames[sdkServiceName][pipesType].map((item) => ({ name: item, value: item }))

    // console.log(implementationTypesNames, sdkServiceName, implementationTypesNames[sdkServiceName][pipesType])
    // console.log(implementationTypesNames.map(({ name, url }) => ({ name, value: url })))
    // console.log(pipesNodes, sdkHostValue, nodeType)
    // console.log(getBusinessObject(element))
    console.log(getBusinessObject(element).$attrs.sdkHostOptions)
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
