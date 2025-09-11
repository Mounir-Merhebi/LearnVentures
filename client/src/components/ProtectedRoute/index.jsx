import React from 'react';
import { Navigate } from 'react-router-dom';

const ProtectedRoute = ({ children, requiredRole, allowedRoles }) => {
  const token = localStorage.getItem('token');
  const user = localStorage.getItem('user');

  // Check if user is authenticated
  if (!token || !user) {
    // Redirect to auth page if not authenticated
    return <Navigate to="/auth" replace />;
  }

  // If specific roles are allowed, check if user has one of those roles
  if (allowedRoles && Array.isArray(allowedRoles)) {
    try {
      const userData = JSON.parse(user);
      const userRole = userData.role || 'Student';

      if (!allowedRoles.includes(userRole)) {
        // Redirect based on user's actual role
        if (userRole === 'Admin') {
          return <Navigate to="/admin/dashboard" replace />;
        } else {
          return <Navigate to="/student_dashboard" replace />;
        }
      }
    } catch (error) {
      // If there's an error parsing user data, redirect to auth
      console.error('Error parsing user data:', error);
      return <Navigate to="/auth" replace />;
    }
  }

  // If a specific role is required, check if user has that role
  if (requiredRole) {
    try {
      const userData = JSON.parse(user);
      const userRole = userData.role || 'Student';

      if (userRole !== requiredRole) {
        // Redirect based on user's actual role
        if (userRole === 'Admin') {
          return <Navigate to="/admin/dashboard" replace />;
        } else {
          return <Navigate to="/student_dashboard" replace />;
        }
      }
    } catch (error) {
      // If there's an error parsing user data, redirect to auth
      console.error('Error parsing user data:', error);
      return <Navigate to="/auth" replace />;
    }
  }

  // If authenticated and has required role/roles (or no role required), render the protected component
  return children;
};

export default ProtectedRoute;
