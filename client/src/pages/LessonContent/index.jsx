import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import './LessonContent.css';
import Navbar from '../../components/shared/Navbar/Navbar';
import API from '../../services/axios';

const LessonContent = () => {
  const navigate = useNavigate();
  const { subject, chapterId, lessonId } = useParams();

  const [lesson, setLesson] = useState(null);
  const [personalized, setPersonalized] = useState(null);
  const [loading, setLoading] = useState(true);
  const [chapterLessons, setChapterLessons] = useState([]);
  const [currentLessonIndex, setCurrentLessonIndex] = useState(-1);

  useEffect(() => {
    const fetchLesson = async () => {
      try {
        // Fetch current lesson
        const lessonRes = await API.get(`/lessons/${lessonId}`);
        const lessonData = lessonRes.data;

        setLesson({ title: lessonData.title, content: lessonData.content, chapter: lessonData.chapter_id });
        setPersonalized(lessonData.personalized_lesson);

        // Fetch all lessons for this chapter to enable navigation
        const chapterRes = await API.get(`/chapters/${chapterId}`);
        const chapterData = chapterRes.data;

        if (chapterData.lessons && chapterData.lessons.length > 0) {
          const sortedLessons = chapterData.lessons.sort((a, b) => a.order - b.order);
          setChapterLessons(sortedLessons);

          // Find current lesson index
          const currentIndex = sortedLessons.findIndex(lesson => lesson.id === parseInt(lessonId));
          setCurrentLessonIndex(currentIndex);
        }
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchLesson();
  }, [lessonId, chapterId]);

  const handleHome = () => {
    navigate(`/subjects/${subject}/chapter/${chapterId}`);
  };

  const handlePreviousLesson = () => {
    if (currentLessonIndex > 0) {
      const prevLesson = chapterLessons[currentLessonIndex - 1];
      navigate(`/subjects/${subject}/chapter/${chapterId}/lesson/${prevLesson.id}`);
    }
  };

  const handleNextLesson = () => {
    if (currentLessonIndex < chapterLessons.length - 1) {
      const nextLesson = chapterLessons[currentLessonIndex + 1];
      navigate(`/subjects/${subject}/chapter/${chapterId}/lesson/${nextLesson.id}`);
    }
  };

  if (loading) {
    return (
      <div className="lesson-content-page">
        <Navbar />
        <div className="lesson-container">Loading...</div>
      </div>
    );
  }

  return (
    <div className="lesson-content-page">
      <Navbar />

      <div className="lesson-container">
        {/* Lesson Header */}
        <div className="lesson-header">
          <div className="lesson-breadcrumb">
            <span className="breadcrumb-item">{subject || 'Subject'}</span>
            <span className="breadcrumb-separator">›</span>
            <span className="breadcrumb-item">{lesson?.chapter || 'Chapter'}</span>
            <span className="breadcrumb-separator">›</span>
            <span className="breadcrumb-item current">{personalized ? personalized.personalized_title : lesson?.title}</span>
          </div>
        </div>

        {/* Main Content Area */}
        <div className="lesson-main-content">
          {personalized ? (
            <div className="personalized-content">
              <h2>{personalized.personalized_title}</h2>
              <div className="personalized-body" dangerouslySetInnerHTML={{ __html: personalized.personalized_content }} />
              {personalized.practical_examples && personalized.practical_examples.length > 0 && (
                <div className="practical-examples">
                  <h3>Practical Examples</h3>
                  <ul>
                    {personalized.practical_examples.map((ex, i) => (
                      <li key={i}>{ex}</li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          ) : (
            <div className="generic-content">
              <h2>{lesson?.title}</h2>
              <div dangerouslySetInnerHTML={{ __html: lesson?.content || '' }} />
            </div>
          )}
        </div>

        {/* Bottom Navigation */}
        <div className="lesson-navigation">
          <div className="nav-controls">
            <button
              className="nav-button home-button"
              onClick={handleHome}
            >
              Home
            </button>
            <button
              className="nav-button prev-button"
              onClick={handlePreviousLesson}
              disabled={currentLessonIndex <= 0}
            >
              ← Previous
            </button>
            <button
              className="nav-button next-button"
              onClick={handleNextLesson}
              disabled={currentLessonIndex < 0 || currentLessonIndex >= chapterLessons.length - 1}
            >
              Next →
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default LessonContent;
