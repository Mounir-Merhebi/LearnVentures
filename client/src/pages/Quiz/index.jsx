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

      // First, get chapter data to find the lesson ID
      const chapterResponse = await API.get(`/chapters/${chapterId}`);
      const chapterData = chapterResponse.data;

      if (!chapterData.lessons || chapterData.lessons.length === 0) {
        setError('No lessons found for this chapter');
        return;
      }

      // Use the first lesson to get the quiz
      const lessonId = chapterData.lessons[0].id;

      // Fetch quiz data
      const quizResponse = await API.get(`/quiz/lesson/${lessonId}`);

      if (!quizResponse.data.success) {
        setError(quizResponse.data.message || 'Failed to load quiz');
        return;
      }

      const quiz = quizResponse.data.quiz;
      setQuizData(quiz);
      setTimeLeft(quiz.timeLimit);

      // If user has previous attempt, show results
      if (quiz.previousAttempt) {
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
          }))
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

      const answers = Object.entries(selectedAnswers).map(([questionId, selectedIndex]) => ({
        questionId: parseInt(questionId),
        selectedAnswer: quizData.questions[selectedIndex]?.options[selectedIndex] || ''
      }));

      const response = await API.post(`/quiz/${quizData.id}/submit`, {
        answers,
        duration
      });

      if (response.data.success) {
        setQuizResults(response.data.results);
        setShowReview(true);
        setQuizStarted(false);
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
    try {
      const response = await API.post(`/quiz/${quizData.id}/start`);

      if (response.data.success) {
        setQuizAttempt(response.data.attempt);
        setQuizStarted(true);
        setStartTime(new Date());
        setShowReview(false);
      } else {
        setError(response.data.message || 'Failed to start quiz');
      }
    } catch (err) {
      console.error('Error starting quiz:', err);
      setError(err.response?.data?.message || 'Failed to start quiz');
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

        <div className="review-actions">
          <button className="action-button primary" onClick={handleHome}>
            Back to Chapter
          </button>
          <button className="action-button secondary" onClick={handleRetakeQuiz}>
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

  const currentQuestionData = quizData.questions[currentQuestion];
  const progress = ((currentQuestion + 1) / quizData.questions.length) * 100;

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
