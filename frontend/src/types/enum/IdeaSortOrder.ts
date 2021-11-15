export const IdeaSortOrderCategorisation = 'categorisation';

enum IdeaSortOrder {
  TIMESTAMP = 'timestamp',
  ALPHABETICAL = 'alphabetical',
  STATE = 'state',
  PARTICIPANT = 'participant',
  COUNT = 'count',
}

export const DefaultIdeaSortOrder = IdeaSortOrder.TIMESTAMP.toUpperCase();

export const DefaultDisplayCount = 20;

export default IdeaSortOrder;
