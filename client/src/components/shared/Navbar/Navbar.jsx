import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import './Navbar.css';

const Navbar = () => {
  const navigate = useNavigate();
  const [showDropdown, setShowDropdown] = useState(false);
  
  // Get user data from localStorage
  const user = JSON.parse(localStorage.getItem('user') || '{}');
  const userName = user.name || "Student";
  const userRole = user.role || "Student";
  const userInitials = userName.split(' ').map(n => n[0]).join('').toUpperCase() || "ST";

  const handleNavigation = (path) => {
    // If path is "dashboard", redirect based on user role
    if (path === '/dashboard' || path === '/student_dashboard') {
      if (userRole === 'Admin') {
        navigate('/admin/dashboard');
      } else {
        navigate('/student_dashboard');
      }
    } else {
      navigate(path);
    }
    setShowDropdown(false);
  };

  const handleLogout = () => {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    navigate('/auth');
    setShowDropdown(false);
  };

  return (
    <nav className="navbar">
      <div className="navbar-container">
        {/* Logo Section */}
        <div className="navbar-logo" onClick={() => handleNavigation('/')}>
          <img src="/images/Logo.png" alt="LearnVentures Logo" className="logo-img" />
          <span className="logo-text">LEARNVENTURES</span>
        </div>

        {/* Navigation Items */}
        <div className="navbar-nav">
          {userRole !== 'Admin' && (
            <button
              className="nav-item"
              onClick={() => handleNavigation('/dashboard')}
            >
              Dashboard
            </button>
          )}
          <button
            className="nav-item"
            onClick={() => handleNavigation('/optimus')}
          >
            Optimus
          </button>
        </div>

        {/* User Profile */}
        <div className="navbar-user">
          <div 
            className="user-avatar-container"
            onClick={() => setShowDropdown(!showDropdown)}
          >
            <div className="user-avatar">
              {userInitials}
            </div>
            <span className="user-name">{userName}</span>
            <span className="dropdown-arrow">▼</span>
          </div>
          
          {showDropdown && (
            <div className="user-dropdown">
              {userRole === 'Admin' && (
                <button
                  className="dropdown-item"
                  onClick={() => handleNavigation('/admin/dashboard')}
                >
                  🛡️ Admin Dashboard
                </button>
              )}
              <button
                className="dropdown-item"
                onClick={() => handleNavigation('/Profile')}
              >
                Profile
              </button>
              <button
                className="dropdown-item logout"
                onClick={handleLogout}
              >
                Logout
              </button>
            </div>
          )}
        </div>
      </div>
    </nav>
  );
};

export default Navbar;
