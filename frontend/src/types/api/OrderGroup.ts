import { Idea } from '@/types/api/Idea';
import { Avatar } from '@/types/api/Participant';
import { Task } from '@/types/api/Task';
import { DefaultDisplayCount } from '@/types/enum/IdeaSortOrder';

export class OrderGroup {
  ideas: Idea[] = [];
  avatar: Avatar[] = [];
  color: string | null = null;
  displayCount: number = DefaultDisplayCount;

  constructor(
    ideas: Idea[],
    avatar: Avatar[] = [],
    color: string | null = null,
    displayCount: number = DefaultDisplayCount
  ) {
    this.ideas = ideas;
    this.avatar = avatar;
    this.color = color;
    this.displayCount = displayCount;
  }

  get filteredIdeas(): Idea[] {
    return this.ideas.slice(0, this.displayCount);
  }

  set filteredIdeas(ideas: Idea[]) {
    ideas.forEach((idea, index) => {
      idea.order = index;
      this.ideas[index] = idea;
    });
  }
}

export interface OrderGroupList {
  [name: string]: OrderGroup;
}

export interface SortOrderOption {
  orderType: string;
  ref: Task | null;
}
