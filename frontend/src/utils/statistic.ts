import { ChartDataset } from 'chart.js';
import * as themeColors from '@/utils/themeColors';

export enum CalculationType {
  Count = 'count',
  Sum = 'sum',
  Average = 'average',
  Min = 'min',
  Max = 'max',
}

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
  calculation: (list: any[], loopItem: any, parameter: any) => number = (
    list
  ) => list.length,
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
      dataset.data.push(calculation(loopSet, loopItem, parameter));
    }
    datasets.push(dataset as ChartDataset);
  }
  return datasets;
}

export function mapArrayToConstantSize(
  list: any[],
  getValue: (item: any) => number,
  index: number,
  mappingLength = 100,
  calculationType = CalculationType.Average
): number {
  const roundingDelta = 0.001;
  const count = list.length;
  const ratio = count / mappingLength;
  const mapIndexStart = ratio * index;
  const mapIndexEnd = ratio * (index + 1);
  const mapIndexPrevious = Math.floor(mapIndexStart + roundingDelta);
  const mapIndexNext = Math.ceil(mapIndexEnd - roundingDelta) - 1;
  let result = calculationType === CalculationType.Min ? 1000 : 0;
  const partQuote = 1 / ratio;
  for (let i = mapIndexPrevious; i <= mapIndexNext; i++) {
    const value = getValue(list[i]);
    let partFactor = 1;
    if (i === mapIndexPrevious && i !== mapIndexNext)
      partFactor = Math.ceil(mapIndexStart + roundingDelta) - mapIndexStart;
    else if (i === mapIndexNext && i !== mapIndexPrevious)
      partFactor = mapIndexEnd - Math.floor(mapIndexEnd - roundingDelta);
    else if (i === mapIndexPrevious && i === mapIndexNext)
      partFactor = mapIndexEnd - mapIndexStart;
    if (calculationType === CalculationType.Average)
      result += value * partQuote * partFactor;
    else if (calculationType === CalculationType.Sum)
      result += value * partFactor;
    else if (calculationType === CalculationType.Count) result += partFactor;
    else if (
      calculationType === CalculationType.Min &&
      result > value * partFactor
    )
      result = value * partFactor;
    else if (
      calculationType === CalculationType.Max &&
      result < value * partFactor
    )
      result = value * partFactor;
  }
  return result;
}
