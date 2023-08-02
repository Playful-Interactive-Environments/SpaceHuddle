import { ChartDataset } from 'chart.js';
import * as themeColors from '@/utils/themeColors';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function calculateChartPerIteration(
  dataList: any[],
  loopList: any[],
  replayColors: string[],
  getIterationNo: (item: any) => number,
  containsToDataset: (item: any, label: any) => boolean,
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
      backgroundColor: replayColors[i],
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
        subset.filter((item) => containsToDataset(item, loopItem)).length
      );
    }
    datasets.push(dataset as ChartDataset);
  }
  return datasets;
}
