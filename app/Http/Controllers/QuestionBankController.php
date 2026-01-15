<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Domain\Quizzes\QuestionBank;
use App\Domain\Quizzes\BankQuestion;
use App\Http\Middleware\AuthMiddleware;

class QuestionBankController extends Controller
{
    public function __construct()
    {
        (new AuthMiddleware())->handle(['instructor', 'master_instructor', 'admin']);
    }

    public function index(): void
    {
        $userId = $_SESSION['user_id'];
        $bankModel = new QuestionBank();
        $banks = $bankModel->findAllByInstructor($userId);

        $this->view('instructor.banks.index', ['banks' => $banks]);
    }

    public function create(): void
    {
        // Simple create is handled via modal/inline form in index, 
        // but if we needed a dedicated page:
        // $this->view('instructor.banks.create');
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($title) {
            $bankModel = new QuestionBank();
            $bankModel->create([
                'instructor_id' => $_SESSION['user_id'],
                'title' => $title,
                'description' => $description
            ]);
            $_SESSION['flash_success'] = 'Question Bank created.';
        }

        header('Location: /instructor/question-banks');
        exit;
    }

    public function show(string $id): void
    {
        $bankId = (int) $id;
        $bankModel = new QuestionBank();
        $bank = $bankModel->find($bankId);

        $roles = $_SESSION['user_roles'] ?? [];
        $hasAccess = ($bank['instructor_id'] == $_SESSION['user_id']) || in_array('admin', $roles) || in_array('master_instructor', $roles);

        if (!$bank || !$hasAccess) {
            header('Location: /instructor/question-banks');
            exit;
        }

        $questionModel = new BankQuestion();
        $questions = $questionModel->findAllByBank($bankId);

        $this->view('instructor.banks.show', ['bank' => $bank, 'questions' => $questions]);
    }

    public function storeQuestion(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $bankId = (int) ($_POST['bank_id'] ?? 0);
        $bankModel = new QuestionBank();
        $bank = $bankModel->find($bankId);

        $roles = $_SESSION['user_roles'] ?? [];
        $hasAccess = ($bank['instructor_id'] == $_SESSION['user_id']) || in_array('admin', $roles) || in_array('master_instructor', $roles);

        if (!$bank || !$hasAccess) {
            http_response_code(403);
            return;
        }

        $type = $_POST['type'] ?? 'single_choice';
        $text = trim($_POST['question_text'] ?? '');
        
        // Handle Options
        $options = [];
        if (isset($_POST['option_text']) && is_array($_POST['option_text'])) {
            foreach ($_POST['option_text'] as $key => $val) {
                if (trim($val) !== '') {
                    $options[] = [
                        'id' => uniqid(),
                        'text' => trim($val)
                    ];
                }
            }
        }

        // Handle Correct Answers
        $correctAnswers = [];
        if ($type === 'true_false') {
            $correctAnswers[] = $_POST['correct_tf'] ?? '1';
        } elseif ($type === 'single_choice') {
            // Mapping index to ID is tricky with dynamic forms. 
            // Simplified: The form should send the index of the correct option.
            $correctIndex = (int) ($_POST['correct_option'] ?? -1);
            if (isset($options[$correctIndex])) {
                $correctAnswers[] = $options[$correctIndex]['id'];
            }
        } elseif ($type === 'multi_choice') {
            if (isset($_POST['correct_options']) && is_array($_POST['correct_options'])) {
                foreach ($_POST['correct_options'] as $idx) {
                    if (isset($options[$idx])) {
                        $correctAnswers[] = $options[$idx]['id'];
                    }
                }
            }
        } elseif ($type === 'short_text') {
             // For short text, correct answer is the text itself (or keywords)
             $correctAnswers[] = trim($_POST['correct_text'] ?? '');
        }

        $questionModel = new BankQuestion();
        $questionModel->create([
            'bank_id' => $bankId,
            'type' => $type,
            'question_text' => $text,
            'options' => json_encode($options),
            'correct_answers' => json_encode($correctAnswers),
            'explanation' => trim($_POST['explanation'] ?? '')
        ]);

        $_SESSION['flash_success'] = 'Question added to bank.';
        header('Location: /instructor/question-bank/' . $bankId);
        exit;
    }

    public function deleteQuestion(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $questionId = (int) $id;
        $questionModel = new BankQuestion();
        $question = $questionModel->find($questionId);

        if ($question) {
            $bankModel = new QuestionBank();
            $bank = $bankModel->find($question['bank_id']);
            
            $roles = $_SESSION['user_roles'] ?? [];
            $hasAccess = ($bank['instructor_id'] == $_SESSION['user_id']) || in_array('admin', $roles) || in_array('master_instructor', $roles);
            
            if ($bank && $hasAccess) {
                $questionModel->delete($questionId);
                $_SESSION['flash_success'] = 'Question deleted.';
                header('Location: /instructor/question-bank/' . $bank['id']);
                exit;
            }
        }
        
        header('Location: /instructor/question-banks');
        exit;
    }

    public function delete(string $id): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $bankId = (int) $id;
        $bankModel = new QuestionBank();
        $bank = $bankModel->find($bankId);

        $roles = $_SESSION['user_roles'] ?? [];
        $hasAccess = ($bank['instructor_id'] == $_SESSION['user_id']) || in_array('admin', $roles) || in_array('master_instructor', $roles);

        if ($bank && $hasAccess) {
            $bankModel->delete($bankId);
            $_SESSION['flash_success'] = 'Question Bank deleted.';
        }

        header('Location: /instructor/question-banks');
        exit;
    }
}
