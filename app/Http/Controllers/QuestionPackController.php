<?php

namespace App\Http\Controllers;

use App\Models\Question;
use App\Models\QuestionPack;
use App\Models\Choice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuestionPackController extends Controller
{
    /**
     * Display a listing of the question packs.
     */
    public function index()
    {
        // Load question packs with their descriptions, test types, durations, and question counts
        $questionPacks = QuestionPack::withCount('questions')->get();

        return Inertia::render('admin/questions/questions-packs/question-packs', [
            'questionPacks' => $questionPacks
        ]);
    }

    /**
     * Show the form for creating a new question pack.
     */
    public function create()
    {
        // Form create baru tidak lagi membutuhkan list pertanyaan existing
        return inertia('admin/questions/questions-packs/add-question-packs');
    }

    /**
     * Store a newly created question pack in storage.
     */

    public function store(Request $request)
    {
        // Validate the request
        $validated = $request->validate([
            'pack_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'test_type' => 'required|string',
            'duration' => 'required|string',
            'opens_at' => 'nullable|date',
            'closes_at' => 'nullable|date|after:opens_at',
            // optional: attach questions existing
            'question_ids' => 'nullable|array',
            'question_ids.*' => 'exists:questions,id',
            // optional: buat questions baru langsung dari form
            'questions' => 'nullable|array',
            'questions.*.question_text' => 'required_with:questions|string',
            'questions.*.question_type' => 'required_with:questions|in:multiple_choice,essay',
            'questions.*.options' => 'required_if:questions.*.question_type,multiple_choice|array',
            'questions.*.options.*' => 'required_with:questions.*.options|string',
            'questions.*.correct_answer' => 'required_if:questions.*.question_type,multiple_choice|string',
        ]);

        // Pastikan minimal ada satu sumber pertanyaan
        if (
            (empty($validated['question_ids']) || count($validated['question_ids']) === 0) &&
            (empty($validated['questions']) || count($validated['questions']) === 0)
        ) {
            return back()
                ->withErrors(['questions' => 'Tambahkan minimal 1 pertanyaan untuk question pack ini.'])
                ->withInput();
        }

        DB::transaction(function () use ($validated) {
            // Get the duration string in HH:MM:SS format
            $durationStr = $validated['duration'];

            // Convert HH:MM:SS string to minutes for storage
            $durationParts = explode(':', $durationStr);
            $hours = (int) ($durationParts[0] ?? 0);
            $minutes = (int) ($durationParts[1] ?? 0);
            $seconds = (int) ($durationParts[2] ?? 0);

            $duration = ($hours * 60) + $minutes + ($seconds / 60);

            // Create the question pack with the validated duration
            $questionPack = QuestionPack::create([
                'pack_name' => $validated['pack_name'],
                'description' => $validated['description'] ?? null,
                'test_type' => $validated['test_type'],
                'duration' => $duration,
                'opens_at' => $validated['opens_at'] ?? null,
                'closes_at' => $validated['closes_at'] ?? null,
                'user_id' => Auth::user()->id,
                'status' => 'active',
            ]);

            // Attach existing questions if provided
            if (!empty($validated['question_ids']) && is_array($validated['question_ids'])) {
                $questionPack->questions()->attach($validated['question_ids']);
            }

            // Create and attach new questions if provided
            if (!empty($validated['questions']) && is_array($validated['questions'])) {
                foreach ($validated['questions'] as $questionData) {
                    if (empty($questionData['question_text'])) {
                        continue;
                    }

                    $questionType = $questionData['question_type'] ?? 'multiple_choice';

                    $question = Question::create([
                        'question_text' => $questionData['question_text'],
                        'question_type' => $questionType,
                    ]);

                    // handle choices untuk multiple choice
                    if ($questionType === 'multiple_choice') {
                        $options = $questionData['options'] ?? [];
                        $correctAnswer = $questionData['correct_answer'] ?? null;

                        // Interpret correct answer sebagai huruf (A, B, C, ...) atau fallback ke teks opsi
                        $correctIndex = null;
                        if (is_string($correctAnswer) && preg_match('/^[A-Z]$/i', $correctAnswer)) {
                            $correctIndex = ord(strtoupper($correctAnswer)) - ord('A');
                        }

                        foreach (array_values($options) as $idx => $option) {
                            $trimmed = trim($option);
                            if ($trimmed === '') {
                                continue;
                            }

                            $isCorrect = false;

                            if ($correctIndex !== null) {
                                $isCorrect = $idx === $correctIndex;
                            } elseif ($correctAnswer !== null) {
                                $isCorrect = $trimmed === $correctAnswer;
                            }

                            Choice::create([
                                'question_id' => $question->id,
                                'choice_text' => $trimmed,
                                'is_correct' => $isCorrect,
                            ]);
                        }
                    }

                    $questionPack->questions()->attach($question->id);
                }
            }
        });

        return redirect()->route('admin.questionpacks.index')->with('success', 'Question pack created successfully!');
    }

    /**
     * Display the specified question pack.
     */
    public function show(QuestionPack $questionpack)
    {
        $questionpack->load(['questions.choices']);

        // Format dates and enrich questions for consistent display
        $questionpackData = $questionpack->toArray();

        // Map questions to include options dan correct answer (huruf + teks)
        $questionpackData['questions'] = collect($questionpack->questions)->map(function (Question $question) {
            $options = $question->choices->pluck('choice_text')->values()->all();
            $correctChoice = $question->choices->firstWhere('is_correct', true);

            $correctIndex = null;
            $correctLetter = null;
            $correctText = null;

            if ($correctChoice) {
                $correctText = $correctChoice->choice_text;
                $correctIndex = array_search($correctText, $options, true);
                if ($correctIndex !== false) {
                    $correctLetter = chr(ord('A') + $correctIndex);
                }
            }

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'options' => $options,
                'correct_answer_letter' => $correctLetter,
                'correct_answer_text' => $correctText,
            ];
        })->all();

        if ($questionpack->opens_at) {
            $questionpackData['opens_at'] = $questionpack->opens_at->format('Y-m-d\TH:i:s');
        }
        if ($questionpack->closes_at) {
            $questionpackData['closes_at'] = $questionpack->closes_at->format('Y-m-d\TH:i:s');
        }

        return inertia('admin/questions/questions-packs/view-question-pack', [
            'questionPack' => $questionpackData
        ]);
    }

    /**
     * Show the form for editing the specified question pack.
     */
    public function edit(QuestionPack $questionpack)
    {
        $questionpack->load(['questions.choices']);

        // Siapkan data pack dan pertanyaannya (options + correct answer)
        $questionpackData = $questionpack->toArray();
        $questionpackData['questions'] = collect($questionpack->questions)->map(function (Question $question) {
            $options = $question->choices->pluck('choice_text')->values()->all();
            $correctChoice = $question->choices->firstWhere('is_correct', true);

            $correctIndex = null;
            $correctLetter = null;
            $correctText = null;

            if ($correctChoice) {
                $correctText = $correctChoice->choice_text;
                $correctIndex = array_search($correctText, $options, true);
                if ($correctIndex !== false) {
                    $correctLetter = chr(ord('A') + $correctIndex);
                }
            }

            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'options' => $options,
                'correct_answer_letter' => $correctLetter,
                'correct_answer_text' => $correctText,
            ];
        })->all();

        if ($questionpack->opens_at) {
            // Format for datetime-local input (YYYY-MM-DDTHH:MM)
            $questionpackData['opens_at'] = $questionpack->opens_at->format('Y-m-d\TH:i');
        }
        if ($questionpack->closes_at) {
            // Format for datetime-local input (YYYY-MM-DDTHH:MM)
            $questionpackData['closes_at'] = $questionpack->closes_at->format('Y-m-d\TH:i');
        }

        return inertia('admin/questions/questions-packs/edit-question-packs', [
            'questionPack' => $questionpackData,
        ]);
    }

    /**
     * Update the specified question pack in storage.
     */
    public function update(Request $request, QuestionPack $questionpack)
    {
        $validated = $request->validate([
            'pack_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'test_type' => 'nullable|string',
            'duration' => 'nullable|numeric|min:0',
            'opens_at' => 'nullable|date',
            'closes_at' => 'nullable|date|after:opens_at',
            'questions' => 'nullable|array',
            'questions.*.id' => 'nullable|integer|exists:questions,id',
            'questions.*.question_text' => 'required_with:questions|string',
            'questions.*.question_type' => 'required_with:questions|in:multiple_choice,essay',
            'questions.*.options' => 'required_if:questions.*.question_type,multiple_choice|array',
            'questions.*.options.*' => 'required_with:questions.*.options|string',
            'questions.*.correct_answer' => 'required_if:questions.*.question_type,multiple_choice|string',
        ]);

        DB::transaction(function () use ($validated, $questionpack) {
            $duration = $validated['duration'] ?? $questionpack->duration;

            // Handle duration conversion if it's provided as string (HH:MM:SS format)
            if (is_string($duration) && strpos($duration, ':') !== false) {
                $durationParts = explode(':', $duration);
                $hours = (int) $durationParts[0];
                $minutes = (int) $durationParts[1];
                $seconds = isset($durationParts[2]) ? (int) $durationParts[2] : 0;
                $duration = ($hours * 60) + $minutes + ($seconds / 60);
            }

            $questionpack->update([
                'pack_name' => $validated['pack_name'],
                'description' => $validated['description'] ?? null,
                'test_type' => $validated['test_type'] ?? $questionpack->test_type,
                'duration' => $duration,
                'opens_at' => $validated['opens_at'] ?? null,
                'closes_at' => $validated['closes_at'] ?? null,
            ]);

            $attachedIds = [];

            if (!empty($validated['questions']) && is_array($validated['questions'])) {
                foreach ($validated['questions'] as $questionData) {
                    if (empty($questionData['question_text'])) {
                        continue;
                    }

                    $questionType = $questionData['question_type'] ?? 'multiple_choice';

                    if (!empty($questionData['id'])) {
                        /** @var Question $question */
                        $question = Question::find($questionData['id']);
                        if (!$question) {
                            continue;
                        }
                        $question->update([
                            'question_text' => $questionData['question_text'],
                            'question_type' => $questionType,
                        ]);

                        // Hapus choices lama sebelum membuat yang baru
                        if ($questionType === 'multiple_choice') {
                            $question->choices()->delete();
                        }
                    } else {
                        $question = Question::create([
                            'question_text' => $questionData['question_text'],
                            'question_type' => $questionType,
                        ]);
                    }

                    if ($questionType === 'multiple_choice') {
                        $options = $questionData['options'] ?? [];
                        $correctAnswer = $questionData['correct_answer'] ?? null;

                        $correctIndex = null;
                        if (is_string($correctAnswer) && preg_match('/^[A-Z]$/i', $correctAnswer)) {
                            $correctIndex = ord(strtoupper($correctAnswer)) - ord('A');
                        }

                        foreach (array_values($options) as $idx => $option) {
                            $trimmed = trim($option);
                            if ($trimmed === '') {
                                continue;
                            }

                            $isCorrect = false;

                            if ($correctIndex !== null) {
                                $isCorrect = $idx === $correctIndex;
                            } elseif ($correctAnswer !== null) {
                                $isCorrect = $trimmed === $correctAnswer;
                            }

                            Choice::create([
                                'question_id' => $question->id,
                                'choice_text' => $trimmed,
                                'is_correct' => $isCorrect,
                            ]);
                        }
                    }

                    $attachedIds[] = $question->id;
                }
            }

            if (!empty($attachedIds)) {
                $questionpack->questions()->sync($attachedIds);
            }
        });

        return redirect()->route('admin.questionpacks.index')->with('success', 'Question pack updated successfully!');
    }

    /**
     * Remove the specified question pack from storage.
     */
    public function destroy(QuestionPack $questionpack)
    {
        $questionpack->delete();

        return redirect()->route('admin.questionpacks.index')->with('success', 'Question pack deleted successfully!');
    }
}
