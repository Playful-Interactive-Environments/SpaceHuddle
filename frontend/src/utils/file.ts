/* eslint-disable @typescript-eslint/no-explicit-any*/
export interface UploadData {
  name: string;
  url: string;
  type: string;
}

export const fileContentToBase64 = (
  file: File,
  callback: (encodeString: string) => void
): void => {
  const reader = new FileReader();
  reader.readAsDataURL(file);
  reader.onload = () => {
    callback(reader.result as string);
  };
};

export const getBase64ImageType = (base64Data: string): string => {
  return `image/${base64Data.substring(
    'data:image/'.length,
    base64Data.indexOf(';base64')
  )}`;
};

export const fileContentToUploadData = (
  file: File,
  callback: (data: UploadData) => void
): void => {
  fileContentToBase64(file, (encodeString) => {
    const data: UploadData = {
      name: file.name,
      url: encodeString,
      type: file.type,
    };
    callback(data);
  });
};

export enum FileTypeImage {
  NONE = 'none',
  PNG = 'png',
  JPG = 'jpg',
}

export const getFileType = (filename: string): FileTypeImage => {
  const fileNameParts = filename.split('.');
  if (fileNameParts.length > 1) {
    if (
      Object.values(FileTypeImage).includes(fileNameParts[1] as FileTypeImage)
    ) {
      const fileType = fileNameParts[1];
      return FileTypeImage[fileType.toUpperCase()];
    }
  }
  return FileTypeImage.NONE;
};

export const isValidFileType = (filename: string): boolean => {
  const fileType = getFileType(filename);
  return fileType !== FileTypeImage.NONE;
};
