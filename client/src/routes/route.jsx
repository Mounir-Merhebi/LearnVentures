import { Routes, Route } from "react-router-dom";

import LandingPage from "../pages/LandingPage";
import Auth from "../pages/Auth";
import AIChat from "../pages/AIChat";
import StudentDashboard from "../pages/StudentDashboard";
import ProtectedRoute from "../components/ProtectedRoute";

const MyRoutes = () => {
  return (
    <Routes>
      <Route path="/" element={<LandingPage />} />
      <Route path="/auth" element={<Auth />} />
      <Route 
        path="/optimus" 
        element={
          <ProtectedRoute>
            <AIChat />
          </ProtectedRoute>
        } 
      />
      <Route 
        path="/student_dashboard" 
        element={
          <ProtectedRoute>
            <StudentDashboard />
          </ProtectedRoute>
        } 
      />
      <Route 
        path="/dashboard" 
        element={
          <ProtectedRoute>
            <StudentDashboard />
          </ProtectedRoute>
        } 
      />
    </Routes>
  );
};

export default MyRoutes;