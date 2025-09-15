import React, { useState, useEffect, useCallback } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import './Quiz.css';
import Navbar from '../../components/shared/Navbar/Navbar';
import API from '../../services/axios';

const Quiz = () => {
  const navigate = useNavigate();
  const { subjectId, chapterId } = useParams();
  const [currentQuestion, setCurrentQuestion] = useState(0);
  const [selectedAnswers, setSelectedAnswers] = useState({});
  const [timeLeft, setTimeLeft] = useState(900); // 15 minutes in seconds
  const [quizStarted, setQuizStarted] = useState(false);
  const [showReview, setShowReview] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [quizData, setQuizData] = useState(null);
  const [quizAttempt, setQuizAttempt] = useState(null);
  const [quizResults, setQuizResults] = useState(null);
  const [startTime, setStartTime] = useState(null);

  const fetchQuizData = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      // Fetch quiz data directly by chapter ID
      const quizResponse = await API.get(`/quiz/chapter/${chapterId}`);

      if (!quizResponse.data.success) {
        setError(quizResponse.data.message || 'Failed to load quiz');
        return;
      }

      const quiz = quizResponse.data.quiz;
      setQuizData(quiz);
      setTimeLeft(quiz.timeLimit);

      // If user has previous attempt, show results
      if (quiz.previousAttempt) {
        // Load feedback for previous attempt
        let feedback = null;
        try {
          const feedbackResponse = await API.get(`/quiz/feedback/${quiz.previousAttempt.id}`);
          if (feedbackResponse.data.success) {
            feedback = feedbackResponse.data.data;
            console.log('Previous attempt feedback loaded:', feedback);
          }
        } catch (feedbackErr) {
          console.warn('Failed to load previous attempt feedback:', feedbackErr);
        }

        setQuizResults({
          score: quiz.previousAttempt.score,
          totalQuestions: quiz.questions.length,
          correctAnswers: Math.round((quiz.previousAttempt.score / 100) * quiz.questions.length),
          startedAt: quiz.previousAttempt.startedAt,
          completedAt: quiz.previousAttempt.completedAt,
          duration: quiz.previousAttempt.duration,
          questions: quiz.questions.map(q => ({
            id: q.id,
            question: q.question,
            userAnswer: quiz.previousAttempt.answers.find(a => a.questionId === q.id)?.selectedAnswer || null,
            correctAnswer: q.correctAnswer,
            isCorrect: quiz.previousAttempt.answers.find(a => a.questionId === q.id)?.isCorrect || false,
            options: q.options
          })),
          feedback: feedback
        });
        setShowReview(true);
      }

    } catch (err) {
      console.error('Error fetching quiz:', err);
      setError(err.response?.data?.message || 'Failed to load quiz');
    } finally {
      setLoading(false);
    }
  }, [chapterId]);

  const handleSubmitQuiz = useCallback(async () => {
    if (!quizData || !quizAttempt) return;

    try {
      const duration = startTime ? Math.floor((new Date() - startTime) / 1000) : 0;

      const answers = Object.entries(selectedAnswers).map(([questionIndex, selectedIndex]) => ({
        questionId: quizData.questions[questionIndex]?.id,
        selectedAnswer: quizData.questions[questionIndex]?.options[selectedIndex] || ''
      }));

      const response = await API.post(`/quiz/${quizData.id}/submit`, {
        answers,
        duration
      });

      if (response.data.success) {
        const attemptId = response.data.results.attemptId;

        // Fetch detailed results after successful submission
        try {
          const resultsResponse = await API.get(`/quiz/attempt/${attemptId}`);
          if (resultsResponse.data.success) {
            // Trigger performance analysis immediately after quiz submission
            let feedback = null;
            try {
              console.log('Triggering performance analysis...');
              await API.post('/quiz/analyze-performance', {
                student_quiz_id: attemptId
              });
              console.log('Performance analysis triggered successfully');

              // Wait a moment for analysis to complete, then fetch feedback
              await new Promise(resolve => setTimeout(resolve, 3000));

              const feedbackResponse = await API.get(`/quiz/feedback/${attemptId}`);
              if (feedbackResponse.data.success) {
                feedback = feedbackResponse.data.data;
                console.log('Feedback generated and loaded:', feedback);
              } else {
                console.log('Feedback generation may still be in progress');
              }
            } catch (analysisErr) {
              console.warn('Performance analysis failed:', analysisErr);
              // Try to fetch existing feedback anyway
              try {
                const feedbackResponse = await API.get(`/quiz/feedback/${attemptId}`);
                if (feedbackResponse.data.success) {
                  feedback = feedbackResponse.data.data;
                  console.log('Existing feedback loaded:', feedback);
                }
              } catch (feedbackErr) {
                console.warn('Failed to load feedback:', feedbackErr);
              }
            }

            setQuizResults({
              score: resultsResponse.data.results.score,
              totalQuestions: resultsResponse.data.results.totalQuestions,
              correctAnswers: resultsResponse.data.results.correctAnswers,
              startedAt: resultsResponse.data.results.startedAt,
              completedAt: resultsResponse.data.results.completedAt,
              duration: resultsResponse.data.results.duration,
              questions: resultsResponse.data.results.questions,
              feedback: feedback
            });
            setShowReview(true);
            setQuizStarted(false);
          } else {
            setError('Failed to load quiz results');
          }
        } catch (resultsErr) {
          console.error('Error fetching quiz results:', resultsErr);
          setError('Quiz submitted but failed to load results');
        }
      } else {
        setError(response.data.message || 'Failed to submit quiz');
      }
    } catch (err) {
      console.error('Error submitting quiz:', err);
      setError(err.response?.data?.message || 'Failed to submit quiz');
    }
  }, [quizData, quizAttempt, startTime, selectedAnswers]);

  // Fetch quiz data on component mount
  useEffect(() => {
    fetchQuizData();
  }, [fetchQuizData]);

  // Timer effect
  useEffect(() => {
    if (!quizStarted || showReview || timeLeft <= 0) return;

    const timer = setInterval(() => {
      setTimeLeft(prev => {
        if (prev <= 1) {
          handleSubmitQuiz();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [quizStarted, showReview, timeLeft, handleSubmitQuiz]);


  const handleStartQuiz = async () => {
    // quick client-side checks
    const token = localStorage.getItem('token');
    if (!token) {
      setError('You are not authenticated. Please login and try again.');
      return;
    }

    try {
      const response = await API.post(`/quiz/${quizData.id}/start`);

      if (response.data && response.data.success) {
        setQuizAttempt(response.data.attempt);
        setQuizStarted(true);
        setStartTime(new Date());
        setShowReview(false);
        setError(null);
      } else {
        // backend returned success=false
        const msg = response.data?.message || 'Failed to start quiz';
        console.warn('Start quiz - backend:', response.data);
        setError(msg);
      }
    } catch (err) {
      // surface server response body when available for debugging
      console.error('Error starting quiz:', err);
      const status = err.response?.status;
      const body = err.response?.data;
      // If server says there's an active attempt, resume it instead of blocking
      if (status === 400 && body && body.attempt) {
        console.warn('Resuming active attempt from server response', body.attempt);
        // ensure quiz questions are loaded before switching into attempt mode
        try {
          await fetchQuizData();
        } catch (e) {
          console.warn('Failed to reload quiz data while resuming attempt', e);
        }

        setQuizAttempt(body.attempt);
        // only start if quiz data is available
        if (quizData && quizData.questions && quizData.questions.length > 0) {
          setQuizStarted(true);
        }
        // try to set startTime from server attempt if provided
        try {
          if (body.attempt.startedAt) {
            setStartTime(new Date(body.attempt.startedAt));
          } else {
            setStartTime(new Date());
          }
        } catch (e) {
          setStartTime(new Date());
        }
        setShowReview(false);
        setError(null);
        return;
      }

      if (body && body.message) {
        setError(`Server: ${body.message}`);
      } else if (body) {
        setError(`Server error (${status}): ${JSON.stringify(body)}`);
      } else {
        setError('Network or server error while starting quiz');
      }
    }
  };


  // Format time display
  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  // Handle answer selection
  const handleAnswerSelect = (questionIndex, answerIndex) => {
    setSelectedAnswers(prev => ({
      ...prev,
      [questionIndex]: answerIndex
    }));
  };

  // Navigate to next question
  const handleNext = () => {
    if (currentQuestion < quizData.questions.length - 1) {
      setCurrentQuestion(currentQuestion + 1);
    } else {
      handleSubmitQuiz();
    }
  };

  // Navigate to previous question
  const handlePrev = () => {
    if (currentQuestion > 0) {
      setCurrentQuestion(currentQuestion - 1);
    }
  };

  // Confirm current answer and move to next
  const handleConfirmAnswer = () => {
    if (selectedAnswers[currentQuestion] !== undefined) {
      handleNext();
    }
  };

  // Handle navigation
  const handleHome = () => {
    navigate(`/subjects/${subjectId}/chapter/${chapterId}`);
  };

  // Handle retake quiz
  const handleRetakeQuiz = () => {
    setCurrentQuestion(0);
    setSelectedAnswers({});
    setTimeLeft(quizData.timeLimit);
    setQuizStarted(false);
    setShowReview(false);
    setQuizAttempt(null);
    setQuizResults(null);
    setStartTime(null);
  };

  // Render loading state
  if (loading) {
    return (
      <div className="quiz-page">
        <Navbar />
        <div className="quiz-container">
          <div className="loading">Loading quiz...</div>
        </div>
      </div>
    );
  }

  // Render error state
  if (error) {
    return (
      <div className="quiz-page">
        <Navbar />
        <div className="quiz-container">
          <div className="error">
            <h2>Error</h2>
            <p>{error}</p>
            <button onClick={() => navigate(`/subjects/${subjectId}/chapter/${chapterId}`)}>
              Back to Chapter
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Render quiz not found
  if (!quizData) {
    return (
      <div className="quiz-page">
        <Navbar />
        <div className="quiz-container">
          <div className="no-quiz">
            <h2>No Quiz Available</h2>
            <p>This chapter doesn't have a quiz yet.</p>
            <button onClick={() => navigate(`/subjects/${subjectId}/chapter/${chapterId}`)}>
              Back to Chapter
            </button>
          </div>
        </div>
      </div>
    );
  }

  // Render quiz start screen
  if (!quizStarted && !showReview) {
    return (
      <div className="quiz-page">
        <Navbar />
        <div className="quiz-container">
          <div className="quiz-start">
            <h2>{quizData.title}</h2>
            <div className="quiz-info">
              <p><strong>Questions:</strong> {quizData.questions.length}</p>
              <p><strong>Time Limit:</strong> {formatTime(quizData.timeLimit)}</p>
            </div>
            <div className="quiz-instructions">
              <h3>Instructions:</h3>
              <ul>
                <li>Read each question carefully</li>
                <li>Select the best answer for each question</li>
                <li>You can navigate between questions</li>
                <li>Click "Confirm Answer" to save your selection</li>
                <li>Once you finish, your answers will be submitted automatically</li>
              </ul>
            </div>
            <div className="start-actions">
              <button className="start-button" onClick={handleStartQuiz}>
                Start Quiz
              </button>
              <button className="cancel-button" onClick={handleHome}>
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    );
  }

  // Render quiz review
  const renderReview = () => {
    if (!quizResults) return null;

    return (
      <div className="quiz-review">
        <div className="review-header">
          <h2>Quiz Complete!</h2>
          <div className="results-summary">
            <div className="score-display">
              <span className="score">{quizResults.correctAnswers}/{quizResults.totalQuestions}</span>
              <span className="percentage">({quizResults.score}%)</span>
            </div>
            <p className="result-message">
              {quizResults.score >= 80 ? 'Excellent work!' :
               quizResults.score >= 60 ? 'Good job!' :
               'Keep practicing!'}
            </p>
          </div>
        </div>

        <div className="review-questions">
          {quizResults.questions.map((question, index) => {
            return (
              <div key={question.id} className={`review-question ${question.isCorrect ? 'correct' : 'incorrect'}`}>
                <div className="question-header">
                  <span className="question-number">Question {index + 1}</span>
                  <span className={`result-indicator ${question.isCorrect ? 'correct' : 'incorrect'}`}>
                    {question.isCorrect ? '✓' : '✗'}
                  </span>
                </div>
                <p className="question-text">{question.question}</p>
                <div className="answer-review">
                  <div className="user-answer">
                    <strong>Your answer:</strong> {question.userAnswer || 'Not answered'}
                  </div>
                  {!question.isCorrect && (
                    <div className="correct-answer">
                      <strong>Correct answer:</strong> {question.correctAnswer}
                    </div>
                  )}
                </div>
              </div>
            );
          })}
        </div>

        {/* Post-Quiz Feedback Section */}
        <div className="post-quiz-feedback">
          <h3>AI Performance Analysis</h3>
          {quizResults.feedback ? (
            <div className="feedback-content">
              <div className="feedback-section">
                <h4>Overall Performance</h4>
                <p>{quizResults.feedback.overall_performance || 'Performance analysis not available.'}</p>
              </div>

              {quizResults.feedback.weak_areas && quizResults.feedback.weak_areas.length > 0 && (
                <div className="feedback-section">
                  <h4>Areas for Improvement</h4>
                  <ul>
                    {quizResults.feedback.weak_areas.map((area, index) => (
                      <li key={index}>
                        <strong>{area.concept}</strong>: {area.description || 'Needs improvement'}
                        {area.missed && area.total && ` (${area.missed}/${area.total} missed)`}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {quizResults.feedback.recommendations && quizResults.feedback.recommendations.length > 0 && (
                <div className="feedback-section">
                  <h4>Study Recommendations</h4>
                  <ul>
                    {quizResults.feedback.recommendations.map((rec, index) => (
                      <li key={index}>
                        <strong>{rec.type || 'General'}</strong>: {rec.description || 'Study recommendation'}
                        {rec.priority && ` (Priority: ${rec.priority})`}
                      </li>
                    ))}
                  </ul>
                </div>
              )}

              {quizResults.feedback.study_plan && (
                <div className="feedback-section">
                  <h4>Personalized Study Plan</h4>
                  <p><strong>Duration:</strong> {quizResults.feedback.study_plan.duration_weeks} weeks</p>
                  <p><strong>Daily Study Time:</strong> {quizResults.feedback.study_plan.daily_study_time} minutes</p>
                  {quizResults.feedback.study_plan.schedule && quizResults.feedback.study_plan.schedule.length > 0 && (
                    <div>
                      <p><strong>Schedule:</strong></p>
                      <ul>
                        {quizResults.feedback.study_plan.schedule.map((day, index) => (
                          <li key={index}>
                            <strong>{day.day}</strong>: {day.focus} ({day.estimated_time} min)
                          </li>
                        ))}
                      </ul>
                    </div>
                  )}
                </div>
              )}

              {quizResults.feedback.encouragement_message && (
                <div className="feedback-section">
                  <h4>Encouragement</h4>
                  <p><em>{quizResults.feedback.encouragement_message}</em></p>
                </div>
              )}
            </div>
          ) : (
            <div className="feedback-loading">
              <p>Analysis not available for this attempt.</p>
              <p className="feedback-note">AI performance analysis is generated after completing new quiz attempts.</p>
            </div>
          )}
        </div>

        <div className="review-actions">
          <button className="action-buttons primary" onClick={handleHome}>
            Back to Chapter
          </button>
          <button className="action-buttons secondary" onClick={handleRetakeQuiz}>
            Retake Quiz
          </button>
        </div>
      </div>
    );
  };

  if (showReview) {
    return (
      <div className="quiz-page">
        <Navbar />
        <div className="quiz-container">
          {renderReview()}
        </div>
      </div>
    );
  }

  // defensive: ensure questions array exists before accessing
  const questionsArray = (quizData && Array.isArray(quizData.questions)) ? quizData.questions : [];
  const totalQuestions = questionsArray.length;
  const currentQuestionData = questionsArray[currentQuestion] || { question: '', options: [] };
  const progress = totalQuestions ? ((currentQuestion + 1) / totalQuestions) * 100 : 0;

  return (
    <div className="quiz-page">
      <Navbar />

      <div className="quiz-container">
        {/* Quiz Header */}
        <div className="quiz-header">
          <div className="quiz-progress">
            <span className="question-counter">Question {currentQuestion + 1}/{quizData.questions.length}</span>
            <div className="progress-bar">
              <div className="progress-fill" style={{ width: `${progress}%` }}></div>
            </div>
            <span className="progress-percentage">{Math.round(progress)}%</span>
          </div>
          <div className="quiz-timer">
            <span className="timer-display">{formatTime(timeLeft)}</span>
          </div>
        </div>

        {/* Question Section */}
        <div className="question-section">
          <h2 className="question-text">{currentQuestionData.question}</h2>

          <div className="answers-section">
            {currentQuestionData.options.map((option, index) => (
              <label key={index} className={`answer-option ${selectedAnswers[currentQuestion] === index ? 'selected' : ''}`}>
                <input
                  type="radio"
                  name="answer"
                  value={index}
                  checked={selectedAnswers[currentQuestion] === index}
                  onChange={() => handleAnswerSelect(currentQuestion, index)}
                />
                <span className="option-text">{option}</span>
              </label>
            ))}
          </div>

          {selectedAnswers[currentQuestion] !== undefined && (
            <button className="confirm-button" onClick={handleConfirmAnswer}>
              Confirm Answer
            </button>
          )}
        </div>

        {/* Bottom Navigation */}
        <div className="quiz-navigation">
          <div className="nav-menu-indicator">
            <span className="menu-icon">≡</span>
            <span className="nav-text">{quizData.title}</span>
          </div>

          <div className="nav-controls">
            <button
              className="nav-button home-button"
              onClick={handleHome}
            >
              Home
            </button>
            <button
              className="nav-button prev-button"
              onClick={handlePrev}
              disabled={currentQuestion === 0}
            >
              Prev
            </button>
            <button
              className="nav-button next-button"
              onClick={handleNext}
              disabled={selectedAnswers[currentQuestion] === undefined}
            >
              {currentQuestion === quizData.questions.length - 1 ? 'Finish' : 'Next'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Quiz;
