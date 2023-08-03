import { ChartDataset } from 'chart.js';
import * as themeColors from '@/utils/themeColors';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function calculateChartPerIteration(
  dataList: any[],
  loopList: any[],
  colorList: string[],
  getIterationNo: (item: any) => number,
  containsToLoop: (item: any, label: any) => boolean,
  containsToChart: ((item: any) => boolean) | null = null,
  maxIterations = 10
): ChartDataset[] {
  const chartDataList = containsToChart
    ? dataList.filter((item) => containsToChart(item))
    : dataList;
  if (chartDataList.length === 0) return [];
  const datasets: ChartDataset[] = [];
  const maxIterationNo = chartDataList
    .map((item) => getIterationNo(item))
    .sort((a, b) => b - a)[0];
  for (let i = 0; i <= maxIterationNo && i <= maxIterations; i++) {
    const subset =
      i < maxIterations
        ? chartDataList.filter((item) => getIterationNo(item) === i)
        : chartDataList.filter((item) => getIterationNo(item) >= i);
    const dataset = {
      label:
        i < maxIterations || i === maxIterationNo
          ? (i + 1).toString()
          : `${i + 1} - ${maxIterationNo + 1}`,
      backgroundColor: colorList[i],
      borderRadius: {
        topRight: 5,
        bottomRight: 5,
        topLeft: 5,
        bottomLeft: 5,
      },
      data: [] as number[],
      borderSkipped: false,
      color: themeColors.getContrastColor(),
    };
    for (const loopItem of loopList) {
      dataset.data.push(
        subset.filter((item) => containsToLoop(item, loopItem)).length
      );
    }
    datasets.push(dataset as ChartDataset);
  }
  return datasets;
}

export function calculateChartPerParameter(
  dataList: any[],
  parameterList: any[],
  loopList: any[],
  colorList: string[],
  containsToParameter: (item: any, parameter: any) => boolean,
  containsToLoop: ((item: any, label: any) => boolean) | null = null,
  containsToChart: ((item: any) => boolean) | null = null,
  calculation: (list: any[], loopItem: any) => number = (list) => list.length,
  parameterToString: (parameter: any) => string = (parameter) =>
    parameter.toString()
): ChartDataset[] {
  const chartDataList = containsToChart
    ? dataList.filter((item) => containsToChart(item))
    : dataList;
  if (chartDataList.length === 0) return [];
  const datasets: ChartDataset[] = [];
  for (const [index, parameter] of parameterList.entries()) {
    const subset = chartDataList.filter((item) =>
      containsToParameter(item, parameter)
    );
    const dataset = {
      label: parameterToString(parameter),
      borderColor: colorList[index],
      backgroundColor: colorList[index],
      borderRadius: {
        topRight: 5,
        bottomRight: 5,
        topLeft: 5,
        bottomLeft: 5,
      },
      data: [] as number[],
      borderSkipped: false,
      color: themeColors.getContrastColor(),
    };
    for (const loopItem of loopList) {
      const loopSet = containsToLoop
        ? subset.filter((item) => containsToLoop(item, loopItem))
        : subset;
      dataset.data.push(calculation(loopSet, loopItem));
    }
    datasets.push(dataset as ChartDataset);
  }
  return datasets;
}

export function mapArrayToConstantSize(
  list: any[],
  getValue: (item: any) => number,
  index: number,
  mappingLength = 100
): number {
  const count = list.length;
  const ratio = count / mappingLength;
  const mapIndex = ratio * index;
  const mapIndexPrevious = Math.floor(mapIndex);
  const mapIndexNext = Math.ceil(ratio * (index + 1) - 0.01) - 1;
  const mapCount = mapIndexNext - mapIndexPrevious + 1;
  let average = 0;
  const quote = mapIndex - mapIndexPrevious;
  const quotePrevious = 1 - quote;
  const quoteNext = quote;
  for (let i = mapIndexPrevious; i <= mapIndexNext; i++) {
    const value = getValue(list[i]);
    let partQuote = 1 / mapCount;
    if (mapCount > 1) {
      if (i === mapIndexPrevious) partQuote = partQuote * 2 * quotePrevious;
      else if (i === mapIndexNext) partQuote = partQuote * 2 * quoteNext;
    }
    average += value * partQuote;
  }
  return average;
  /*const valuePrevious = getValue(list[mapIndexPrevious]);
  const valueNext = getValue(list[mapIndexNext]);
  return valuePrevious * quotePrevious + valueNext * quoteNext;*/
}
