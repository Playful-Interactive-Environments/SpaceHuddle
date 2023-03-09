enum SessionSortOrder {
  CHRONOLOGICAL = 'chronological',
  ALPHABETICAL = 'alphabetical',
  TOPICS = 'topics',
  TASKS = 'tasks',
  MODERATORS = 'moderators',
  PARTICIPANTS = 'participants',
}

export const DefaultSessionSortOrder = SessionSortOrder.CHRONOLOGICAL;

export default SessionSortOrder;
