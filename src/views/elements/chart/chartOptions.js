export function lineTimeChartOption(parameters){
  let {data, title, subTitle, seriesName} = parameters;
  const keys = Object.keys(data);
  return {
    title: {
      text: title,
      subtext: subTitle
    },
    tooltip: {
      trigger: 'axis'
    },
    legend: {
      data: []
    },
    toolbox: {
      show: false
    },
    calculable: false,
    xAxis: [{
      type: 'time'
    }],
    yAxis: [{
      type: 'value'
    }],
    series: [{
      name: seriesName,
      type: 'line',
      itemStyle: {normal: {areaStyle: {type: 'default'}}},
      data: keys.map(key => [new Date(parseInt(key) * 1000), data[key]]),
      markPoint: {
        data: [{
          type: 'max',
          name: '???'
        }, {
          type: 'min'
        }]
      },
      markLine: {
        data: [{
          type: 'average'
        }]
      }
    }]
  };
}

export function barChartOption(parameters){
  let {keys, values, title, subTitle, seriesName} = parameters;
  return {
    title: {
      text: title,
      subtext: subTitle
    },
    tooltip: {
      trigger: 'axis'
    },
    legend: {
      data: []
    },
    toolbox: {
      show: false
    },
    calculable: false,
    xAxis: [{
      type: 'category',
      axisLabel: {
        rotate: 45,
        interval: 0,
      },
      data: keys
    }],
    yAxis: [{
      type: 'value'
    }],
    series: [{
      name: seriesName,
      type: 'bar',
      itemStyle: {normal: {areaStyle: {type: 'default'}}},
      data: values,
      markPoint: {
        data: [{
          type: 'max'
        }, {
          type: 'min'
        }]
      },
      markLine: {
        data: [{
          type: 'average'
        }]
      }
    }]
  };
}

