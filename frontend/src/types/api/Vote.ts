import { Idea } from '@/types/api/Idea';

export interface Vote {
  id: string;
  ideaId: string;
  rating: number;
  detailRating: number;
  timestamp: string;
}

export interface VoteResult {
  idea: Idea;
  ratingSum: number;
  detailRatingSum: number;
  countParticipant: number;
}
