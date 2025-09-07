import React, { useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import './LessonContent.css';
import Navbar from '../../components/shared/Navbar/Navbar';

const LessonContent = () => {
  const navigate = useNavigate();
  const { subject, chapterId, lessonId } = useParams();
  const [currentSlide, setCurrentSlide] = useState(1);

  // Mock lesson content data
  const lessonData = {
    title: 'Introduction to Algebra',
    subject: 'Mathematics',
    chapter: 'Algebra',
    totalSlides: 5,
    currentSlide: 1,
    content: {
      1: {
        type: 'observation',
        title: 'Geometric Patterns Analysis',
        image: '/images/geometric-pattern.png', // Placeholder for the dot pattern image
        observations: [
          'In the first figure we can see ...',
          'In the second figure we can see ...',
          'In the third figure we can see ...',
          'In the fourth figure we can see ...',
          'In the fifth figure we can see ...'
        ],
        mainText: 'Here from above figure we can observe the following things:'
      },
      2: {
        type: 'explanation',
        title: 'Understanding Patterns',
        content: 'This slide explains the mathematical concepts behind the patterns...'
      },
      3: {
        type: 'example',
        title: 'Worked Example',
        content: 'Let\'s work through a practical example...'
      },
      4: {
        type: 'practice',
        title: 'Practice Problems',
        content: 'Try these problems on your own...'
      },
      5: {
        type: 'summary',
        title: 'Lesson Summary',
        content: 'Key takeaways from this lesson...'
      }
    }
  };

  const currentContent = lessonData.content[currentSlide];

  const handleHome = () => {
    navigate(`/mathematics/chapter/${chapterId}`);
  };

  const handlePrev = () => {
    if (currentSlide > 1) {
      setCurrentSlide(currentSlide - 1);
    }
  };

  const handleNext = () => {
    if (currentSlide < lessonData.totalSlides) {
      setCurrentSlide(currentSlide + 1);
    }
  };

  const renderContent = () => {
    switch (currentContent.type) {
      case 'observation':
        return (
          <div className="observation-content">
            {/* Content Area with Image */}
            <div className="content-image-area">
              <div className="pattern-grid">
                {/* Simulating the dot pattern from the screenshot */}
                <div className="pattern-row">
                  <div className="pattern-figure">
                    <div className="dot-grid">
                      {Array.from({ length: 9 }, (_, i) => (
                        <div key={i} className="dot"></div>
                      ))}
                    </div>
                  </div>
                  <div className="pattern-figure">
                    <div className="dot-grid">
                      {Array.from({ length: 16 }, (_, i) => (
                        <div key={i} className="dot"></div>
                      ))}
                    </div>
                  </div>
                  <div className="pattern-figure">
                    <div className="dot-grid">
                      {Array.from({ length: 25 }, (_, i) => (
                        <div key={i} className="dot"></div>
                      ))}
                    </div>
                  </div>
                </div>
                <div className="pattern-row">
                  <div className="pattern-figure">
                    <div className="dot-grid">
                      {Array.from({ length: 36 }, (_, i) => (
                        <div key={i} className="dot"></div>
                      ))}
                    </div>
                  </div>
                  <div className="pattern-figure">
                    <div className="dot-grid">
                      {Array.from({ length: 49 }, (_, i) => (
                        <div key={i} className="dot"></div>
                      ))}
                    </div>
                  </div>
                  <div className="pattern-figure">
                    <div className="dot-grid">
                      {Array.from({ length: 64 }, (_, i) => (
                        <div key={i} className="dot"></div>
                      ))}
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Observation Points */}
            <div className="observation-section">
              <h3 className="observation-title">{currentContent.mainText}</h3>
              <ol className="observation-list">
                {currentContent.observations.map((observation, index) => (
                  <li key={index} className="observation-item">
                    {observation}
                  </li>
                ))}
              </ol>
            </div>
          </div>
        );
      
      default:
        return (
          <div className="generic-content">
            <h2>{currentContent.title}</h2>
            <p>{currentContent.content}</p>
          </div>
        );
    }
  };

  return (
    <div className="lesson-content-page">
      <Navbar />
      
      <div className="lesson-container">
        {/* Lesson Header */}
        <div className="lesson-header">
          <div className="lesson-breadcrumb">
            <span className="breadcrumb-item">{lessonData.subject}</span>
            <span className="breadcrumb-separator">›</span>
            <span className="breadcrumb-item">{lessonData.chapter}</span>
            <span className="breadcrumb-separator">›</span>
            <span className="breadcrumb-item current">{lessonData.title}</span>
          </div>
          <div className="progress-indicator">
            <span className="progress-text">
              {currentSlide} of {lessonData.totalSlides}
            </span>
          </div>
        </div>

        {/* Main Content Area */}
        <div className="lesson-main-content">
          {renderContent()}
        </div>

        {/* Bottom Navigation */}
        <div className="lesson-navigation">
          <div className="nav-menu-indicator">
            <span className="menu-icon">≡</span>
            <span className="nav-text">Real life value of AI</span>
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
              disabled={currentSlide === 1}
            >
              Prev
            </button>
            <button 
              className="nav-button next-button"
              onClick={handleNext}
              disabled={currentSlide === lessonData.totalSlides}
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default LessonContent;
