import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import './LessonContent.css';
import Navbar from '../../components/shared/Navbar/Navbar';

const LessonContent = () => {
  const navigate = useNavigate();
  const { subject, chapterId, lessonId } = useParams();

  const [lesson, setLesson] = useState(null);
  const [personalized, setPersonalized] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchLesson = async () => {
      try {
        const token = JSON.parse(localStorage.getItem('user') || '{}').token;
        const res = await fetch(`http://127.0.0.1:8002/api/v0.1/lessons/${lessonId}`, {
          headers: { Authorization: token ? `Bearer ${token}` : '' },
        });
        if (!res.ok) throw new Error('Failed to load lesson');
        const data = await res.json();

        setLesson({ title: data.title, content: data.content, chapter: data.chapter_id });
        setPersonalized(data.personalized_lesson);
      } catch (err) {
        console.error(err);
      } finally {
        setLoading(false);
      }
    };

    fetchLesson();
  }, [lessonId]);

  const handleHome = () => {
    navigate(`/mathematics/chapter/${chapterId}`);
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
            <span className="breadcrumb-item">{subject || 'Mathematics'}</span>
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
          </div>
        </div>
      </div>
    </div>
  );
};

export default LessonContent;
