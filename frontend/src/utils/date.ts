import { ElMessage, ElMessageBox } from 'element-plus';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export const formatDate = (date: string): string => {
  if (!date) return date;
  const [year, month, day] = date.split('-');
  return `${day}.${month}.${year}`;
};

export function copyToClipboard(
  text: string,
  $t: (key: string) => string
): void {
  navigator.clipboard.writeText(text).then(
    () => {
      ElMessage({
        message: $t('info.copyToClipboard'),
        type: 'success',
        center: true,
        showClose: true,
      });
    },
    () => {
      ElMessage({
        message: $t('error.gui.copyToClipboard'),
        type: 'error',
        center: true,
        showClose: true,
      });
    }
  );
}

export async function pasteFromClipboard(
  dataType: string, // 'image/png'
  $t: (key: string) => string
): Promise<any> {
  const permission = await (navigator.permissions as any).query({
    name: 'clipboard-read',
  });
  let allowClipboard = permission.state === 'granted';
  if (permission.state === 'prompt') {
    await ElMessageBox.confirm(
      $t('confirm.clipboard.clipboardInfo'),
      $t('confirm.clipboard.clipboardInfoTitle'),
      {
        confirmButtonText: $t('confirm.clipboard.clipboardInfoOk'),
        cancelButtonText: $t('confirm.clipboard.clipboardInfoCancel'),
        type: 'warning',
      }
    )
      .then(() => {
        allowClipboard = true;
      })
      .catch(() => {
        allowClipboard = false;
      });
  } else if (permission.state === 'denied') {
    ElMessage({
      message: $t('confirm.clipboard.clipboardNotAllowed'),
      type: 'error',
      center: true,
      showClose: true,
    });
    allowClipboard = false;
  }

  if (allowClipboard) {
    const clipboardContents = await navigator.clipboard.read();
    for (const item of clipboardContents) {
      if (!item.types.includes(dataType)) {
        ElMessage({
          message: $t('confirm.clipboard.pasteNoData'),
          type: 'error',
          center: true,
          showClose: true,
        });
      } else {
        if (!dataType.startsWith('text/')) {
          return await item.getType(dataType);
        } else {
          return await navigator.clipboard.readText();
        }
      }
    }
  }
  return null;
}
