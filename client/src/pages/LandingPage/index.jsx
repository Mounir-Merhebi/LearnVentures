import React from 'react';
import "./index.css";
import { useNavigate } from "react-router-dom";
import {
  BookOpenIcon,
  UsersIcon,
  ChartBarIcon,
  ChatBubbleLeftRightIcon,
  GraduationCapIcon,
  FeatureCard,
  FeatureListItem
} from '../../components/LandingPageComponents';


export default function LandingPage() {
  const navigate = useNavigate(); 

  return (
    <>
      {/* Header */}
      <header className="header">
        <div className="container">
          <div className="header-logo">
            <img src="/images/logo.png" alt="LearnVentures Logo" className="logo-image" />
            <span className="logo-text">LEARNVENTURES</span>
          </div>
          <nav className="header-nav">
            <a href="#features" className="nav-link">Features</a>
            <a href="#contact" className="nav-link">Contact</a>
            <button 
              onClick={() => navigate("/auth")}  // ✅ navigate to auth page
              className="sign-in-button"
            >
              Sign In
            </button>
          </nav>
        </div>
      </header>

      <div className="container">
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
                <button 
                  className="get-started-button"
                  onClick={() => navigate("/auth")}  // ✅ navigate to auth page
                >
                  Get Started
                </button>
              </div>
              <div className="hero-image">
                <img 
                  src="/images/hero-image.svg" 
                  alt="Modern classroom with educational imagery" 
                  onError={(e) => { e.target.onerror = null; e.target.src='https://placehold.co/600x400/CCCCCC/FFFFFF?text=Image+Not+Found'; }}
                />
              </div>
            </div>
          </section>

          {/* Features Section */}
          <section id="features" className="features-section">
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
                  src="/images/students-image.svg" 
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
                  <button 
                    className="join-now-button"
                    onClick={() => navigate("/auth")} // ✅ navigate to auth page
                  >
                    Join now
                  </button>
              </div>
            </div>
          </section>
        </main>
      </div>

      {/* Footer */}
      <footer id="contact" className="footer">
        <p>&copy; {new Date().getFullYear()} LearnVentures. All rights reserved.</p>
      </footer>
    </>
  );
}
