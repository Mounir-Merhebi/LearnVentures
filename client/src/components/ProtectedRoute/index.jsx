import React from 'react';
import { Navigate } from 'react-router-dom';

const ProtectedRoute = ({ children }) => {
  const token = localStorage.getItem('token');
  const user = localStorage.getItem('user');

  // Check if user is authenticated
  if (!token || !user) {
    // Redirect to auth page if not authenticated
    return <Navigate to="/auth" replace />;
  }

  // If authenticated, render the protected component
  return children;
};

export default ProtectedRoute;
