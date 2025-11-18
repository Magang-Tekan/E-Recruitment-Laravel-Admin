import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Dashboard', href: '/dashboard' },
  { title: 'Test & Assessment', href: '#' },
  { title: 'Question Sets', href: '/dashboard/questions/questions-set' },
  { title: 'Edit Question', href: '#' },
];

interface QuestionItem {
  id: number;
  question_text: string;
  options: string[];
  correct_answer: string;
  question_type: string;
  created_at: string;
  updated_at: string;
  question_packs?: { id: number; pack_name: string }[];
}

interface Props {
  question: QuestionItem;
}

export default function EditQuestionPage({ question }: Props) {
  const [questionText, setQuestionText] = useState(question.question_text);
  const [questionType, setQuestionType] = useState<'multiple_choice' | 'essay'>(question.question_type as 'multiple_choice' | 'essay' || 'multiple_choice');
  const [options, setOptions] = useState(question.question_type === 'essay' ? [] : (question.options || []));
  const [correctAnswer, setCorrectAnswer] = useState(question.question_type === 'essay' ? '' : (question.correct_answer || ''));
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isDirty, setIsDirty] = useState(false);

  // Set isDirty when there's a change from the original data
  useEffect(() => {
    const isChanged = 
      questionText !== question.question_text ||
      questionType !== question.question_type ||
      (questionType === 'multiple_choice' && (
        JSON.stringify(options) !== JSON.stringify(question.options || []) ||
        correctAnswer !== (question.correct_answer || '')
      )) ||
      (questionType === 'essay' && question.question_type === 'multiple_choice');
    
    setIsDirty(isChanged);
  }, [questionText, questionType, options, correctAnswer, question]);

  const handleQuestionTypeChange = (type: 'multiple_choice' | 'essay') => {
    setQuestionType(type);
    
    // Reset options and correct_answer when switching to essay
    if (type === 'essay') {
      setOptions([]);
      setCorrectAnswer('');
    } else {
      // Initialize with default options when switching to multiple_choice
      if (options.length === 0) {
        setOptions(['', '']);
        setCorrectAnswer('');
      }
    }
  };

  const handleOptionChange = (index: number, value: string) => {
    const newOptions = [...options];
    newOptions[index] = value;
    setOptions(newOptions);

    // If the correct answer was this option before the change, update it
    if (question.options[index] === correctAnswer) {
      setCorrectAnswer(value);
    }
  };

  const addOption = () => {
    setOptions([...options, '']);
  };

  const removeOption = (index: number) => {
    if (options.length <= 2) {
      alert('At least 2 options are required');
      return;
    }

    const newOptions = [...options];
    
    // If the correct answer was the removed option, reset it
    if (options[index] === correctAnswer) {
      setCorrectAnswer(newOptions[0]);
    }
    
    newOptions.splice(index, 1);
    setOptions(newOptions);
  };

  const validateForm = () => {
    if (!questionText.trim()) {
      alert('Question text is required');
      return false;
    }

    // For multiple choice questions, validate options and correct answer
    if (questionType === 'multiple_choice') {
      if (options.some(opt => !opt.trim())) {
        alert('All options must have content');
        return false;
      }

      if (!correctAnswer || !options.includes(correctAnswer)) {
        alert('You must select a valid correct answer');
        return false;
      }
    }
    // For essay questions, no additional validation needed

    return true;
  };

  const handleSave = () => {
    if (!validateForm()) {
      return;
    }

    setIsSubmitting(true);

    const payload: any = {
      question_text: questionText,
      question_type: questionType,
    };
    
    // Only include options and correct_answer for multiple choice questions
    if (questionType === 'multiple_choice') {
      payload.options = options;
      payload.correct_answer = correctAnswer;
    } else {
      // For essay questions, send empty array for options
      payload.options = [];
      payload.correct_answer = '';
    }

    router.put(`/dashboard/questions/${question.id}`, payload, {
      onSuccess: () => {
        router.visit('/dashboard/questions/questions-set');
      },
      onError: (errors) => {
        setIsSubmitting(false);
        alert('Failed to update the question. Please check the form and try again.');
      }
    });
  };

  const handleCancel = () => {
    if (isDirty) {
      if (confirm('You have unsaved changes. Are you sure you want to leave?')) {
        router.visit('/dashboard/questions/questions-set');
      }
    } else {
      router.visit('/dashboard/questions/questions-set');
    }
  };

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Edit Question" />
      <div className="flex flex-col p-6 gap-6">
        <div className="flex items-center justify-between">
          <Button
            variant="outline"
            className="gap-2"
            onClick={handleCancel}
          >
            <ArrowLeft className="h-4 w-4" /> Back to Questions
          </Button>
          
          <div className="flex gap-2">
            <Button
              variant="outline"
              onClick={() => router.visit(`/dashboard/questions/questions-set/view/${question.id}`)}
            >
              View Question
            </Button>
          </div>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Edit Question</CardTitle>
          </CardHeader>
          <CardContent className="space-y-6">
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Question Type</label>
                <select
                  value={questionType}
                  onChange={(e) => handleQuestionTypeChange(e.target.value as 'multiple_choice' | 'essay')}
                  className="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                  <option value="multiple_choice">Multiple Choice</option>
                  <option value="essay">Essay</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Question Text</label>
                <textarea
                  value={questionText}
                  onChange={(e) => setQuestionText(e.target.value)}
                  className="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500 min-h-[100px]"
                  placeholder="Enter question text"
                />
              </div>

              {questionType === 'multiple_choice' && (
                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1">Options</label>
                  <p className="text-xs text-gray-500 mb-2">Select the radio button next to the correct answer</p>
                  
                  {options.map((option, index) => (
                    <div key={index} className="flex items-center gap-3 mb-2">
                      <input
                        type="radio"
                        name="correctAnswer"
                        checked={option === correctAnswer}
                        onChange={() => setCorrectAnswer(option)}
                        className="h-4 w-4 text-blue-600 focus:ring-blue-500"
                      />
                      <input
                        type="text"
                        value={option}
                        onChange={(e) => handleOptionChange(index, e.target.value)}
                        className="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        placeholder={`Option ${String.fromCharCode(65 + index)}`}
                      />
                      {options.length > 2 && (
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => removeOption(index)}
                          className="text-red-500 h-8 w-8 p-0"
                        >
                          ×
                        </Button>
                      )}
                    </div>
                  ))}
                  
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={addOption}
                    className="mt-2"
                  >
                    + Add Option
                  </Button>
                </div>
              )}

              {questionType === 'essay' && (
                <div className="p-3 bg-blue-50 rounded-md">
                  <p className="text-sm text-blue-700">
                    This is an essay question. Candidates will provide a written answer when taking the test.
                  </p>
                </div>
              )}
            </div>

            <div className="flex justify-end gap-3 pt-4 border-t">
              <Button
                variant="outline"
                onClick={handleCancel}
              >
                Cancel
              </Button>
              <Button
                className="bg-blue-500 text-white hover:bg-blue-600"
                onClick={handleSave}
                disabled={isSubmitting || !isDirty}
              >
                {isSubmitting ? 'Saving...' : 'Save Changes'}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
