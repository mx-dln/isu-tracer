<?php

declare(strict_types=1);

namespace App\Controllers\Graduate;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\CurriculumFeedback;
use App\Models\CurriculumQuestion;
use App\Validators\Validator;

/**
 * Curriculum feedback for the logged-in graduate.
 */
class CurriculumController extends Controller
{
    private function graduateId(): int
    {
        return (int) (auth()->graduate_id ?? 0);
    }

    public function index(Request $request): void
    {
        $graduateId = $this->graduateId();
        $questions = CurriculumQuestion::all(true);
        $feedback = CurriculumFeedback::forGraduate($graduateId);

        $this->view('graduate/curriculum/index', [
            'title'     => 'Curriculum Feedback',
            'subtitle'  => 'Rate the IAT curriculum on its relevance to your employment (1 = strongly disagree, 5 = strongly agree)',
            'questions' => $questions,
            'feedback'  => $feedback,
            'submitted' => CurriculumFeedback::hasSubmitted($graduateId),
        ]);
    }

    public function submit(Request $request): void
    {
        Csrf::validateOrAbort();
        $graduateId = $this->graduateId();

        $ratings = $request->input('ratings', []);
        $comments = $request->input('comments', []);
        if (!is_array($ratings)) {
            $this->error('Invalid submission.', 'graduate/curriculum-feedback');
        }

        $clean = [];
        $errors = [];
        foreach ($ratings as $qid => $rating) {
            $qid = (int) $qid;
            $rating = (int) $rating;
            if ($rating < 1 || $rating > 5) {
                $errors[] = 'Ratings must be between 1 and 5.';
                break;
            }
            $clean[$qid] = $rating;
        }

        if ($errors) {
            flash('error', $errors[0]);
            redirect('graduate/curriculum-feedback');
        }

        if ($clean === []) {
            $this->error('Please rate at least one curriculum question.', 'graduate/curriculum-feedback');
        }

        $commentList = [];
        if (is_array($comments)) {
            foreach ($comments as $qid => $comment) {
                $commentList[(int) $qid] = trim((string) $comment) !== '' ? trim((string) $comment) : null;
            }
        }

        CurriculumFeedback::replaceAll($graduateId, $clean, $commentList);
        $this->audit('submit', 'curriculum', "Graduate submitted curriculum feedback (graduate {$graduateId}).");
        $this->success('Your curriculum feedback has been saved.', 'graduate/curriculum-feedback');
    }
}
