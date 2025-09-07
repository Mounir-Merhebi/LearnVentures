import { Routes, Route } from "react-router-dom";

import LandingPage from "../pages/LandingPage";
import Auth from "../pages/Auth";
import AIChat from "../pages/AIChat";
import StudentDashboard from "../pages/StudentDashboard";
import Mathematics from "../pages/Mathematics";
import Chapter from "../pages/Chapter";
import LessonContent from "../pages/LessonContent";
import Quiz from "../pages/Quiz";
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
      <Route 
        path="/mathematics" 
        element={
          <ProtectedRoute>
            <Mathematics />
          </ProtectedRoute>
        } 
      />
      <Route 
        path="/mathematics/chapter/:chapterId" 
        element={
          <ProtectedRoute>
            <Chapter />
          </ProtectedRoute>
        } 
      />
      <Route 
        path="/mathematics/chapter/:chapterId/lesson/:lessonId" 
        element={
          <ProtectedRoute>
            <LessonContent />
          </ProtectedRoute>
        } 
      />
      <Route 
        path="/mathematics/chapter/:chapterId/quiz" 
        element={
          <ProtectedRoute>
            <Quiz />
          </ProtectedRoute>
        } 
      />
    </Routes>
  );
};

export default MyRoutes;