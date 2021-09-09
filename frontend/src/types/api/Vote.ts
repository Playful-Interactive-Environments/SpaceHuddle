import { Idea } from '@/types/api/Idea';

export interface Vote {
  id: string;
  ideaId: string;
  rating: number;
  detailRating: number;
}

export interface VoteResult {
  idea: Idea;
  ratingSum: number;
  detailRatingSum: number;
}
