import React from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import './Chapter.css';
import Navbar from '../../components/shared/Navbar/Navbar';
import {
  ArrowLeft,
  Play,
  BookOpen,
  Video,
  HelpCircle,
  List,
  CheckCircle,
  Circle,
  FileText,
  Target,
} from 'lucide-react';

const Chapter = () => {
  const navigate = useNavigate();
  const { chapterId } = useParams();

  const chapterData = {
    subject: 'Mathematics',
    chapterTitle: 'Algebra',
    chapterNumber: 1,
    totalLessons: 15,
    completedLessons: 8,
    totalQuizes: 4,
    totalQuiz: 1,
    topics: [
      {
        id: 1,
        title: 'Topic 1: Multiplication',
        description: 'Learn multiplications between numbers',
        totalEpisodes: 6,
        totalVideos: 2,
        totalQuiz: 1,
        progress: 100,
        isCompleted: true,
        lessons: [
          { id: 1, title: 'Introduction to Algebra', type: 'video', isCompleted: true, duration: '12 min' },
          { id: 2, title: 'What is multiplication', type: 'lesson', isCompleted: true, duration: '8 min' },
          { id: 3, title: 'Introduction to Algebra', type: 'video', isCompleted: true, duration: '15 min' },
          { id: 4, title: 'What is multiplication', type: 'lesson', isCompleted: true, duration: '10 min' },
        ],
      },
      {
        id: 2,
        title: 'Topic 2: Division',
        description: 'Learn multiplications between numbers',
        totalEpisodes: 4,
        totalVideos: 2,
        totalQuiz: 1,
        progress: 60,
        isCompleted: false,
        lessons: [
          { id: 5, title: 'Introduction to Division', type: 'video', isCompleted: true, duration: '14 min' },
          { id: 6, title: 'What is Division', type: 'lesson', isCompleted: true, duration: '9 min' },
          { id: 7, title: 'more into Division', type: 'video', isCompleted: false, duration: '16 min' },
          { id: 8, title: 'What is multiplication', type: 'lesson', isCompleted: false, duration: '11 min' },
        ],
      },
    ],
    learningObjectives: [
      'Introduction to multiplication',
      'Introduction to multiplication',
      'Introduction to multiplication',
      'Introduction to multiplication',
      'Introduction to multiplication',
    ],
  };

  const handleBackToMathematics = () => {
    navigate('/mathematics');
  };

  const handleLessonClick = (lesson) => {
    navigate(`/mathematics/chapter/${chapterId}/lesson/${lesson.id}`);
  };

  const handleQuizClick = () => {
    navigate(`/mathematics/chapter/${chapterId}/quiz`);
  };

  return (
    <div className="chapter-page">
      <Navbar />

      <div className="chapter-container">
        {/* Back Button */}
        <button className="back-button" onClick={handleBackToMathematics}>
          <ArrowLeft className="back-arrow" size={18} />
          Back to Mathematics
        </button>

        {/* Chapter Header */}
        <div className="chapter-header">
          <div className="chapter-video-section">
            <div className="video-thumbnail">
              <div className="chapter-badge">chapter</div>
              <div className="video-placeholder">
                <div className="play-button">
                  <Play size={28} />
                </div>
              </div>
            </div>
            <div className="chapter-info">
              <h1 className="chapter-title">{chapterData.chapterTitle}</h1>
              <div className="chapter-stats">
                <span className="stat-item">
                  <BookOpen size={16} /> {chapterData.totalLessons} lessons
                </span>
                <span className="stat-item">
                  <Video size={16} /> {chapterData.totalVideos} videos
                </span>
                <span className="stat-item">
                  <HelpCircle size={16} /> {chapterData.totalQuiz} quiz
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Main Content */}
        <div className="chapter-main">
          {/* Topics Section */}
          <div className="topics-section">
            {chapterData.topics.map((topic) => (
              <div key={topic.id} className="topic-section">
                {/* Topic Header */}
                <div className="topic-header">
                  <div className="topic-title-section">
                    <h2 className="topic-title">{topic.title}</h2>
                    <p className="topic-description">{topic.description}</p>
                    <div className="topic-stats">
                      <span>
                        <List size={16} /> {topic.totalEpisodes} Episodes
                      </span>
                      <span>
                        <Video size={16} /> {topic.totalVideos} videos
                      </span>
                      <span>
                        <HelpCircle size={16} /> {topic.totalQuiz} quiz
                      </span>
                    </div>
                  </div>
                  <div className="topic-progress-section">
                    <div className="progress-circle">
                      <span className="progress-text">{topic.progress}% completed</span>
                    </div>
                  </div>
                </div>

                {/* Lessons List */}
                <div className="lessons-list">
                  {topic.lessons.map((lesson) => (
                    <div
                      key={lesson.id}
                      className={`lesson-item ${lesson.isCompleted ? 'completed' : 'pending'}`}
                      onClick={() => handleLessonClick(lesson)}
                    >
                      <div className="lesson-status">
                        <div className={`status-icon ${lesson.isCompleted ? 'completed' : 'pending'}`}>
                          {lesson.isCompleted ? <CheckCircle size={16} /> : <Circle size={16} />}
                        </div>
                      </div>
                      <div className="lesson-content">
                        <div className="lesson-type-icon">
                          {lesson.type === 'video' ? <Video size={16} /> : <FileText size={16} />}
                        </div>
                        <span className="lesson-title">{lesson.title}</span>
                      </div>
                      <div className="lesson-duration">{lesson.duration}</div>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>

          {/* Sidebar */}
          <div className="chapter-sidebar">
            <div className="learning-objectives">
              <h3 className="sidebar-title">What you will learn</h3>
              <ul className="objectives-list">
                {chapterData.learningObjectives.map((objective, index) => (
                  <li key={index} className="objective-item">
                    <span className="bullet">•</span>
                    {objective}
                  </li>
                ))}
              </ul>
            </div>

            <div className="chapter-actions">
              <button className="quiz-button" onClick={handleQuizClick}>
                <Target className="quiz-icon" size={18} />
                <span>Take Chapter Quiz</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Chapter;
