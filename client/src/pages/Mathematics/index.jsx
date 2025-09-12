import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import './Mathematics.css';
import Navbar from '../../components/shared/Navbar/Navbar';
import API from '../../services/axios';

const Mathematics = () => {
  const navigate = useNavigate();

  const { subjectId } = useParams();

  const [subjectData, setSubjectData] = useState({
    subject: subjectId ? `Subject ${subjectId}` : 'Subject',
    icon: '📐',
    totalChapters: 0,
    completedChapters: 0,
    chapters: [],
  });

  useEffect(() => {
    const fetchChapters = async () => {
      if (!subjectId) return;
      try {
        const res = await API.get(`/subjects/${subjectId}/chapters`);
        const data = res.data || {};
        const chapters = (data.chapters || []).map((c, idx) => ({
          id: c.id,
          title: c.title,
          chapterNumber: c.order ?? idx + 1,
          isCompleted: false,
          thumbnail: '/images/math-video-thumbnail.jpg',
          duration: null,
          lessons: 0,
        }));

        setSubjectData({
          subject: data.subject_name ?? `Subject ${subjectId}`,
          icon: '📐',
          totalChapters: chapters.length,
          completedChapters: chapters.filter(ch => ch.isCompleted).length,
          chapters,
        });
      } catch (err) {
        console.error('Failed to fetch chapters for subject', err);
      }
    };

    fetchChapters();
  }, [subjectId]);

  const handleBackToDashboard = () => {
    navigate('/student_dashboard');
  };

  const handleChapterClick = (chapter) => {
    // Navigate to specific chapter
    navigate(`/mathematics/chapter/${chapter.id}`);
  };

  return (
    <div className="mathematics-page">
      <Navbar />
      
      <div className="mathematics-container">
        {/* Header Section */}
        <div className="mathematics-header">
          <button 
            className="back-button"
            onClick={handleBackToDashboard}
          >
            <span className="back-arrow">←</span>
            Back to Dashboard
          </button>
          
          <div className="subject-info">
            <div className="subject-icon-large">{subjectData.icon}</div>
            <div className="subject-details">
              <h1 className="subject-title">{subjectData.subject}</h1>
              <p className="subject-progress">
                {subjectData.completedChapters} of {subjectData.totalChapters} chapters completed
              </p>
            </div>
          </div>
        </div>

        {/* Chapters Grid */}
        <div className="chapters-container">
          <div className="chapters-grid">
            {subjectData.chapters.map((chapter, index) => (
              <div 
                key={chapter.id} 
                className={`chapter-card ${chapter.isCompleted ? 'completed' : ''}`}
                onClick={() => handleChapterClick(chapter)}
              >
                {/* Video Thumbnail */}
                <div className="chapter-thumbnail">
                  <div className="video-placeholder">
                    <div className="play-button">▶</div>
                  </div>
                  <div className="chapter-badge">
                    <span className={`chapter-status ${chapter.isCompleted ? 'completed' : 'pending'}`}>
                      CHAPTER {chapter.chapterNumber}
                    </span>
                    <span className={`completion-status ${chapter.isCompleted ? 'completed' : 'pending'}`}>
                      {chapter.isCompleted ? 'COMPLETED' : 'PENDING'}
                    </span>
                  </div>
                </div>

                {/* Chapter Info */}
                <div className="chapter-info">
                  <h3 className="chapter-title">{chapter.title}</h3>
                  <div className="chapter-meta">
                    <span className="chapter-duration">{chapter.duration}</span>
                    <span className="chapter-lessons">{chapter.lessons} lessons</span>
                  </div>
                </div>

                {/* Continue Button */}
                <button className="continue-button">
                  {chapter.isCompleted ? 'REVIEW' : 'CONTINUE'}
                </button>
              </div>
            ))}
          </div>

          {/* Navigation Arrows */}
          <button className="nav-arrow nav-arrow-left">
            <span>‹</span>
          </button>
          <button className="nav-arrow nav-arrow-right">
            <span>›</span>
          </button>
        </div>
      </div>
    </div>
  );
};

export default Mathematics;
