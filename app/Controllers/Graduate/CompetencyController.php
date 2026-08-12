<?php

declare(strict_types=1);

namespace App\Controllers\Graduate;

use App\Controllers\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Models\Competency;
use App\Models\CompetencyResponse;
use App\Validators\Validator;

/**
 * Competency self-assessment for the logged-in graduate.
 */
class CompetencyController extends Controller
{
    private function graduateId(): int
    {
        return (int) (auth()->graduate_id ?? 0);
    }

    public function index(Request $request): void
    {
        $graduateId = $this->graduateId();
        $list = Competency::allForGraduate($graduateId);

        $this->view('graduate/competencies/index', [
            'title'       => 'Competency Self-Assessment',
            'subtitle'    => 'Rate your competencies developed by the IAT program (1 = very low, 5 = very high)',
            'competencies' => $list,
            'total'       => count($list),
            'completed'   => Competency::completedCount($graduateId),
        ]);
    }

    public function submit(Request $request): void
    {
        Csrf::validateOrAbort();
        $graduateId = $this->graduateId();

        $ratings = $request->input('ratings', []);
        if (!is_array($ratings)) {
            $this->error('Invalid submission.', 'graduate/competencies');
        }

        $clean = [];
        $errors = [];
        foreach ($ratings as $cid => $rating) {
            $cid = (int) $cid;
            $rating = (int) $rating;
            if ($rating < 1 || $rating > 5) {
                $errors[] = 'Ratings must be between 1 and 5.';
                break;
            }
            $clean[$cid] = $rating;
        }

        if ($errors) {
            flash('error', $errors[0]);
            redirect('graduate/competencies');
        }

        if ($clean === []) {
            $this->error('Please rate at least one competency.', 'graduate/competencies');
        }

        CompetencyResponse::replaceAll($graduateId, $clean);
        $this->audit('submit', 'competencies', "Graduate submitted competency self-assessment (graduate {$graduateId}).");
        $this->success('Your competency self-assessment has been saved.', 'graduate/competencies');
    }
}
