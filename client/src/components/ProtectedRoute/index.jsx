import React from 'react';
import { Navigate } from 'react-router-dom';

const ProtectedRoute = ({ children, requiredRole, allowedRoles }) => {
  const token = localStorage.getItem('token');
  const user = localStorage.getItem('user');

  if (!token || !user) {
    return <Navigate to="/auth" replace />;
  }

  if (allowedRoles && Array.isArray(allowedRoles)) {
    try {
      const userData = JSON.parse(user);
      const userRole = userData.role || 'Student';

      if (!allowedRoles.includes(userRole)) {
        if (userRole === 'Admin') {
          return <Navigate to="/admin/dashboard" replace />;
        } else {
          return <Navigate to="/student_dashboard" replace />;
        }
      }
    } catch (error) {
      console.error('Error parsing user data:', error);
      return <Navigate to="/auth" replace />;
    }
  }

  if (requiredRole) {
    try {
      const userData = JSON.parse(user);
      const userRole = userData.role || 'Student';

      if (userRole !== requiredRole) {
        if (userRole === 'Admin') {
          return <Navigate to="/admin/dashboard" replace />;
        } else {
          return <Navigate to="/student_dashboard" replace />;
        }
      }
    } catch (error) {
      console.error('Error parsing user data:', error);
      return <Navigate to="/auth" replace />;
    }
  }
  return children;
};

export default ProtectedRoute;
