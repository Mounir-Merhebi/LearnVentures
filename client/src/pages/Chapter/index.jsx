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
    <div className="cc-chapter-page">
      <Navbar />

      <div className="cc-chapter-container">
        {/* Back Button */}
        <button className="cc-back-button" onClick={handleBackToMathematics}>
          <ArrowLeft className="cc-back-arrow" size={18} />
          {subjectName ? `Back to ${subjectName}` : 'Back to Subjects'}
        </button>

        {/* Chapter Header */}
        <div className="cc-chapter-header">
          <div className="cc-chapter-video-section">
            <div className="cc-video-thumbnail">
              <img className="cc-chapter-image" src={getCoverSrc(chapterData.cover_photo)} alt={chapterData.chapterTitle} />
            </div>
            <div className="cc-chapter-info">
              <h1 className="cc-chapter-title">{chapterData.chapterTitle}</h1>
              <div className="cc-chapter-stats">
                <span className="cc-stat-item">
                  <BookOpen size={16} /> {chapterData.totalLessons} lessons
                </span>
                <span className="cc-stat-item">
                  <HelpCircle size={16} /> {chapterData.totalQuiz} quiz
                </span>
              </div>
            </div>
          </div>
        </div>

        {/* Main Content */}
        <div className="cc-chapter-main">
          {/* Topics Section */}
          <div className="cc-topics-section">
            {chapterData.topics.map((topic) => (
              <div key={topic.id} className="cc-topic-section">
                {/* Topic Header */}
                <div className="cc-topic-header">
                  <div className="cc-topic-title-section">
                    <h2 className="cc-topic-title">{topic.title}</h2>
                    <p className="cc-topic-description">{topic.description}</p>
                    <div className="cc-topic-stats">
                      <span>
                        <List size={16} /> {topic.totalEpisodes} Episodes
                      </span>
                      <span>
                        <HelpCircle size={16} /> {topic.totalQuiz} quiz
                      </span>
                    </div>
                  </div>
                  <div className="cc-topic-progress-section">
                    <div className="cc-progress-circle">
                      <span className="cc-progress-text">{topic.progress}% completed</span>
                    </div>
                  </div>
                </div>

                {/* Lessons List */}
                <div className="cc-lessons-list">
                  {topic.lessons.map((lesson) => (
                    <div
                      key={lesson.id}
                      className={`cc-lesson-item ${lesson.isCompleted ? 'cc-completed' : 'cc-pending'}`}
                      onClick={() => handleLessonClick(lesson)}
                    >
                      <div className="cc-lesson-status">
                        <div className={`cc-status-icon ${lesson.isCompleted ? 'cc-completed' : 'cc-pending'}`}>
                          {lesson.isCompleted ? <CheckCircle size={16} /> : <Circle size={20} />}
                        </div>
                      </div>
                      <div className="cc-lesson-content">
                        <div className="cc-lesson-type-icon">
                          {lesson.type === 'video' ? <Video size={16} /> : <FileText size={16} />}
                        </div>
                        <span className="cc-lesson-title">
                          {lesson.personalized ? lesson.personalized.personalized_title : lesson.title}
                        </span>
                        {lesson.personalized && (
                          <span className="cc-personalized-badge">Personalized</span>
                        )}
                      </div>
                      <div className="cc-lesson-duration">{lesson.duration}</div>
                    </div>
                  ))}
                </div>
              </div>
            ))}
          </div>

          {/* Sidebar */}
          <div className="cc-chapter-sidebar">
            <div className="cc-learning-objectives">
              <h3 className="cc-sidebar-title">What you will learn</h3>
              <ul className="cc-objectives-list">
                {chapterData.learningObjectives.map((objective, index) => (
                  <li key={index} className="cc-objective-item">
                    <span className="cc-bullet">•</span>
                    {objective}
                  </li>
                ))}
              </ul>
            </div>

            <div className="cc-chapter-actions">
              <button className="cc-quiz-button" onClick={handleQuizClick}>
                <Target className="cc-quiz-icon" size={18} />
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