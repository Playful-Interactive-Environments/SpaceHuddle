export const formatDate = (date: string): string => {
  if (!date) return date;
  const [year, month, day] = date.split('-');
  return `${day}.${month}.${year}`;
};
