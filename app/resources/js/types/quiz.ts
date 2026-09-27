export type QuizChoiceDraft = {
    label: string;
    is_correct: boolean;
};

export type QuizQuestionDraft = {
    id?: number;
    type: 'choice' | 'essay';
    prompt: string;
    points: number;
    choices: QuizChoiceDraft[];
};

export type QuizMaterialDraft = {
    id: number;
    type: 'quiz';
    title: string;
    summary: string | null;
    status: 'draft' | 'published';
    published_at: string | null;
    questions: QuizQuestionDraft[];
};
