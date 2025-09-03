import React from 'react';
import "./index.css";
import { useNavigate } from "react-router-dom";

// --- SVG Icons --- //
const BookOpenIcon = () => (
  <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" className="icon-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1}>
    <path strokeLinecap="round" strokeLinejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
  </svg>
);

const UsersIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" className="icon-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.653-.125-1.274-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.653.125-1.274.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
    </svg>
);

const ChartBarIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" className="icon-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
    </svg>
);

const ChatBubbleLeftRightIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" className="icon-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
    </svg>
);

const GraduationCapIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" className="icon-gray" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path d="M12 14l9-5-9-5-9 5 9 5z" />
        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
        <path strokeLinecap="round" strokeLinejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6" />
    </svg>
);

const CheckCircleIcon = () => (
    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" className="icon-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
);


// --- Reusable Components --- //
const FeatureCard = ({ icon, title, description }) => (
  <div className="feature-card">
    <div className="feature-card-icon">
      {icon}
    </div>
    <h3 className="feature-card-title">{title}</h3>
    <p className="feature-card-description">{description}</p>
  </div>
);

const FeatureListItem = ({ text }) => (
  <li className="student-list-item">
    <CheckCircleIcon />
    <span className="student-list-item-text">{text}</span>
  </li>
);


// --- Main App Component --- //
export default function LandingPage() {
  return (
    <>
      <div className="container">
          
        {/* Header */}
        <header className="header">
          <div className="header-logo">
            <img src="https://placehold.co/40x40/34D399/FFFFFF?text=LV" alt="LearnVentures Logo" className="logo-image" />
            <span className="logo-text">LEARNVENTURES</span>
          </div>
          <nav>
            <a href="#" className="sign-in-button">
              Sign In
            </a>
          </nav>
        </header>

        <main className="main-content">
          {/* Hero Section */}
          <section className="hero-section">
            <div className="hero-grid">
              <div className="hero-text">
                <h1 className="hero-title">
                  Learn, Adapt and <span className="highlight">Grow</span> Together
                </h1>
                <p className="hero-description">
                  Join our comprehensive educational platform that adapts to your learning style with AI-powered personalization and collaborative tools.
                </p>
                <button className="get-started-button">
                  Get Started
                </button>
              </div>
              <div className="hero-image">
                <img 
                  src="https://images.unsplash.com/photo-1588072432836-e10032774350?q=80&w=2072&auto=format&fit=crop" 
                  alt="Modern classroom with educational imagery" 
                  onError={(e) => { e.target.onerror = null; e.target.src='https://placehold.co/600x400/CCCCCC/FFFFFF?text=Image+Not+Found'; }}
                />
              </div>
            </div>
          </section>

          {/* Features Section */}
          <section className="features-section">
            <div className="features-header">
              <h2 className="features-title">Everything you need to succeed</h2>
              <p className="features-description">
                Our platform combines the best of modern technology with proven educational methods.
              </p>
            </div>
            <div className="features-grid">
              <FeatureCard 
                icon={<BookOpenIcon />} 
                title="Interactive Learning"
                description="Engage with personalized AI-powered content that adapts to your learning style and pace."
              />
              <FeatureCard 
                icon={<UsersIcon />} 
                title="Collaborative Environment"
                description="Connect with classmates and instructors through video calls, chat and group discussions."
              />
              <FeatureCard 
                icon={<ChartBarIcon />} 
                title="Progress Tracking"
                description="Monitor your learning journey with detailed analytics and personalized recommendations."
              />
              <FeatureCard 
                icon={<ChatBubbleLeftRightIcon />} 
                title="AI Assistant"
                description="Get instant help with questions and receive personalized study suggestions."
              />
            </div>
          </section>

          {/* Student Section */}
          <section className="student-section">
            <div className="student-grid">
              <div className="student-image">
                <img 
                  src="https://images.unsplash.com/photo-1571260899234-3c48a7a01d59?q=80&w=2070&auto=format&fit=crop" 
                  alt="Group of children learning together on a tablet" 
                  onError={(e) => { e.target.onerror = null; e.target.src='https://placehold.co/600x400/CCCCCC/FFFFFF?text=Image+Not+Found'; }}
                />
              </div>
              <div className="student-card">
                  <div className="student-card-header">
                    <div className="student-card-icon-wrapper">
                      <GraduationCapIcon />
                    </div>
                    <h3 className="student-card-title">Student</h3>
                  </div>
                  <p className="student-card-description">
                    Access courses, track progress, and collaborate with peers
                  </p>
                  <ul className="student-list">
                    <FeatureListItem text="Weekly chapter unlocks" />
                    <FeatureListItem text="AI-personalized content" />
                    <FeatureListItem text="Interactive quizzes" />
                    <FeatureListItem text="Group Chat" />
                  </ul>
                  <button className="join-now-button">
                    Join now
                  </button>
              </div>
            </div>
          </section>
        </main>
      </div>

      {/* Footer */}
      <footer className="footer">
        <p>&copy; {new Date().getFullYear()} LearnVentures. All rights reserved.</p>
      </footer>
    </>
  );
}

