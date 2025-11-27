import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type BreadcrumbItem } from '@/types';
import { Loader2, ArrowLeft } from 'lucide-react';  
import { ScrollArea } from '@/components/ui/scroll-area';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Test & Assessment', href: '#' },
  { title: 'Question Packs', href: '/dashboard/questionpacks' },
  { title: 'Edit Question Pack', href: '#' },
];

interface ExistingQuestion {
  id: number;
  question_text: string;
  question_type: 'multiple_choice' | 'essay';
  options?: string[];
  correct_answer_letter?: string | null;
  correct_answer_text?: string | null;
}

interface QuestionPackProps {
  id: number;
  pack_name: string;
  description: string;
  test_type: string;
  duration: number;
  opens_at?: string | null;
  closes_at?: string | null;
  questions: ExistingQuestion[];
}

interface NewQuestion {
  id?: number;
  question_text: string;
  question_type: 'multiple_choice' | 'essay';
  options: string[];
  correct_answer: string;
}

interface Props {
  questionPack: QuestionPackProps;
}

export default function EditQuestionPacks({ questionPack }: Props) {
  const [packName, setPackName] = useState(questionPack.pack_name);
  const [description, setDescription] = useState(questionPack.description);
  const [testType, setTestType] = useState(questionPack.test_type);

  const [opensAt, setOpensAt] = useState(() => {
    if (questionPack.opens_at) {
      const date = new Date(questionPack.opens_at);
      return date.toISOString().slice(0, 16);
    }
    return '';
  });

  const [closesAt, setClosesAt] = useState(() => {
    if (questionPack.closes_at) {
      const date = new Date(questionPack.closes_at);
      return date.toISOString().slice(0, 16);
    }
    return '';
  });

  const [duration, setDuration] = useState(() => {
    const totalMinutes = questionPack.duration;
    const hours = Math.floor(totalMinutes / 60).toString().padStart(2, '0');
    const minutes = (totalMinutes % 60).toString().padStart(2, '0');
    return { hours, minutes, seconds: '00' };
  });

  const [step, setStep] = useState<1 | 2>(1);
  const [searchQuery, setSearchQuery] = useState('');
  const [questions, setQuestions] = useState<NewQuestion[]>(() =>
    (questionPack.questions || []).map((q) => ({
      id: q.id,
      question_text: q.question_text,
      question_type: q.question_type ?? 'multiple_choice',
      options: q.options && q.options.length > 0 ? q.options : ['', '', ''],
      correct_answer: (q.correct_answer_letter || '').toString().toUpperCase(),
    })),
  );

  const [isSubmitting, setIsSubmitting] = useState(false);

  const formatDurationToMinutes = () =>
    parseInt(duration.hours || '0') * 60 + parseInt(duration.minutes || '0');

  const addEmptyQuestion = () => {
    setQuestions((prev) => [
      ...prev,
      {
        id: undefined,
        question_text: '',
        question_type: 'multiple_choice',
        options: ['', '', ''],
        correct_answer: '',
      },
    ]);
  };

  const updateQuestion = (index: number, updater: (q: NewQuestion) => NewQuestion) => {
    setQuestions((prev) => prev.map((q, idx) => (idx === index ? updater(q) : q)));
  };

  const removeQuestion = (index: number) => {
    setQuestions((prev) => prev.filter((_, idx) => idx !== index));
  };

  const handleSubmit = () => {
    setIsSubmitting(true);

    // Validasi questions mirip halaman create
    if (questions.length === 0) {
      alert('Tambahkan minimal 1 pertanyaan untuk pack ini.');
      setIsSubmitting(false);
      return;
    }

    for (const q of questions) {
      if (!q.question_text.trim()) {
        alert('Semua pertanyaan harus memiliki teks.');
        setIsSubmitting(false);
        return;
      }

      if (q.question_type === 'multiple_choice') {
        const validOptions = q.options.map((o) => o.trim()).filter((o) => o !== '');
        if (validOptions.length < 2) {
          alert('Setiap pertanyaan multiple choice harus memiliki minimal 2 opsi.');
          setIsSubmitting(false);
          return;
        }

        const upper = q.correct_answer.trim().toUpperCase();
        const allowedLetters = Array.from({ length: validOptions.length }, (_, i) =>
          String.fromCharCode('A'.charCodeAt(0) + i),
        );

        if (!upper || !allowedLetters.includes(upper)) {
          alert(`Correct answer harus berupa huruf ${allowedLetters.join(', ')} sesuai jumlah opsi.`);
          setIsSubmitting(false);
          return;
        }
      }
    }

    const durationInMinutes = formatDurationToMinutes();

    router.put(
      `/dashboard/questionpacks/${questionPack.id}`,
      {
        pack_name: packName,
        description,
        test_type: testType,
        duration: durationInMinutes,
        opens_at: opensAt,
        closes_at: closesAt,
        questions: questions.map((q) => ({
          id: q.id,
          question_text: q.question_text,
          question_type: q.question_type,
          options: q.question_type === 'multiple_choice' ? q.options : [],
          correct_answer: q.question_type === 'multiple_choice' ? q.correct_answer.toUpperCase() : null,
        })),
      },
      {
        onFinish: () => setIsSubmitting(false),
      },
    );
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Edit Question Pack" />
      <div className="flex flex-col p-6 gap-6">
        <Card className="w-full">
          <CardHeader>
            <CardTitle>
              {step === 1
                ? `Edit Question Pack - Step 1 (Pack Info)`
                : `Edit Question Pack - Step 2 (Questions)`}
            </CardTitle>
          </CardHeader>
          <CardContent className="flex flex-col gap-6">
            <div className="space-y-2">
              <Label htmlFor="packName" className="text-blue-500">
                Pack Name
              </Label>
              <Input
                id="packName"
                placeholder="Enter pack name"
                value={packName}
                onChange={(e) => setPackName(e.target.value)}
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="description" className="text-blue-500">
                Description
              </Label>
              <Input
                id="description"
                placeholder="Enter description"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="testType" className="text-blue-500">
                  Test Type
                </Label>
                <Select value={testType} onValueChange={(value) => setTestType(value)}>
                  <SelectTrigger id="testType">
                    <SelectValue placeholder="Select test type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="Logic">Logic</SelectItem>
                    <SelectItem value="Emotional">Emotional</SelectItem>
                    <SelectItem value="Personality">Personality</SelectItem>
                    <SelectItem value="Technical">Technical</SelectItem>
                    <SelectItem value="Leadership">Leadership</SelectItem>
                    <SelectItem value="General">General</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="duration" className="text-blue-500">
                  Test Duration
                </Label>
                <div className="flex items-center gap-2 border rounded-lg px-4 py-2 w-full">
                  <input
                    type="number"
                    min="0"
                    max="23"
                    className="w-10 text-center text-gray-500 outline-none bg-transparent"
                    value={duration.hours}
                    onChange={(e) => setDuration((prev) => ({ ...prev, hours: e.target.value }))}
                  />
                  <span className="text-gray-400">:</span>
                  <input
                    type="number"
                    min="0"
                    max="59"
                    className="w-10 text-center text-gray-500 outline-none bg-transparent"
                    value={duration.minutes}
                    onChange={(e) => setDuration((prev) => ({ ...prev, minutes: e.target.value }))}
                  />
                  <span className="text-gray-400">:</span>
                  <input
                    type="number"
                    min="0"
                    max="59"
                    className="w-10 text-center text-gray-500 outline-none bg-transparent"
                    value={duration.seconds}
                    onChange={(e) => setDuration((prev) => ({ ...prev, seconds: e.target.value }))}
                  />
                </div>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="opensAt" className="text-blue-500">
                  Opens At
                </Label>
                <Input
                  id="opensAt"
                  type="datetime-local"
                  value={opensAt}
                  onChange={(e) => setOpensAt(e.target.value)}
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="closesAt" className="text-blue-500">
                  Closes At
                </Label>
                <Input
                  id="closesAt"
                  type="datetime-local"
                  value={closesAt}
                  onChange={(e) => setClosesAt(e.target.value)}
                />
              </div>
            </div>

            {step === 2 && (
              <div className="space-y-4">
                <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                  <div className="space-y-1">
                    <Label className="text-blue-500">Questions in this Pack</Label>
                    <p className="text-xs text-gray-500">
                      Cari berdasarkan nomor (1, 2, 3, ...) atau isi teks soal.
                    </p>
                  </div>
                  <div className="flex flex-1 gap-2 md:flex-none">
                    <Input
                      placeholder="Search question number or text..."
                      className="max-w-xs"
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                    />
                    <Button
                      type="button"
                      variant="outline"
                      onClick={() => setSearchQuery('')}
                      className="text-xs"
                      disabled={!searchQuery.trim()}
                    >
                      Clear
                    </Button>
                    <Button
                      type="button"
                      className="bg-blue-500 text-white hover:bg-blue-600"
                      onClick={addEmptyQuestion}
                    >
                      + Add Question
                    </Button>
                  </div>
                </div>

                {questions.length === 0 ? (
                  <p className="text-sm text-gray-500">
                    Belum ada pertanyaan. Klik &quot;Add Question&quot; untuk menambahkan.
                  </p>
                ) : (
                  <div className="space-y-4">
                    {questions
                      .map((q, index) => ({ q, index }))
                      .filter(({ q, index }) => {
                        if (!searchQuery.trim()) return true;
                        const query = searchQuery.trim().toLowerCase();
                        const byNumber = (index + 1).toString().includes(query);
                        const byText = q.question_text.toLowerCase().includes(query);
                        return byNumber || byText;
                      })
                      .map(({ q: question, index }) => (
                      <div
                        key={index}
                        className="space-y-3 rounded-lg border bg-white p-4 shadow-sm"
                      >
                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-2">
                            <span className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-500 text-xs font-semibold text-white">
                              {index + 1}
                            </span>
                            <span className="text-sm font-medium">Question</span>
                            <span className="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-medium uppercase text-blue-700">
                              {question.question_type === 'multiple_choice' ? 'Multiple Choice' : 'Essay'}
                            </span>
                          </div>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => removeQuestion(index)}
                          >
                            Remove
                          </Button>
                        </div>

                        <div className="space-y-2">
                          <Label className="text-xs">Question Text</Label>
                          <Input
                            value={question.question_text}
                            onChange={(e) =>
                              updateQuestion(index, (q) => ({ ...q, question_text: e.target.value }))
                            }
                            placeholder="Masukkan teks pertanyaan"
                          />
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                          <div className="space-y-2">
                            <Label className="text-xs">Question Type</Label>
                            <Select
                              value={question.question_type}
                              onValueChange={(value) =>
                                updateQuestion(index, (q) => ({
                                  ...q,
                                  question_type: value as NewQuestion['question_type'],
                                  options: value === 'multiple_choice' ? q.options || ['', '', ''] : [],
                                  correct_answer: value === 'multiple_choice' ? q.correct_answer : '',
                                }))
                              }
                            >
                              <SelectTrigger>
                                <SelectValue placeholder="Select type" />
                              </SelectTrigger>
                              <SelectContent>
                                <SelectItem value="multiple_choice">Multiple Choice</SelectItem>
                                <SelectItem value="essay">Essay</SelectItem>
                              </SelectContent>
                            </Select>
                          </div>

                          {question.question_type === 'multiple_choice' && (
                            <div className="space-y-2">
                              <Label className="text-xs">Correct Answer (A, B, C, ...)</Label>
                              <Select
                                value={question.correct_answer.toUpperCase()}
                                onValueChange={(value) =>
                                  updateQuestion(index, (q) => ({ ...q, correct_answer: value }))
                                }
                              >
                                <SelectTrigger>
                                  <SelectValue placeholder="Pilih jawaban benar (A, B, C, ...)" />
                                </SelectTrigger>
                                <SelectContent>
                                  {question.options
                                    .map((o) => o.trim())
                                    .map((opt, idx) => ({ opt, idx }))
                                    .filter(({ opt }) => opt !== '')
                                    .map(({ idx }) => {
                                      const letter = String.fromCharCode('A'.charCodeAt(0) + idx);
                                      return (
                                        <SelectItem key={letter} value={letter}>
                                          {letter}
                                        </SelectItem>
                                      );
                                    })}
                                </SelectContent>
                              </Select>
                            </div>
                          )}
                        </div>

                        {question.question_type === 'multiple_choice' && (
                          <div className="space-y-2">
                            <Label className="text-xs">Options (A, B, C, ...)</Label>
                            <div className="space-y-2">
                              <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                                {question.options.map((opt, idx) => (
                                  <Input
                                    key={idx}
                                    value={opt}
                                    placeholder={`Option ${String.fromCharCode('A'.charCodeAt(0) + idx)}`}
                                    onChange={(e) =>
                                      updateQuestion(index, (q) => {
                                        const nextOptions = [...q.options];
                                        nextOptions[idx] = e.target.value;
                                        return { ...q, options: nextOptions };
                                      })
                                    }
                                  />
                                ))}
                              </div>
                              <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                  updateQuestion(index, (q) => ({
                                    ...q,
                                    options:
                                      q.options.length >= 26 ? q.options : [...q.options, ''],
                                  }))
                                }
                              >
                                + Add Option
                              </Button>
                            </div>
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            )}
          </CardContent>
          <CardFooter className="flex justify-between p-6 pt-4">
            <Button
              variant="outline"
              className="gap-2"
              onClick={() => {
                if (step === 1) {
                  router.visit('/dashboard/questionpacks');
                } else {
                  setStep(1);
                }
              }}
            >
              <ArrowLeft className="h-4 w-4" /> {step === 1 ? 'Back to Question Packs' : 'Back to Pack Info'}
            </Button>
            {step === 1 ? (
              <Button
                type="button"
                className="bg-blue-500 text-white hover:bg-blue-600"
                disabled={!packName || !description || !testType}
                onClick={() => setStep(2)}
              >
                Next: Edit Questions
              </Button>
            ) : (
              <Button
                onClick={handleSubmit}
                className="bg-blue-500 text-white hover:bg-blue-600"
                disabled={isSubmitting || questions.length === 0}
              >
                {isSubmitting ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Saving...
                  </>
                ) : (
                  `Save Changes (${questions.length} Questions)`
                )}
              </Button>
            )}
          </CardFooter>
        </Card>
      </div>
    </AppLayout>
  );
}


