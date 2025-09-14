import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import './Quiz.css';
import Navbar from '../../components/shared/Navbar/Navbar';

const Quiz = () => {
  const navigate = useNavigate();
  const { chapterId } = useParams();
  const [currentQuestion, setCurrentQuestion] = useState(0);
  const [selectedAnswers, setSelectedAnswers] = useState({});
  const [timeLeft, setTimeLeft] = useState(600); // 10 minutes in seconds
  const [quizStarted] = useState(true);
  const [showReview, setShowReview] = useState(false);

  // Mock quiz data
  const quizData = {
    title: 'Quiz of AI',
    totalQuestions: 10,
    timeLimit: 600, // 10 minutes
    questions: [
      {
        id: 1,
        question: 'AI is the main driver of emerging technologies like big data, robotics and IoT.',
        options: ['Yes', 'No', "Can't say", 'Maybe'],
        correctAnswer: 0
      },
      {
        id: 2,
        question: 'Machine Learning is a subset of Artificial Intelligence.',
        options: ['True', 'False', 'Sometimes', 'Depends on context'],
        correctAnswer: 0
      },
      {
        id: 3,
        question: 'Which of the following is NOT a type of machine learning?',
        options: ['Supervised Learning', 'Unsupervised Learning', 'Reinforcement Learning', 'Deterministic Learning'],
        correctAnswer: 3
      },
      {
        id: 4,
        question: 'Neural networks are inspired by the human brain.',
        options: ['Yes', 'No', 'Partially', 'Only in theory'],
        correctAnswer: 0
      },
      {
        id: 5,
        question: 'What does NLP stand for in AI?',
        options: ['Natural Language Processing', 'Neural Learning Process', 'Network Learning Protocol', 'None of the above'],
        correctAnswer: 0
      },
      {
        id: 6,
        question: 'Deep Learning requires large amounts of data to be effective.',
        options: ['Always', 'Never', 'Usually', 'Rarely'],
        correctAnswer: 2
      },
      {
        id: 7,
        question: 'Which company developed ChatGPT?',
        options: ['Google', 'Microsoft', 'OpenAI', 'Meta'],
        correctAnswer: 2
      },
      {
        id: 8,
        question: 'Computer Vision is a field of AI that focuses on:',
        options: ['Speech recognition', 'Image analysis', 'Text processing', 'Data mining'],
        correctAnswer: 1
      },
      {
        id: 9,
        question: 'AI algorithms can exhibit bias if trained on biased data.',
        options: ['True', 'False', 'Only in some cases', 'Never'],
        correctAnswer: 0
      },
      {
        id: 10,
        question: 'The Turing Test was proposed to evaluate:',
        options: ['Computer speed', 'Machine intelligence', 'Data accuracy', 'Network security'],
        correctAnswer: 1
      }
    ]
  };

  // Timer effect
  useEffect(() => {
    if (!quizStarted || showReview) return;

    const timer = setInterval(() => {
      setTimeLeft(prev => {
        if (prev <= 1) {
          setShowReview(true);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [quizStarted, showReview]);

  // Format time display
  const formatTime = (seconds) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  // Handle answer selection
  const handleAnswerSelect = (answerIndex) => {
    setSelectedAnswers(prev => ({
      ...prev,
      [currentQuestion]: answerIndex
    }));
  };

  // Navigate to next question
  const handleNext = () => {
    if (currentQuestion < quizData.totalQuestions - 1) {
      setCurrentQuestion(currentQuestion + 1);
    } else {
      setShowReview(true);
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

  // Calculate quiz results
  const calculateResults = () => {
    let correct = 0;
    quizData.questions.forEach((question, index) => {
      if (selectedAnswers[index] === question.correctAnswer) {
        correct++;
      }
    });
    return {
      correct,
      total: quizData.totalQuestions,
      percentage: Math.round((correct / quizData.totalQuestions) * 100)
    };
  };

  // Handle navigation
  const handleHome = () => {
    navigate(`/subjects/chapter/${chapterId}`);
  };

  // Render quiz review
  const renderReview = () => {
    const results = calculateResults();
    
    return (
      <div className="quiz-review">
        <div className="review-header">
          <h2>Quiz Complete!</h2>
          <div className="results-summary">
            <div className="score-display">
              <span className="score">{results.correct}/{results.total}</span>
              <span className="percentage">({results.percentage}%)</span>
            </div>
            <p className="result-message">
              {results.percentage >= 80 ? 'Excellent work!' : 
               results.percentage >= 60 ? 'Good job!' : 
               'Keep practicing!'}
            </p>
          </div>
        </div>

        <div className="review-questions">
          {quizData.questions.map((question, index) => {
            const userAnswer = selectedAnswers[index];
            const isCorrect = userAnswer === question.correctAnswer;
            
            return (
              <div key={question.id} className={`review-question ${isCorrect ? 'correct' : 'incorrect'}`}>
                <div className="question-header">
                  <span className="question-number">Question {index + 1}</span>
                  <span className={`result-indicator ${isCorrect ? 'correct' : 'incorrect'}`}>
                    {isCorrect ? '✓' : '✗'}
                  </span>
                </div>
                <p className="question-text">{question.question}</p>
                <div className="answer-review">
                  <div className="user-answer">
                    <strong>Your answer:</strong> {userAnswer !== undefined ? question.options[userAnswer] : 'Not answered'}
                  </div>
                  {!isCorrect && (
                    <div className="correct-answer">
                      <strong>Correct answer:</strong> {question.options[question.correctAnswer]}
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
          <button className="action-button secondary" onClick={() => window.location.reload()}>
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
  const progress = ((currentQuestion + 1) / quizData.totalQuestions) * 100;

  return (
    <div className="quiz-page">
      <Navbar />
      
      <div className="quiz-container">
        {/* Quiz Header */}
        <div className="quiz-header">
          <div className="quiz-progress">
            <span className="question-counter">Question {currentQuestion + 1}/{quizData.totalQuestions}</span>
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
                  onChange={() => handleAnswerSelect(index)}
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
              {currentQuestion === quizData.totalQuestions - 1 ? 'Finish' : 'Next'}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Quiz;
