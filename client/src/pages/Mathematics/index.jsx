import React from 'react';
import { useNavigate } from 'react-router-dom';
import './Mathematics.css';
import Navbar from '../../components/shared/Navbar/Navbar';

const Mathematics = () => {
  const navigate = useNavigate();

  // Mock data for mathematics chapters
  const mathData = {
    subject: 'Mathematics',
    icon: '📐',
    totalChapters: 8,
    completedChapters: 6,
    chapters: [
      {
        id: 1,
        title: 'Algebra',
        chapterNumber: 1,
        isCompleted: true,
        thumbnail: '/images/math-video-thumbnail.jpg', // You can replace with actual thumbnails
        duration: '45 min',
        lessons: 12
      },
      {
        id: 2,
        title: 'Algebra',
        chapterNumber: 1,
        isCompleted: true,
        thumbnail: '/images/math-video-thumbnail.jpg',
        duration: '38 min',
        lessons: 10
      },
      {
        id: 3,
        title: 'Algebra',
        chapterNumber: 1,
        isCompleted: false,
        thumbnail: '/images/math-video-thumbnail.jpg',
        duration: '42 min',
        lessons: 15
      },
      {
        id: 4,
        title: 'Geometry',
        chapterNumber: 2,
        isCompleted: false,
        thumbnail: '/images/math-video-thumbnail.jpg',
        duration: '50 min',
        lessons: 18
      },
      {
        id: 5,
        title: 'Trigonometry',
        chapterNumber: 3,
        isCompleted: false,
        thumbnail: '/images/math-video-thumbnail.jpg',
        duration: '55 min',
        lessons: 14
      },
      {
        id: 6,
        title: 'Calculus',
        chapterNumber: 4,
        isCompleted: false,
        thumbnail: '/images/math-video-thumbnail.jpg',
        duration: '60 min',
        lessons: 20
      }
    ]
  };

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
            <div className="subject-icon-large">{mathData.icon}</div>
            <div className="subject-details">
              <h1 className="subject-title">{mathData.subject}</h1>
              <p className="subject-progress">
                {mathData.completedChapters} of {mathData.totalChapters} chapters completed
              </p>
            </div>
          </div>
        </div>

        {/* Chapters Grid */}
        <div className="chapters-container">
          <div className="chapters-grid">
            {mathData.chapters.map((chapter, index) => (
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
