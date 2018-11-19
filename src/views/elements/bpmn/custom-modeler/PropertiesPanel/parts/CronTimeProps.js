import entryFactory from 'bpmn-js-properties-panel/lib/factory/EntryFactory';
import cmdHelper from 'bpmn-js-properties-panel/lib/helper/CmdHelper';
import { getBusinessObject } from 'bpmn-js/lib/util/ModelUtil';
import cronParser from 'cron-parser';

export default function (group, element, translate) {
  if (element.pipesType !== 'cron' && (!element.businessObject || element.businessObject.pipesType !== 'cron')) {
    return;
  }

  group.entries.push(entryFactory.validationAwareTextField({
    id: 'cronTime',
    label: 'Cron time',
    description: 'eg. */2 * * * *',
    modelProperty: 'cronTime',
    validate: (element, values) => {
      if (!values.cronTime) return {};

      try {
        cronParser.parseExpression(values.cronTime);
      } catch (err) {
        return {
          cronTime: err.message
            .replace('undefinedundefinedundefined', '\'\'')
            .replace('undefined', 'unexpected'),
        };
      }

      return {};
    },
    getProperty(element) {
      return getBusinessObject(element).get('cronTime');
    },
    setProperty(element, properties) {
      return cmdHelper.updateProperties(element, properties);
    },
  }));
}
