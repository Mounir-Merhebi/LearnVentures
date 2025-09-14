import React from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import './Chapter.css';
import Navbar from '../../components/shared/Navbar/Navbar';
import API from '../../services/axios';
import {
  ArrowLeft,
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
  const { subjectId, chapterId } = useParams();

  const [subjectName, setSubjectName] = React.useState(null);

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
        const res = await API.get(`/chapters/${chapterId}`);
        const data = res.data;

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
          cover_photo: data.cover_photo ?? null,
        });
      } catch (err) {
        console.error(err);
      }
    };

    fetchChapter();
    // fetch subject name for breadcrumb/back link
    const fetchSubject = async () => {
      if (!subjectId) return;
      try {
        const res = await API.get(`/subjects/${subjectId}`);
        const data = res.data || {};
        const name = data.subject_name || data.name || data.title;
        setSubjectName(name || `Subject ${subjectId}`);
      } catch (err) {
        console.warn('Failed to fetch subject name', err);
      }
    };

    fetchSubject();
  }, [chapterId, subjectId]);

  const getCoverSrc = (cover) => {
    if (!cover) return '/images/default-chapter-cover.jpg';
    // if already a data URL or an http(s) url, return as-is
    if (/^data:|^https?:\/\//i.test(cover)) return cover;
    // otherwise assume it's base64 and prefix as png
    return `data:image/png;base64,${cover}`;
  };

  const handleBackToMathematics = () => {
    navigate(`/subjects/${subjectId}`);
  };

  const handleLessonClick = (lesson) => {
    navigate(`/subjects/${subjectId}/chapter/${chapterId}/lesson/${lesson.id}`);
  };

  const handleQuizClick = () => {
    navigate(`/subjects/${subjectId}/chapter/${chapterId}/quiz`);
  };

  return (
    <div className="chapter-page">
      <Navbar />

      <div className="chapter-container">
        {/* Back Button */}
        <button className="back-button" onClick={handleBackToMathematics}>
          <ArrowLeft className="back-arrow" size={18} />
          {subjectName ? `Back to ${subjectName}` : 'Back to Subjects'}
        </button>

        {/* Chapter Header */}
        <div className="chapter-header">
          <div className="chapter-video-section">
            <div className="video-thumbnail">
              <img className="chapter-image" src={getCoverSrc(chapterData.cover_photo)} alt={chapterData.chapterTitle} />
            </div>
            <div className="chapter-info">
              <h1 className="chapter-title">{chapterData.chapterTitle}</h1>
              <div className="chapter-stats">
                <span className="stat-item">
                  <BookOpen size={16} /> {chapterData.totalLessons} lessons
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
                          {lesson.isCompleted ? <CheckCircle size={16} /> : <Circle size={20} />}
                        </div>
                      </div>
                      <div className="lesson-content">
                        <div className="lesson-type-icon">
                          {lesson.type === 'video' ? <Video size={16} /> : <FileText size={16} />}
                        </div>
                        <span className="lesson-title">
                          {lesson.personalized ? lesson.personalized.personalized_title : lesson.title}
                        </span>
                        {lesson.personalized && (
                          <span className="personalized-badge">Personalized</span>
                        )}
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
