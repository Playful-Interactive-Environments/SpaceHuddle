/* eslint-disable @typescript-eslint/no-explicit-any*/
import TaskType from '@/types/enum/TaskType';
import ViewType from '@/types/enum/ViewType';

export interface View {
  type: string;
  detailType: string | null;
  id: string;
  taskId: string | null;
  name: string;
}

export const getViewName = (
  view: View,
  $t: null | ((key: string) => string) = null
): string => {
  const type: string =
    $t !== null
      ? $t(`enum.viewType.${ViewType[view.type]}`)
      : ViewType[view.type];
  const detailType = view.detailType
    ? ' - ' +
      ($t !== null
        ? $t(`enum.taskType.${TaskType[view.detailType]}`)
        : TaskType[view.detailType])
    : '';
  return `${type}${detailType} - ${view.name}`;
};

export const getViewKey = (view: View): string => {
  return `${view.type}.${view.id}`;
};
