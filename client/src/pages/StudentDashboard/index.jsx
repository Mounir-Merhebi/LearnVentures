import React from 'react';
import { useNavigate } from 'react-router-dom';
import './StudentDashboard.css';
import Navbar from '../../components/shared/Navbar/Navbar';

const StudentDashboard = () => {
  const navigate = useNavigate();
  
  // Get user data from localStorage
  const user = JSON.parse(localStorage.getItem('user') || '{}');

  // Static data for demonstration
  const dashboardData = {
    overallProgress: 60,
    completedLessons: 240,
    totalLessons: 400,
    completedSubjects: 0,
    totalSubjects: 6,
    studyStreak: 12,
    subjects: [
      {
        id: 1,
        name: 'Mathematics',
        icon: '📐',
        chapters: '6 of 8 chapters',
        progress: 75,
        color: '#10B981'
      },
      {
        id: 2,
        name: 'Science',
        icon: '🔬',
        chapters: '3 of 6 chapters',
        progress: 45,
        color: '#3B82F6'
      },
      {
        id: 3,
        name: 'History',
        icon: '📚',
        chapters: '6 of 10 chapters',
        progress: 60,
        color: '#8B5CF6'
      },
      {
        id: 4,
        name: 'Art',
        icon: '🎨',
        chapters: '2 of 5 chapters',
        progress: 30,
        color: '#F59E0B'
      },
      {
        id: 5,
        name: 'Literature',
        icon: '📖',
        chapters: '6 of 7 chapters',
        progress: 85,
        color: '#EF4444'
      },
      {
        id: 6,
        name: 'Music',
        icon: '🎵',
        chapters: '1 of 4 chapters',
        progress: 20,
        color: '#06B6D4'
      }
    ]
  };

  return (
    <div className="student-dashboard">
      <Navbar />
      
      <div className="dashboard-container">
        {/* Welcome Section */}
        <div className="welcome-section">
          <h1 className="welcome-title">Welcome back {user.name || 'Student'}!</h1>
          <p className="welcome-subtitle">Ready to continue your learning journey?</p>
        </div>

        {/* Progress Cards */}
        <div className="progress-cards">
          <div className="progress-card">
            <div className="progress-card-header">
              <h3>Overall Progress</h3>
              <span className="progress-percentage">{dashboardData.overallProgress}%</span>
            </div>
            <div className="progress-bar">
              <div 
                className="progress-fill" 
                style={{ width: `${dashboardData.overallProgress}%` }}
              ></div>
            </div>
            <p className="progress-text">
              {dashboardData.completedLessons}/{dashboardData.totalLessons} lessons
            </p>
          </div>

          <div className="progress-card">
            <div className="progress-card-header">
              <h3>Completed Subjects</h3>
              <span className="progress-number">{dashboardData.completedSubjects}</span>
            </div>
            <p className="progress-text">of {dashboardData.totalSubjects}</p>
            <div className="progress-icon">🎯</div>
          </div>

          <div className="progress-card">
            <div className="progress-card-header">
              <h3>Study Streak</h3>
              <span className="progress-number">{dashboardData.studyStreak}</span>
            </div>
            <p className="progress-text">days</p>
            <p className="streak-message">Keep it up!</p>
            <div className="progress-icon">🔥</div>
          </div>
        </div>

        {/* Subjects Section */}
        <div className="subjects-section">
          <h2 className="section-title">Your Subjects</h2>
          <div className="subjects-grid">
            {dashboardData.subjects.map((subject) => (
              <div 
                key={subject.id} 
                className="subject-card"
                onClick={() => {
                  if (subject.name === 'Mathematics') {
                    navigate('/mathematics');
                  }
                  // Add navigation for other subjects as needed
                }}
                style={{ cursor: subject.name === 'Mathematics' ? 'pointer' : 'default' }}
              >
                <div className="subject-header">
                  <div className="subject-icon">{subject.icon}</div>
                  <div className="subject-info">
                    <h3 className="subject-name">{subject.name}</h3>
                    <p className="subject-chapters">{subject.chapters}</p>
                  </div>
                </div>
                
                <div className="subject-progress">
                  <div className="progress-info">
                    <span className="progress-label">Progress</span>
                    <span className="progress-percentage">{subject.progress}%</span>
                  </div>
                  <div className="progress-bar">
                    <div 
                      className="progress-fill" 
                      style={{ 
                        width: `${subject.progress}%`,
                        backgroundColor: subject.color 
                      }}
                    ></div>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Quick Actions */}
        <div className="quick-actions">
          <h2 className="section-title">Quick Actions</h2>
          <div className="actions-grid">
            <button 
              className="action-button primary"
              onClick={() => navigate('/optimus')}
            >
              <div className="action-icon">📚</div>
              <span>Continue Learning</span>
            </button>
            <button 
              className="action-button secondary"
              onClick={() => navigate('/optimus')}
            >
              <div className="action-icon">🤖</div>
              <span>Chat with Optimus</span>
            </button>
            <button className="action-button secondary">
              <div className="action-icon">📊</div>
              <span>View Analytics</span>
            </button>
            <button className="action-button secondary">
              <div className="action-icon">⚙️</div>
              <span>Settings</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default StudentDashboard;
