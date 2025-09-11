import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import './StudentDashboard.css';
import Navbar from '../../components/shared/Navbar/Navbar';
import API from '../../services/axios';
import {
  Calculator,
  FlaskConical,
  BookOpen,
  Palette,
  Music2,
  Target,
  Flame,
  Bot,
  BarChart3,
  Settings,
} from 'lucide-react';

const StudentDashboard = () => {
  const navigate = useNavigate();

  const user = JSON.parse(localStorage.getItem('user') || '{}');

  const [dashboardData, setDashboardData] = useState({
    overallProgress: 0,
    completedLessons: 0,
    totalLessons: 0,
    completedSubjects: 0,
    totalSubjects: 0,
    averageScore: 0,
    subjects: [],
  });

  // local defaults for icons/colors when API doesn't provide them
  const subjectDefaults = {
    Mathematics: { icon: <Calculator size={28} />, color: '#10B981' },
    Science: { icon: <FlaskConical size={28} />, color: '#3B82F6' },
    History: { icon: <BookOpen size={28} />, color: '#8B5CF6' },
    Art: { icon: <Palette size={28} />, color: '#F59E0B' },
    Literature: { icon: <BookOpen size={28} />, color: '#EF4444' },
    Music: { icon: <Music2 size={28} />, color: '#06B6D4' },
  };

  useEffect(() => {
    const fetchDashboard = async () => {
      try {
        console.log('Fetching dashboard data...');

        const response = await API.get('/dashboard');
        const data = response.data;

        console.log('Dashboard data received:', data);

        // Map subjects to include icon/color fallbacks and chapters string
        const subjects = (data.subjects || []).map((s) => {
          const defaults = subjectDefaults[s.name] || {};
          return {
            id: s.id,
            name: s.name,
            icon: defaults.icon || <BookOpen size={28} />,
            chapters: s.chapters || `${s.chapters || '0'} chapters`,
            progress: s.progress ?? 0,
            color: s.color || defaults.color || '#6B7280',
          };
        });

        console.log('Mapped subjects:', subjects);

        const newDashboardData = {
          overallProgress: data.overallProgress ?? 0,
          completedLessons: data.completedLessons ?? 0,
          totalLessons: data.totalLessons ?? 0,
          completedSubjects: data.completedSubjects ?? 0,
          totalSubjects: data.totalSubjects ?? 0,
          averageScore: data.averageScore ?? 0,
          subjects,
        };

        console.log('Setting dashboard data:', newDashboardData);
        setDashboardData(newDashboardData);

      } catch (err) {
        console.error('Dashboard fetch error:', err);
        console.error('Error details:', err.response?.data || err.message);

        // For debugging, show some fallback data if API fails
        setDashboardData(prev => ({
          ...prev,
          averageScore: prev.averageScore || 0,
        }));
      }
    };

    fetchDashboard();
    // subjectDefaults is stable (declared inside component) and safe to omit from deps
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

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
              />
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
            <div className="progress-icon">
              <Target size={24} />
            </div>
          </div>

          <div className="progress-card">
            <div className="progress-card-header">
              <h3>Average Quiz Score</h3>
              <span className="progress-number">{dashboardData.averageScore}%</span>
            </div>
            <p className="progress-text">average across completed quizzes</p>
            <p className="streak-message">Keep improving!</p>
            <div className="progress-icon">
              <Flame size={24} />
            </div>
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
                }}
                style={{ cursor: subject.name === 'Mathematics' ? 'pointer' : 'default' }}
              >
                <div className="subject-header">
                  <div className="subject-icon" aria-hidden="true">
                    {subject.icon}
                  </div>
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
                        backgroundColor: subject.color,
                      }}
                    />
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Quick Actions removed */}
      </div>
    </div>
  );
};

export default StudentDashboard;
