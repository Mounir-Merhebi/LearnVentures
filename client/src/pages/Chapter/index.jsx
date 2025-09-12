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

  const [chapterData, setChapterData] = React.useState({
    chapterTitle: '',
    totalLessons: 0,
    totalVideos: 0,
    totalQuiz: 0,
    topics: [],
    learningObjectives: [],
  });

  React.useEffect(() => {
    const fetchChapter = async () => {
      try {
        const token = JSON.parse(localStorage.getItem('user') || '{}').token;
        const res = await fetch(`http://127.0.0.1:8002/api/v0.1/chapters/${chapterId}`, {
          headers: { Authorization: token ? `Bearer ${token}` : '' },
        });
        if (!res.ok) throw new Error('Failed to load chapter');
        const data = await res.json();

        // Convert lessons into a single topic for now
        const topic = {
          id: data.id,
          title: data.title,
          description: '',
          totalEpisodes: data.lessons.length,
          totalVideos: 0,
          totalQuiz: 0,
          progress: 0,
          isCompleted: false,
          lessons: data.lessons,
        };

        setChapterData({
          chapterTitle: data.title,
          totalLessons: data.lessons.length,
          totalVideos: 0,
          totalQuiz: 0,
          topics: [topic],
          learningObjectives: [],
        });
      } catch (err) {
        console.error(err);
      }
    };

    fetchChapter();
  }, [chapterId]);

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
